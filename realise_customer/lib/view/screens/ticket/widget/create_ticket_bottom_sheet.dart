import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/local_strings.dart';
import 'package:realise/core/utils/style.dart';
import 'package:realise/data/controller/ticket/ticket_controller.dart';
import 'package:realise/data/model/ticket/ticket_types_model.dart';
import 'package:realise/data/repo/ticket/ticket_repo.dart';
import 'package:realise/data/services/api_service.dart';
import 'package:realise/view/components/bottom-sheet/bottom_sheet_header_row.dart';
import 'package:realise/view/components/buttons/rounded_button.dart';
import 'package:realise/view/components/buttons/rounded_loading_button.dart';
import 'package:realise/view/components/custom_loader/custom_loader.dart';
import 'package:realise/view/components/text-form-field/custom_drop_down_text_field.dart';
import 'package:realise/view/components/text-form-field/custom_text_field.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';

class CreateTicketBottomSheet extends StatefulWidget {
  const CreateTicketBottomSheet({super.key});

  @override
  State<CreateTicketBottomSheet> createState() =>
      _CreateTicketBottomSheetState();
}

class _CreateTicketBottomSheetState extends State<CreateTicketBottomSheet> {
  XFile? imageFile;

  @override
  void initState() {
    Get.put(ApiClient(sharedPreferences: Get.find()));
    Get.put(TicketRepo(apiClient: Get.find()));
    final controller = Get.put(TicketController(ticketRepo: Get.find()));
    controller.isLoading = true;
    super.initState();

    WidgetsBinding.instance.addPostFrameCallback((timeStamp) {
      controller.loadTicketCreateData();
    });
  }

  @override
  void dispose() {
    Get.find<TicketController>().clearData();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<TicketController>(builder: (controller) {
      return controller.isLoading
          ? const CustomLoader()
          : Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                BottomSheetHeaderRow(
                  header: LocalStrings.createNewTicket.tr,
                  bottomSpace: 0,
                ),
                const SizedBox(height: Dimensions.space20),
                CustomTextField(
                  animatedLabel: true,
                  needOutlineBorder: true,
                  labelText: LocalStrings.title.tr,
                  controller: controller.titleController,
                  focusNode: controller.titleFocusNode,
                  textInputType: TextInputType.text,
                  nextFocus: controller.ticketTypeFocusNode,
                  validator: (value) {
                    if (value!.isEmpty) {
                      return LocalStrings.enterTicketSubject.tr;
                    } else {
                      return null;
                    }
                  },
                  onChanged: (value) {
                    return;
                  },
                ),
                const SizedBox(height: Dimensions.space15),
                CustomDropDownTextField(
                  onChanged: (value) {
                    controller.ticketTypeController.text = value;
                  },
                  dropDownColor: Theme.of(context).cardColor,
                  hintText: LocalStrings.selectTicketType.tr,
                  items:
                      controller.ticketTypesModel.data!.map((TicketType value) {
                    return DropdownMenuItem(
                      value: value.id,
                      child: Text(
                        value.title?.tr ?? '',
                        style: regularDefault.copyWith(
                            color:
                                Theme.of(context).textTheme.bodyMedium!.color),
                      ),
                    );
                  }).toList(),
                ),
                const SizedBox(height: Dimensions.space15),
                CustomTextField(
                  animatedLabel: true,
                  needOutlineBorder: true,
                  labelText: LocalStrings.description.tr,
                  textInputType: TextInputType.multiline,
                  maxLines: 3,
                  focusNode: controller.descriptionFocusNode,
                  controller: controller.descriptionController,
                  onChanged: (value) {
                    return;
                  },
                ),
                const SizedBox(height: Dimensions.space25),
                controller.isSubmitLoading
                    ? const RoundedLoadingBtn()
                    : RoundedButton(
                        text: LocalStrings.submit.tr,
                        press: () {
                          controller.submitTicket(context);
                        },
                      ),
              ],
            );
    });
  }
}
