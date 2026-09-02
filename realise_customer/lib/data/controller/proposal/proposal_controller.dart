import 'dart:async';
import 'dart:convert';
import 'package:realise/core/helper/shared_preference_helper.dart';
import 'package:realise/data/model/proposal/proposal_details_model.dart';
import 'package:realise/data/model/proposal/proposal_model.dart';
import 'package:realise/data/repo/proposal/proposal_repo.dart';
import 'package:get/get.dart';
import 'package:realise/data/model/global/response_model/response_model.dart';
import 'package:realise/view/components/snack_bar/show_custom_snackbar.dart';

class ProposalController extends GetxController {
  ProposalRepo proposalRepo;
  ProposalController({required this.proposalRepo});

  bool isLoading = true;
  ProposalsModel proposalsModel = ProposalsModel();
  ProposalDetailsModel proposalDetailsModel = ProposalDetailsModel();
  int subtotal = 0;
  String? currency;

  Future<void> initialData({bool shouldLoad = true}) async {
    isLoading = shouldLoad ? true : false;
    update();

    await loadProposals();
    currency = proposalRepo.apiClient.sharedPreferences
        .getString(SharedPreferenceHelper.currencySymbol);
    isLoading = false;
    update();
  }

  Future<void> loadProposals() async {
    ResponseModel responseModel = await proposalRepo.getAllProposals();
    proposalsModel =
        ProposalsModel.fromJson(jsonDecode(responseModel.responseJson));
    isLoading = false;
    update();
  }

  Future<void> loadProposalDetails(proposalId) async {
    ResponseModel responseModel =
        await proposalRepo.getProposalDetails(proposalId);
    currency = proposalRepo.apiClient.sharedPreferences
        .getString(SharedPreferenceHelper.currencySymbol);
    if (responseModel.statusCode == 200) {
      proposalDetailsModel =
          ProposalDetailsModel.fromJson(jsonDecode(responseModel.responseJson));

      proposalDetailsModel.data!.items?.forEach((item) {
        subtotal = subtotal + int.parse(item.total!);
      });
    } else {
      CustomSnackBar.error(errorList: [responseModel.message]);
    }

    isLoading = false;
    update();
  }
}
