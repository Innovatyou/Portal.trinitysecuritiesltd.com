import 'dart:async';
import 'dart:convert';
import 'package:realise/core/helper/shared_preference_helper.dart';
import 'package:realise/data/model/contract/contract_details_model.dart';
import 'package:realise/data/model/contract/contract_model.dart';
import 'package:realise/data/repo/contract/contract_repo.dart';
import 'package:get/get.dart';
import 'package:realise/data/model/global/response_model/response_model.dart';
import 'package:realise/view/components/snack_bar/show_custom_snackbar.dart';

class ContractController extends GetxController {
  ContractRepo contractRepo;
  ContractController({required this.contractRepo});

  bool isLoading = true;
  ContractsModel contractsModel = ContractsModel();
  ContractDetailsModel contractDetailsModel = ContractDetailsModel();
  String? currency;

  Future<void> initialData({bool shouldLoad = true}) async {
    isLoading = shouldLoad ? true : false;
    update();

    await loadContracts();
    currency = contractRepo.apiClient.sharedPreferences
        .getString(SharedPreferenceHelper.currencySymbol);
    isLoading = false;
    update();
  }

  Future<void> loadContracts() async {
    ResponseModel responseModel = await contractRepo.getAllContracts();
    contractsModel =
        ContractsModel.fromJson(jsonDecode(responseModel.responseJson));
    isLoading = false;
    update();
  }

  Future<void> loadContractDetails(contractId) async {
    ResponseModel responseModel =
        await contractRepo.getContractDetails(contractId);
    currency = contractRepo.apiClient.sharedPreferences
        .getString(SharedPreferenceHelper.currencySymbol);
    if (responseModel.statusCode == 200) {
      contractDetailsModel =
          ContractDetailsModel.fromJson(jsonDecode(responseModel.responseJson));
    } else {
      CustomSnackBar.error(errorList: [responseModel.message]);
    }

    isLoading = false;
    update();
  }
}
