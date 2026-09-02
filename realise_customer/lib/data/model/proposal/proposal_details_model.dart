class ProposalDetailsModel {
  ProposalDetailsModel({
    bool? success,
    String? message,
    Proposal? data,
  }) {
    _success = success;
    _message = message;
    _data = data;
  }

  ProposalDetailsModel.fromJson(dynamic json) {
    _success = json['success'];
    _message = json['message'];
    _data = json['data'] != null ? Proposal.fromJson(json['data']) : null;
  }
  bool? _success;
  String? _message;
  Proposal? _data;

  bool? get success => _success;
  String? get message => _message;
  Proposal? get data => _data;

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

class Proposal {
  Proposal({
    String? id,
    String? clientId,
    String? proposalDate,
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
    String? createdBy,
    String? totalViews,
    String? lastPreviewSeen,
    String? metaData,
    String? companyId,
    String? projectId,
    String? deleted,
    String? currency,
    String? currencySymbol,
    String? companyName,
    String? isLead,
    String? signerName,
    String? signerEmail,
    String? proposalValue,
    String? taxPercentage,
    String? taxPercentage2,
    String? lastEmailReadTime,
    String? totalComments,
    String? projectTitle,
    List<Items>? items,
  }) {
    _id = id;
    _clientId = clientId;
    _proposalDate = proposalDate;
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
    _createdBy = createdBy;
    _totalViews = totalViews;
    _lastPreviewSeen = lastPreviewSeen;
    _metaData = metaData;
    _companyId = companyId;
    _projectId = projectId;
    _deleted = deleted;
    _currency = currency;
    _currencySymbol = currencySymbol;
    _companyName = companyName;
    _isLead = isLead;
    _signerName = signerName;
    _signerEmail = signerEmail;
    _proposalValue = proposalValue;
    _taxPercentage = taxPercentage;
    _taxPercentage2 = taxPercentage2;
    _lastEmailReadTime = lastEmailReadTime;
    _totalComments = totalComments;
    _projectTitle = projectTitle;
    _items = items;
  }

  Proposal.fromJson(dynamic json) {
    _id = json['id'];
    _clientId = json['client_id'];
    _proposalDate = json['proposal_date'];
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
    _createdBy = json['created_by'];
    _totalViews = json['total_views'];
    _lastPreviewSeen = json['last_preview_seen'];
    _metaData = json['meta_data'];
    _companyId = json['company_id'];
    _projectId = json['project_id'];
    _deleted = json['deleted'];
    _currency = json['currency'];
    _currencySymbol = json['currency_symbol'];
    _companyName = json['company_name'];
    _isLead = json['is_lead'];
    _signerName = json['signer_name'];
    _signerEmail = json['signer_email'];
    _proposalValue = json['proposal_value'];
    _taxPercentage = json['tax_percentage'];
    _taxPercentage2 = json['tax_percentage2'];
    _lastEmailReadTime = json['last_email_read_time'];
    _totalComments = json['total_comments'];
    _projectTitle = json['project_title'];
    if (json['items'] != null) {
      _items = [];
      json['items'].forEach((v) {
        _items?.add(Items.fromJson(v));
      });
    }
  }

  String? _id;
  String? _clientId;
  String? _proposalDate;
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
  String? _createdBy;
  String? _totalViews;
  String? _lastPreviewSeen;
  String? _metaData;
  String? _companyId;
  String? _projectId;
  String? _deleted;
  String? _currency;
  String? _currencySymbol;
  String? _companyName;
  String? _isLead;
  String? _signerName;
  String? _signerEmail;
  String? _proposalValue;
  String? _taxPercentage;
  String? _taxPercentage2;
  String? _lastEmailReadTime;
  String? _totalComments;
  String? _projectTitle;
  List<Items>? _items;

  String? get id => _id;
  String? get clientId => _clientId;
  String? get proposalDate => _proposalDate;
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
  String? get createdBy => _createdBy;
  String? get totalViews => _totalViews;
  String? get lastPreviewSeen => _lastPreviewSeen;
  String? get metaData => _metaData;
  String? get companyId => _companyId;
  String? get projectId => _projectId;
  String? get deleted => _deleted;
  String? get currency => _currency;
  String? get currencySymbol => _currencySymbol;
  String? get companyName => _companyName;
  String? get isLead => _isLead;
  String? get signerName => _signerName;
  String? get signerEmail => _signerEmail;
  String? get proposalValue => _proposalValue;
  String? get taxPercentage => _taxPercentage;
  String? get taxPercentage2 => _taxPercentage2;
  String? get lastEmailReadTime => _lastEmailReadTime;
  String? get totalComments => _totalComments;
  String? get projectTitle => _projectTitle;
  List<Items>? get items => _items;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = _id;
    map['client_id'] = _clientId;
    map['proposal_date'] = _proposalDate;
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
    map['created_by'] = _createdBy;
    map['total_views'] = _totalViews;
    map['last_preview_seen'] = _lastPreviewSeen;
    map['meta_data'] = _metaData;
    map['company_id'] = _companyId;
    map['project_id'] = _projectId;
    map['deleted'] = _deleted;
    map['currency'] = _currency;
    map['currency_symbol'] = _currencySymbol;
    map['company_name'] = _companyName;
    map['is_lead'] = _isLead;
    map['signer_name'] = _signerName;
    map['signer_email'] = _signerEmail;
    map['proposal_value'] = _proposalValue;
    map['tax_percentage'] = _taxPercentage;
    map['tax_percentage2'] = _taxPercentage2;
    map['last_email_read_time'] = _lastEmailReadTime;
    map['total_comments'] = _totalComments;
    map['project_title'] = _projectTitle;
    if (_items != null) {
      map['items'] = _items?.map((v) => v.toJson()).toList();
    }
    return map;
  }
}

class Items {
  Items({
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

  Items.fromJson(dynamic json) {
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
