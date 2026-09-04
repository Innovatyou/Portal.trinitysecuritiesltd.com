import 'dart:io';

import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/data/controller/messages/messages_controller.dart';
import 'package:realise/data/model/messages/messages_models.dart';
import 'package:realise/data/repo/messages/messages_repo.dart';
import 'package:realise/view/components/custom_loader/custom_loader.dart';

const _imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

class ChatScreen extends StatefulWidget {
  final int otherUserId;
  const ChatScreen({super.key, required this.otherUserId});
  @override
  State<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final _messageController = TextEditingController();
  final _scrollController = ScrollController();
  File? _pickedFile;
  int _lastMessageCount = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      final c = Get.find<MessagesController>();
      await c.openThread(widget.otherUserId);
      _scrollToBottom();
      c.startThreadPolling(widget.otherUserId);
    });
  }

  @override
  void dispose() {
    Get.find<MessagesController>().stopThreadPolling();
    _messageController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.jumpTo(_scrollController.position.maxScrollExtent);
      }
    });
  }

  Future<void> _pickFile() async {
    final result = await FilePicker.platform.pickFiles(type: FileType.any);
    final path = result?.files.single.path;
    if (path == null) return;
    setState(() => _pickedFile = File(path));
  }

  Future<void> _send(MessagesController c) async {
    final text = _messageController.text;
    final file = _pickedFile;
    if (text.trim().isEmpty && file == null) return;
    _messageController.clear();
    setState(() => _pickedFile = null);
    final ok = await c.sendMessage(widget.otherUserId, text, file: file);
    if (ok) _scrollToBottom();
  }

  String _formatTime(String utcString) {
    final parsed = DateTime.tryParse('${utcString.replaceFirst(' ', 'T')}Z');
    if (parsed == null) return '';
    final local = parsed.toLocal();
    final h = local.hour % 12 == 0 ? 12 : local.hour % 12;
    final m = local.minute.toString().padLeft(2, '0');
    return '$h:$m ${local.hour >= 12 ? 'PM' : 'AM'}';
  }

  bool _isImage(String fileName) {
    final ext = fileName.split('.').last.toLowerCase();
    return _imageExtensions.contains(ext);
  }

  @override
  Widget build(BuildContext context) {
    final repo = Get.find<MessagesRepo>();
    return Scaffold(
      backgroundColor: const Color(0xFFF3F3F3),
      appBar: AppBar(
        titleSpacing: 0,
        title: GetBuilder<MessagesController>(builder: (c) {
          final thread = c.activeThread;
          return Row(
            children: [
              CircleAvatar(
                radius: 18,
                backgroundColor: ColorResources.lineColor,
                backgroundImage: thread != null && thread.otherUserImage.isNotEmpty ? NetworkImage(thread.otherUserImage) : null,
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(thread?.otherUserName ?? '', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold), overflow: TextOverflow.ellipsis),
                    if (thread?.isOnline == true) const Text('online', style: TextStyle(fontSize: 12, color: Colors.green)),
                  ],
                ),
              ),
            ],
          );
        }),
      ),
      body: GetBuilder<MessagesController>(builder: (c) {
        final thread = c.activeThread;
        if (c.loadingThread && thread == null) return const CustomLoader();
        final messages = thread?.messages ?? [];
        if (messages.length != _lastMessageCount) {
          _lastMessageCount = messages.length;
          _scrollToBottom();
        }
        return Column(
          children: [
            Expanded(
              child: messages.isEmpty
                  ? const Center(child: Text('Say hello 👋', style: TextStyle(color: Colors.black45)))
                  : ListView.builder(
                      controller: _scrollController,
                      padding: const EdgeInsets.all(12),
                      itemCount: messages.length,
                      itemBuilder: (_, index) => _bubble(messages[index], repo),
                    ),
            ),
            _inputBar(c),
          ],
        );
      }),
    );
  }

  Widget _bubble(ChatMessage message, MessagesRepo repo) {
    final mine = message.isMine;
    return Align(
      alignment: mine ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.75),
        margin: const EdgeInsets.symmetric(vertical: 4),
        padding: const EdgeInsets.all(10),
        decoration: BoxDecoration(
          color: mine ? ColorResources.primaryColor.withOpacity(0.85) : Colors.white,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(12),
            topRight: const Radius.circular(12),
            bottomLeft: Radius.circular(mine ? 12 : 2),
            bottomRight: Radius.circular(mine ? 2 : 12),
          ),
          boxShadow: const [BoxShadow(color: Colors.black12, blurRadius: 2, offset: Offset(0, 1))],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ...message.files.map((f) => _fileChip(f, repo)),
            if (message.message.isNotEmpty)
              Padding(
                padding: EdgeInsets.only(top: message.files.isNotEmpty ? 6 : 0),
                child: Text(message.message, style: const TextStyle(fontSize: 15)),
              ),
            const SizedBox(height: 4),
            Align(
              alignment: Alignment.bottomRight,
              child: Text(_formatTime(message.createdAt), style: const TextStyle(fontSize: 11, color: Colors.black45)),
            ),
          ],
        ),
      ),
    );
  }

  Widget _fileChip(Map<String, dynamic> file, MessagesRepo repo) {
    final fileName = '${file['file_name'] ?? ''}';
    if (fileName.isEmpty) return const SizedBox.shrink();
    final url = repo.attachmentUrl(fileName);
    if (_isImage(fileName)) {
      return Padding(
        padding: const EdgeInsets.only(bottom: 4),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(8),
          child: Image.network(url, width: 200, fit: BoxFit.cover, errorBuilder: (_, __, ___) => const SizedBox.shrink()),
        ),
      );
    }
    return InkWell(
      onTap: () => launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication),
      child: Padding(
        padding: const EdgeInsets.only(bottom: 4),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.insert_drive_file_outlined, size: 18),
            const SizedBox(width: 6),
            Flexible(child: Text(fileName, overflow: TextOverflow.ellipsis, style: const TextStyle(decoration: TextDecoration.underline))),
          ],
        ),
      ),
    );
  }

  Widget _inputBar(MessagesController c) {
    return SafeArea(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
        decoration: const BoxDecoration(color: Colors.white, boxShadow: [BoxShadow(color: Colors.black12, blurRadius: 4)]),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (_pickedFile != null)
              Align(
                alignment: Alignment.centerLeft,
                child: Chip(
                  label: Text(_pickedFile!.path.split(Platform.pathSeparator).last, overflow: TextOverflow.ellipsis),
                  onDeleted: () => setState(() => _pickedFile = null),
                ),
              ),
            Row(
              children: [
                IconButton(
                  icon: const Icon(Icons.attach_file, color: ColorResources.primaryColor),
                  onPressed: c.sending ? null : _pickFile,
                ),
                Expanded(
                  child: TextField(
                    controller: _messageController,
                    minLines: 1,
                    maxLines: 4,
                    textCapitalization: TextCapitalization.sentences,
                    decoration: const InputDecoration(
                      hintText: 'Message',
                      border: OutlineInputBorder(borderRadius: BorderRadius.all(Radius.circular(24)), borderSide: BorderSide.none),
                      filled: true,
                      fillColor: Color(0xFFF0F0F0),
                      contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                    ),
                  ),
                ),
                const SizedBox(width: 6),
                CircleAvatar(
                  backgroundColor: ColorResources.primaryColor,
                  child: c.sending
                      ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.black))
                      : IconButton(icon: const Icon(Icons.send, color: Colors.black), onPressed: () => _send(c)),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
