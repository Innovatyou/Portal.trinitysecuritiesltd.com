<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Re-encrypts every tenant's stored db_password_encrypted with the CURRENT
 * encryption.key, after rotating it in .env. Safe to run mid-rotation
 * because of encryption.previousKeys (see app/Config/Encryption.php):
 * decryption tries the current key first, then falls back to previous ones,
 * so tenant DB connections (Tenant_resolver, on every tenant page load)
 * keep working throughout - even if this command needs to be fixed and
 * re-run, nothing here is a single irreversible step.
 *
 * Defaults to a dry run: reports what would change and round-trips every
 * value (decrypt old cipher -> re-encrypt -> decrypt again -> compare)
 * without writing anything. Only pass --apply once a dry run is clean.
 *
 * Once every tenant shows re-encrypted (or already current) with --apply,
 * and only then, remove the old key from encryption.previousKeys in .env -
 * that's the step that actually finishes a key rotation.
 *
 * Usage:
 *   php spark tenant:rotate-encryption-key
 *   php spark tenant:rotate-encryption-key --apply
 */
class TenantRotateEncryptionKey extends BaseCommand {

    protected $group = 'Tenants';
    protected $name = 'tenant:rotate-encryption-key';
    protected $description = 'Re-encrypts every tenant db password with the current encryption key, after rotating encryption.key.';

    public function run(array $params) {
        $apply = (bool) CLI::getOption('apply');

        $encryption = config('Encryption');
        if (!$encryption->key) {
            CLI::error('encryption.key is not set - nothing to rotate to.');
            return;
        }
        if (!$encryption->previousKeys) {
            CLI::error('encryption.previousKeys is empty - set it to the OLD key first, or every existing tenant password will fail to decrypt.');
            return;
        }

        $landlord = db_connect('landlord');
        $tenants = $landlord->table('tenants')->select('id, slug, db_password_encrypted')->get()->getResult();

        if (!$tenants) {
            CLI::write('No tenants found.', 'yellow');
            return;
        }

        $encrypter = service('encrypter');
        $ok = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($tenants as $tenant) {
            try {
                $plaintext = $encrypter->decrypt(base64_decode($tenant->db_password_encrypted));
            } catch (\Throwable $e) {
                CLI::error("{$tenant->slug}: could not decrypt existing value - {$e->getMessage()}");
                $failed++;
                continue;
            }

            $reencrypted = base64_encode($encrypter->encrypt($plaintext));

            // Round-trip check before trusting this value - catches a bad
            // rotation here, not the next time this tenant's site tries to
            // connect to its own database.
            try {
                $verify = $encrypter->decrypt(base64_decode($reencrypted));
            } catch (\Throwable $e) {
                $verify = null;
            }
            if ($verify !== $plaintext) {
                CLI::error("{$tenant->slug}: round-trip check failed, skipping - old cipher was NOT changed.");
                $failed++;
                continue;
            }

            if ($reencrypted === $tenant->db_password_encrypted) {
                CLI::write("{$tenant->slug}: already current, skipped.", 'yellow');
                $skipped++;
                continue;
            }

            if ($apply) {
                $landlord->table('tenants')->where('id', $tenant->id)->update(['db_password_encrypted' => $reencrypted]);
                CLI::write("{$tenant->slug}: re-encrypted.", 'green');
            } else {
                CLI::write("{$tenant->slug}: would re-encrypt (dry run).", 'green');
            }
            $ok++;
        }

        CLI::write('');
        CLI::write(($apply ? 'Applied' : 'Would apply') . ": {$ok}   Already current: {$skipped}   Failed: {$failed}");

        if (!$apply && $failed === 0 && $ok > 0) {
            CLI::write('Dry run clean - re-run with --apply to write the changes.', 'green');
        }
        if ($failed > 0) {
            CLI::write('Do NOT remove the old key from encryption.previousKeys until every tenant above succeeds.', 'red');
        } elseif ($apply) {
            CLI::write('All tenants on the current key - safe to remove the old key from encryption.previousKeys now.', 'green');
        }
    }
}
