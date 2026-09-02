import 'package:realise/core/utils/method.dart';
import 'package:realise/core/utils/url_container.dart';
import 'package:realise/data/model/global/response_model/response_model.dart';
import 'package:realise/data/services/api_service.dart';

class SplashRepo {
  ApiClient apiClient;
  SplashRepo({required this.apiClient});

  Future<ResponseModel> getOverviewData() async {
    String url = "${UrlContainer.baseUrl}${UrlContainer.overviewUrl}";
    ResponseModel responseModel =
        await apiClient.request(url, Method.getMethod, null, passHeader: true);
    return responseModel;
  }
}
