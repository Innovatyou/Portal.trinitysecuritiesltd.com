package com.realise.customer

import io.flutter.embedding.android.FlutterFragmentActivity

// local_auth's BiometricPrompt needs a FragmentActivity to attach to -
// plain FlutterActivity crashes when biometric auth is triggered.
class MainActivity: FlutterFragmentActivity()
