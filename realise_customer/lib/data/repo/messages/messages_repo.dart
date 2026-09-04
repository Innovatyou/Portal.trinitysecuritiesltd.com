import 'dart:io';

import 'package:http/http.dart' as http;
import 'package:realise/core/utils/method.dart';
import 'package:realise/core/utils/url_container.dart';
import 'package:realise/data/model/global/response_model/response_model.dart';
import 'package:realise/data/services/api_service.dart';

class MessagesRepo {
  final ApiClient api;
  MessagesRepo({required this.api});
  String get base => '${UrlContainer.baseUrl}messages';

  Future<ResponseModel> conversations() =>
      api.request('$base/conversations', Method.getMethod, null, passHeader: true);

  Future<ResponseModel> contacts() =>
      api.request('$base/contacts', Method.getMethod, null, passHeader: true);

  Future<ResponseModel> thread(int otherUserId) =>
      api.request('$base/thread/$otherUserId', Method.getMethod, null, passHeader: true);

  /// Always goes through multipart so one code path handles both a
  /// text-only message and one with a file attached.
  Future<ResponseModel> send(int toUserId, String message, {File? file}) async {
    api.initToken();
    final request = http.MultipartRequest('POST', Uri.parse('$base/send'))
      ..headers.addAll({
        'Authorization': '${api.tokenType} ${api.token}',
        'X-Authorization': api.token,
      })
      ..fields.addAll({
        'to_user_id': '$toUserId',
        'message': message,
      });
    if (file != null) {
      request.files.add(await http.MultipartFile.fromPath('file', file.path));
    }
    try {
      final streamed = await request.send().timeout(const Duration(seconds: 60));
      final response = await http.Response.fromStream(streamed);
      if (response.statusCode == 200) {
        return ResponseModel(true, 'Success', 200, response.body);
      }
      return ResponseModel(false, 'Send failed', response.statusCode, response.body);
    } catch (e) {
      return ResponseModel(false, 'Send failed: $e', 499, '');
    }
  }

  String attachmentUrl(String fileName) => '${UrlContainer.attachmentUrl}$fileName';
}
