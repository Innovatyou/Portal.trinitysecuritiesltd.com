class MenuModel {
  MenuModel({
    bool? status,
    String? message,
    List<Data>? data,
  }) {
    _status = status;
    _message = message;
    _data = data;
  }

  MenuModel.fromJson(dynamic json) {
    _status = json['status'];
    _message = json['message'];
    if (json['data'] != null) {
      _data = [];
      json['data'].forEach((v) {
        _data?.add(Data.fromJson(v));
      });
    }
  }
  bool? _status;
  String? _message;
  List<Data>? _data;

  bool? get status => _status;
  String? get message => _message;
  List<Data>? get data => _data;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['status'] = _status;
    map['message'] = _message;
    if (_data != null) {
      map['data'] = _data?.map((v) => v.toJson()).toList();
    }
    return map;
  }
}

class Data {
  Data({
    int? id,
    String? name,
    String? shortName,
  }) {
    _id = id;
    _name = name;
    _shortName = shortName;
  }

  Data.fromJson(dynamic json) {
    _id = json['id'];
    _name = json['name'];
    _shortName = json['short_name'];
  }

  int? _id;
  String? _name;
  String? _shortName;

  int? get id => _id;
  String? get name => _name;
  String? get shortName => _shortName;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = _id;
    map['name'] = _name;
    map['short_name'] = _shortName;
    return map;
  }
}
