import 'dart:async';
import 'dart:convert';
import 'package:realise/core/helper/shared_preference_helper.dart';
import 'package:realise/data/model/estimate/estimate_details_model.dart';
import 'package:realise/data/model/estimate/estimate_model.dart';
import 'package:realise/data/repo/estimate/estimate_repo.dart';
import 'package:get/get.dart';
import 'package:realise/data/model/global/response_model/response_model.dart';
import 'package:realise/view/components/snack_bar/show_custom_snackbar.dart';

class EstimateController extends GetxController {
  EstimateRepo estimateRepo;
  EstimateController({required this.estimateRepo});

  bool isLoading = true;
  EstimatesModel estimatesModel = EstimatesModel();
  EstimateDetailsModel estimateDetailsModel = EstimateDetailsModel();
  int subtotal = 0;
  String? currency;

  Future<void> initialData({bool shouldLoad = true}) async {
    isLoading = shouldLoad ? true : false;
    update();

    await loadEstimates();
    currency = estimateRepo.apiClient.sharedPreferences
        .getString(SharedPreferenceHelper.currencySymbol);
    isLoading = false;
    update();
  }

  Future<void> loadEstimates() async {
    ResponseModel responseModel = await estimateRepo.getAllEstimates();
    estimatesModel =
        EstimatesModel.fromJson(jsonDecode(responseModel.responseJson));
    isLoading = false;
    update();
  }

  Future<void> loadEstimateDetails(estimateId) async {
    ResponseModel responseModel =
        await estimateRepo.getEstimateDetails(estimateId);
    currency = estimateRepo.apiClient.sharedPreferences
        .getString(SharedPreferenceHelper.currencySymbol);
    if (responseModel.statusCode == 200) {
      estimateDetailsModel =
          EstimateDetailsModel.fromJson(jsonDecode(responseModel.responseJson));
      estimateDetailsModel.data!.items?.forEach((item) {
        subtotal = subtotal + int.parse(item.total!);
      });
    } else {
      CustomSnackBar.error(errorList: [responseModel.message]);
    }

    isLoading = false;
    update();
  }
}
