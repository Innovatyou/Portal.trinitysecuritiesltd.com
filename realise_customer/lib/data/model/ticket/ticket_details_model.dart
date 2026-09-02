class TicketDetailsModel {
  TicketDetailsModel({
    bool? success,
    String? message,
    Ticket? data,
  }) {
    _success = success;
    _message = message;
    _data = data;
  }

  TicketDetailsModel.fromJson(dynamic json) {
    _success = json['success'];
    _message = json['message'];
    _data = json['data'] != null ? Ticket.fromJson(json['data']) : null;
  }
  bool? _success;
  String? _message;
  Ticket? _data;

  bool? get success => _success;
  String? get message => _message;
  Ticket? get data => _data;

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

class Ticket {
  Ticket({
    String? id,
    String? clientId,
    String? projectId,
    String? ticketTypeId,
    String? title,
    String? createdBy,
    String? requestedBy,
    String? createdAt,
    String? status,
    String? lastActivityAt,
    String? assignedTo,
    String? creatorName,
    String? creatorEmail,
    String? labels,
    String? taskId,
    String? closedAt,
    String? mergedWithTicketId,
    String? deleted,
    String? ticketType,
    String? companyName,
    String? projectTitle,
    String? taskTitle,
    String? assignedToUser,
    String? assignedToAvatar,
    String? labelsList,
    String? requestedByName,
    List<Comment>? comments,
  }) {
    _id = id;
    _clientId = clientId;
    _projectId = projectId;
    _ticketTypeId = ticketTypeId;
    _title = title;
    _createdBy = createdBy;
    _requestedBy = requestedBy;
    _createdAt = createdAt;
    _status = status;
    _lastActivityAt = lastActivityAt;
    _assignedTo = assignedTo;
    _creatorName = creatorName;
    _creatorEmail = creatorEmail;
    _labels = labels;
    _taskId = taskId;
    _closedAt = closedAt;
    _mergedWithTicketId = mergedWithTicketId;
    _deleted = deleted;
    _ticketType = ticketType;
    _companyName = companyName;
    _projectTitle = projectTitle;
    _taskTitle = taskTitle;
    _assignedToUser = assignedToUser;
    _assignedToAvatar = assignedToAvatar;
    _labelsList = labelsList;
    _requestedByName = requestedByName;
    _comments = comments;
  }

  Ticket.fromJson(dynamic json) {
    _id = json['id'];
    _clientId = json['client_id'];
    _projectId = json['project_id'];
    _ticketTypeId = json['ticket_type_id'];
    _title = json['title'];
    _createdBy = json['created_by'];
    _requestedBy = json['requested_by'];
    _createdAt = json['created_at'];
    _status = json['status'];
    _lastActivityAt = json['last_activity_at'];
    _assignedTo = json['assigned_to'];
    _creatorName = json['creator_name'];
    _creatorEmail = json['creator_email'];
    _labels = json['labels'];
    _taskId = json['task_id'];
    _closedAt = json['closed_at'];
    _mergedWithTicketId = json['merged_with_ticket_id'];
    _deleted = json['deleted'];
    _ticketType = json['ticket_type'];
    _companyName = json['company_name'];
    _projectTitle = json['project_title'];
    _taskTitle = json['task_title'];
    _assignedToUser = json['assigned_to_user'];
    _assignedToAvatar = json['assigned_to_avatar'];
    _labelsList = json['labels_list'];
    _requestedByName = json['requested_by_name'];
    if (json['comments'] != null) {
      _comments = [];
      json['comments'].forEach((v) {
        _comments?.add(Comment.fromJson(v));
      });
    }
  }

  String? _id;
  String? _clientId;
  String? _projectId;
  String? _ticketTypeId;
  String? _title;
  String? _createdBy;
  String? _requestedBy;
  String? _createdAt;
  String? _status;
  String? _lastActivityAt;
  String? _assignedTo;
  String? _creatorName;
  String? _creatorEmail;
  String? _labels;
  String? _taskId;
  String? _closedAt;
  String? _mergedWithTicketId;
  String? _deleted;
  String? _ticketType;
  String? _companyName;
  String? _projectTitle;
  String? _taskTitle;
  String? _assignedToUser;
  String? _assignedToAvatar;
  String? _labelsList;
  String? _requestedByName;
  List<Comment>? _comments;

  String? get id => _id;
  String? get clientId => _clientId;
  String? get projectId => _projectId;
  String? get ticketTypeId => _ticketTypeId;
  String? get title => _title;
  String? get createdBy => _createdBy;
  String? get requestedBy => _requestedBy;
  String? get createdAt => _createdAt;
  String? get status => _status;
  String? get lastActivityAt => _lastActivityAt;
  String? get assignedTo => _assignedTo;
  String? get creatorName => _creatorName;
  String? get creatorEmail => _creatorEmail;
  String? get labels => _labels;
  String? get taskId => _taskId;
  String? get closedAt => _closedAt;
  String? get mergedWithTicketId => _mergedWithTicketId;
  String? get deleted => _deleted;
  String? get ticketType => _ticketType;
  String? get companyName => _companyName;
  String? get projectTitle => _projectTitle;
  String? get taskTitle => _taskTitle;
  String? get assignedToUser => _assignedToUser;
  String? get assignedToAvatar => _assignedToAvatar;
  String? get labelsList => _labelsList;
  String? get requestedByName => _requestedByName;
  List<Comment>? get comments => _comments;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = _id;
    map['client_id'] = _clientId;
    map['project_id'] = _projectId;
    map['ticket_type_id'] = _ticketTypeId;
    map['title'] = _title;
    map['created_by'] = _createdBy;
    map['requested_by'] = _requestedBy;
    map['created_at'] = _createdAt;
    map['status'] = _status;
    map['last_activity_at'] = _lastActivityAt;
    map['assigned_to'] = _assignedTo;
    map['creator_name'] = _creatorName;
    map['creator_email'] = _creatorEmail;
    map['labels'] = _labels;
    map['task_id'] = _taskId;
    map['closed_at'] = _closedAt;
    map['merged_with_ticket_id'] = _mergedWithTicketId;
    map['deleted'] = _deleted;
    map['ticket_type'] = _ticketType;
    map['company_name'] = _companyName;
    map['project_title'] = _projectTitle;
    map['task_title'] = _taskTitle;
    map['assigned_to_user'] = _assignedToUser;
    map['assigned_to_avatar'] = _assignedToAvatar;
    map['labels_list'] = _labelsList;
    map['requested_by_name'] = _requestedByName;
    if (_comments != null) {
      map['comments'] = _comments?.map((v) => v.toJson()).toList();
    }
    return map;
  }
}

class Comment {
  Comment({
    String? id,
    String? createdBy,
    String? createdAt,
    String? description,
    String? ticketId,
    List<TicketFile>? files,
    String? isNote,
    String? deleted,
    String? createdByUser,
    String? createdByAvatar,
    String? userType,
    String? creatorName,
    String? creatorEmail,
  }) {
    _id = id;
    _createdBy = createdBy;
    _createdAt = createdAt;
    _description = description;
    _ticketId = ticketId;
    _files = files;
    _isNote = isNote;
    _deleted = deleted;
    _createdByUser = createdByUser;
    _createdByAvatar = createdByAvatar;
    _userType = userType;
    _creatorName = creatorName;
    _creatorEmail = creatorEmail;
  }

  Comment.fromJson(dynamic json) {
    _id = json['id'];
    _createdBy = json['created_by'];
    _createdAt = json['created_at'];
    _description = json['description'];
    _ticketId = json['ticket_id'];
    if (json['files'] != null) {
      _files = [];
      json['files'].forEach((v) {
        _files?.add(TicketFile.fromJson(v));
      });
    }
    _isNote = json['is_note'];
    _deleted = json['deleted'];
    _createdByUser = json['created_by_user'];
    _createdByAvatar = json['created_by_avatar'];
    _userType = json['user_type'];
    _creatorName = json['creator_name'];
    _creatorEmail = json['creator_email'];
  }

  String? _id;
  String? _createdBy;
  String? _createdAt;
  String? _description;
  String? _ticketId;
  List<TicketFile>? _files;
  String? _isNote;
  String? _deleted;
  String? _createdByUser;
  String? _createdByAvatar;
  String? _userType;
  String? _creatorName;
  String? _creatorEmail;

  String? get id => _id;
  String? get createdBy => _createdBy;
  String? get createdAt => _createdAt;
  String? get description => _description;
  String? get ticketId => _ticketId;
  List<TicketFile>? get files => _files;
  String? get isNote => _isNote;
  String? get deleted => _deleted;
  String? get createdByUser => _createdByUser;
  String? get createdByAvatar => _createdByAvatar;
  String? get userType => _userType;
  String? get creatorName => _creatorName;
  String? get creatorEmail => _creatorEmail;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = _id;
    map['created_by'] = _createdBy;
    map['created_at'] = _createdAt;
    map['description'] = _description;
    map['ticket_id'] = _ticketId;
    map['files'] = _files;
    if (_files != null) {
      map['files'] = _files?.map((v) => v.toJson()).toList();
    }
    map['is_note'] = _isNote;
    map['deleted'] = _deleted;
    map['created_by_user'] = _createdByUser;
    map['created_by_avatar'] = _createdByAvatar;
    map['user_type'] = _userType;
    map['creator_name'] = _creatorName;
    map['creator_email'] = _creatorEmail;
    return map;
  }
}

class TicketFile {
  TicketFile({
    String? fileName,
    String? fileSize,
    String? fileId,
    String? serviceType,
  }) {
    _fileName = fileName;
    _fileSize = fileSize;
    _fileId = fileId;
    _serviceType = serviceType;
  }

  TicketFile.fromJson(dynamic json) {
    _fileName = json['file_name'];
    _fileSize = json['file_size'];
    _fileId = json['file_id'];
    _serviceType = json['service_type'];
  }

  String? _fileName;
  String? _fileSize;
  String? _fileId;
  String? _serviceType;

  String? get fileName => _fileName;
  String? get fileSize => _fileSize;
  String? get fileId => _fileId;
  String? get serviceType => _serviceType;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['file_name'] = _fileName;
    map['file_size'] = _fileSize;
    map['file_id'] = _fileId;
    map['service_type'] = _serviceType;
    return map;
  }
}
