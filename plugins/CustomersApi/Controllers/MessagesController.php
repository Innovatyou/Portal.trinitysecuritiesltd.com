<?php

namespace CustomersApi\Controllers;

use App\Models\Messages_model;
use App\Models\Users_model;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once PLUGINPATH . 'CustomersApi/Vendor/autoload.php';

/**
 * Mobile chat API. Reuses the exact same `messages` table and permission
 * rules as the web Messages module (app/Controllers/Messages.php,
 * app/Models/Messages_model.php) so a conversation started on mobile shows
 * up correctly on web and vice versa - this is deliberately NOT a separate
 * chat system.
 *
 * Auth mirrors operations_approval's Operations_api::auth() (same
 * customersapi_secret_key JWT, staff+client, not the client-only scheme in
 * this plugin's own RestApiController::login) since that's the token the
 * app already obtains from operations-login and reuses for every other
 * mobile endpoint.
 */
class MessagesController extends ResourceController
{
    private $db;
    private $p;
    private $secret;
    private $users;

    public function __construct()
    {
        // is_online_user() lives in date_time_helper.php, which - unlike
        // general_helper.php's get_setting()/get_avatar()/clean_data() -
        // isn't already loaded for a plain ResourceController. Confirmed
        // live: conversations() 500'd with "Call to undefined function
        // is_online_user()" the moment a real row needed it.
        helper('date_time');
        $this->db = db_connect('default');
        $this->p = $this->db->getPrefix();
        $this->users = new Users_model();
        $secretRow = $this->db->table($this->p . 'settings')->select('setting_value')->where(['setting_name' => 'customersapi_secret_key', 'deleted' => 0])->get()->getRow();
        $this->secret = (string) ($secretRow->setting_value ?? '');
    }

    public function conversations(): ResponseInterface
    {
        $user = $this->auth();
        if (!$user) return $this->unauthorized();
        $me = (int) $user->id;
        $messagesTable = $this->p . 'messages';
        $usersTable = $this->p . 'users';
        $this->db->query("SET sql_mode = ''");
        $sql = "SELECT root.id AS thread_id,
                IFNULL(last.message, root.message) AS last_message,
                IFNULL(last.files, root.files) AS last_files,
                IFNULL(last.created_at, root.created_at) AS last_time,
                IFNULL(last.status, root.status) AS status,
                IFNULL(last.from_user_id, root.from_user_id) AS last_from_user_id,
                other.id AS other_user_id,
                CONCAT(other.first_name, ' ', other.last_name) AS other_user_name,
                other.image AS other_user_image,
                other.user_type AS other_user_type,
                other.last_online AS other_last_online,
                (SELECT COUNT(*) FROM $messagesTable um WHERE um.deleted = 0 AND um.to_user_id = $me AND um.status = 'unread' AND (um.id = root.id OR um.message_id = root.id)) AS unread_count
                FROM $messagesTable root
                LEFT JOIN (SELECT message_id, MAX(id) AS max_id FROM $messagesTable WHERE deleted = 0 AND message_id != 0 GROUP BY message_id) lm ON lm.message_id = root.id
                LEFT JOIN $messagesTable last ON last.id = lm.max_id
                LEFT JOIN $usersTable other ON other.id = IF(root.from_user_id = $me, root.to_user_id, root.from_user_id)
                WHERE root.deleted = 0 AND root.message_id = 0
                AND FIND_IN_SET($me, root.deleted_by_users) = 0
                AND (root.from_user_id = $me OR root.to_user_id = $me)
                ORDER BY last_time DESC LIMIT 100";
        $rows = $this->db->query($sql)->getResultArray();
        $data = array_map(function ($row) {
            $files = $row['last_files'] ? @unserialize($row['last_files']) : [];
            return [
                'thread_id' => (int) $row['thread_id'],
                'other_user_id' => (int) $row['other_user_id'],
                'other_user_name' => trim((string) $row['other_user_name']),
                'other_user_image' => $row['other_user_image'] ? get_avatar($row['other_user_image']) : get_avatar(''),
                'other_user_type' => $row['other_user_type'],
                'is_online' => $row['other_last_online'] ? is_online_user($row['other_last_online']) : false,
                'last_message' => (string) $row['last_message'],
                'has_attachment' => is_array($files) && count($files) > 0,
                'last_time' => $row['last_time'],
                'unread_count' => (int) $row['unread_count'],
            ];
        }, $rows);
        return $this->respond(['success' => true, 'message' => 'Conversations', 'data' => $data]);
    }

    public function contacts(): ResponseInterface
    {
        $user = $this->auth();
        if (!$user) return $this->unauthorized();
        if (!$this->canAccessMessages($user)) return $this->respond(['success' => false, 'message' => 'Forbidden'], 403);
        $rows = (new Messages_model())->get_users_for_messaging($this->messagingRecipientOptions($user))->getResultArray();
        $data = array_map(function ($row) {
            return [
                'id' => (int) $row['id'],
                'name' => trim($row['first_name'] . ' ' . $row['last_name']) . ($row['user_type'] === 'client' && $row['company_name'] ? ' - ' . $row['company_name'] : ''),
                'image' => get_avatar($row['image']),
                'user_type' => $row['user_type'],
                'job_title' => $row['job_title'],
                'is_online' => $row['last_online'] ? is_online_user($row['last_online']) : false,
            ];
        }, $rows);
        return $this->respond(['success' => true, 'message' => 'Contacts', 'data' => $data]);
    }

    public function thread(int $otherUserId): ResponseInterface
    {
        $user = $this->auth();
        if (!$user) return $this->unauthorized();
        $me = (int) $user->id;
        $other = $this->users->get_one_where(['id' => $otherUserId, 'deleted' => 0, 'status' => 'active']);
        if (!$other->id) return $this->respond(['success' => false, 'message' => 'Not found'], 404);

        $root = $this->findRootMessage($me, $otherUserId);
        $messages = [];
        if ($root) {
            $rows = $this->db->table($this->p . 'messages m')
                ->select('m.id, m.from_user_id, m.to_user_id, m.message, m.files, m.created_at, m.status')
                ->groupStart()->where('m.id', $root->id)->orWhere('m.message_id', $root->id)->groupEnd()
                ->where('m.deleted', 0)
                ->orderBy('m.id', 'ASC')
                ->get()->getResultArray();
            $messages = array_map(function ($row) use ($me) {
                $files = $row['files'] ? @unserialize($row['files']) : [];
                return [
                    'id' => (int) $row['id'],
                    'is_mine' => (int) $row['from_user_id'] === $me,
                    'message' => (string) $row['message'],
                    'files' => is_array($files) ? array_map(function ($f) {
                        return ['file_name' => $f['file_name'] ?? '', 'file_size' => $f['file_size'] ?? 0];
                    }, $files) : [],
                    'created_at' => $row['created_at'],
                ];
            }, $rows);
            (new Messages_model())->set_message_status_as_read($root->id, $me);
        }

        return $this->respond(['success' => true, 'message' => 'Thread', 'data' => [
            'other_user' => ['id' => (int) $other->id, 'name' => trim($other->first_name . ' ' . $other->last_name), 'image' => get_avatar($other->image), 'user_type' => $other->user_type, 'is_online' => $other->last_online ? is_online_user($other->last_online) : false],
            'messages' => $messages,
        ]]);
    }

    public function send(): ResponseInterface
    {
        $user = $this->auth();
        if (!$user) return $this->unauthorized();
        $me = (int) $user->id;
        $toUserId = (int) $this->request->getPost('to_user_id');
        $message = trim((string) $this->request->getPost('message'));
        $file = $this->request->getFile('file');
        $hasFile = $file && $file->isValid();
        if (!$toUserId) return $this->respond(['success' => false, 'message' => 'to_user_id is required'], 422);
        if (!$message && !$hasFile) return $this->respond(['success' => false, 'message' => 'message or file is required'], 422);
        if (!$this->canSendTo($user, $toUserId)) return $this->respond(['success' => false, 'message' => 'You are not allowed to message this user'], 403);

        $filesData = [];
        if ($hasFile) {
            helper('app_files');
            $originalName = $file->getClientName();
            $targetPath = rtrim(get_setting('timeline_file_path'), '/\\') . '/';
            $stored = move_temp_file($originalName, $targetPath, 'message', $file->getTempName(), '', '', false, $file->getSize());
            $storedName = is_array($stored) ? ($stored['file_name'] ?? '') : '';
            if (!$storedName) return $this->respond(['success' => false, 'message' => 'The file could not be uploaded'], 500);
            $filesData[] = ['file_name' => $storedName, 'file_size' => $file->getSize(), 'file_id' => null, 'service_type' => null];
        }

        $root = $this->findRootMessage($me, $toUserId);
        $now = get_current_utc_time();
        $data = [
            'from_user_id' => $me,
            'to_user_id' => $root ? ((int) $root->from_user_id === $me ? (int) $root->to_user_id : (int) $root->from_user_id) : $toUserId,
            'message_id' => $root ? (int) $root->id : 0,
            'subject' => '',
            'message' => clean_data($message),
            'files' => serialize($filesData),
            'created_at' => $now,
            'deleted_by_users' => '',
        ];
        $saveId = (new Messages_model())->ci_save($data);
        if (!$saveId) return $this->respond(['success' => false, 'message' => 'The message could not be sent'], 500);

        (new Messages_model())->clear_deleted_status($root ? (int) $root->id : $saveId);

        if (get_setting('enable_chat_via_pusher') && get_setting('enable_push_notification')) {
            send_message_via_pusher($data['to_user_id'], $data, $root ? (int) $root->id : $saveId);
        }

        return $this->respond(['success' => true, 'message' => 'Message sent', 'data' => ['id' => (int) $saveId, 'thread_id' => $root ? (int) $root->id : (int) $saveId]]);
    }

    private function findRootMessage(int $me, int $otherUserId): ?object
    {
        return $this->db->table($this->p . 'messages')
            ->where('deleted', 0)->where('message_id', 0)
            ->groupStart()
                ->groupStart()->where('from_user_id', $me)->where('to_user_id', $otherUserId)->groupEnd()
                ->orGroupStart()->where('from_user_id', $otherUserId)->where('to_user_id', $me)->groupEnd()
            ->groupEnd()
            ->get()->getRow();
    }

    // Mirrors Security_Controller::check_access_on_messages_for_this_user()
    private function canAccessMessages(object $user): bool
    {
        if ($user->user_type === 'staff') {
            if (!empty($user->is_admin)) return true;
            $clientMessageUsersArray = array_filter(explode(',', (string) get_setting('client_message_users')));
            if (($user->permissions['message_permission'] ?? '') === 'no' && !in_array((string) $user->id, $clientMessageUsersArray, true)) return false;
            return true;
        }
        return (bool) get_setting('client_message_users');
    }

    // Mirrors Security_Controller::get_user_options_for_query()
    private function messagingRecipientOptions(object $user): array
    {
        $options = ['login_user_id' => $user->id];
        $clientMessageUsers = (string) get_setting('client_message_users');
        if ($user->user_type === 'staff') {
            $permission = $user->permissions['message_permission'] ?? '';
            if (!$permission) {
                $options['all_members'] = true;
            } elseif ($permission === 'specific') {
                $options['specific_members'] = prepare_allowed_members_array(explode(',', $user->permissions['message_permission_specific'] ?? ''), $user->id);
            }
            if (in_array((string) $user->id, explode(',', $clientMessageUsers), true)) $options['member_to_clients'] = true;
        } else {
            if ($clientMessageUsers) {
                $options['client_to_members'] = $clientMessageUsers;
                if (get_setting('client_message_own_contacts')) $options['client_id'] = $user->client_id;
            }
        }
        return $options;
    }

    // Mirrors Security_Controller::validate_sending_message()
    private function canSendTo(object $user, int $toUserId): bool
    {
        if (!$this->canAccessMessages($user)) return false;
        $recipients = (new Messages_model())->get_users_for_messaging($this->messagingRecipientOptions($user))->getResultArray();
        if (!in_array($toUserId, array_column($recipients, 'id'), true)) return false;

        $toUser = $this->users->get_access_info($toUserId);
        if ($toUser && $toUser->user_type === 'staff') {
            $toPermissions = [];
            if ($toUser->permissions) {
                $p = @unserialize($toUser->permissions);
                $toPermissions = is_array($p) ? $p : [];
            }
            $toPermission = $toPermissions['message_permission'] ?? '';
            if ($toPermission === 'no') return false;
            if ($toPermission === 'specific') {
                $allowed = array_map('strval', prepare_allowed_members_array(explode(',', $toPermissions['message_permission_specific'] ?? ''), $toUserId));
                if (!in_array((string) $user->id, $allowed, true)) return false;
            }
        }
        return true;
    }

    private function auth(): ?object
    {
        if (!$this->secret || !class_exists(JWT::class)) return null;
        $header = $this->request->getHeaderLine('Authorization') ?: $this->request->getHeaderLine('X-Authorization');
        if (!$header) $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_AUTHORIZATION'] ?? '');
        if (!$header && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $header = (string) ($headers['Authorization'] ?? $headers['authorization'] ?? $headers['X-Authorization'] ?? '');
        }
        $token = preg_replace('/^Bearer\s+/i', '', trim($header));
        if (!$token) return null;
        try {
            $jwt = JWT::decode($token, new Key($this->secret, 'HS256'));
            $email = $jwt->data->email ?? '';
            $basicUser = $this->users->get_one_where(['email' => $email, 'deleted' => 0]);
            if (!$basicUser->id || $basicUser->status !== 'active') return null;
            $user = $this->users->get_access_info($basicUser->id);
            $permissionData = $user->permissions ?? null;
            if ($permissionData) {
                $p = @unserialize($permissionData);
                $user->permissions = is_array($p) ? $p : [];
            } else {
                $user->permissions = [];
            }
            return $user;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function unauthorized(): ResponseInterface
    {
        return $this->respond(['success' => false, 'message' => 'Unauthorized. Token is missing or invalid.'], 401);
    }
}
