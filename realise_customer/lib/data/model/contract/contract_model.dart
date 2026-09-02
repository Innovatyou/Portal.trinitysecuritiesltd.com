class ContractsModel {
  ContractsModel({
    bool? success,
    String? message,
    List<Contract>? data,
  }) {
    _success = success;
    _message = message;
    _data = data;
  }

  ContractsModel.fromJson(dynamic json) {
    _success = json['success'];
    _message = json['message'];
    if (json['data'] != null) {
      _data = [];
      json['data'].forEach((v) {
        _data?.add(Contract.fromJson(v));
      });
    }
  }
  bool? _success;
  String? _message;
  List<Contract>? _data;

  bool? get success => _success;
  String? get message => _message;
  List<Contract>? get data => _data;

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

class Contract {
  Contract({
    String? id,
    String? title,
    String? clientId,
    String? projectId,
    String? contractDate,
    String? validUntil,
    String? note,
    String? lastEmailSentDate,
    String? status,
    String? taxId,
    String? taxId2,
    String? discountType,
    String? discountAmount,
    String? discountAmountType,
    String? content,
    String? publicKey,
    String? acceptedBy,
    String? staffSignedBy,
    String? metaData,
    String? files,
    String? companyId,
    String? deleted,
    String? currency,
    String? currencySymbol,
    String? companyName,
    String? isLead,
    String? projectTitle,
    String? signerName,
    String? signerEmail,
    String? staffSignerName,
    String? contractValue,
    String? taxPercentage,
    String? taxPercentage2,
  }) {
    _id = id;
    _title = title;
    _clientId = clientId;
    _projectId = projectId;
    _contractDate = contractDate;
    _validUntil = validUntil;
    _note = note;
    _lastEmailSentDate = lastEmailSentDate;
    _status = status;
    _taxId = taxId;
    _taxId2 = taxId2;
    _discountType = discountType;
    _discountAmount = discountAmount;
    _discountAmountType = discountAmountType;
    _content = content;
    _publicKey = publicKey;
    _acceptedBy = acceptedBy;
    _staffSignedBy = staffSignedBy;
    _metaData = metaData;
    _files = files;
    _companyId = companyId;
    _deleted = deleted;
    _currency = currency;
    _currencySymbol = currencySymbol;
    _companyName = companyName;
    _isLead = isLead;
    _projectTitle = projectTitle;
    _signerName = signerName;
    _signerEmail = signerEmail;
    _staffSignerName = staffSignerName;
    _contractValue = contractValue;
    _taxPercentage = taxPercentage;
    _taxPercentage2 = taxPercentage2;
  }

  Contract.fromJson(dynamic json) {
    _id = json['id'];
    _title = json['title'];
    _clientId = json['client_id'];
    _projectId = json['project_id'];
    _contractDate = json['contract_date'];
    _validUntil = json['valid_until'];
    _note = json['note'];
    _lastEmailSentDate = json['last_email_sent_date'];
    _status = json['status'];
    _taxId = json['tax_id'];
    _taxId2 = json['tax_id2'];
    _discountType = json['discount_type'];
    _discountAmount = json['discount_amount'];
    _discountAmountType = json['discount_amount_type'];
    _content = json['content'];
    _publicKey = json['public_key'];
    _acceptedBy = json['accepted_by'];
    _staffSignedBy = json['staff_signed_by'];
    _metaData = json['meta_data'];
    _files = json['files'];
    _companyId = json['company_id'];
    _deleted = json['deleted'];
    _currency = json['currency'];
    _currencySymbol = json['currency_symbol'];
    _companyName = json['company_name'];
    _isLead = json['is_lead'];
    _projectTitle = json['project_title'];
    _signerName = json['signer_name'];
    _signerEmail = json['signer_email'];
    _staffSignerName = json['staff_signer_name'];
    _contractValue = json['contract_value'];
    _taxPercentage = json['tax_percentage'];
    _taxPercentage2 = json['tax_percentage2'];
  }

  String? _id;
  String? _title;
  String? _clientId;
  String? _projectId;
  String? _contractDate;
  String? _validUntil;
  String? _note;
  String? _lastEmailSentDate;
  String? _status;
  String? _taxId;
  String? _taxId2;
  String? _discountType;
  String? _discountAmount;
  String? _discountAmountType;
  String? _content;
  String? _publicKey;
  String? _acceptedBy;
  String? _staffSignedBy;
  String? _metaData;
  String? _files;
  String? _companyId;
  String? _deleted;
  String? _currency;
  String? _currencySymbol;
  String? _companyName;
  String? _isLead;
  String? _projectTitle;
  String? _signerName;
  String? _signerEmail;
  String? _staffSignerName;
  String? _contractValue;
  String? _taxPercentage;
  String? _taxPercentage2;

  String? get id => _id;
  String? get title => _title;
  String? get clientId => _clientId;
  String? get projectId => _projectId;
  String? get contractDate => _contractDate;
  String? get validUntil => _validUntil;
  String? get note => _note;
  String? get lastEmailSentDate => _lastEmailSentDate;
  String? get status => _status;
  String? get taxId => _taxId;
  String? get taxId2 => _taxId2;
  String? get discountType => _discountType;
  String? get discountAmount => _discountAmount;
  String? get discountAmountType => _discountAmountType;
  String? get content => _content;
  String? get publicKey => _publicKey;
  String? get acceptedBy => _acceptedBy;
  String? get staffSignedBy => _staffSignedBy;
  String? get metaData => _metaData;
  String? get files => _files;
  String? get companyId => _companyId;
  String? get deleted => _deleted;
  String? get currency => _currency;
  String? get currencySymbol => _currencySymbol;
  String? get companyName => _companyName;
  String? get isLead => _isLead;
  String? get projectTitle => _projectTitle;
  String? get signerName => _signerName;
  String? get signerEmail => _signerEmail;
  String? get staffSignerName => _staffSignerName;
  String? get contractValue => _contractValue;
  String? get taxPercentage => _taxPercentage;
  String? get taxPercentage2 => _taxPercentage2;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = _id;
    map['title'] = _title;
    map['client_id'] = _clientId;
    map['project_id'] = _projectId;
    map['contract_date'] = _contractDate;
    map['valid_until'] = _validUntil;
    map['note'] = _note;
    map['last_email_sent_date'] = _lastEmailSentDate;
    map['status'] = _status;
    map['tax_id'] = _taxId;
    map['tax_id2'] = _taxId2;
    map['discount_type'] = _discountType;
    map['discount_amount'] = _discountAmount;
    map['discount_amount_type'] = _discountAmountType;
    map['content'] = _content;
    map['public_key'] = _publicKey;
    map['accepted_by'] = _acceptedBy;
    map['staff_signed_by'] = _staffSignedBy;
    map['meta_data'] = _metaData;
    map['files'] = _files;
    map['company_id'] = _companyId;
    map['deleted'] = _deleted;
    map['currency'] = _currency;
    map['currency_symbol'] = _currencySymbol;
    map['company_name'] = _companyName;
    map['is_lead'] = _isLead;
    map['project_title'] = _projectTitle;
    map['signer_name'] = _signerName;
    map['signer_email'] = _signerEmail;
    map['staff_signer_name'] = _staffSignerName;
    map['contract_value'] = _contractValue;
    map['tax_percentage'] = _taxPercentage;
    map['tax_percentage2'] = _taxPercentage2;
    return map;
  }
}
