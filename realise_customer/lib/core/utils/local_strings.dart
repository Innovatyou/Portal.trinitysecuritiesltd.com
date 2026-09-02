import 'package:realise/data/model/language/language_model.dart';

class LocalStrings {
  static const String appName = "WorkFlow";

  static List<LanguageModel> appLanguages = [
    LanguageModel(
        languageName: 'English', countryCode: 'US', languageCode: 'en'),
    LanguageModel(
        languageName: 'العربية', countryCode: 'SA', languageCode: 'ar'),
    LanguageModel(
        languageName: 'Spanish', countryCode: 'ES', languageCode: 'es'),
    LanguageModel(
        languageName: 'French', countryCode: 'FR', languageCode: 'fr'),
  ];

  // Onboarding Screens
  static const String onboardTitle1 = "Onboarding one";
  static const String onboardSubTitle1 = "Onboarding One Description";
  static const String onboardTitle2 = "Onboarding Two";
  static const String onboardSubTitle2 = "Onboarding Two Description.";
  static const String onboardTitle3 = "Onboarding Three";
  static const String onboardSubTitle3 = "Onboarding Three Description.";
  static const String skip = "Skip";
  static const String next = "Next";
  static const String getStarted = "Get Started";

  // Login Screen
  static const String password = "Password";
  static const String rememberMe = "Remember Me";
  static const String forgotPassword = "Forgot Password?";
  static const String forgotPasswordTitle = 'Forgot Password';
  static const String forgotPasswordDesc =
      'Enter your email below to receive a password reset verification link';
  static const String signIn = "Sign In";
  static const String login = 'Login';
  static const String loginDesc = 'Login to your account';
  static const String doNotHaveAccount = "Don't have an account?";
  static const String createAnAccount = "Create an Account";
  static const String iAgreeWith = "I agree with the";
  static const String policies = 'Policies';
  static const String loginFailedTryAgain = 'Login failed,please try again';

  // Register Screen
  static const String firstName = "First Name";
  static const String enterFirstName = "Enter first name";
  static const String lastName = "Last Name";
  static const String last = "Last";
  static const String enterLastName = "Enter last name";
  static const String mobileNumber = "Mobile Number";
  static const String emailAddress = "Email Address";
  static const String confirmPassword = "Confirm Password";
  static const String firstNameHint = "Enter first Name";
  static const String lastNameHint = "Enter last Name";
  static const String mobileNumberHint = "Your phone number";
  static const String emailAddressHint = "Enter email address";
  static const String emailAddressEmptyMsg = "Email address can't be empty";
  static const String confirmPasswordHint = "Enter confirm Password";
  static const String signUp = "Sign Up";
  static const String alreadyAccount = "Already have an account?";
  static const String signInNow = "Sign In Now";
  static const String signUpNow = "Sign Up Now";
  static const String companyName = "Company Name";
  static const String enterCompanyName = "Enter Company Name";
  static const String email = "Email";
  static const String enterEmail = "Enter email";
  static const String invalidEmailMsg = "Enter valid email";
  static const String enterYourPassword = 'Enter your password';
  static const String passwordMatchError = "Password doesn't match";
  static const String companyAccount = "Organization";
  static const String personalAccount = "Personal";
  static const String agreePolicyMessage =
      "You must agree with our privacy & policies";

  // Change Password
  static const String changePassword = "Change Password";
  static const String currentPassword = "Current Password";
  static const String currentPasswordHint = "Enter current password";
  static const String saveNewPassword = "Save New Password";

  // Home
  static const String dashboard = "Dashboard";
  static const String welcome = 'Welcome';

  // Profile
  static const String viewProfile = "View Profile";
  static const String name = "Name";
  static const String phone = "Phone";
  static const String address = "Address";

  // Settings
  static const String profile = "Profile";
  static const String theme = "Theme";
  static const String notification = 'Notifications';
  static const String settings = "Settings";
  static const String language = "Language";
  static const String selectLanguage = 'Select Language';
  static const String privacyPolicy = "Privacy & Policy";
  static const String darkmode = "Dark Mode";
  static const String exitTitle = "Are you sure you want to exit the app?";
  static const String logout = "Logout";
  static const String logoutTitle = "Log out";
  static const String logoutSureWarningMSg =
      "Are you sure you want to log out from your account?";
  static const String logoutSuccessMsg = 'Sign Out Successfully';

  // Operations
  static const String viewAll = "View All";
  static const String submit = "Submit";
  static const String status = "Status";
  static const String fieldErrorMsg = "Please fill out this field";
  static const String badResponseMsg = 'Bad Response Format!';
  static const String serverError = 'Server Error';
  static const String unAuthorized = 'Unauthorized';
  static const String somethingWentWrong = 'Something went wrong';
  static const String noInternet = 'No internet connection';
  static const String noDataFound = 'Sorry! there are no data to show';
  static const String yes = "Yes";
  static const String no = "No";

  // Contract
  static const String contracts = 'Contracts';
  static const String contract = 'Contract';
  static const String contractDetails = 'Contract Details';
  static const String contractValue = 'Contract Value';
  static const String startDate = 'Start Date';
  static const String endDate = 'End Date';
  static const String note = 'Note';

  // Estimate
  static const String estimates = 'Estimates';
  static const String estimate = 'Estimate';
  static const String estimateDetails = 'Estimate Details';
  static const String estimateDate = 'Estimate Date';
  static const String expiryDate = 'Expiry Date';
  static const String price = 'Price';
  static const String subtotal = 'Subtotal';
  static const String total = 'Total';
  static const String balanceDue = 'Balance Due';

  // Project
  static const String project = 'Project';
  static const String projects = 'Projects';
  static const String projectDetails = 'Project Details';
  static const String filter = 'Filter';
  static const String description = 'Description';
  static const String deadline = 'Deadline';
  static const String overview = 'Overview';
  static const String comment = 'Comment';
  static const String comments = 'Comments';
  static const String addComment = 'Add Comment';
  static const String tasks = 'Tasks';
  static const String taskDetails = 'Task Details';
  static const String taskTitle = 'Task Title';
  static const String assignedTo = 'Assigned To';

  // Invoice
  static const String invoice = 'Invoice';
  static const String invoices = 'Invoices';
  static const String invoiceDetails = 'Invoice Details';
  static const String billTo = 'Bill to';
  static const String invoiceDate = 'Invoice Date';
  static const String dueDate = 'Due Date';
  static const String totalPaid = 'Total Paid';
  static const String amountDue = 'Amount Due';
  static const String transactions = 'Transactions';
  static const String id = 'ID';
  static const String qty = 'Qty';
  static const String item = 'Item';
  static const String items = 'Items';
  static const String paymentMode = 'Payment Mode';
  static const String date = 'Date';
  static const String amount = 'Amount';
  static const String tax = 'Tax';
  static const String discount = 'Discount';
  static const String totalInvoiced = 'Total Invoiced';

  // Payments
  static const String payments = 'Payments';
  static const String payment = 'Payment';
  static const String paymentMethod = 'Payment Method';

  // Proposal
  static const String proposal = 'Proposal';
  static const String proposals = 'Proposals';
  static const String proposalDetails = 'Proposal Details';

  // Ticket
  static const String ticket = 'Ticket';
  static const String tickets = 'Tickets';
  static const String ticketDetails = 'Ticket Details';
  static const String title = 'Title';
  static const String openedBy = 'Opened By';
  static const String reply = 'Reply';
  static const String ticketType = 'Ticket Type';
  static const String selectTicketType = 'Select Ticket Type';
  static const String createNewTicket = 'Create New Ticket';
  static const String enterTicketSubject = 'Please, Enter Ticket Subject.';
  static const String enterTicketDescription =
      "Please, fill up Ticket Description field";
  static const String ticketComment = 'Add Ticket Comment';
  static const String ticketComments = 'Ticket Comments';
  static const String ticketMessage = 'Message';
  static const String enterTicketReply = 'Please, Enter Ticket Reply.';
  static const String attachment = "Attachment";
  static const String selectTicketAttachment = "No File Attached";
}
