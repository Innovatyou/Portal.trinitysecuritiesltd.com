import 'package:local_auth/local_auth.dart';

/// Thin wrapper around local_auth. Biometric sign-in here is an app-unlock
/// gate, not a credential replacement - it only ever runs when the user is
/// already in a "remember me" session with a real, previously-issued access
/// token stored (see SplashController.getData / LoginController). A
/// successful scan just lets that existing session through instead of
/// showing the login form again; it never talks to the server itself.
class BiometricAuthHelper {
  static final LocalAuthentication _auth = LocalAuthentication();

  /// Whether this device can even do biometric auth (hardware present +
  /// enrolled). Used to decide whether to offer the toggle at all.
  static Future<bool> isDeviceSupported() async {
    try {
      final canCheck = await _auth.canCheckBiometrics;
      final isSupported = await _auth.isDeviceSupported();
      return canCheck && isSupported;
    } catch (_) {
      return false;
    }
  }

  /// Prompts the OS biometric UI. Returns true only on an actual successful
  /// scan - any error (no biometrics enrolled, hardware lockout, user
  /// cancels) is treated as "not authenticated", never thrown to the caller.
  static Future<bool> authenticate({required String reason}) async {
    try {
      return await _auth.authenticate(
        localizedReason: reason,
        options: const AuthenticationOptions(
          biometricOnly: false,
          stickyAuth: true,
        ),
      );
    } catch (_) {
      return false;
    }
  }
}
