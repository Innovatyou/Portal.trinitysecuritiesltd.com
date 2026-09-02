class LoginModel {
  LoginModel({
    bool? success,
    String? message,
    Data? data,
  }) {
    _success = success;
    _message = message;
    _data = data;
  }

  LoginModel.fromJson(dynamic json) {
    _success = json['success'];
    _message = json['message'].toString();
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
    String? accessToken,
    String? clientId,
    String? companyName,
    String? firstName,
    String? lastName,
    String? type,
    String? address,
    String? email,
  }) {
    _token = token;
    _clientId = clientId;
    _companyName = companyName;
    _firstName = firstName;
    _lastName = lastName;
    _type = type;
    _address = address;
    _email = email;
  }

  Data.fromJson(dynamic json) {
    _token = json['token'];
    _clientId = json['id']?.toString();
    _companyName = json['company_name'];
    _firstName = json['first_name'];
    _lastName = json['last_name'];
    _type = json['type'];
    _address = json['address'];
    _email = json['email'];
  }
  String? _token;
  String? _clientId;
  String? _companyName;
  String? _firstName;
  String? _lastName;
  String? _type;
  String? _address;
  String? _email;

  String? get token => _token;
  String? get clientId => _clientId;
  String? get companyName => _companyName;
  String? get firstName => _firstName;
  String? get lastName => _lastName;
  String? get type => _type;
  String? get address => _address;
  String? get email => _email;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['token'] = _token;
    map['id'] = _clientId;
    map['company_name'] = _companyName;
    map['first_name'] = _firstName;
    map['last_name'] = _lastName;
    map['type'] = _type;
    map['address'] = _address;
    map['email'] = _email;
    return map;
  }
}
