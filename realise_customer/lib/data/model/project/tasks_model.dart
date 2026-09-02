class TasksModel {
  TasksModel({
    bool? success,
    String? message,
    List<Task>? data,
  }) {
    _success = success;
    _message = message;
    _data = data;
  }
  TasksModel.fromJson(dynamic json) {
    _success = json['success'];
    _message = json['message'];
    if (json['data'] != null) {
      _data = [];
      json['data'].forEach((v) {
        _data?.add(Task.fromJson(v));
      });
    }
  }
  bool? _success;
  String? _message;
  List<Task>? _data;

  bool? get success => _success;
  String? get message => _message;
  List<Task>? get data => _data;

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

class Task {
  Task({
    String? id,
    String? title,
    String? description,
    String? projectId,
    String? milestoneId,
    String? assignedTo,
    String? deadline,
    String? labels,
    String? points,
    String? status,
    String? statusId,
    String? priorityId,
    String? startDate,
    String? collaborators,
    String? sort,
    String? recurring,
    String? repeatEvery,
    String? repeatType,
    String? noOfCycles,
    String? recurringTaskId,
    String? noOfCyclesCompleted,
    String? createdDate,
    String? blocking,
    String? blockedBy,
    String? parentTaskId,
    String? nextRecurringDate,
    String? reminderDate,
    String? ticketId,
    String? statusChangedAt,
    String? deleted,
    String? expenseId,
    String? subscriptionId,
    String? proposalId,
    String? contractId,
    String? orderId,
    String? estimateId,
    String? invoiceId,
    String? leadId,
    String? clientId,
    String? context,
    String? createdBy,
    String? statusKeyName,
    String? statusTitle,
    String? statusColor,
    String? assignedToUser,
    String? assignedToAvatar,
    String? userType,
    String? projectTitle,
    String? milestoneTitle,
    String? ticketTitle,
    String? collaboratorList,
    String? priorityTitle,
    String? priorityIcon,
    String? priorityColor,
    String? companyName,
    String? contractTitle,
    String? subscriptionTitle,
    String? expenseDate,
    String? expenseTitle,
    String? invoiceDisplayId,
    String? deadlineMilestoneTitle,
    String? unread,
    String? hasSubTasks,
    String? labelsList,
  }) {
    _id = id;
    _title = title;
    _description = description;
    _projectId = projectId;
    _milestoneId = milestoneId;
    _assignedTo = assignedTo;
    _deadline = deadline;
    _labels = labels;
    _points = points;
    _status = status;
    _statusId = statusId;
    _priorityId = priorityId;
    _startDate = startDate;
    _collaborators = collaborators;
    _sort = sort;
    _recurring = recurring;
    _repeatEvery = repeatEvery;
    _repeatType = repeatType;
    _noOfCycles = noOfCycles;
    _recurringTaskId = recurringTaskId;
    _noOfCyclesCompleted = noOfCyclesCompleted;
    _createdDate = createdDate;
    _blocking = blocking;
    _blockedBy = blockedBy;
    _parentTaskId = parentTaskId;
    _nextRecurringDate = nextRecurringDate;
    _reminderDate = reminderDate;
    _ticketId = ticketId;
    _statusChangedAt = statusChangedAt;
    _deleted = deleted;
    _expenseId = expenseId;
    _subscriptionId = subscriptionId;
    _proposalId = proposalId;
    _contractId = contractId;
    _orderId = orderId;
    _estimateId = estimateId;
    _invoiceId = invoiceId;
    _leadId = leadId;
    _clientId = clientId;
    _context = context;
    _createdBy = createdBy;
    _statusKeyName = statusKeyName;
    _statusTitle = statusTitle;
    _statusColor = statusColor;
    _assignedToUser = assignedToUser;
    _assignedToAvatar = assignedToAvatar;
    _userType = userType;
    _projectTitle = projectTitle;
    _milestoneTitle = milestoneTitle;
    _ticketTitle = ticketTitle;
    _collaboratorList = collaboratorList;
    _priorityTitle = priorityTitle;
    _priorityIcon = priorityIcon;
    _priorityColor = priorityColor;
    _companyName = companyName;
    _contractTitle = contractTitle;
    _subscriptionTitle = subscriptionTitle;
    _expenseDate = expenseDate;
    _expenseTitle = expenseTitle;
    _invoiceDisplayId = invoiceDisplayId;
    _deadlineMilestoneTitle = deadlineMilestoneTitle;
    _unread = unread;
    _hasSubTasks = hasSubTasks;
    _labelsList = labelsList;
  }

  Task.fromJson(dynamic json) {
    _id = json['id'];
    _title = json['title'];
    _description = json['description'];
    _projectId = json['project_id'];
    _milestoneId = json['milestone_id'];
    _assignedTo = json['assigned_to'];
    _deadline = json['deadline'];
    _labels = json['labels'];
    _points = json['points'];
    _status = json['status'];
    _statusId = json['status_id'];
    _priorityId = json['priority_id'];
    _startDate = json['start_date'];
    _collaborators = json['collaborators'];
    _sort = json['sort'];
    _recurring = json['recurring'];
    _repeatEvery = json['repeat_every'];
    _repeatType = json['repeat_type'];
    _noOfCycles = json['no_of_cycles'];
    _recurringTaskId = json['recurring_task_id'];
    _noOfCyclesCompleted = json['no_of_cycles_completed'];
    _createdDate = json['created_date'];
    _blocking = json['blocking'];
    _blockedBy = json['blocked_by'];
    _parentTaskId = json['parent_task_id'];
    _nextRecurringDate = json['next_recurring_date'];
    _reminderDate = json['reminder_date'];
    _ticketId = json['ticket_id'];
    _statusChangedAt = json['status_changed_at'];
    _deleted = json['deleted'];
    _expenseId = json['expense_id'];
    _subscriptionId = json['subscription_id'];
    _proposalId = json['proposal_id'];
    _contractId = json['contract_id'];
    _orderId = json['order_id'];
    _estimateId = json['estimate_id'];
    _invoiceId = json['invoice_id'];
    _leadId = json['lead_id'];
    _clientId = json['client_id'];
    _context = json['context'];
    _createdBy = json['created_by'];
    _statusKeyName = json['status_key_name'];
    _statusTitle = json['status_title'];
    _statusColor = json['status_color'];
    _assignedToUser = json['assigned_to_user'];
    _assignedToAvatar = json['assigned_to_avatar'];
    _userType = json['user_type'];
    _projectTitle = json['project_title'];
    _milestoneTitle = json['milestone_title'];
    _ticketTitle = json['ticket_title'];
    _collaboratorList = json['collaborator_list'];
    _priorityTitle = json['priority_title'];
    _priorityIcon = json['priority_icon'];
    _priorityColor = json['priority_color'];
    _companyName = json['company_name'];
    _contractTitle = json['contract_title'];
    _subscriptionTitle = json['subscription_title'];
    _expenseDate = json['expense_date'];
    _expenseTitle = json['expense_title'];
    _invoiceDisplayId = json['invoice_display_id'];
    _deadlineMilestoneTitle = json['deadline_milestone_title'];
    _unread = json['unread'];
    _hasSubTasks = json['has_sub_tasks'];
    _labelsList = json['labels_list'];
  }
  String? _id;
  String? _title;
  String? _description;
  String? _projectId;
  String? _milestoneId;
  String? _assignedTo;
  String? _deadline;
  String? _labels;
  String? _points;
  String? _status;
  String? _statusId;
  String? _priorityId;
  String? _startDate;
  String? _collaborators;
  String? _sort;
  String? _recurring;
  String? _repeatEvery;
  String? _repeatType;
  String? _noOfCycles;
  String? _recurringTaskId;
  String? _noOfCyclesCompleted;
  String? _createdDate;
  String? _blocking;
  String? _blockedBy;
  String? _parentTaskId;
  String? _nextRecurringDate;
  String? _reminderDate;
  String? _ticketId;
  String? _statusChangedAt;
  String? _deleted;
  String? _expenseId;
  String? _subscriptionId;
  String? _proposalId;
  String? _contractId;
  String? _orderId;
  String? _estimateId;
  String? _invoiceId;
  String? _leadId;
  String? _clientId;
  String? _context;
  String? _createdBy;
  String? _statusKeyName;
  String? _statusTitle;
  String? _statusColor;
  String? _assignedToUser;
  String? _assignedToAvatar;
  String? _userType;
  String? _projectTitle;
  String? _milestoneTitle;
  String? _ticketTitle;
  String? _collaboratorList;
  String? _priorityTitle;
  String? _priorityIcon;
  String? _priorityColor;
  String? _companyName;
  String? _contractTitle;
  String? _subscriptionTitle;
  String? _expenseDate;
  String? _expenseTitle;
  String? _invoiceDisplayId;
  String? _deadlineMilestoneTitle;
  String? _unread;
  String? _hasSubTasks;
  String? _labelsList;

  String? get id => _id;
  String? get title => _title;
  String? get description => _description;
  String? get projectId => _projectId;
  String? get milestoneId => _milestoneId;
  String? get assignedTo => _assignedTo;
  String? get deadline => _deadline;
  String? get labels => _labels;
  String? get points => _points;
  String? get status => _status;
  String? get statusId => _statusId;
  String? get priorityId => _priorityId;
  String? get startDate => _startDate;
  String? get collaborators => _collaborators;
  String? get sort => _sort;
  String? get recurring => _recurring;
  String? get repeatEvery => _repeatEvery;
  String? get repeatType => _repeatType;
  String? get noOfCycles => _noOfCycles;
  String? get recurringTaskId => _recurringTaskId;
  String? get noOfCyclesCompleted => _noOfCyclesCompleted;
  String? get createdDate => _createdDate;
  String? get blocking => _blocking;
  String? get blockedBy => _blockedBy;
  String? get parentTaskId => _parentTaskId;
  String? get nextRecurringDate => _nextRecurringDate;
  String? get reminderDate => _reminderDate;
  String? get ticketId => _ticketId;
  String? get statusChangedAt => _statusChangedAt;
  String? get deleted => _deleted;
  String? get expenseId => _expenseId;
  String? get subscriptionId => _subscriptionId;
  String? get proposalId => _proposalId;
  String? get contractId => _contractId;
  String? get orderId => _orderId;
  String? get estimateId => _estimateId;
  String? get invoiceId => _invoiceId;
  String? get leadId => _leadId;
  String? get clientId => _clientId;
  String? get context => _context;
  String? get createdBy => _createdBy;
  String? get statusKeyName => _statusKeyName;
  String? get statusTitle => _statusTitle;
  String? get statusColor => _statusColor;
  String? get assignedToUser => _assignedToUser;
  String? get assignedToAvatar => _assignedToAvatar;
  String? get userType => _userType;
  String? get projectTitle => _projectTitle;
  String? get milestoneTitle => _milestoneTitle;
  String? get ticketTitle => _ticketTitle;
  String? get collaboratorList => _collaboratorList;
  String? get priorityTitle => _priorityTitle;
  String? get priorityIcon => _priorityIcon;
  String? get priorityColor => _priorityColor;
  String? get companyName => _companyName;
  String? get contractTitle => _contractTitle;
  String? get subscriptionTitle => _subscriptionTitle;
  String? get expenseDate => _expenseDate;
  String? get expenseTitle => _expenseTitle;
  String? get invoiceDisplayId => _invoiceDisplayId;
  String? get deadlineMilestoneTitle => _deadlineMilestoneTitle;
  String? get unread => _unread;
  String? get hasSubTasks => _hasSubTasks;
  String? get labelsList => _labelsList;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = _id;
    map['title'] = _title;
    map['description'] = _description;
    map['project_id'] = _projectId;
    map['milestone_id'] = _milestoneId;
    map['assigned_to'] = _assignedTo;
    map['deadline'] = _deadline;
    map['labels'] = _labels;
    map['points'] = _points;
    map['status'] = _status;
    map['status_id'] = _statusId;
    map['priority_id'] = _priorityId;
    map['start_date'] = _startDate;
    map['collaborators'] = _collaborators;
    map['sort'] = _sort;
    map['recurring'] = _recurring;
    map['repeat_every'] = _repeatEvery;
    map['repeat_type'] = _repeatType;
    map['no_of_cycles'] = _noOfCycles;
    map['recurring_task_id'] = _recurringTaskId;
    map['no_of_cycles_completed'] = _noOfCyclesCompleted;
    map['created_date'] = _createdDate;
    map['blocking'] = _blocking;
    map['blocked_by'] = _blockedBy;
    map['parent_task_id'] = _parentTaskId;
    map['next_recurring_date'] = _nextRecurringDate;
    map['reminder_date'] = _reminderDate;
    map['ticket_id'] = _ticketId;
    map['status_changed_at'] = _statusChangedAt;
    map['deleted'] = _deleted;
    map['expense_id'] = _expenseId;
    map['subscription_id'] = _subscriptionId;
    map['proposal_id'] = _proposalId;
    map['contract_id'] = _contractId;
    map['order_id'] = _orderId;
    map['estimate_id'] = _estimateId;
    map['invoice_id'] = _invoiceId;
    map['lead_id'] = _leadId;
    map['client_id'] = _clientId;
    map['context'] = _context;
    map['created_by'] = _createdBy;
    map['status_key_name'] = _statusKeyName;
    map['status_title'] = _statusTitle;
    map['status_color'] = _statusColor;
    map['assigned_to_user'] = _assignedToUser;
    map['assigned_to_avatar'] = _assignedToAvatar;
    map['user_type'] = _userType;
    map['project_title'] = _projectTitle;
    map['milestone_title'] = _milestoneTitle;
    map['ticket_title'] = _ticketTitle;
    map['collaborator_list'] = _collaboratorList;
    map['priority_title'] = _priorityTitle;
    map['priority_icon'] = _priorityIcon;
    map['priority_color'] = _priorityColor;
    map['company_name'] = _companyName;
    map['contract_title'] = _contractTitle;
    map['subscription_title'] = _subscriptionTitle;
    map['expense_date'] = _expenseDate;
    map['expense_title'] = _expenseTitle;
    map['invoice_display_id'] = _invoiceDisplayId;
    map['deadline_milestone_title'] = _deadlineMilestoneTitle;
    map['unread'] = _unread;
    map['has_sub_tasks'] = _hasSubTasks;
    map['labels_list'] = _labelsList;
    return map;
  }
}
