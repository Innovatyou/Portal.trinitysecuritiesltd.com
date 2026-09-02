import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/data/controller/project/project_controller.dart';
import 'package:realise/data/repo/project/project_repo.dart';
import 'package:realise/data/services/api_service.dart';
import 'package:realise/view/components/custom_loader/custom_loader.dart';
import 'package:realise/view/components/no_data.dart';
import 'package:realise/view/screens/project/widget/task_card.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

class ProjectTasks extends StatefulWidget {
  const ProjectTasks({super.key, required this.id});
  final String id;

  @override
  State<ProjectTasks> createState() => _ProjectTasksState();
}

class _ProjectTasksState extends State<ProjectTasks> {
  @override
  void initState() {
    Get.put(ApiClient(sharedPreferences: Get.find()));
    Get.put(ProjectRepo(apiClient: Get.find()));
    final controller = Get.put(ProjectController(projectRepo: Get.find()));
    controller.isLoading = true;
    super.initState();

    WidgetsBinding.instance.addPostFrameCallback((timeStamp) {
      controller.loadProjectTasks(widget.id);
    });
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<ProjectController>(
      builder: (controller) {
        return Scaffold(
          body: controller.isLoading
              ? const CustomLoader()
              : controller.tasksModel.success!
                  ? RefreshIndicator(
                      color: ColorResources.primaryColor,
                      onRefresh: () async {
                        await controller.loadProjectTasks(widget.id);
                      },
                      child: Padding(
                        padding: const EdgeInsets.all(Dimensions.space10),
                        child: ListView.separated(
                            itemBuilder: (context, index) {
                              return TaskCard(
                                  taskModel:
                                      controller.tasksModel.data![index]);
                            },
                            separatorBuilder: (context, index) =>
                                const SizedBox(height: Dimensions.space5),
                            itemCount: controller.tasksModel.data!.length),
                      ),
                    )
                  : const Center(child: NoDataWidget()),
        );
      },
    );
  }
}
