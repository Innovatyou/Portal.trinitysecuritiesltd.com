import 'package:shared_preferences/shared_preferences.dart';
import 'package:realise/core/helper/shared_preference_helper.dart';

class UrlContainer {
  // Android emulators reach the Windows localhost through 10.0.2.2.
  // For the local emulator use: --dart-define=RISE_URL=http://10.0.2.2/portal.trinitysecuritiesltd.com
  static const String _compiledDefaultDomain = String.fromEnvironment(
    'RISE_URL',
    defaultValue: 'https://portal.trinitysecuritiesltd.com',
  );

  // This is a multi-tenant SaaS: each company runs on its own domain, with
  // its own database - there is no single "the" server this app can be
  // built pointing at. domainUrl is therefore resolved at runtime from
  // whatever the user picked on the "choose your company" screen (see
  // CompanyDomainScreen), persisted in SharedPreferences, rather than baked
  // in at compile time. The compiled default above only matters until the
  // user has chosen a company, and for local dev via --dart-define.
  static String _domainUrl = _compiledDefaultDomain;
  static String get domainUrl => _domainUrl;

  /// Call once at app startup (before any repo/API call is made) so
  /// domainUrl reflects a previously-chosen company from the very first
  /// frame, not just after the async company-check screen runs.
  static void init(SharedPreferences prefs) {
    final stored = prefs.getString(SharedPreferenceHelper.companyDomainKey);
    if (stored != null && stored.isNotEmpty) {
      _domainUrl = stored;
    }
  }

  /// True once a company domain has been chosen and persisted - either by
  /// the user, or via the compiled default in local dev builds that pass
  /// --dart-define=RISE_URL.
  static bool hasChosenCompany(SharedPreferences prefs) =>
      (prefs.getString(SharedPreferenceHelper.companyDomainKey) ?? '')
          .isNotEmpty;

  /// Normalizes free-text user input ("prodigybank.com",
  /// "https://prodigybank.com/", "prodigybank.com/index.php") into the form
  /// the rest of this app expects (scheme present, no trailing slash).
  static String normalizeDomainInput(String input) {
    var value = input.trim();
    if (!value.startsWith('http://') && !value.startsWith('https://')) {
      value = 'https://$value';
    }
    while (value.endsWith('/')) {
      value = value.substring(0, value.length - 1);
    }
    return value;
  }

  /// Persists the chosen company and switches this session to it
  /// immediately - subsequent UrlContainer.baseUrl etc. reads reflect it
  /// without needing an app restart.
  static Future<void> setDomain(
      SharedPreferences prefs, String normalizedDomain) async {
    _domainUrl = normalizedDomain;
    await prefs.setString(
        SharedPreferenceHelper.companyDomainKey, normalizedDomain);
  }

  // if your domain have index.php at the end please add it to the domain url too
  // Example: https://your-domain.com/index.php

  static String get baseUrl => '$domainUrl/customersapi/';
  static String get attachmentUrl => '$domainUrl/files/timeline_files/';
  static String get profileImgUrl => '$domainUrl/files/profile_images/';
  static String get systemImgUrl => '$domainUrl/files/system/';

  static RegExp emailValidatorRegExp =
      RegExp(r"^[a-zA-Z0-9.]+@[a-zA-Z0-9]+\.[a-zA-Z]+");

  // Authentication
  static const String loginUrl = 'operations-login';
  static const String operationsUrl = 'operations';
  static const String registrationUrl = 'register';
  static const String forgotPasswordUrl = 'forget-password';

  // Dashboard
  static const String overviewUrl = 'overview';
  static const String dashboardUrl = 'dashboard';

  // Pages
  static const String projectsUrl = 'projects';
  static const String invoicesUrl = 'invoices';
  static const String contractsUrl = 'contracts';
  static const String estimatesUrl = 'estimates';
  static const String proposalsUrl = 'proposals';
  static const String paymentsUrl = 'payments';
  static const String ticketsUrl = 'tickets';
  static const String profileUrl = 'profile';
  static const String privacyPolicyUrl = 'knowledge_base/privacy-policy';
}
