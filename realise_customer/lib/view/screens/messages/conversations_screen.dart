import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:realise/core/route/route.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/data/controller/messages/messages_controller.dart';
import 'package:realise/data/model/messages/messages_models.dart';
import 'package:realise/view/components/custom_loader/custom_loader.dart';
import 'package:realise/view/components/no_data.dart';
import 'package:realise/view/screens/messages/contacts_screen.dart';

class ConversationsScreen extends StatefulWidget {
  const ConversationsScreen({super.key});
  @override
  State<ConversationsScreen> createState() => _ConversationsScreenState();
}

class _ConversationsScreenState extends State<ConversationsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final c = Get.find<MessagesController>();
      c.loadConversations();
      c.startListPolling();
    });
  }

  @override
  void dispose() {
    Get.find<MessagesController>().stopListPolling();
    super.dispose();
  }

  String _relativeTime(String utcString) {
    if (utcString.isEmpty) return '';
    final parsed = DateTime.tryParse('${utcString.replaceFirst(' ', 'T')}Z');
    if (parsed == null) return '';
    final local = parsed.toLocal();
    final diff = DateTime.now().difference(local);
    if (diff.inMinutes < 1) return 'now';
    if (diff.inMinutes < 60) return '${diff.inMinutes}m';
    if (diff.inHours < 24) return '${diff.inHours}h';
    if (diff.inDays < 7) return '${diff.inDays}d';
    return '${local.day}/${local.month}/${local.year}';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Messages'),
        actions: [
          IconButton(
            icon: const Icon(Icons.chat_bubble_outline_rounded),
            onPressed: () => Get.to(() => const ContactsScreen()),
          ),
        ],
      ),
      body: GetBuilder<MessagesController>(builder: (c) {
        if (c.loadingConversations && c.conversations.isEmpty) {
          return const CustomLoader();
        }
        if (c.conversations.isEmpty) {
          return const NoDataWidget(text: 'No conversations yet. Tap the chat icon to start one.');
        }
        return RefreshIndicator(
          onRefresh: c.loadConversations,
          child: ListView.separated(
            itemCount: c.conversations.length,
            separatorBuilder: (_, __) => const Divider(height: 1, indent: 74),
            itemBuilder: (_, index) {
              final Conversation item = c.conversations[index];
              final unread = item.unreadCount > 0;
              return ListTile(
                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                leading: Stack(
                  children: [
                    CircleAvatar(
                      radius: 26,
                      backgroundColor: ColorResources.lineColor,
                      backgroundImage: NetworkImage(item.otherUserImage),
                    ),
                    if (item.isOnline)
                      Positioned(
                        right: 0,
                        bottom: 0,
                        child: Container(
                          width: 12,
                          height: 12,
                          decoration: BoxDecoration(
                            color: Colors.green,
                            shape: BoxShape.circle,
                            border: Border.all(color: Colors.white, width: 2),
                          ),
                        ),
                      ),
                  ],
                ),
                title: Text(
                  item.otherUserName,
                  style: TextStyle(fontWeight: unread ? FontWeight.w800 : FontWeight.w600),
                ),
                subtitle: Row(
                  children: [
                    if (item.hasAttachment) const Padding(padding: EdgeInsets.only(right: 4), child: Icon(Icons.attach_file, size: 14, color: Colors.black45)),
                    Expanded(
                      child: Text(
                        item.lastMessage.isEmpty ? (item.hasAttachment ? 'Attachment' : '') : item.lastMessage,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: unread ? Colors.black87 : Colors.black54,
                          fontWeight: unread ? FontWeight.w700 : FontWeight.normal,
                        ),
                      ),
                    ),
                  ],
                ),
                trailing: Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(_relativeTime(item.lastTime), style: const TextStyle(fontSize: 12, color: Colors.black45)),
                    const SizedBox(height: 6),
                    if (unread)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                        decoration: const BoxDecoration(color: ColorResources.primaryColor, shape: BoxShape.circle),
                        child: Text('${item.unreadCount}', style: const TextStyle(color: Colors.black, fontSize: 11, fontWeight: FontWeight.bold)),
                      ),
                  ],
                ),
                onTap: () => Get.toNamed(RouteHelper.chatScreen, arguments: item.otherUserId),
              );
            },
          ),
        );
      }),
    );
  }
}
