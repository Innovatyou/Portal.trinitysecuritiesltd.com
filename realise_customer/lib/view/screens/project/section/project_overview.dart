import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/local_strings.dart';
import 'package:realise/core/utils/style.dart';
import 'package:realise/data/model/project/project_details_model.dart';
import 'package:realise/view/components/card/custom_card.dart';
import 'package:realise/view/components/divider/custom_divider.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

class ProjectOverview extends StatelessWidget {
  const ProjectOverview({
    super.key,
    required this.project,
    required this.currency,
  });
  final Project project;
  final String currency;

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
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
                  project.title ?? '',
                  style: mediumLarge,
                ),
                Text(
                  project.status?.tr.capitalize ?? '',
                  style: TextStyle(
                      color:
                          ColorResources.projectStatusColor(project.status!)),
                )
              ],
            ),
            const SizedBox(height: Dimensions.space10),
            CustomCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(LocalStrings.companyName.tr, style: lightSmall),
                      Text(LocalStrings.price.tr, style: lightSmall),
                    ],
                  ),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(project.companyName ?? '', style: regularDefault),
                      Text('$currency${project.price ?? '-'}',
                          style: regularDefault),
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
                      Text(project.startDate ?? '', style: regularDefault),
                      Text(project.deadline ?? '', style: regularDefault),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: Dimensions.space10),
            CustomCard(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.start,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    LocalStrings.description.tr,
                    style: Theme.of(context).textTheme.bodyLarge,
                  ),
                  const Divider(
                    color: ColorResources.blueGreyColor,
                    thickness: 0.50,
                  ),
                  Text(
                    project.description ?? '-',
                    style: lightSmall,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
