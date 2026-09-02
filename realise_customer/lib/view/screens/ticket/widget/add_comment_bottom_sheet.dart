import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/local_strings.dart';
import 'package:realise/data/controller/ticket/ticket_controller.dart';
import 'package:realise/view/components/bottom-sheet/bottom_sheet_header_row.dart';
import 'package:realise/view/components/buttons/rounded_button.dart';
import 'package:realise/view/components/buttons/rounded_loading_button.dart';
import 'package:realise/view/components/text-form-field/custom_text_field.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';

class AddReplyBottomSheet extends StatefulWidget {
  final String ticketId;
  const AddReplyBottomSheet({super.key, required this.ticketId});

  @override
  State<AddReplyBottomSheet> createState() => _AddReplyBottomSheetState();
}

class _AddReplyBottomSheetState extends State<AddReplyBottomSheet> {
  XFile? imageFile;

  @override
  void dispose() {
    Get.find<TicketController>().clearData();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<TicketController>(builder: (controller) {
      return Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          BottomSheetHeaderRow(
            header: LocalStrings.ticketComment.tr,
            bottomSpace: 0,
          ),
          const SizedBox(height: Dimensions.space20),
          CustomTextField(
            animatedLabel: true,
            needOutlineBorder: true,
            labelText: LocalStrings.ticketMessage.tr,
            controller: controller.messageController,
            focusNode: controller.messageFocusNode,
            textInputType: TextInputType.multiline,
            maxLines: 3,
            validator: (value) {
              if (value!.isEmpty) {
                return LocalStrings.enterTicketReply.tr;
              } else {
                return null;
              }
            },
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
                    controller.addTicketReply(widget.ticketId, context);
                  },
                ),
          const SizedBox(height: Dimensions.space25),
        ],
      );
    });
  }
}
