import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/style.dart';
import 'package:realise/data/model/project/tasks_model.dart';
import 'package:realise/view/components/bottom-sheet/custom_bottom_sheet.dart';
import 'package:realise/view/components/divider/custom_divider.dart';
import 'package:realise/view/screens/project/widget/task_details_bottom_sheet.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:nb_utils/nb_utils.dart';

class TaskCard extends StatelessWidget {
  const TaskCard({
    super.key,
    required this.taskModel,
  });
  final Task taskModel;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () {
        CustomBottomSheet(
            child: TaskDetailsBottomSheet(
          taskModel: taskModel,
        )).customBottomSheet(context);
      },
      child: Card(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(Dimensions.cardRadius),
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(Dimensions.cardRadius),
          child: Container(
            decoration: BoxDecoration(
              color: Theme.of(context).cardColor,
              border: Border(
                left: BorderSide(
                  width: 5.0,
                  color: ColorResources.taskStatusColor(taskModel.statusId!),
                ),
              ),
            ),
            child: Padding(
                padding: const EdgeInsets.all(15),
                child: Column(
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        TextIcon(
                          text: taskModel.title,
                          prefix: taskModel.parentTaskId == '0'
                              ? const SizedBox.shrink()
                              : const Icon(
                                  Icons.subdirectory_arrow_right_outlined,
                                  color: ColorResources.blueGreyColor,
                                  size: Dimensions.space15,
                                ),
                          edgeInsets: EdgeInsets.zero,
                          textStyle: regularLarge,
                        ),
                        Container(
                            padding: const EdgeInsets.all(Dimensions.space5),
                            decoration: ShapeDecoration(
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(
                                    Dimensions.cardRadius),
                                side: BorderSide(
                                    color: ColorResources.taskStatusColor(
                                        taskModel.statusId!)),
                              ),
                            ),
                            child: Text(
                              taskModel.statusTitle?.capitalize?.tr ?? '',
                              style: lightSmall.copyWith(
                                  color: ColorResources.taskStatusColor(
                                      taskModel.statusId!)),
                            ))
                      ],
                    ),
                    const SizedBox(height: Dimensions.space5),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          taskModel.description ?? '',
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                              fontSize: Dimensions.fontSmall,
                              color: ColorResources.blueGreyColor),
                        ),
                        Text(
                          taskModel.priorityTitle ?? '',
                          style: regularSmall.copyWith(
                              color: ColorResources.taskPriorityColor(
                                  taskModel.priorityId!)),
                        ),
                      ],
                    ),
                    const CustomDivider(space: Dimensions.space10),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        TextIcon(
                          text: taskModel.assignedToUser,
                          prefix: const Icon(
                            Icons.assignment_ind_outlined,
                            color: ColorResources.blueGreyColor,
                            size: Dimensions.space15,
                          ),
                          edgeInsets: EdgeInsets.zero,
                          textStyle: regularSmall.copyWith(
                              color: ColorResources.blueGreyColor),
                        ),
                        TextIcon(
                          text: taskModel.createdDate,
                          prefix: const Icon(
                            Icons.calendar_month,
                            color: ColorResources.blueGreyColor,
                            size: Dimensions.space15,
                          ),
                          edgeInsets: EdgeInsets.zero,
                          textStyle: regularSmall.copyWith(
                              color: ColorResources.blueGreyColor),
                        ),
                      ],
                    )
                  ],
                )),
          ),
        ),
      ),
    );
  }
}
