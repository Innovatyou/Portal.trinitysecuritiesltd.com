import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/local_strings.dart';
import 'package:realise/data/controller/contract/contract_controller.dart';
import 'package:realise/data/repo/contract/contract_repo.dart';
import 'package:realise/data/services/api_service.dart';
import 'package:realise/view/components/app-bar/custom_appbar.dart';
import 'package:realise/view/components/custom_loader/custom_loader.dart';
import 'package:realise/view/components/no_data.dart';
import 'package:realise/view/screens/contract/widget/contract_card.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

class ContractsScreen extends StatefulWidget {
  const ContractsScreen({super.key});

  @override
  State<ContractsScreen> createState() => _ContractsScreenState();
}

class _ContractsScreenState extends State<ContractsScreen> {
  @override
  void initState() {
    Get.put(ApiClient(sharedPreferences: Get.find()));
    Get.put(ContractRepo(apiClient: Get.find()));
    final controller = Get.put(ContractController(contractRepo: Get.find()));
    controller.isLoading = true;
    super.initState();

    WidgetsBinding.instance.addPostFrameCallback((timeStamp) {
      controller.initialData();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppBar(
        title: LocalStrings.contracts.tr,
      ),
      body: GetBuilder<ContractController>(
        builder: (controller) {
          return controller.isLoading
              ? const CustomLoader()
              : RefreshIndicator(
                  onRefresh: () async {
                    await controller.initialData(shouldLoad: false);
                  },
                  color: Theme.of(context).primaryColor,
                  child: controller.contractsModel.success!
                      ? Padding(
                          padding: const EdgeInsets.all(Dimensions.space12),
                          child: ListView.separated(
                              itemBuilder: (context, index) {
                                return ContractCard(
                                    currency: controller.currency ?? '',
                                    contractModel:
                                        controller.contractsModel.data![index]);
                              },
                              separatorBuilder: (context, index) =>
                                  const SizedBox(
                                    height: Dimensions.space10,
                                  ),
                              itemCount:
                                  controller.contractsModel.data!.length),
                        )
                      : const NoDataWidget(),
                );
        },
      ),
    );
  }
}
