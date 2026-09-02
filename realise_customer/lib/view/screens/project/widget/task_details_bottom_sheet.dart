import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/local_strings.dart';
import 'package:realise/core/utils/style.dart';
import 'package:realise/data/model/project/tasks_model.dart';
import 'package:realise/view/components/bottom-sheet/bottom_sheet_header_row.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:realise/view/components/card/custom_card.dart';
import 'package:realise/view/components/divider/custom_divider.dart';

class TaskDetailsBottomSheet extends StatelessWidget {
  final Task taskModel;
  const TaskDetailsBottomSheet({super.key, required this.taskModel});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        BottomSheetHeaderRow(
          header: LocalStrings.taskDetails.tr,
          bottomSpace: 10,
        ),
        CustomCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(LocalStrings.taskTitle.tr, style: lightSmall),
                  Text(LocalStrings.status.tr, style: lightSmall),
                ],
              ),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(taskModel.title ?? '', style: regularDefault),
                  Text(taskModel.statusTitle?.capitalize?.tr ?? '',
                      style: regularDefault.copyWith(
                          color: ColorResources.taskStatusColor(
                              taskModel.statusId!))),
                ],
              ),
              const CustomDivider(space: Dimensions.space10),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(LocalStrings.description.tr, style: lightSmall),
                  Text(taskModel.description ?? '-',
                      maxLines: 3, style: regularDefault),
                ],
              ),
              const CustomDivider(space: Dimensions.space10),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(LocalStrings.startDate.tr, style: lightSmall),
                  Text(LocalStrings.deadline.tr, style: lightSmall),
                ],
              ),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(taskModel.startDate ?? '-', style: regularDefault),
                  Text(taskModel.deadline ?? '-', style: regularDefault),
                ],
              ),
            ],
          ),
        ),
      ],
    );
  }
}
