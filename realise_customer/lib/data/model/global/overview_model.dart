class OverviewModel {
  OverviewModel({
    bool? success,
    String? message,
    Data? data,
  }) {
    _success = success;
    _message = message;
    _data = data;
  }

  OverviewModel.fromJson(dynamic json) {
    _success = json['success'];
    _message = json['message'];
    _data = json['data'] != null ? Data.fromJson(json['data']) : null;
  }
  bool? _success;
  String? _message;
  Data? _data;

  bool? get success => _success;
  String? get message => _message;
  Data? get data => _data;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['success'] = _success;
    map['message'] = _message;
    if (_data != null) {
      map['data'] = _data?.toJson();
    }
    return map;
  }
}

class Data {
  Data({
    String? appTitle,
    String? appLogo,
    String? language,
    String? currencySymbol,
    String? defaultCurrency,
    String? currencyPosition,
    String? disableLogin,
    String? disableRegistration,
    String? viewTasks,
    String? createTasks,
    String? editTasks,
    String? commentTasks,
    String? viewOverview,
  }) {
    _appTitle = appTitle;
    _appLogo = appLogo;
    _language = language;
    _currencySymbol = currencySymbol;
    _defaultCurrency = defaultCurrency;
    _currencyPosition = currencyPosition;
    _disableLogin = disableLogin;
    _disableRegistration = disableRegistration;
    _viewTasks = viewTasks;
    _createTasks = createTasks;
    _editTasks = editTasks;
    _commentTasks = commentTasks;
    _viewOverview = viewOverview;
  }

  Data.fromJson(dynamic json) {
    _appTitle = json['app_title'];
    _appLogo = json['app_logo'];
    _language = json['language'];
    _currencySymbol = json['currency_symbol'];
    _defaultCurrency = json['default_currency'];
    _currencyPosition = json['currency_position'];
    _disableLogin = json['disable_login'];
    _disableRegistration = json['disable_registration'];
    _viewTasks = json['client_can_view_tasks'];
    _createTasks = json['client_can_create_tasks'];
    _editTasks = json['client_can_edit_tasks'];
    _commentTasks = json['client_can_comment_on_tasks'];
    _viewOverview = json['client_can_view_overview'];
  }

  String? _appTitle;
  String? _appLogo;
  String? _language;
  String? _currencySymbol;
  String? _defaultCurrency;
  String? _currencyPosition;
  String? _disableLogin;
  String? _disableRegistration;
  String? _viewTasks;
  String? _createTasks;
  String? _editTasks;
  String? _commentTasks;
  String? _viewOverview;

  String? get appTitle => _appTitle;
  String? get appLogo => _appLogo;
  String? get language => _language;
  String? get currencySymbol => _currencySymbol;
  String? get defaultCurrency => _defaultCurrency;
  String? get currencyPosition => _currencyPosition;
  String? get disableLogin => _disableLogin;
  String? get disableRegistration => _disableRegistration;
  String? get viewTasks => _viewTasks;
  String? get createTasks => _createTasks;
  String? get editTasks => _editTasks;
  String? get commentTasks => _commentTasks;
  String? get viewOverview => _viewOverview;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['app_title'] = _appTitle;
    map['app_logo'] = _appLogo;
    map['language'] = _language;
    map['currency_symbol'] = _currencySymbol;
    map['default_currency'] = _defaultCurrency;
    map['currency_position'] = _currencyPosition;
    map['disable_login'] = _disableLogin;
    map['disable_registration'] = _disableRegistration;
    map['client_can_view_tasks'] = _viewTasks;
    map['client_can_create_tasks'] = _createTasks;
    map['client_can_edit_tasks'] = _editTasks;
    map['client_can_comment_on_tasks'] = _commentTasks;
    map['client_can_view_overview'] = _viewOverview;
    return map;
  }
}
