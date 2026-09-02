class TicketTypesModel {
  TicketTypesModel({
    bool? success,
    String? message,
    List<TicketType>? data,
  }) {
    _success = success;
    _message = message;
    _data = data;
  }

  TicketTypesModel.fromJson(dynamic json) {
    _success = json['success'];
    _message = json['message'];
    if (json['data'] != null) {
      _data = [];
      json['data'].forEach((v) {
        _data?.add(TicketType.fromJson(v));
      });
    }
  }
  bool? _success;
  String? _message;
  List<TicketType>? _data;

  bool? get success => _success;
  String? get message => _message;
  List<TicketType>? get data => _data;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['success'] = _success;
    map['message'] = _message;
    if (_data != null) {
      map['data'] = _data?.map((v) => v.toJson()).toList();
    }
    return map;
  }
}

class TicketType {
  TicketType({
    String? id,
    String? title,
  }) {
    _id = id;
    _title = title;
  }

  TicketType.fromJson(dynamic json) {
    _id = json['id'].toString();
    _title = json['title'];
  }
  String? _id;
  String? _title;

  String? get id => _id;
  String? get title => _title;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = _id;
    map['title'] = _title;
    return map;
  }
}
