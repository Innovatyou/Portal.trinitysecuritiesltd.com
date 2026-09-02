import 'dart:convert';
import 'package:realise/core/utils/method.dart';
import 'package:realise/core/utils/url_container.dart';
import 'package:realise/data/model/auth/sign_up_model/registration_response_model.dart';
import 'package:realise/data/model/auth/sign_up_model/sign_up_model.dart';
import 'package:realise/data/services/api_service.dart';

class RegistrationRepo {
  ApiClient apiClient;

  RegistrationRepo({required this.apiClient});

  Future<RegistrationResponseModel> registerUser(SignUpModel model) async {
    final map = modelToMap(model);
    String url = '${UrlContainer.baseUrl}${UrlContainer.registrationUrl}';
    final res = await apiClient.request(url, Method.postMethod, map,
        passHeader: true, isOnlyAcceptType: true);
    final json = jsonDecode(res.responseJson);
    RegistrationResponseModel responseModel =
        RegistrationResponseModel.fromJson(json);
    return responseModel;
  }

  Map<String, dynamic> modelToMap(SignUpModel model) {
    Map<String, dynamic> bodyFields = {
      'company_name': model.company,
      'first_name': model.firstName,
      'last_name': model.lastName,
      'account_type': model.accountType,
      'email': model.email,
      'phone': model.mobile,
      'password': model.password,
    };

    return bodyFields;
  }
}
