class PaymentsModel {
  PaymentsModel({
    bool? success,
    String? message,
    List<Payment>? data,
  }) {
    _success = success;
    _message = message;
    _data = data;
  }

  PaymentsModel.fromJson(dynamic json) {
    _success = json['success'];
    _message = json['message'];
    if (json['data'] != null) {
      _data = [];
      json['data'].forEach((v) {
        _data?.add(Payment.fromJson(v));
      });
    }
  }
  bool? _success;
  String? _message;
  List<Payment>? _data;

  bool? get success => _success;
  String? get message => _message;
  List<Payment>? get data => _data;

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
