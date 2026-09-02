class ProjectsModel {
  ProjectsModel({
    bool? success,
    String? message,
    List<Project>? data,
  }) {
    _success = success;
    _message = message;
    _data = data;
  }

  ProjectsModel.fromJson(dynamic json) {
    _success = json['success'];
    _message = json['message'];
    if (json['data'] != null) {
      _data = [];
      json['data'].forEach((v) {
        _data?.add(Project.fromJson(v));
      });
    }
  }
  bool? _success;
  String? _message;
  List<Project>? _data;

  bool? get success => _success;
  String? get message => _message;
  List<Project>? get data => _data;

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

class Project {
  Project({
    String? id,
    String? title,
    String? description,
    String? projectType,
    String? startDate,
    String? deadline,
    String? clientId,
    String? createdDate,
    String? createdBy,
    String? status,
    String? statusId,
    String? labels,
    String? price,
    String? starredBy,
    String? estimateId,
    String? orderId,
    String? proposalId,
    String? deleted,
    String? companyName,
    String? currencySymbol,
    String? totalPoints,
    String? completedPoints,
    String? statusKeyName,
    String? titleLanguageKey,
    String? statusTitle,
    String? statusIcon,
    String? labelsList,
  }) {
    _id = id;
    _title = title;
    _description = description;
    _projectType = projectType;
    _startDate = startDate;
    _deadline = deadline;
    _clientId = clientId;
    _createdDate = createdDate;
    _createdBy = createdBy;
    _status = status;
    _statusId = statusId;
    _labels = labels;
    _price = price;
    _starredBy = starredBy;
    _estimateId = estimateId;
    _orderId = orderId;
    _proposalId = proposalId;
    _deleted = deleted;
    _companyName = companyName;
    _currencySymbol = currencySymbol;
    _totalPoints = totalPoints;
    _completedPoints = completedPoints;
    _statusKeyName = statusKeyName;
    _titleLanguageKey = titleLanguageKey;
    _statusTitle = statusTitle;
    _statusIcon = statusIcon;
    _labelsList = labelsList;
  }
  Project.fromJson(dynamic json) {
    _id = json['id'];
    _title = json['title'];
    _description = json['description'];
    _projectType = json['project_type'];
    _startDate = json['start_date'];
    _deadline = json['deadline'];
    _clientId = json['client_id'];
    _createdDate = json['created_date'];
    _createdBy = json['created_by'];
    _status = json['status'];
    _statusId = json['status_id'];
    _labels = json['labels'];
    _price = json['price'];
    _starredBy = json['starred_by'];
    _estimateId = json['estimate_id'];
    _orderId = json['order_id'];
    _proposalId = json['proposal_id'];
    _deleted = json['deleted'];
    _companyName = json['company_name'];
    _currencySymbol = json['currency_symbol'];
    _totalPoints = json['total_points'];
    _completedPoints = json['completed_points'];
    _statusKeyName = json['status_key_name'];
    _titleLanguageKey = json['title_language_key'];
    _statusTitle = json['status_title'];
    _statusIcon = json['status_icon'];
    _labelsList = json['labels_list'];
  }

  String? _id;
  String? _title;
  String? _description;
  String? _projectType;
  String? _startDate;
  String? _deadline;
  String? _clientId;
  String? _createdDate;
  String? _createdBy;
  String? _status;
  String? _statusId;
  String? _labels;
  String? _price;
  String? _starredBy;
  String? _estimateId;
  String? _orderId;
  String? _proposalId;
  String? _deleted;
  String? _companyName;
  String? _currencySymbol;
  String? _totalPoints;
  String? _completedPoints;
  String? _statusKeyName;
  String? _titleLanguageKey;
  String? _statusTitle;
  String? _statusIcon;
  String? _labelsList;

  String? get id => _id;
  String? get title => _title;
  String? get description => _description;
  String? get projectType => _projectType;
  String? get startDate => _startDate;
  String? get deadline => _deadline;
  String? get clientId => _clientId;
  String? get createdDate => _createdDate;
  String? get createdBy => _createdBy;
  String? get status => _status;
  String? get statusId => _statusId;
  String? get labels => _labels;
  String? get price => _price;
  String? get starredBy => _starredBy;
  String? get estimateId => _estimateId;
  String? get orderId => _orderId;
  String? get proposalId => _proposalId;
  String? get deleted => _deleted;
  String? get companyName => _companyName;
  String? get currencySymbol => _currencySymbol;
  String? get totalPoints => _totalPoints;
  String? get completedPoints => _completedPoints;
  String? get statusKeyName => _statusKeyName;
  String? get titleLanguageKey => _titleLanguageKey;
  String? get statusTitle => _statusTitle;
  String? get statusIcon => _statusIcon;
  String? get labelsList => _labelsList;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = _id;
    map['title'] = _title;
    map['description'] = _description;
    map['project_type'] = _projectType;
    map['start_date'] = _startDate;
    map['deadline'] = _deadline;
    map['client_id'] = _clientId;
    map['created_date'] = _createdDate;
    map['created_by'] = _createdBy;
    map['status'] = _status;
    map['status_id'] = _statusId;
    map['labels'] = _labels;
    map['price'] = _price;
    map['starred_by'] = _starredBy;
    map['estimate_id'] = _estimateId;
    map['order_id'] = _orderId;
    map['proposal_id'] = _proposalId;
    map['deleted'] = _deleted;
    map['company_name'] = _companyName;
    map['currency_symbol'] = _currencySymbol;
    map['total_points'] = _totalPoints;
    map['completed_points'] = _completedPoints;
    map['status_key_name'] = _statusKeyName;
    map['title_language_key'] = _titleLanguageKey;
    map['status_title'] = _statusTitle;
    map['status_icon'] = _statusIcon;
    map['labels_list'] = _labelsList;
    return map;
  }
}
