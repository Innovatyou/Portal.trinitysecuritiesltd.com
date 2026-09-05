import 'dart:io';

import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/data/controller/operations/operations_controller.dart';
import 'package:realise/data/model/operations/operations_models.dart';
import 'package:realise/view/components/operations/depth_card.dart';
import 'package:realise/view/screens/operations/sign_document_screen.dart';

class OperationsDetailScreen extends StatefulWidget {
  final int id;
  const OperationsDetailScreen({super.key, required this.id});
  @override State<OperationsDetailScreen> createState() => _OperationsDetailState();
}
class _OperationsDetailState extends State<OperationsDetailScreen> {
  final comment = TextEditingController();
  // loadDetail() calls update() (loading=true) before its first await -
  // calling it synchronously from initState, during the build phase,
  // throws "setState() called during build" (same bug fixed in
  // operations_screen.dart and sign_document_screen.dart).
  @override void initState() { super.initState(); WidgetsBinding.instance.addPostFrameCallback((_) => Get.find<OperationsController>().loadDetail(widget.id)); }
  @override Widget build(BuildContext context) => Scaffold(appBar: AppBar(title: const Text('Request review')), body: GetBuilder<OperationsController>(builder: (controller) {
    final detail = controller.detailData;
    if (controller.loading || detail == null) return const Center(child: CircularProgressIndicator());
    return ListView(padding: const EdgeInsets.all(18), children: [
      DepthCard(accent: ColorResources.primaryColor, child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(detail.request.number, style: const TextStyle(color: ColorResources.primaryColor)), Text(detail.request.title, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w900)), Text('${detail.request.workflow} | ${detail.request.stage}'), Text(detail.request.status.toUpperCase())])),
      const Text('Request details', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900)),
      ...detail.values.map((v) => ListTile(title: Text('${v['field_key']}'.replaceAll('_', ' ')), subtitle: Text('${v['value_text'] ?? v['value_json'] ?? '-'}'))),
      const Text('Approval journey', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900)),
      ...detail.timeline.map((t) => ListTile(leading: const Icon(Icons.task_alt), title: Text('${t['name_snapshot']}'), subtitle: Text('${t['status']}'))),
      _attachments(controller, detail),
      if (detail.canRespondInformation) _informationRequest(controller, detail),
      if (detail.canResubmit) _resubmit(controller, detail),
      TextField(controller: comment, maxLines: 3, decoration: InputDecoration(labelText: 'Add a comment', suffixIcon: IconButton(icon: const Icon(Icons.send), onPressed: () { controller.addComment(comment.text); comment.clear(); }))),
      if (detail.canDecide) _decisions(controller, detail),
      if (detail.canCancel) _cancel(controller),
      if (detail.canDelete) _delete(controller),
    ]);
  }));

  static String _formatSize(dynamic bytes) {
    final n = int.tryParse('$bytes') ?? 0;
    if (n < 1024) return '$n B';
    if (n < 1024 * 1024) return '${(n / 1024).toStringAsFixed(1)} KB';
    return '${(n / (1024 * 1024)).toStringAsFixed(1)} MB';
  }

  Widget _attachments(OperationsController controller, OperationsDetail detail) => DepthCard(accent: Colors.teal, child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
    Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
      const Text('Attachments', style: TextStyle(fontWeight: FontWeight.w900)),
      IconButton(
        icon: const Icon(Icons.attach_file, color: ColorResources.primaryColor),
        onPressed: controller.submitting ? null : () => _pickAndUpload(controller),
      ),
    ]),
    if (detail.attachments.isEmpty) const Text('No attachments yet.', style: TextStyle(color: Colors.black45)),
    ...detail.attachments.map((a) => ListTile(
      contentPadding: EdgeInsets.zero,
      leading: const Icon(Icons.insert_drive_file_outlined),
      title: Text('${a['original_name']}', overflow: TextOverflow.ellipsis),
      subtitle: Text(_formatSize(a['size_bytes'])),
      onTap: () => launchUrl(
        Uri.parse(controller.repo.downloadUrl(int.tryParse('${a['id']}') ?? 0)),
        mode: LaunchMode.externalApplication,
      ),
    )),
  ]));

  Future<void> _pickAndUpload(OperationsController controller) async {
    final result = await FilePicker.platform.pickFiles(allowMultiple: true);
    final paths = (result?.paths ?? []).whereType<String>();
    for (final path in paths) {
      await controller.uploadAttachment(File(path));
    }
  }

  Widget _informationRequest(OperationsController controller, OperationsDetail detail) { final response = TextEditingController(); final q = detail.openConversation?['question']?.toString() ?? ''; return DepthCard(accent: Colors.orange, child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
    const Text('Information requested', style: TextStyle(fontWeight: FontWeight.w900)),
    const SizedBox(height: 6), Text(q),
    const SizedBox(height: 12),
    TextField(controller: response, maxLines: 3, decoration: const InputDecoration(labelText: 'Your response')),
    const SizedBox(height: 8),
    Align(alignment: Alignment.centerRight, child: FilledButton(onPressed: () => controller.respondToInformation(response.text), child: const Text('Send response'))),
  ])); }

  Widget _resubmit(OperationsController controller, OperationsDetail detail) {
    final note = TextEditingController();
    final currentValues = {for (final v in detail.values) '${v['field_key']}': '${v['value_text'] ?? v['value_json'] ?? ''}'};
    final fieldControllers = <String, TextEditingController>{};
    return DepthCard(accent: Colors.purple, child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      const Text('This request was returned', style: TextStyle(fontWeight: FontWeight.w900)),
      const SizedBox(height: 6),
      const Text('Update anything the approver flagged, then resubmit for approval.'),
      const SizedBox(height: 12),
      ...detail.fields.map((f) {
        final key = '${f['field_key']}';
        final editable = f['editable_on_return'] == true;
        final controllerForField = fieldControllers[key] = TextEditingController(text: currentValues[key] ?? '');
        return Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: TextField(
            controller: controllerForField,
            enabled: editable,
            maxLines: f['field_type'] == 'textarea' ? 4 : 1,
            decoration: InputDecoration(
              labelText: '${f['label']}${'${f['is_required']}' == '1' ? ' *' : ''}',
              helperText: editable ? null : 'Locked by this workflow',
            ),
          ),
        );
      }),
      TextField(controller: note, maxLines: 2, decoration: const InputDecoration(labelText: 'What changed?')),
      const SizedBox(height: 8),
      Align(
        alignment: Alignment.centerRight,
        child: FilledButton(
          onPressed: controller.submitting
              ? null
              : () => controller.resubmit(note.text, fieldControllers.map((k, c) => MapEntry(k, c.text))),
          child: const Text('Resubmit'),
        ),
      ),
    ]));
  }

  Widget _decisions(OperationsController controller, OperationsDetail detail) { final note = TextEditingController(); final pdfAttachments = detail.attachments.where((a) => '${a['original_name']}'.toLowerCase().endsWith('.pdf')).toList(); return DepthCard(accent: ColorResources.primaryColor, child: Column(children: [TextField(controller: note, decoration: const InputDecoration(labelText: 'Decision note')), const SizedBox(height: 12), Wrap(spacing: 8, children: [FilledButton(onPressed: () => controller.decide('approve', note.text), child: const Text('Approve')), if (pdfAttachments.isNotEmpty) OutlinedButton.icon(onPressed: () => _signAndApprove(context, controller, pdfAttachments, note), icon: const Icon(Icons.draw_outlined), label: const Text('Sign & Approve')), OutlinedButton(onPressed: () => controller.decide('return', note.text), child: const Text('Return')), OutlinedButton(onPressed: () => controller.decide('reject', note.text), child: const Text('Reject')), TextButton.icon(onPressed: () => _askQuestion(context, controller), icon: const Icon(Icons.help_outline), label: const Text('Ask a question'))]) ])); }

  Future<void> _signAndApprove(BuildContext context, OperationsController controller, List<Map<String, dynamic>> pdfAttachments, TextEditingController note) async {
    // Most recently uploaded PDF attachment by default - matches the
    // common case of one document requiring approval per request. With
    // several PDFs attached, let the approver pick which one to sign.
    var target = pdfAttachments.last;
    if (pdfAttachments.length > 1) {
      final picked = await showModalBottomSheet<Map<String, dynamic>>(
        context: context,
        builder: (context) => SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: pdfAttachments
                .map((a) => ListTile(
                      leading: const Icon(Icons.picture_as_pdf_outlined),
                      title: Text('${a['original_name']}'),
                      onTap: () => Navigator.of(context).pop(a),
                    ))
                .toList(),
          ),
        ),
      );
      if (picked == null) return;
      target = picked;
    }

    if (!context.mounted) return;
    final signed = await Navigator.of(context).push<bool>(MaterialPageRoute(
      builder: (_) => SignDocumentScreen(
        attachmentId: int.tryParse('${target['id']}') ?? 0,
        attachmentName: '${target['original_name']}',
      ),
    ));

    if (signed == true) {
      controller.decide('approve', note.text);
    }
  }

  Widget _cancel(OperationsController controller) => Align(
    alignment: Alignment.centerRight,
    child: TextButton.icon(
      onPressed: () => _confirmCancel(context, controller),
      icon: const Icon(Icons.cancel_outlined, color: Colors.red),
      label: const Text('Cancel request', style: TextStyle(color: Colors.red)),
    ),
  );

  void _confirmCancel(BuildContext context, OperationsController controller) {
    final reason = TextEditingController();
    showDialog(context: context, builder: (_) => AlertDialog(
      title: const Text('Cancel this request?'),
      content: TextField(controller: reason, autofocus: true, decoration: const InputDecoration(hintText: 'Reason for cancelling')),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: const Text('Keep it')),
        FilledButton(
          style: FilledButton.styleFrom(backgroundColor: Colors.red),
          onPressed: () { Navigator.pop(context); controller.cancelRequest(reason.text); },
          child: const Text('Cancel request'),
        ),
      ],
    ));
  }

  Widget _delete(OperationsController controller) => Align(
    alignment: Alignment.centerRight,
    child: TextButton.icon(
      onPressed: () => _confirmDelete(context, controller),
      icon: const Icon(Icons.delete_outline, color: Colors.red),
      label: const Text('Delete request', style: TextStyle(color: Colors.red)),
    ),
  );

  void _confirmDelete(BuildContext context, OperationsController controller) {
    showDialog(context: context, builder: (_) => AlertDialog(
      title: const Text('Delete this request?'),
      content: const Text('This cannot be undone.'),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: const Text('Keep it')),
        FilledButton(
          style: FilledButton.styleFrom(backgroundColor: Colors.red),
          onPressed: () async {
            Navigator.pop(context);
            final ok = await controller.deleteRequest();
            if (ok && context.mounted) Navigator.pop(context);
          },
          child: const Text('Delete'),
        ),
      ],
    ));
  }

  void _askQuestion(BuildContext context, OperationsController controller) { final question = TextEditingController(); showDialog(context: context, builder: (_) => AlertDialog(
    title: const Text('Ask a question'),
    content: TextField(controller: question, maxLines: 3, autofocus: true, decoration: const InputDecoration(hintText: 'What do you need clarified?')),
    actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancel')), FilledButton(onPressed: () { Navigator.pop(context); controller.askQuestion(question.text); }, child: const Text('Send'))],
  )); }
}
