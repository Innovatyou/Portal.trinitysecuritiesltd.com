class InvoicesModel {
  InvoicesModel({
    bool? success,
    String? message,
    List<Invoice>? data,
  }) {
    _success = success;
    _message = message;
    _data = data;
  }

  InvoicesModel.fromJson(dynamic json) {
    _success = json['success'];
    _message = json['message'];
    if (json['data'] != null) {
      _data = [];
      json['data'].forEach((v) {
        _data?.add(Invoice.fromJson(v));
      });
    }
  }
  bool? _success;
  String? _message;
  List<Invoice>? _data;

  bool? get success => _success;
  String? get message => _message;
  List<Invoice>? get data => _data;

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
    return map;
  }
}
