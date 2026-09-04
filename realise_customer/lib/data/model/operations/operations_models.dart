class OperationsRequest {
  final int id;
  final String number, title, workflow, status, priority, stage;
  final String? dueAt, submittedAt;
  final int lockVersion;
  OperationsRequest.fromJson(Map<String,dynamic> j):id=int.tryParse('${j['id']}')??0,number='${j['request_no']??'DRAFT'}',title='${j['title']??''}',workflow='${j['workflow_name']??''}',status='${j['status']??''}',priority='${j['priority']??'normal'}',stage='${j['current_stage']??''}',dueAt=j['due_at']?.toString(),submittedAt=j['submitted_at']?.toString(),lockVersion=int.tryParse('${j['lock_version']??j['stage_lock_version']??0}')??0;
}

class OperationsWorkflow {
  final int id;
  final String name, description;
  final List<Map<String,dynamic>> fields;
  OperationsWorkflow.fromJson(Map<String,dynamic> j):id=int.tryParse('${j['id']}')??0,name='${j['name']??''}',description='${j['description']??''}',fields=List<Map<String,dynamic>>.from(j['fields']??const[]);
}

class OperationsDetail {
  final Map<String,dynamic> raw;
  OperationsDetail(this.raw);
  OperationsRequest get request=>OperationsRequest.fromJson(raw);
  bool get canDecide=>raw['can_decide']==true;
  bool get canResubmit=>raw['can_resubmit']==true;
  bool get canCancel=>raw['can_cancel']==true;
  bool get canDelete=>raw['can_delete']==true;
  bool get canRespondInformation=>raw['can_respond_information']==true;
  int? get openConversationId=>int.tryParse('${raw['open_conversation_id']}');
  Map<String,dynamic>? get assignment=>raw['active_assignment'] is Map?Map<String,dynamic>.from(raw['active_assignment']):null;
  List<Map<String,dynamic>> get values=>List<Map<String,dynamic>>.from(raw['values']??const[]);
  List<Map<String,dynamic>> get timeline=>List<Map<String,dynamic>>.from(raw['timeline']??const[]);
  List<Map<String,dynamic>> get comments=>List<Map<String,dynamic>>.from(raw['comments']??const[]);
  List<Map<String,dynamic>> get conversations=>List<Map<String,dynamic>>.from(raw['conversations']??const[]);
  List<Map<String,dynamic>> get attachments=>List<Map<String,dynamic>>.from(raw['attachments']??const[]);
  /// Only present (and only useful) when canResubmit is true - the
  /// workflow's field definitions, each carrying editable_on_return.
  List<Map<String,dynamic>> get fields=>List<Map<String,dynamic>>.from(raw['fields']??const[]);
  /// The still-open conversation this requester needs to answer, if any.
  Map<String,dynamic>? get openConversation{
    if(openConversationId==null) return null;
    for(final c in conversations){if(int.tryParse('${c['id']}')==openConversationId) return c;}
    return null;
  }
}
