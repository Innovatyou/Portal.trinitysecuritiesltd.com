import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/data/model/project/project_details_model.dart';
import 'package:realise/view/components/no_data.dart';
import 'package:flutter/material.dart';
import 'package:realise/view/screens/project/widget/project_comment.dart';

class ProjectComments extends StatelessWidget {
  const ProjectComments({super.key, required this.comments});
  final List<Comment>? comments;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: comments?.isNotEmpty ?? false
          ? Padding(
              padding: const EdgeInsets.all(8.0),
              child: ListView.separated(
                  itemBuilder: (context, index) {
                    return ProjectComment(comment: comments![index]);
                  },
                  separatorBuilder: (context, index) =>
                      const SizedBox(height: Dimensions.space10),
                  itemCount: comments!.length),
            )
          : const Center(child: NoDataWidget()),
    );
  }
}
