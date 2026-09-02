import 'package:cached_network_image/cached_network_image.dart';
import 'package:realise/core/helper/date_converter.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/images.dart';
import 'package:realise/core/utils/style.dart';
import 'package:realise/core/utils/url_container.dart';
import 'package:realise/data/model/project/project_details_model.dart';
import 'package:realise/view/components/card/custom_card.dart';
import 'package:realise/view/components/divider/custom_divider.dart';
import 'package:flutter/material.dart';
import 'package:nb_utils/nb_utils.dart';

class ProjectComment extends StatelessWidget {
  const ProjectComment({
    super.key,
    required this.comment,
  });
  final Comment comment;

  @override
  Widget build(BuildContext context) {
    return CustomCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            comment.description ?? '',
            style: regularDefault,
          ),
          comment.files?.isNotEmpty ?? false
              ? ListView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemBuilder: (context, index) {
                    return CachedNetworkImage(
                        imageUrl:
                            '${UrlContainer.attachmentUrl}${comment.files![index].fileName}',
                        height: 200,
                        errorWidget: (ctx, object, trx) {
                          return Image.asset(
                            MyImages.noDataFound,
                            height: 200,
                            color: Colors.grey,
                          );
                        },
                        placeholder: (ctx, trx) {
                          return Image.asset(
                            MyImages.noDataFound,
                            height: 30,
                            color: Colors.grey,
                          );
                        });
                  },
                  itemCount: comment.files!.length)
              : const SizedBox.shrink(),
          const CustomDivider(space: Dimensions.space10),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              TextIcon(
                text: comment.createdByUser ?? '',
                prefix: const Icon(
                  Icons.account_box_rounded,
                  color: ColorResources.primaryColor,
                  size: 16,
                ),
                edgeInsets: EdgeInsets.zero,
                textStyle: const TextStyle(
                    fontSize: Dimensions.fontSmall,
                    color: ColorResources.blueGreyColor),
              ),
              const SizedBox(
                width: Dimensions.space15,
              ),
              TextIcon(
                text: DateConverter.formatValidityDate(comment.createdAt ?? ''),
                prefix: const Icon(
                  Icons.calendar_month,
                  color: ColorResources.primaryColor,
                  size: 16,
                ),
                edgeInsets: EdgeInsets.zero,
                textStyle: const TextStyle(
                    fontSize: Dimensions.fontSmall,
                    color: ColorResources.blueGreyColor),
              ),
            ],
          )
        ],
      ),
    );
  }
}
