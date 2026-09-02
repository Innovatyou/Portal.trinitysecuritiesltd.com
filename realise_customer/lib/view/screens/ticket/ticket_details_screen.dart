import 'package:realise/core/helper/date_converter.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/local_strings.dart';
import 'package:realise/core/utils/style.dart';
import 'package:realise/data/controller/ticket/ticket_controller.dart';
import 'package:realise/data/repo/ticket/ticket_repo.dart';
import 'package:realise/data/services/api_service.dart';
import 'package:realise/view/components/app-bar/custom_appbar.dart';
import 'package:realise/view/components/bottom-sheet/custom_bottom_sheet.dart';
import 'package:realise/view/components/card/custom_card.dart';
import 'package:realise/view/components/custom_fab.dart';
import 'package:realise/view/components/custom_loader/custom_loader.dart';
import 'package:realise/view/components/divider/custom_divider.dart';
import 'package:realise/view/components/no_data.dart';
import 'package:realise/view/screens/ticket/widget/add_comment_bottom_sheet.dart';
import 'package:realise/view/screens/ticket/widget/ticket_comment.dart';
import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart';
import 'package:get/get.dart';

class TicketDetailsScreen extends StatefulWidget {
  const TicketDetailsScreen({super.key, required this.id});
  final String id;

  @override
  State<TicketDetailsScreen> createState() => _TicketDetailsScreenState();
}

class _TicketDetailsScreenState extends State<TicketDetailsScreen> {
  @override
  void initState() {
    Get.put(ApiClient(sharedPreferences: Get.find()));
    Get.put(TicketRepo(apiClient: Get.find()));
    final controller = Get.put(TicketController(ticketRepo: Get.find()));
    controller.isLoading = true;
    super.initState();
    handleScroll();

    WidgetsBinding.instance.addPostFrameCallback((timeStamp) {
      controller.loadTicketDetails(widget.id);
    });
  }

  bool showFab = true;
  ScrollController scrollController = ScrollController();

  @override
  void dispose() {
    scrollController.removeListener(() {});
    super.dispose();
  }

  void handleScroll() async {
    scrollController.addListener(() {
      if (scrollController.position.userScrollDirection ==
          ScrollDirection.reverse) {
        if (showFab) setState(() => showFab = false);
      }
      if (scrollController.position.userScrollDirection ==
          ScrollDirection.forward) {
        if (!showFab) setState(() => showFab = true);
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppBar(
        title: LocalStrings.ticketDetails.tr,
      ),
      floatingActionButton: AnimatedSlide(
        offset: showFab ? Offset.zero : const Offset(0, 2),
        duration: const Duration(milliseconds: 300),
        child: AnimatedOpacity(
          opacity: showFab ? 1 : 0,
          duration: const Duration(milliseconds: 300),
          child: CustomFAB(
            icon: Icons.reply,
            isShowText: true,
            text: LocalStrings.reply.tr,
            press: () {
              CustomBottomSheet(child: AddReplyBottomSheet(ticketId: widget.id))
                  .customBottomSheet(context);
            },
          ),
        ),
      ),
      body: GetBuilder<TicketController>(
        builder: (controller) {
          return controller.isLoading
              ? const CustomLoader()
              : RefreshIndicator(
                  color: ColorResources.primaryColor,
                  onRefresh: () async {
                    await controller.loadTicketDetails(widget.id);
                  },
                  child: SingleChildScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    child: Padding(
                      padding: const EdgeInsets.all(Dimensions.space15),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                controller.ticketDetailsModel.data!.title ?? '',
                                style: mediumLarge,
                              ),
                              Text(
                                controller.ticketDetailsModel.data!.status?.tr
                                        .capitalize
                                        ?.replaceAll("_", " ") ??
                                    '',
                                style: TextStyle(
                                    color: ColorResources.ticketStatusColor(
                                        controller
                                            .ticketDetailsModel.data!.status!)),
                              )
                            ],
                          ),
                          const SizedBox(height: Dimensions.space10),
                          CustomCard(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(LocalStrings.companyName.tr,
                                        style: lightSmall),
                                    Text(LocalStrings.project.tr,
                                        style: lightSmall),
                                  ],
                                ),
                                Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(
                                        controller.ticketDetailsModel.data!
                                                .companyName ??
                                            '',
                                        style: regularDefault),
                                    Text(
                                        controller.ticketDetailsModel.data!
                                                .projectTitle ??
                                            '-',
                                        style: regularDefault),
                                  ],
                                ),
                                const CustomDivider(space: Dimensions.space10),
                                Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(LocalStrings.openedBy.tr,
                                        style: lightSmall),
                                    Text(LocalStrings.assignedTo.tr,
                                        style: lightSmall),
                                  ],
                                ),
                                Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(
                                        controller.ticketDetailsModel.data!
                                                .requestedByName ??
                                            '',
                                        style: regularDefault),
                                    Text(
                                        controller.ticketDetailsModel.data!
                                                .assignedToUser ??
                                            '-',
                                        style: regularDefault),
                                  ],
                                ),
                                const CustomDivider(space: Dimensions.space10),
                                Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(LocalStrings.ticketType.tr,
                                        style: lightSmall),
                                    Text(LocalStrings.date.tr,
                                        style: lightSmall),
                                  ],
                                ),
                                Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(
                                        controller.ticketDetailsModel.data!
                                                .ticketType ??
                                            '',
                                        style: regularDefault),
                                    Text(
                                        DateConverter.formatValidityDate(
                                            controller.ticketDetailsModel.data!
                                                    .createdAt ??
                                                ''),
                                        style: regularDefault),
                                  ],
                                ),
                              ],
                            ),
                          ),
                          Padding(
                            padding: const EdgeInsets.symmetric(
                                vertical: Dimensions.space10),
                            child: Text(
                              LocalStrings.ticketComments.tr,
                              style: mediumLarge,
                            ),
                          ),
                          controller.ticketDetailsModel.data!.comments
                                      ?.isNotEmpty ??
                                  false
                              ? ListView.separated(
                                  shrinkWrap: true,
                                  controller: scrollController,
                                  itemBuilder: (context, index) {
                                    return TicketComment(
                                      comment: controller.ticketDetailsModel
                                          .data!.comments![index],
                                    );
                                  },
                                  separatorBuilder: (context, index) =>
                                      const SizedBox(
                                          height: Dimensions.space10),
                                  itemCount: controller.ticketDetailsModel.data!
                                      .comments!.length)
                              : const NoDataWidget(),
                        ],
                      ),
                    ),
                  ),
                );
        },
      ),
    );
  }
}
