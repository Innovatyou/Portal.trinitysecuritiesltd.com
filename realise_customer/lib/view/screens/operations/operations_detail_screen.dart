import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/data/controller/operations/operations_controller.dart';
import 'package:realise/view/components/operations/depth_card.dart';

class OperationsDetailScreen extends StatefulWidget {
  final int id;
  const OperationsDetailScreen({super.key, required this.id});
  @override State<OperationsDetailScreen> createState() => _OperationsDetailState();
}
class _OperationsDetailState extends State<OperationsDetailScreen> {
  final comment = TextEditingController();
  @override void initState() { super.initState(); Get.find<OperationsController>().loadDetail(widget.id); }
  @override Widget build(BuildContext context) => Scaffold(appBar: AppBar(title: const Text('Request review')), body: GetBuilder<OperationsController>(builder: (controller) {
    final detail = controller.detailData;
    if (controller.loading || detail == null) return const Center(child: CircularProgressIndicator());
    return ListView(padding: const EdgeInsets.all(18), children: [
      DepthCard(accent: ColorResources.primaryColor, child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(detail.request.number, style: const TextStyle(color: ColorResources.primaryColor)), Text(detail.request.title, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w900)), Text('${detail.request.workflow} | ${detail.request.stage}'), Text(detail.request.status.toUpperCase())])),
      const Text('Request details', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900)),
      ...detail.values.map((v) => ListTile(title: Text('${v['field_key']}'.replaceAll('_', ' ')), subtitle: Text('${v['value_text'] ?? v['value_json'] ?? '-'}'))),
      const Text('Approval journey', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900)),
      ...detail.timeline.map((t) => ListTile(leading: const Icon(Icons.task_alt), title: Text('${t['name_snapshot']}'), subtitle: Text('${t['status']}'))),
      TextField(controller: comment, maxLines: 3, decoration: InputDecoration(labelText: 'Add a comment', suffixIcon: IconButton(icon: const Icon(Icons.send), onPressed: () { controller.addComment(comment.text); comment.clear(); }))),
      if (detail.canDecide) _decisions(controller),
    ]);
  }));
  Widget _decisions(OperationsController controller) { final note = TextEditingController(); return DepthCard(accent: ColorResources.primaryColor, child: Column(children: [TextField(controller: note, decoration: const InputDecoration(labelText: 'Decision note')), const SizedBox(height: 12), Wrap(spacing: 8, children: [FilledButton(onPressed: () => controller.decide('approve', note.text), child: const Text('Approve')), OutlinedButton(onPressed: () => controller.decide('return', note.text), child: const Text('Return')), OutlinedButton(onPressed: () => controller.decide('reject', note.text), child: const Text('Reject'))]) ])); }
}
