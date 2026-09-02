import 'package:realise/core/utils/method.dart';
import 'package:realise/core/utils/url_container.dart';
import 'package:realise/data/model/global/response_model/response_model.dart';
import 'package:realise/data/services/api_service.dart';

class ContractRepo {
  ApiClient apiClient;
  ContractRepo({required this.apiClient});

  Future<ResponseModel> getAllContracts() async {
    String url = "${UrlContainer.baseUrl}${UrlContainer.contractsUrl}";
    ResponseModel responseModel =
        await apiClient.request(url, Method.getMethod, null, passHeader: true);
    return responseModel;
  }

  Future<ResponseModel> getContractDetails(contractId) async {
    String url =
        "${UrlContainer.baseUrl}${UrlContainer.contractsUrl}/$contractId";
    ResponseModel responseModel =
        await apiClient.request(url, Method.getMethod, null, passHeader: true);
    return responseModel;
  }

  Future<ResponseModel> postContractComment(contractId, String comment) async {
    String url =
        "${UrlContainer.baseUrl}${UrlContainer.contractsUrl}/id/$contractId/group/contract_comment";

    Map<String, String> params = {"content": comment};

    ResponseModel responseModel = await apiClient
        .request(url, Method.postMethod, params, passHeader: true);
    return responseModel;
  }
}
