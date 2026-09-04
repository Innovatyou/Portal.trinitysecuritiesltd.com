class Conversation {
  final int threadId, otherUserId, unreadCount;
  final String otherUserName, otherUserImage, otherUserType, lastMessage, lastTime;
  final bool isOnline, hasAttachment;
  Conversation.fromJson(Map<String, dynamic> j)
      : threadId = int.tryParse('${j['thread_id']}') ?? 0,
        otherUserId = int.tryParse('${j['other_user_id']}') ?? 0,
        unreadCount = int.tryParse('${j['unread_count']}') ?? 0,
        otherUserName = '${j['other_user_name'] ?? ''}',
        otherUserImage = '${j['other_user_image'] ?? ''}',
        otherUserType = '${j['other_user_type'] ?? ''}',
        lastMessage = '${j['last_message'] ?? ''}',
        lastTime = '${j['last_time'] ?? ''}',
        isOnline = j['is_online'] == true,
        hasAttachment = j['has_attachment'] == true;
}

class Contact {
  final int id;
  final String name, image, userType, jobTitle;
  final bool isOnline;
  Contact.fromJson(Map<String, dynamic> j)
      : id = int.tryParse('${j['id']}') ?? 0,
        name = '${j['name'] ?? ''}',
        image = '${j['image'] ?? ''}',
        userType = '${j['user_type'] ?? ''}',
        jobTitle = '${j['job_title'] ?? ''}',
        isOnline = j['is_online'] == true;
}

class ChatMessage {
  final int id;
  final bool isMine;
  final String message, createdAt;
  final List<Map<String, dynamic>> files;
  ChatMessage.fromJson(Map<String, dynamic> j)
      : id = int.tryParse('${j['id']}') ?? 0,
        isMine = j['is_mine'] == true,
        message = '${j['message'] ?? ''}',
        createdAt = '${j['created_at'] ?? ''}',
        files = List<Map<String, dynamic>>.from(j['files'] ?? const []);
}

class ChatThread {
  final int otherUserId;
  final String otherUserName, otherUserImage, otherUserType;
  final bool isOnline;
  final List<ChatMessage> messages;
  ChatThread.fromJson(Map<String, dynamic> j)
      : otherUserId = int.tryParse('${j['other_user']?['id']}') ?? 0,
        otherUserName = '${j['other_user']?['name'] ?? ''}',
        otherUserImage = '${j['other_user']?['image'] ?? ''}',
        otherUserType = '${j['other_user']?['user_type'] ?? ''}',
        isOnline = j['other_user']?['is_online'] == true,
        messages = (j['messages'] as List? ?? const [])
            .map((e) => ChatMessage.fromJson(Map<String, dynamic>.from(e)))
            .toList();
}
