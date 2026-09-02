import 'package:get/get.dart';
import 'package:realise/data/model/global/response_model/response_model.dart';
import 'package:realise/data/repo/privacy/privacy_repo.dart';
import 'package:realise/view/components/snack_bar/show_custom_snackbar.dart';

class PrivacyController extends GetxController {
  PrivacyRepo privacyRepo;
  bool isLoading = true;
  //KnowledgeBaseDetailsModel privacyModel = KnowledgeBaseDetailsModel();
  PrivacyController({required this.privacyRepo});

  Future<void> initialData({bool shouldLoad = true}) async {
    isLoading = shouldLoad ? true : false;
    update();

    await loadData();
    isLoading = false;
    update();
  }

  Future<void> loadData() async {
    ResponseModel responseModel = await privacyRepo.loadPrivacyData();
    if (responseModel.statusCode == 200) {
      //privacyModel = KnowledgeBaseDetailsModel.fromJson(
      //    jsonDecode(responseModel.responseJson));
    } else {
      CustomSnackBar.error(errorList: [responseModel.message]);
    }

    isLoading = false;
    update();
  }
}
