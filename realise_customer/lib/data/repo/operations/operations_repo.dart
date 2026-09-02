import 'package:realise/core/utils/method.dart';
import 'package:realise/core/utils/url_container.dart';
import 'package:realise/data/model/global/response_model/response_model.dart';
import 'package:realise/data/services/api_service.dart';

class OperationsRepo {
 final ApiClient api; OperationsRepo({required this.api});
 String get base=>'${UrlContainer.baseUrl}${UrlContainer.operationsUrl}';
 Future<ResponseModel> dashboard()=>api.request(base,Method.getMethod,null,passHeader:true);
 Future<ResponseModel> requests()=>api.request('$base/requests',Method.getMethod,null,passHeader:true);
 Future<ResponseModel> pending()=>api.request('$base/pending',Method.getMethod,null,passHeader:true);
 Future<ResponseModel> workflows()=>api.request('$base/workflows',Method.getMethod,null,passHeader:true);
 Future<ResponseModel> detail(int id)=>api.request('$base/requests/$id',Method.getMethod,null,passHeader:true);
 Future<ResponseModel> create(Map<String,dynamic> data)=>api.request('$base/requests',Method.postMethod,data,passHeader:true);
 Future<ResponseModel> decide(int id,Map<String,dynamic> data)=>api.request('$base/requests/$id/decision',Method.postMethod,data,passHeader:true);
 Future<ResponseModel> comment(int id,String value)=>api.request('$base/requests/$id/comment',Method.postMethod,{'comment':value},passHeader:true);
}
