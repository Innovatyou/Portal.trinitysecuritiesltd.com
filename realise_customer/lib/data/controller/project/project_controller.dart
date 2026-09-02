import 'dart:async';
import 'dart:convert';
import 'package:realise/core/helper/shared_preference_helper.dart';
import 'package:realise/data/model/invoice/invoice_model.dart';
import 'package:realise/data/model/project/project_details_model.dart';
import 'package:realise/data/model/project/project_model.dart';
import 'package:realise/data/model/project/tasks_model.dart';
import 'package:realise/data/repo/project/project_repo.dart';
import 'package:get/get.dart';
import 'package:realise/data/model/global/response_model/response_model.dart';
import 'package:realise/view/components/snack_bar/show_custom_snackbar.dart';

class ProjectController extends GetxController {
  ProjectRepo projectRepo;
  ProjectController({required this.projectRepo});

  bool isLoading = true;
  ProjectsModel projectsModel = ProjectsModel();
  ProjectDetailsModel projectDetailsModel = ProjectDetailsModel();
  TasksModel tasksModel = TasksModel();
  InvoicesModel invoicesModel = InvoicesModel();
  String? currency;
  bool viewTasks = false;
  bool viewOverview = false;

  Future<void> initialData({bool shouldLoad = true}) async {
    isLoading = shouldLoad ? true : false;
    update();

    await loadProjects();
    currency = projectRepo.apiClient.sharedPreferences
        .getString(SharedPreferenceHelper.currencySymbol);
    isLoading = false;
    update();
  }

  Future<void> loadProjects() async {
    ResponseModel responseModel = await projectRepo.getAllProjects();
    projectsModel =
        ProjectsModel.fromJson(jsonDecode(responseModel.responseJson));

    isLoading = false;
    update();
  }

  Future<void> loadProjectDetails(projectId) async {
    ResponseModel responseModel =
        await projectRepo.getProjectDetails(projectId);
    currency = projectRepo.apiClient.sharedPreferences
        .getString(SharedPreferenceHelper.currencySymbol);
    if (responseModel.statusCode == 200) {
      projectDetailsModel =
          ProjectDetailsModel.fromJson(jsonDecode(responseModel.responseJson));
      viewTasks = projectRepo.apiClient.sharedPreferences
              .getString(SharedPreferenceHelper.viewTasks) ==
          '1';
      viewOverview = projectRepo.apiClient.sharedPreferences
              .getString(SharedPreferenceHelper.viewOverview) ==
          '1';
    } else {
      CustomSnackBar.error(errorList: [responseModel.message]);
    }

    isLoading = false;
    update();
  }

  Future<void> loadProjectTasks(projectId) async {
    ResponseModel responseModel = await projectRepo.getProjectTasks(projectId);
    tasksModel = TasksModel.fromJson(jsonDecode(responseModel.responseJson));
    isLoading = false;
    update();
  }

  Future<void> loadProjectInvoices(projectId) async {
    ResponseModel responseModel =
        await projectRepo.getProjectInvoices(projectId);
    invoicesModel =
        InvoicesModel.fromJson(jsonDecode(responseModel.responseJson));
    isLoading = false;
    update();
  }
}
