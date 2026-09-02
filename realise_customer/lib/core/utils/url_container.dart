class UrlContainer {
  // Android emulators reach the Windows localhost through 10.0.2.2.
  // For the local emulator use: --dart-define=RISE_URL=http://10.0.2.2/portal.trinitysecuritiesltd.com
  static const String domainUrl = String.fromEnvironment(
    'RISE_URL',
    defaultValue: 'https://portal.trinitysecuritiesltd.com',
  );

  // if your domain have index.php at the end please add it to the domain url too
  // Example: https://your-domain.com/index.php

  static const String baseUrl = '$domainUrl/customersapi/';
  static const String attachmentUrl = '$domainUrl/files/timeline_files/';
  static const String profileImgUrl = '$domainUrl/files/profile_images/';
  static const String systemImgUrl = '$domainUrl/files/system/';

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
