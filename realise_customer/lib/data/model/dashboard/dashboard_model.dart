import 'package:realise/data/model/profile/profile_model.dart';
import 'package:realise/data/model/project/project_model.dart';

class DashboardModel {
  DashboardModel({
    bool? success,
    String? message,
    Data? data,
  }) {
    _success = success;
    _message = message;
    _data = data;
  }

  DashboardModel.fromJson(dynamic json) {
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
    WidgetsData? widgetsData,
    ClientData? clientData,
    List<String>? permissions,
    List<Project>? projects,
  }) {
    _widgetsData = widgetsData;
    _clientData = clientData;
    _permissions = permissions;
    _projects = projects;
  }

  Data.fromJson(dynamic json) {
    _widgetsData = WidgetsData.fromJson(json['widgets']);
    _clientData = ClientData.fromJson(json['client']);
    if (json['permissions'] != null) {
      _permissions = [];
      json['permissions'].forEach((v) {
        _permissions?.add(v);
      });
    }
    if (json['projects'] != null) {
      _projects = [];
      json['projects'].forEach((v) {
        _projects?.add(Project.fromJson(v));
      });
    }
  }
  WidgetsData? _widgetsData;
  ClientData? _clientData;
  List<String>? _permissions;
  List<Project>? _projects;

  WidgetsData? get widgetsData => _widgetsData;
  ClientData? get clientData => _clientData;
  List<String>? get permissions => _permissions;
  List<Project>? get projects => _projects;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};

    if (_widgetsData != null) {
      map['widgets'] = _widgetsData?.toJson();
    }

    if (_clientData != null) {
      map['client'] = _clientData?.toJson();
    }

    if (_permissions != null) {
      map['permissions'] = _permissions?.map((v) => v).toList();
    }

    if (_projects != null) {
      map['projects'] = _projects?.map((v) => v.toJson()).toList();
    }
    return map;
  }
}

class WidgetsData {
  WidgetsData({
    int? projects,
    int? totalInvoiced,
    int? payments,
    int? due,
  }) {
    _projects = projects;
    _totalInvoiced = totalInvoiced;
    _payments = payments;
    _due = due;
  }

  WidgetsData.fromJson(dynamic json) {
    _projects = json['project_count'];
    _totalInvoiced = json['total_invoiced'];
    _payments = json['payments'];
    _due = json['due'];
  }

  int? _projects;
  int? _totalInvoiced;
  int? _payments;
  int? _due;

  int? get projects => _projects;
  int? get totalInvoiced => _totalInvoiced;
  int? get payments => _payments;
  int? get due => _due;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['project_count'] = _projects;
    map['total_invoiced'] = _totalInvoiced;
    map['payments'] = _payments;
    map['due'] = _due;
    return map;
  }
}

class Permissions {
  Permissions({
    String? id,
  }) {
    _id = id;
  }

  Permissions.fromJson(dynamic json) {
    _id = json['id'];
  }

  String? _id;

  String? get id => _id;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = _id;
    return map;
  }
}
