class ProfileModel {
  ProfileModel({
    bool? success,
    String? message,
    ClientData? data,
  }) {
    _success = success;
    _message = message;
    _data = data;
  }

  ProfileModel.fromJson(dynamic json) {
    _success = json['success'];
    _message = json['message'].toString();
    _data = json['data'] != null ? ClientData.fromJson(json['data']) : null;
  }
  bool? _success;
  String? _message;
  ClientData? _data;

  bool? get success => _success;
  String? get message => _message;
  ClientData? get data => _data;

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

class ClientData {
  ClientData({
    String? clientId,
    String? companyName,
    String? firstName,
    String? lastName,
    String? type,
    String? address,
    String? email,
    String? phone,
    String? jobTitle,
    String? gender,
    String? note,
    String? alternativePhone,
    String? dob,
    String? avatar,
  }) {
    _clientId = clientId;
    _companyName = companyName;
    _firstName = firstName;
    _lastName = lastName;
    _type = type;
    _address = address;
    _email = email;
    _phone = phone;
    _jobTitle = jobTitle;
    _gender = gender;
    _note = note;
    _alternativePhone = alternativePhone;
    _dob = dob;
    _avatar = avatar;
  }

  ClientData.fromJson(dynamic json) {
    _clientId = json['id'];
    _companyName = json['company_name'];
    _firstName = json['first_name'];
    _lastName = json['last_name'];
    _type = json['type'];
    _address = json['address'];
    _email = json['email'];
    _phone = json['phone'];
    _jobTitle = json['job_title'];
    _gender = json['gender'];
    _note = json['note'];
    _alternativePhone = json['alternative_phone'];
    _dob = json['dob'];
    _avatar = json['avatar'];
  }
  String? _clientId;
  String? _companyName;
  String? _firstName;
  String? _lastName;
  String? _type;
  String? _address;
  String? _email;
  String? _phone;
  String? _jobTitle;
  String? _gender;
  String? _note;
  String? _alternativePhone;
  String? _dob;
  String? _avatar;

  String? get clientId => _clientId;
  String? get companyName => _companyName;
  String? get firstName => _firstName;
  String? get lastName => _lastName;
  String? get type => _type;
  String? get address => _address;
  String? get email => _email;
  String? get phone => _phone;
  String? get jobTitle => _jobTitle;
  String? get gender => _gender;
  String? get note => _note;
  String? get alternativePhone => _alternativePhone;
  String? get dob => _dob;
  String? get avatar => _avatar;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = _clientId;
    map['company_name'] = _companyName;
    map['first_name'] = _firstName;
    map['last_name'] = _lastName;
    map['type'] = _type;
    map['address'] = _address;
    map['email'] = _email;
    map['phone'] = _phone;
    map['job_title'] = _jobTitle;
    map['gender'] = _gender;
    map['note'] = _note;
    map['alternative_phone'] = _alternativePhone;
    map['dob'] = _dob;
    map['avatar'] = _avatar;
    return map;
  }
}
