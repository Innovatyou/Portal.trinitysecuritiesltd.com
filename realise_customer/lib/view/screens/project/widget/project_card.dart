import 'package:realise/core/route/route.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/style.dart';
import 'package:realise/data/model/project/project_model.dart';
import 'package:realise/view/components/divider/custom_divider.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:nb_utils/nb_utils.dart';

class ProjectCard extends StatelessWidget {
  const ProjectCard({
    super.key,
    required this.projectModel,
  });
  final Project projectModel;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () {
        Get.toNamed(RouteHelper.projectDetailsScreen,
            arguments: projectModel.id!);
      },
      child: Card(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(Dimensions.cardRadius),
        ),
        margin: EdgeInsets.zero,
        child: ClipRRect(
          borderRadius: BorderRadius.circular(Dimensions.cardRadius),
          child: Container(
            decoration: BoxDecoration(
              color: Theme.of(context).cardColor,
              border: Border(
                left: BorderSide(
                  width: 5.0,
                  color:
                      ColorResources.projectStatusColor(projectModel.statusId!),
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
                        Text(
                          projectModel.title!,
                          style: regularLarge,
                        ),
                        Container(
                            padding: const EdgeInsets.all(Dimensions.space5),
                            decoration: ShapeDecoration(
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(
                                    Dimensions.cardRadius),
                                side: BorderSide(
                                    color: ColorResources.projectStatusColor(
                                        projectModel.statusId!)),
                              ),
                            ),
                            child: Text(
                              projectModel.statusTitle!.tr,
                              style: lightSmall.copyWith(
                                  color: ColorResources.projectStatusColor(
                                      projectModel.statusId!)),
                            ))
                      ],
                    ),
                    const SizedBox(height: Dimensions.space5),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        SizedBox(
                          width: MediaQuery.sizeOf(context).width / 2,
                          child: Text(
                            projectModel.description ?? '',
                            overflow: TextOverflow.ellipsis,
                            style: regularSmall.copyWith(
                                color: ColorResources.blueGreyColor),
                          ),
                        ),
                      ],
                    ),
                    const CustomDivider(space: Dimensions.space10),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        TextIcon(
                          text: projectModel.companyName!,
                          prefix: const Icon(
                            Icons.account_box_rounded,
                            color: ColorResources.blueGreyColor,
                            size: 15,
                          ),
                          edgeInsets: EdgeInsets.zero,
                          textStyle: regularSmall.copyWith(
                              color: ColorResources.blueGreyColor),
                        ),
                        TextIcon(
                          text: projectModel.deadline,
                          prefix: const Icon(
                            Icons.calendar_month,
                            color: ColorResources.blueGreyColor,
                            size: 15,
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
