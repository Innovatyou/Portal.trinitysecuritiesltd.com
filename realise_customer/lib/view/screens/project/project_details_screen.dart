import 'package:contained_tab_bar_view/contained_tab_bar_view.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/local_strings.dart';
import 'package:realise/core/utils/style.dart';
import 'package:realise/data/controller/project/project_controller.dart';
import 'package:realise/data/repo/project/project_repo.dart';
import 'package:realise/data/services/api_service.dart';
import 'package:realise/view/components/app-bar/custom_appbar.dart';
import 'package:realise/view/components/custom_loader/custom_loader.dart';
import 'package:realise/view/screens/project/section/project_comments.dart';
import 'package:realise/view/screens/project/section/project_invoices.dart';
import 'package:realise/view/screens/project/section/project_overview.dart';
import 'package:realise/view/screens/project/section/project_tasks.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

class ProjectDetailsScreen extends StatefulWidget {
  const ProjectDetailsScreen({super.key, required this.id});
  final String id;

  @override
  State<ProjectDetailsScreen> createState() => _ProjectDetailsScreenState();
}

class _ProjectDetailsScreenState extends State<ProjectDetailsScreen> {
  @override
  void initState() {
    Get.put(ApiClient(sharedPreferences: Get.find()));
    Get.put(ProjectRepo(apiClient: Get.find()));
    final controller = Get.put(ProjectController(projectRepo: Get.find()));
    controller.isLoading = true;
    super.initState();

    WidgetsBinding.instance.addPostFrameCallback((timeStamp) {
      controller.loadProjectDetails(widget.id);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppBar(
        title: LocalStrings.projectDetails.tr,
      ),
      body: GetBuilder<ProjectController>(
        builder: (controller) {
          return controller.isLoading
              ? const CustomLoader()
              : ContainedTabBarView(
                  tabBarProperties: TabBarProperties(
                      isScrollable: false,
                      indicatorSize: TabBarIndicatorSize.tab,
                      unselectedLabelColor: ColorResources.blueGreyColor,
                      labelColor: Theme.of(context).textTheme.bodyLarge!.color,
                      labelStyle: regularDefault,
                      indicatorColor: ColorResources.secondaryColor,
                      labelPadding: const EdgeInsets.symmetric(
                          vertical: Dimensions.space15)),
                  tabs: [
                    if (controller.viewOverview)
                      Text(
                        LocalStrings.overview.tr,
                        style: regularDefault,
                      ),
                    if (controller.viewTasks)
                      Text(
                        LocalStrings.tasks.tr,
                        style: regularDefault,
                      ),
                    Text(
                      LocalStrings.comments.tr,
                      style: regularDefault,
                    ),
                    Text(
                      LocalStrings.invoices.tr,
                      style: regularDefault,
                    ),
                  ],
                  views: [
                    if (controller.viewOverview)
                      RefreshIndicator(
                          onRefresh: () async {
                            await controller.loadProjectDetails(widget.id);
                          },
                          child: ProjectOverview(
                              currency: controller.currency ?? '',
                              project: controller.projectDetailsModel.data!)),
                    if (controller.viewTasks) ProjectTasks(id: widget.id),
                    ProjectComments(
                      comments: controller.projectDetailsModel.data?.comments,
                    ),
                    ProjectInvoices(id: widget.id),
                  ],
                );
        },
      ),
    );
  }
}
