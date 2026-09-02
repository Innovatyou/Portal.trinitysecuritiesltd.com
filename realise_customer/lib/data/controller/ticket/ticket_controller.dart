import 'dart:async';
import 'dart:convert';
import 'package:realise/core/utils/local_strings.dart';
import 'package:realise/data/model/authorization/authorization_response_model.dart';
import 'package:realise/data/model/ticket/ticket_types_model.dart';
import 'package:realise/data/model/ticket/ticket_create_model.dart';
import 'package:realise/data/model/ticket/ticket_details_model.dart';
import 'package:realise/data/model/ticket/ticket_model.dart';
import 'package:realise/data/repo/ticket/ticket_repo.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:realise/data/model/global/response_model/response_model.dart';
import 'package:realise/view/components/snack_bar/show_custom_snackbar.dart';

class TicketController extends GetxController {
  TicketRepo ticketRepo;
  TicketController({required this.ticketRepo});

  bool isLoading = true;
  bool isSubmitLoading = false;
  TicketsModel ticketsModel = TicketsModel();
  TicketTypesModel ticketTypesModel = TicketTypesModel();
  TicketDetailsModel ticketDetailsModel = TicketDetailsModel();

  Future<void> initialData({bool shouldLoad = true}) async {
    isLoading = shouldLoad ? true : false;
    update();

    await loadTickets();
    isLoading = false;
    update();
  }

  Future<void> loadTickets() async {
    ResponseModel responseModel = await ticketRepo.getAllTickets();
    ticketsModel =
        TicketsModel.fromJson(jsonDecode(responseModel.responseJson));
    isLoading = false;
    update();
  }

  TextEditingController titleController = TextEditingController();
  TextEditingController ticketTypeController = TextEditingController();
  TextEditingController descriptionController = TextEditingController();

  FocusNode titleFocusNode = FocusNode();
  FocusNode ticketTypeFocusNode = FocusNode();
  FocusNode descriptionFocusNode = FocusNode();

  Future<void> submitTicket(BuildContext context) async {
    String title = titleController.text.toString();
    String ticketTypeId = ticketTypeController.text.toString();
    String description = descriptionController.text.toString();

    if (title.isEmpty) {
      CustomSnackBar.error(errorList: [LocalStrings.enterTicketSubject]);
      return;
    }
    if (ticketTypeId.isEmpty) {
      CustomSnackBar.error(errorList: [LocalStrings.enterTicketDescription]);
      return;
    }
    if (description.isEmpty) {
      CustomSnackBar.error(errorList: [LocalStrings.enterTicketDescription]);
      return;
    }

    isSubmitLoading = true;
    update();

    TicketCreateModel ticketModel = TicketCreateModel(
        title: title, description: description, ticketTypeId: ticketTypeId);

    ResponseModel responseModel = await ticketRepo.createTicket(ticketModel);

    if (responseModel.statusCode == 200) {
      AuthorizationResponseModel model = AuthorizationResponseModel.fromJson(
          jsonDecode(responseModel.responseJson));
      if (model.success!) {
        if (context.mounted) Navigator.pop(context);
        await initialData();
        CustomSnackBar.success(successList: [model.message!]);
      } else {
        CustomSnackBar.error(errorList: [model.message!]);
      }
    } else {
      CustomSnackBar.error(errorList: [responseModel.message]);
      return;
    }

    isSubmitLoading = false;
    update();
  }

  Future<void> loadTicketCreateData() async {
    ResponseModel ticketTypesResponseModel = await ticketRepo.getTicketTypes();
    ticketTypesModel = TicketTypesModel.fromJson(
        jsonDecode(ticketTypesResponseModel.responseJson));
    isLoading = false;
    update();
  }

  Future<void> loadTicketDetails(ticketId) async {
    ResponseModel responseModel = await ticketRepo.getTicketDetails(ticketId);
    if (responseModel.statusCode == 200) {
      ticketDetailsModel =
          TicketDetailsModel.fromJson(jsonDecode(responseModel.responseJson));
    } else {
      CustomSnackBar.error(errorList: [responseModel.message]);
    }

    isLoading = false;
    update();
  }

  TextEditingController messageController = TextEditingController();
  FocusNode messageFocusNode = FocusNode();

  Future<void> addTicketReply(ticketId, BuildContext context) async {
    String message = messageController.text.toString();

    if (message.isEmpty) {
      CustomSnackBar.error(errorList: [LocalStrings.enterTicketReply]);
      return;
    }

    isSubmitLoading = true;
    update();

    ResponseModel responseModel =
        await ticketRepo.postTicketReply(ticketId, message);
    if (responseModel.statusCode == 200) {
      AuthorizationResponseModel model = AuthorizationResponseModel.fromJson(
          jsonDecode(responseModel.responseJson));
      if (model.success!) {
        if (context.mounted) Navigator.pop(context);
        await loadTicketDetails(ticketId);
        CustomSnackBar.success(successList: [model.message!]);
      } else {
        CustomSnackBar.error(errorList: [model.message!]);
      }
    } else {
      CustomSnackBar.error(errorList: [responseModel.message]);
      return;
    }

    isSubmitLoading = false;
    update();
  }

  void clearData() {
    isLoading = false;
    isSubmitLoading = false;
    titleController.text = '';
    ticketTypeController.text = '';
    descriptionController.text = '';
    messageController.text = '';
  }
}
