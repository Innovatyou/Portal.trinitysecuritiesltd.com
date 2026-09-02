import 'dart:io';

import 'package:http/http.dart' as http;
import 'package:realise/core/utils/method.dart';
import 'package:realise/core/utils/url_container.dart';
import 'package:realise/data/model/global/response_model/response_model.dart';
import 'package:realise/data/services/api_service.dart';

class OperationsRepo {
 final ApiClient api; OperationsRepo({required this.api});
 String get base=>'${UrlContainer.baseUrl}${UrlContainer.operationsUrl}';

 /// Attachment download links need the token as a query param (no custom
 /// headers when opened directly, e.g. in an external viewer) - the API
 /// already supports that (Operations_api::auth() falls back to
 /// ?access_token=).
 String downloadUrl(int attachmentId) {
   api.initToken();
   return '${UrlContainer.baseUrl}operations/attachments/$attachmentId/download?access_token=${api.token}';
 }

 Future<ResponseModel> uploadAttachment(int id, File file) async {
   api.initToken();
   final request = http.MultipartRequest(
       'POST', Uri.parse('$base/requests/$id/attachments'))
     ..headers.addAll({
       'Authorization': '${api.tokenType} ${api.token}',
       'X-Authorization': api.token,
     })
     ..files.add(await http.MultipartFile.fromPath('file', file.path));
   try {
     final streamed = await request.send().timeout(const Duration(seconds: 60));
     final response = await http.Response.fromStream(streamed);
     if (response.statusCode == 200) {
       return ResponseModel(true, 'Success', 200, response.body);
     }
     return ResponseModel(false, 'Upload failed', response.statusCode, response.body);
   } catch (e) {
     return ResponseModel(false, 'Upload failed: $e', 499, '');
   }
 }
 Future<ResponseModel> dashboard()=>api.request(base,Method.getMethod,null,passHeader:true);
 Future<ResponseModel> requests()=>api.request('$base/requests',Method.getMethod,null,passHeader:true);
 Future<ResponseModel> pending()=>api.request('$base/pending',Method.getMethod,null,passHeader:true);
 Future<ResponseModel> workflows()=>api.request('$base/workflows',Method.getMethod,null,passHeader:true);
 Future<ResponseModel> detail(int id)=>api.request('$base/requests/$id',Method.getMethod,null,passHeader:true);
 Future<ResponseModel> create(Map<String,dynamic> data)=>api.request('$base/requests',Method.postMethod,data,passHeader:true);
 Future<ResponseModel> decide(int id,Map<String,dynamic> data)=>api.request('$base/requests/$id/decision',Method.postMethod,data,passHeader:true);
 Future<ResponseModel> comment(int id,String value)=>api.request('$base/requests/$id/comment',Method.postMethod,{'comment':value},passHeader:true);
 Future<ResponseModel> requestInformation(int id,String question)=>api.request('$base/requests/$id/information',Method.postMethod,{'action':'request','question':question},passHeader:true);
 Future<ResponseModel> respondInformation(int id,int conversationId,String response)=>api.request('$base/requests/$id/information',Method.postMethod,{'action':'respond','conversation_id':'$conversationId','response':response},passHeader:true);
 Future<ResponseModel> resubmit(int id,String comment,Map<String,String> values)=>api.request('$base/requests/$id/resubmit',Method.postMethod,{'resubmission_comment':comment,...values.map((k,v)=>MapEntry('field_$k',v))},passHeader:true);
 Future<ResponseModel> cancel(int id,String reason)=>api.request('$base/requests/$id/cancel',Method.postMethod,{'reason':reason},passHeader:true);
}
