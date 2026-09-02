import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/local_strings.dart';
import 'package:realise/data/controller/proposal/proposal_controller.dart';
import 'package:realise/data/repo/proposal/proposal_repo.dart';
import 'package:realise/data/services/api_service.dart';
import 'package:realise/view/components/app-bar/custom_appbar.dart';
import 'package:realise/view/components/custom_loader/custom_loader.dart';
import 'package:realise/view/components/no_data.dart';
import 'package:realise/view/screens/proposal/widget/proposal_card.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

class ProposalScreen extends StatefulWidget {
  const ProposalScreen({super.key});

  @override
  State<ProposalScreen> createState() => _ProposalScreenState();
}

class _ProposalScreenState extends State<ProposalScreen> {
  @override
  void initState() {
    Get.put(ApiClient(sharedPreferences: Get.find()));
    Get.put(ProposalRepo(apiClient: Get.find()));
    final controller = Get.put(ProposalController(proposalRepo: Get.find()));
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
        title: LocalStrings.proposals.tr,
      ),
      body: GetBuilder<ProposalController>(
        builder: (controller) {
          return controller.isLoading
              ? const CustomLoader()
              : RefreshIndicator(
                  onRefresh: () async {
                    await controller.initialData(shouldLoad: false);
                  },
                  color: Theme.of(context).primaryColor,
                  child: controller.proposalsModel.success!
                      ? Padding(
                          padding: const EdgeInsets.all(Dimensions.space15),
                          child: ListView.separated(
                              itemBuilder: (context, index) {
                                return ProposalCard(
                                  currency: controller.currency ?? '',
                                  proposalModel:
                                      controller.proposalsModel.data![index],
                                );
                              },
                              separatorBuilder: (context, index) =>
                                  const SizedBox(height: Dimensions.space10),
                              itemCount:
                                  controller.proposalsModel.data!.length),
                        )
                      : const NoDataWidget(),
                );
        },
      ),
    );
  }
}
