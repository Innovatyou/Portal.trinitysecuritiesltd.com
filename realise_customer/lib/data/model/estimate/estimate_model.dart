class EstimatesModel {
  EstimatesModel({
    bool? success,
    String? message,
    List<Estimate>? data,
  }) {
    _success = success;
    _message = message;
    _data = data;
  }

  EstimatesModel.fromJson(dynamic json) {
    _success = json['success'];
    _message = json['message'];
    if (json['data'] != null) {
      _data = [];
      json['data'].forEach((v) {
        _data?.add(Estimate.fromJson(v));
      });
    }
  }
  bool? _success;
  String? _message;
  List<Estimate>? _data;

  bool? get success => _success;
  String? get message => _message;
  List<Estimate>? get data => _data;

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

class Estimate {
  Estimate({
    String? id,
    String? clientId,
    String? estimateRequestId,
    String? estimateDate,
    String? validUntil,
    String? note,
    String? lastEmailSentDate,
    String? status,
    String? taxId,
    String? taxId2,
    String? discountType,
    String? discountAmount,
    String? discountAmountType,
    String? projectId,
    String? acceptedBy,
    String? metaData,
    String? createdBy,
    String? signature,
    String? publicKey,
    String? companyId,
    String? deleted,
    String? currency,
    String? currencySymbol,
    String? companyName,
    String? projectTitle,
    String? isLead,
    String? signerName,
    String? signerEmail,
    String? estimateValue,
    String? taxPercentage,
    String? taxPercentage2,
  }) {
    _id = id;
    _clientId = clientId;
    _estimateRequestId = estimateRequestId;
    _estimateDate = estimateDate;
    _validUntil = validUntil;
    _note = note;
    _lastEmailSentDate = lastEmailSentDate;
    _status = status;
    _taxId = taxId;
    _taxId2 = taxId2;
    _discountType = discountType;
    _discountAmount = discountAmount;
    _discountAmountType = discountAmountType;
    _projectId = projectId;
    _acceptedBy = acceptedBy;
    _metaData = metaData;
    _createdBy = createdBy;
    _signature = signature;
    _publicKey = publicKey;
    _companyId = companyId;
    _deleted = deleted;
    _currency = currency;
    _currencySymbol = currencySymbol;
    _companyName = companyName;
    _projectTitle = projectTitle;
    _isLead = isLead;
    _signerName = signerName;
    _signerEmail = signerEmail;
    _estimateValue = estimateValue;
    _taxPercentage = taxPercentage;
    _taxPercentage2 = taxPercentage2;
  }

  Estimate.fromJson(dynamic json) {
    _id = json['id'];
    _clientId = json['client_id'];
    _estimateRequestId = json['estimate_request_id'];
    _estimateDate = json['estimate_date'];
    _validUntil = json['valid_until'];
    _note = json['note'];
    _lastEmailSentDate = json['last_email_sent_date'];
    _status = json['status'];
    _taxId = json['tax_id'];
    _taxId2 = json['tax_id2'];
    _discountType = json['discount_type'];
    _discountAmount = json['discount_amount'];
    _discountAmountType = json['discount_amount_type'];
    _projectId = json['project_id'];
    _acceptedBy = json['accepted_by'];
    _metaData = json['meta_data'];
    _createdBy = json['created_by'];
    _signature = json['signature'];
    _publicKey = json['public_key'];
    _companyId = json['company_id'];
    _deleted = json['deleted'];
    _currency = json['currency'];
    _currencySymbol = json['currency_symbol'];
    _companyName = json['company_name'];
    _projectTitle = json['project_title'];
    _isLead = json['is_lead'];
    _signerName = json['signer_name'];
    _signerEmail = json['signer_email'];
    _estimateValue = json['estimate_value'];
    _taxPercentage = json['tax_percentage'];
    _taxPercentage2 = json['tax_percentage2'];
  }

  String? _id;
  String? _clientId;
  String? _estimateRequestId;
  String? _estimateDate;
  String? _validUntil;
  String? _note;
  String? _lastEmailSentDate;
  String? _status;
  String? _taxId;
  String? _taxId2;
  String? _discountType;
  String? _discountAmount;
  String? _discountAmountType;
  String? _projectId;
  String? _acceptedBy;
  String? _metaData;
  String? _createdBy;
  String? _signature;
  String? _publicKey;
  String? _companyId;
  String? _deleted;
  String? _currency;
  String? _currencySymbol;
  String? _companyName;
  String? _projectTitle;
  String? _isLead;
  String? _signerName;
  String? _signerEmail;
  String? _estimateValue;
  String? _taxPercentage;
  String? _taxPercentage2;

  String? get id => _id;
  String? get clientId => _clientId;
  String? get estimateRequestId => _estimateRequestId;
  String? get estimateDate => _estimateDate;
  String? get validUntil => _validUntil;
  String? get note => _note;
  String? get lastEmailSentDate => _lastEmailSentDate;
  String? get status => _status;
  String? get taxId => _taxId;
  String? get taxId2 => _taxId2;
  String? get discountType => _discountType;
  String? get discountAmount => _discountAmount;
  String? get discountAmountType => _discountAmountType;
  String? get projectId => _projectId;
  String? get acceptedBy => _acceptedBy;
  String? get metaData => _metaData;
  String? get createdBy => _createdBy;
  String? get signature => _signature;
  String? get publicKey => _publicKey;
  String? get companyId => _companyId;
  String? get deleted => _deleted;
  String? get currency => _currency;
  String? get currencySymbol => _currencySymbol;
  String? get companyName => _companyName;
  String? get projectTitle => _projectTitle;
  String? get isLead => _isLead;
  String? get signerName => _signerName;
  String? get signerEmail => _signerEmail;
  String? get estimateValue => _estimateValue;
  String? get taxPercentage => _taxPercentage;
  String? get taxPercentage2 => _taxPercentage2;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = _id;
    map['client_id'] = _clientId;
    map['estimate_request_id'] = _estimateRequestId;
    map['estimate_date'] = _estimateDate;
    map['valid_until'] = _validUntil;
    map['note'] = _note;
    map['last_email_sent_date'] = _lastEmailSentDate;
    map['status'] = _status;
    map['tax_id'] = _taxId;
    map['tax_id2'] = _taxId2;
    map['discount_type'] = _discountType;
    map['discount_amount'] = _discountAmount;
    map['discount_amount_type'] = _discountAmountType;
    map['project_id'] = _projectId;
    map['accepted_by'] = _acceptedBy;
    map['meta_data'] = _metaData;
    map['created_by'] = _createdBy;
    map['signature'] = _signature;
    map['public_key'] = _publicKey;
    map['company_id'] = _companyId;
    map['deleted'] = _deleted;
    map['currency'] = _currency;
    map['currency_symbol'] = _currencySymbol;
    map['company_name'] = _companyName;
    map['project_title'] = _projectTitle;
    map['is_lead'] = _isLead;
    map['signer_name'] = _signerName;
    map['signer_email'] = _signerEmail;
    map['estimate_value'] = _estimateValue;
    map['tax_percentage'] = _taxPercentage;
    map['tax_percentage2'] = _taxPercentage2;
    return map;
  }
}
