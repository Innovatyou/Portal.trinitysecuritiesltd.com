class InvoiceDetailsModel {
  InvoiceDetailsModel({
    bool? success,
    String? message,
    Invoice? data,
  }) {
    _success = success;
    _message = message;
    _data = data;
  }

  InvoiceDetailsModel.fromJson(dynamic json) {
    _success = json['success'];
    _message = json['message'];
    _data = json['data'] != null ? Invoice.fromJson(json['data']) : null;
  }
  bool? _success;
  String? _message;
  Invoice? _data;

  bool? get success => _success;
  String? get message => _message;
  Invoice? get data => _data;

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

class Invoice {
  Invoice({
    String? id,
    String? type,
    String? clientId,
    String? projectId,
    String? billDate,
    String? dueDate,
    String? note,
    String? labels,
    String? lastEmailSentDate,
    String? status,
    String? taxId,
    String? taxId2,
    String? taxId3,
    String? recurring,
    String? recurringInvoiceId,
    String? repeatEvery,
    String? repeatType,
    String? noOfCycles,
    String? nextRecurringDate,
    String? noOfCyclesCompleted,
    String? dueReminderDate,
    String? recurringReminderDate,
    String? discountAmount,
    String? discountAmountType,
    String? discountType,
    String? cancelledAt,
    String? cancelledBy,
    String? files,
    String? companyId,
    String? estimateId,
    String? mainInvoiceId,
    String? subscriptionId,
    String? invoiceTotal,
    String? invoiceSubtotal,
    String? discountTotal,
    String? tax,
    String? tax2,
    String? tax3,
    String? deleted,
    String? orderId,
    String? displayId,
    String? numberYear,
    String? numberSequence,
    String? currency,
    String? currencySymbol,
    String? companyName,
    String? projectTitle,
    String? creditNoteId,
    String? creditNoteDisplayId,
    String? mainInvoiceDisplayId,
    String? recurringInvoiceDisplayId,
    String? invoiceValue,
    String? paymentReceived,
    String? taxPercentage,
    String? taxPercentage2,
    String? taxPercentage3,
    String? cancelledByUser,
    String? labelsList,
    List<Item>? items,
    List<Payment>? payments,
  }) {
    _id = id;
    _type = type;
    _clientId = clientId;
    _projectId = projectId;
    _billDate = billDate;
    _dueDate = dueDate;
    _note = note;
    _labels = labels;
    _lastEmailSentDate = lastEmailSentDate;
    _status = status;
    _taxId = taxId;
    _taxId2 = taxId2;
    _taxId3 = taxId3;
    _recurring = recurring;
    _recurringInvoiceId = recurringInvoiceId;
    _repeatEvery = repeatEvery;
    _repeatType = repeatType;
    _noOfCycles = noOfCycles;
    _nextRecurringDate = nextRecurringDate;
    _noOfCyclesCompleted = noOfCyclesCompleted;
    _dueReminderDate = dueReminderDate;
    _recurringReminderDate = recurringReminderDate;
    _discountAmount = discountAmount;
    _discountAmountType = discountAmountType;
    _discountType = discountType;
    _cancelledAt = cancelledAt;
    _cancelledBy = cancelledBy;
    _files = files;
    _companyId = companyId;
    _estimateId = estimateId;
    _mainInvoiceId = mainInvoiceId;
    _subscriptionId = subscriptionId;
    _invoiceTotal = invoiceTotal;
    _invoiceSubtotal = invoiceSubtotal;
    _discountTotal = discountTotal;
    _tax = tax;
    _tax2 = tax2;
    _tax3 = tax3;
    _deleted = deleted;
    _orderId = orderId;
    _displayId = displayId;
    _numberYear = numberYear;
    _numberSequence = numberSequence;
    _currency = currency;
    _currencySymbol = currencySymbol;
    _companyName = companyName;
    _projectTitle = projectTitle;
    _creditNoteId = creditNoteId;
    _creditNoteDisplayId = creditNoteDisplayId;
    _mainInvoiceDisplayId = mainInvoiceDisplayId;
    _recurringInvoiceDisplayId = recurringInvoiceDisplayId;
    _invoiceValue = invoiceValue;
    _paymentReceived = paymentReceived;
    _taxPercentage = taxPercentage;
    _taxPercentage2 = taxPercentage2;
    _taxPercentage3 = taxPercentage3;
    _cancelledByUser = cancelledByUser;
    _labelsList = labelsList;
    _items = items;
    _payments = payments;
  }

  Invoice.fromJson(dynamic json) {
    _id = json['id'];
    _type = json['type'];
    _clientId = json['client_id'];
    _projectId = json['project_id'];
    _billDate = json['bill_date'];
    _dueDate = json['due_date'];
    _note = json['note'];
    _labels = json['labels'];
    _lastEmailSentDate = json['last_email_sent_date'];
    _status = json['status'];
    _taxId = json['tax_id'];
    _taxId2 = json['tax_id2'];
    _taxId3 = json['tax_id3'];
    _recurring = json['recurring'];
    _recurringInvoiceId = json['recurring_invoice_id'];
    _repeatEvery = json['repeat_every'];
    _repeatType = json['repeat_type'];
    _noOfCycles = json['no_of_cycles'];
    _nextRecurringDate = json['next_recurring_date'];
    _noOfCyclesCompleted = json['no_of_cycles_completed'];
    _dueReminderDate = json['due_reminder_date'];
    _recurringReminderDate = json['recurring_reminder_date'];
    _discountAmount = json['discount_amount'];
    _discountAmountType = json['discount_amount_type'];
    _discountType = json['discount_type'];
    _cancelledAt = json['cancelled_at'];
    _cancelledBy = json['cancelled_by'];
    _files = json['files'];
    _companyId = json['company_id'];
    _estimateId = json['estimate_id'];
    _mainInvoiceId = json['main_invoice_id'];
    _subscriptionId = json['subscription_id'];
    _invoiceTotal = json['invoice_total'];
    _invoiceSubtotal = json['invoice_subtotal'];
    _discountTotal = json['discount_total'];
    _tax = json['tax'];
    _tax2 = json['tax2'];
    _tax3 = json['tax3'];
    _deleted = json['deleted'];
    _orderId = json['order_id'];
    _displayId = json['display_id'];
    _numberYear = json['number_year'];
    _numberSequence = json['number_sequence'];
    _currency = json['currency'];
    _currencySymbol = json['currency_symbol'];
    _companyName = json['company_name'];
    _projectTitle = json['project_title'];
    _creditNoteId = json['credit_note_id'];
    _creditNoteDisplayId = json['credit_note_display_id'];
    _mainInvoiceDisplayId = json['main_invoice_display_id'];
    _recurringInvoiceDisplayId = json['recurring_invoice_display_id'];
    _invoiceValue = json['invoice_value'];
    _paymentReceived = json['payment_received'];
    _taxPercentage = json['tax_percentage'];
    _taxPercentage2 = json['tax_percentage2'];
    _taxPercentage3 = json['tax_percentage3'];
    _cancelledByUser = json['cancelled_by_user'];
    _labelsList = json['labels_list'];
    if (json['items'] != null) {
      _items = [];
      json['items'].forEach((v) {
        _items?.add(Item.fromJson(v));
      });
    }
    if (json['payments'] != null) {
      _payments = [];
      json['payments'].forEach((v) {
        _payments?.add(Payment.fromJson(v));
      });
    }
  }

  String? _id;
  String? _type;
  String? _clientId;
  String? _projectId;
  String? _billDate;
  String? _dueDate;
  String? _note;
  String? _labels;
  String? _lastEmailSentDate;
  String? _status;
  String? _taxId;
  String? _taxId2;
  String? _taxId3;
  String? _recurring;
  String? _recurringInvoiceId;
  String? _repeatEvery;
  String? _repeatType;
  String? _noOfCycles;
  String? _nextRecurringDate;
  String? _noOfCyclesCompleted;
  String? _dueReminderDate;
  String? _recurringReminderDate;
  String? _discountAmount;
  String? _discountAmountType;
  String? _discountType;
  String? _cancelledAt;
  String? _cancelledBy;
  String? _files;
  String? _companyId;
  String? _estimateId;
  String? _mainInvoiceId;
  String? _subscriptionId;
  String? _invoiceTotal;
  String? _invoiceSubtotal;
  String? _discountTotal;
  String? _tax;
  String? _tax2;
  String? _tax3;
  String? _deleted;
  String? _orderId;
  String? _displayId;
  String? _numberYear;
  String? _numberSequence;
  String? _currency;
  String? _currencySymbol;
  String? _companyName;
  String? _projectTitle;
  String? _creditNoteId;
  String? _creditNoteDisplayId;
  String? _mainInvoiceDisplayId;
  String? _recurringInvoiceDisplayId;
  String? _invoiceValue;
  String? _paymentReceived;
  String? _taxPercentage;
  String? _taxPercentage2;
  String? _taxPercentage3;
  String? _cancelledByUser;
  String? _labelsList;
  List<Item>? _items;
  List<Payment>? _payments;

  String? get id => _id;
  String? get type => _type;
  String? get clientId => _clientId;
  String? get projectId => _projectId;
  String? get billDate => _billDate;
  String? get dueDate => _dueDate;
  String? get note => _note;
  String? get labels => _labels;
  String? get lastEmailSentDate => _lastEmailSentDate;
  String? get status => _status;
  String? get taxId => _taxId;
  String? get taxId2 => _taxId2;
  String? get taxId3 => _taxId3;
  String? get recurring => _recurring;
  String? get recurringInvoiceId => _recurringInvoiceId;
  String? get repeatEvery => _repeatEvery;
  String? get repeatType => _repeatType;
  String? get noOfCycles => _noOfCycles;
  String? get nextRecurringDate => _nextRecurringDate;
  String? get noOfCyclesCompleted => _noOfCyclesCompleted;
  String? get dueReminderDate => _dueReminderDate;
  String? get recurringReminderDate => _recurringReminderDate;
  String? get discountAmount => _discountAmount;
  String? get discountAmountType => _discountAmountType;
  String? get discountType => _discountType;
  String? get cancelledAt => _cancelledAt;
  String? get cancelledBy => _cancelledBy;
  String? get files => _files;
  String? get companyId => _companyId;
  String? get estimateId => _estimateId;
  String? get mainInvoiceId => _mainInvoiceId;
  String? get subscriptionId => _subscriptionId;
  String? get invoiceTotal => _invoiceTotal;
  String? get invoiceSubtotal => _invoiceSubtotal;
  String? get discountTotal => _discountTotal;
  String? get tax => _tax;
  String? get tax2 => _tax2;
  String? get tax3 => _tax3;
  String? get deleted => _deleted;
  String? get orderId => _orderId;
  String? get displayId => _displayId;
  String? get numberYear => _numberYear;
  String? get numberSequence => _numberSequence;
  String? get currency => _currency;
  String? get currencySymbol => _currencySymbol;
  String? get companyName => _companyName;
  String? get projectTitle => _projectTitle;
  String? get creditNoteId => _creditNoteId;
  String? get creditNoteDisplayId => _creditNoteDisplayId;
  String? get mainInvoiceDisplayId => _mainInvoiceDisplayId;
  String? get recurringInvoiceDisplayId => _recurringInvoiceDisplayId;
  String? get invoiceValue => _invoiceValue;
  String? get paymentReceived => _paymentReceived;
  String? get taxPercentage => _taxPercentage;
  String? get taxPercentage2 => _taxPercentage2;
  String? get taxPercentage3 => _taxPercentage3;
  String? get cancelledByUser => _cancelledByUser;
  String? get labelsList => _labelsList;
  List<Item>? get items => _items;
  List<Payment>? get payments => _payments;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = _id;
    map['type'] = _type;
    map['client_id'] = _clientId;
    map['project_id'] = _projectId;
    map['bill_date'] = _billDate;
    map['due_date'] = _dueDate;
    map['note'] = _note;
    map['labels'] = _labels;
    map['last_email_sent_date'] = _lastEmailSentDate;
    map['status'] = _status;
    map['tax_id'] = _taxId;
    map['tax_id2'] = _taxId2;
    map['tax_id3'] = _taxId3;
    map['recurring'] = _recurring;
    map['recurring_invoice_id'] = _recurringInvoiceId;
    map['repeat_every'] = _repeatEvery;
    map['repeat_type'] = _repeatType;
    map['no_of_cycles'] = _noOfCycles;
    map['next_recurring_date'] = _nextRecurringDate;
    map['no_of_cycles_completed'] = _noOfCyclesCompleted;
    map['due_reminder_date'] = _dueReminderDate;
    map['recurring_reminder_date'] = _recurringReminderDate;
    map['discount_amount'] = _discountAmount;
    map['discount_amount_type'] = _discountAmountType;
    map['discount_type'] = _discountType;
    map['cancelled_at'] = _cancelledAt;
    map['cancelled_by'] = _cancelledBy;
    map['files'] = _files;
    map['company_id'] = _companyId;
    map['estimate_id'] = _estimateId;
    map['main_invoice_id'] = _mainInvoiceId;
    map['subscription_id'] = _subscriptionId;
    map['invoice_total'] = _invoiceTotal;
    map['invoice_subtotal'] = _invoiceSubtotal;
    map['discount_total'] = _discountTotal;
    map['tax'] = _tax;
    map['tax2'] = _tax2;
    map['tax3'] = _tax3;
    map['deleted'] = _deleted;
    map['order_id'] = _orderId;
    map['display_id'] = _displayId;
    map['number_year'] = _numberYear;
    map['number_sequence'] = _numberSequence;
    map['currency'] = _currency;
    map['currency_symbol'] = _currencySymbol;
    map['company_name'] = _companyName;
    map['project_title'] = _projectTitle;
    map['credit_note_id'] = _creditNoteId;
    map['credit_note_display_id'] = _creditNoteDisplayId;
    map['main_invoice_display_id'] = _mainInvoiceDisplayId;
    map['recurring_invoice_display_id'] = _recurringInvoiceDisplayId;
    map['invoice_value'] = _invoiceValue;
    map['payment_received'] = _paymentReceived;
    map['tax_percentage'] = _taxPercentage;
    map['tax_percentage2'] = _taxPercentage2;
    map['tax_percentage3'] = _taxPercentage3;
    map['cancelled_by_user'] = _cancelledByUser;
    map['labels_list'] = _labelsList;
    if (_items != null) {
      map['items'] = _items?.map((v) => v.toJson()).toList();
    }
    if (_payments != null) {
      map['payments'] = _payments?.map((v) => v.toJson()).toList();
    }
    return map;
  }
}

class Item {
  Item({
    String? id,
    String? title,
    String? description,
    String? quantity,
    String? unitType,
    String? rate,
    String? total,
    String? sort,
    String? estimateId,
    String? itemId,
    String? deleted,
    String? currencySymbol,
    String? createdBy,
  }) {
    _id = id;
    _title = title;
    _description = description;
    _quantity = quantity;
    _unitType = unitType;
    _rate = rate;
    _total = total;
    _sort = sort;
    _estimateId = estimateId;
    _itemId = itemId;
    _deleted = deleted;
    _currencySymbol = currencySymbol;
    _createdBy = createdBy;
  }

  Item.fromJson(dynamic json) {
    _id = json['id'];
    _title = json['title'];
    _description = json['description'];
    _quantity = json['quantity'];
    _unitType = json['unit_type'];
    _rate = json['rate'];
    _total = json['total'];
    _sort = json['sort'];
    _estimateId = json['estimate_id'];
    _itemId = json['item_id'];
    _deleted = json['deleted'];
    _currencySymbol = json['currency_symbol'];
    _createdBy = json['created_by'];
  }

  String? _id;
  String? _title;
  String? _description;
  String? _quantity;
  String? _unitType;
  String? _rate;
  String? _total;
  String? _sort;
  String? _estimateId;
  String? _itemId;
  String? _deleted;
  String? _currencySymbol;
  String? _createdBy;

  String? get id => _id;
  String? get title => _title;
  String? get description => _description;
  String? get quantity => _quantity;
  String? get unitType => _unitType;
  String? get rate => _rate;
  String? get total => _total;
  String? get sort => _sort;
  String? get estimateId => _estimateId;
  String? get itemId => _itemId;
  String? get deleted => _deleted;
  String? get currencySymbol => _currencySymbol;
  String? get createdBy => _createdBy;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = _id;
    map['title'] = _title;
    map['description'] = _description;
    map['quantity'] = _quantity;
    map['unit_type'] = _unitType;
    map['rate'] = _rate;
    map['total'] = _total;
    map['sort'] = _sort;
    map['estimate_id'] = _estimateId;
    map['item_id'] = _itemId;
    map['deleted'] = _deleted;
    map['currency_symbol'] = _currencySymbol;
    map['created_by'] = _createdBy;
    return map;
  }
}

class Payment {
  Payment({
    String? id,
    String? amount,
    String? paymentDate,
    String? paymentMethodId,
    String? note,
    String? invoiceId,
    String? deleted,
    String? transactionId,
    String? createdBy,
    String? createdAt,
    String? clientId,
    String? displayId,
    String? currencySymbol,
    String? paymentMethodTitle,
  }) {
    _id = id;
    _amount = amount;
    _paymentDate = paymentDate;
    _paymentMethodId = paymentMethodId;
    _note = note;
    _invoiceId = invoiceId;
    _deleted = deleted;
    _transactionId = transactionId;
    _createdBy = createdBy;
    _createdAt = createdAt;
    _clientId = clientId;
    _displayId = displayId;
    _currencySymbol = currencySymbol;
    _paymentMethodTitle = paymentMethodTitle;
  }

  Payment.fromJson(dynamic json) {
    _id = json['id'];
    _amount = json['amount'];
    _paymentDate = json['payment_date'];
    _paymentMethodId = json['payment_method_id'];
    _note = json['note'];
    _invoiceId = json['invoice_id'];
    _deleted = json['deleted'];
    _transactionId = json['transaction_id'];
    _createdBy = json['created_by'];
    _createdAt = json['created_at'];
    _clientId = json['client_id'];
    _displayId = json['display_id'];
    _currencySymbol = json['currency_symbol'];
    _paymentMethodTitle = json['payment_method_title'];
  }

  String? _id;
  String? _amount;
  String? _paymentDate;
  String? _paymentMethodId;
  String? _note;
  String? _invoiceId;
  String? _deleted;
  String? _transactionId;
  String? _createdBy;
  String? _createdAt;
  String? _clientId;
  String? _displayId;
  String? _currencySymbol;
  String? _paymentMethodTitle;

  String? get id => _id;
  String? get amount => _amount;
  String? get paymentDate => _paymentDate;
  String? get paymentMethodId => _paymentMethodId;
  String? get note => _note;
  String? get invoiceId => _invoiceId;
  String? get deleted => _deleted;
  String? get transactionId => _transactionId;
  String? get createdBy => _createdBy;
  String? get createdAt => _createdAt;
  String? get clientId => _clientId;
  String? get displayId => _displayId;
  String? get currencySymbol => _currencySymbol;
  String? get paymentMethodTitle => _paymentMethodTitle;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = _id;
    map['amount'] = _amount;
    map['payment_date'] = _paymentDate;
    map['payment_method_id'] = _paymentMethodId;
    map['note'] = _note;
    map['invoice_id'] = _invoiceId;
    map['deleted'] = _deleted;
    map['transaction_id'] = _transactionId;
    map['created_by'] = _createdBy;
    map['created_at'] = _createdAt;
    map['client_id'] = _clientId;
    map['display_id'] = _displayId;
    map['currency_symbol'] = _currencySymbol;
    map['payment_method_title'] = _paymentMethodTitle;
    return map;
  }
}

class Attachments {
  Attachments({
    String? id,
    String? relId,
    String? relType,
    String? fileName,
    String? fileType,
    String? visibleToCustomer,
    String? attachmentKey,
    String? external,
    String? externalLink,
    String? thumbnailLink,
    String? staffId,
    String? contactId,
    String? taskCommentId,
    String? dateAdded,
  }) {
    _id = id;
    _relId = relId;
    _relType = relType;
    _fileName = fileName;
    _fileType = fileType;
    _visibleToCustomer = visibleToCustomer;
    _attachmentKey = attachmentKey;
    _external = external;
    _externalLink = externalLink;
    _thumbnailLink = thumbnailLink;
    _staffId = staffId;
    _contactId = contactId;
    _taskCommentId = taskCommentId;
    _dateAdded = dateAdded;
  }

  Attachments.fromJson(dynamic json) {
    _id = json['id'];
    _relId = json['rel_id'];
    _relType = json['rel_type'];
    _fileName = json['file_name'];
    _fileType = json['filetype'];
    _visibleToCustomer = json['visible_to_customer'];
    _attachmentKey = json['attachment_key'];
    _external = json['external'];
    _externalLink = json['external_link'];
    _thumbnailLink = json['thumbnail_link'];
    _staffId = json['staffid'];
    _contactId = json['contact_id'];
    _taskCommentId = json['task_comment_id'];
    _dateAdded = json['dateadded'];
  }

  String? _id;
  String? _relId;
  String? _relType;
  String? _fileName;
  String? _fileType;
  String? _visibleToCustomer;
  String? _attachmentKey;
  String? _external;
  String? _externalLink;
  String? _thumbnailLink;
  String? _staffId;
  String? _contactId;
  String? _taskCommentId;
  String? _dateAdded;

  String? get id => _id;
  String? get relId => _relId;
  String? get relType => _relType;
  String? get fileName => _fileName;
  String? get fileType => _fileType;
  String? get visibleToCustomer => _visibleToCustomer;
  String? get attachmentKey => _attachmentKey;
  String? get external => _external;
  String? get externalLink => _externalLink;
  String? get thumbnailLink => _thumbnailLink;
  String? get staffId => _staffId;
  String? get contactId => _contactId;
  String? get taskCommentId => _taskCommentId;
  String? get dateAdded => _dateAdded;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = _id;
    map['rel_id'] = _relId;
    map['rel_type'] = _relType;
    map['file_name'] = _fileName;
    map['filetype'] = _fileType;
    map['visible_to_customer'] = _visibleToCustomer;
    map['attachment_key'] = _attachmentKey;
    map['external'] = _external;
    map['external_link'] = _externalLink;
    map['thumbnail_link'] = _thumbnailLink;
    map['staffid'] = _staffId;
    map['contact_id'] = _contactId;
    map['task_comment_id'] = _taskCommentId;
    map['dateadded'] = _dateAdded;
    return map;
  }
}
