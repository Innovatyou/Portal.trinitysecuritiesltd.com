import 'dart:io';

import 'package:http/http.dart' as http;
import 'package:realise/core/utils/method.dart';
import 'package:realise/core/utils/url_container.dart';
import 'package:realise/data/model/global/response_model/response_model.dart';
import 'package:realise/data/services/api_service.dart';

class ProfileRepo {
  ApiClient apiClient;
  ProfileRepo({required this.apiClient});

  Future<ResponseModel> getProfileData() async {
    String url = "${UrlContainer.baseUrl}${UrlContainer.profileUrl}";
    ResponseModel responseModel =
        await apiClient.request(url, Method.getMethod, null, passHeader: true);
    return responseModel;
  }

  // customersapi/profile/avatar is served by Operations_api (the
  // staff-aware plugin controller), not the client-only RestApiController
  // customersapi/profile hits - same reasoning as operations-login using a
  // different controller than the client login. Multipart upload here
  // mirrors OperationsRepo.uploadAttachment's already-working pattern.
  Future<ResponseModel> uploadAvatar(File file) async {
    apiClient.initToken();
    final request = http.MultipartRequest(
        'POST', Uri.parse('${UrlContainer.baseUrl}profile/avatar'))
      ..headers.addAll({
        'Authorization': '${apiClient.tokenType} ${apiClient.token}',
        'X-Authorization': apiClient.token,
      })
      ..files.add(await http.MultipartFile.fromPath('avatar', file.path));
    try {
      final streamed =
          await request.send().timeout(const Duration(seconds: 60));
      final response = await http.Response.fromStream(streamed);
      if (response.statusCode == 200) {
        return ResponseModel(true, 'Success', 200, response.body);
      }
      return ResponseModel(
          false, 'Upload failed', response.statusCode, response.body);
    } catch (e) {
      return ResponseModel(false, 'Upload failed: $e', 499, '');
    }
  }
}
