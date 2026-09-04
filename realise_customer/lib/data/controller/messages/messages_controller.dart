import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:realise/data/model/messages/messages_models.dart';
import 'package:realise/data/repo/messages/messages_repo.dart';
import 'package:realise/view/components/snack_bar/show_custom_snackbar.dart';

class MessagesController extends GetxController {
  final MessagesRepo repo;
  MessagesController({required this.repo});

  bool loadingConversations = false, loadingContacts = false, loadingThread = false, sending = false;
  List<Conversation> conversations = [];
  List<Contact> contacts = [];
  ChatThread? activeThread;
  int? activeOtherUserId;
  Timer? _threadPoller;
  Timer? _listPoller;

  int get totalUnread => conversations.fold(0, (sum, c) => sum + c.unreadCount);
  bool _hasLoadedOnce = false;

  Future<void> loadConversations() async {
    loadingConversations = true;
    update();
    final r = await repo.conversations();
    if (r.isSuccess) {
      final decoded = jsonDecode(r.responseJson);
      if (decoded['success'] == true) {
        final previousUnread = totalUnread;
        conversations = (decoded['data'] as List? ?? [])
            .map((e) => Conversation.fromJson(Map<String, dynamic>.from(e)))
            .toList();
        // Only alert on a rise after the first load, so opening the app
        // with an existing unread backlog doesn't itself trigger a sound.
        if (_hasLoadedOnce && totalUnread > previousUnread) _notifyNewMessage();
        _hasLoadedOnce = true;
      }
    }
    loadingConversations = false;
    update();
  }

  /// No dedicated notification-sound asset/package is wired up yet - this
  /// uses the platform's own UI sound plus a haptic buzz, which needs no new
  /// dependency and no shipped audio file.
  void _notifyNewMessage() {
    SystemSound.play(SystemSoundType.click);
    HapticFeedback.mediumImpact();
  }

  Future<void> loadContacts() async {
    loadingContacts = true;
    update();
    final r = await repo.contacts();
    if (r.isSuccess) {
      final decoded = jsonDecode(r.responseJson);
      if (decoded['success'] == true) {
        contacts = (decoded['data'] as List? ?? [])
            .map((e) => Contact.fromJson(Map<String, dynamic>.from(e)))
            .toList();
      } else {
        CustomSnackBar.error(errorList: [decoded['message']?.toString() ?? r.message]);
      }
    } else {
      CustomSnackBar.error(errorList: [r.message]);
    }
    loadingContacts = false;
    update();
  }

  Future<void> openThread(int otherUserId, {bool silent = false}) async {
    activeOtherUserId = otherUserId;
    if (!silent) {
      loadingThread = true;
      update();
    }
    final r = await repo.thread(otherUserId);
    if (r.isSuccess) {
      final decoded = jsonDecode(r.responseJson);
      if (decoded['success'] == true) {
        activeThread = ChatThread.fromJson(Map<String, dynamic>.from(decoded['data']));
      } else if (!silent) {
        CustomSnackBar.error(errorList: [decoded['message']?.toString() ?? r.message]);
      }
    }
    loadingThread = false;
    update();
  }

  /// Polls the open thread every 4s so new replies show up without the user
  /// having to pull-to-refresh - there's no push/websocket wiring on mobile.
  void startThreadPolling(int otherUserId) {
    stopThreadPolling();
    _threadPoller = Timer.periodic(const Duration(seconds: 4), (_) => openThread(otherUserId, silent: true));
  }

  void stopThreadPolling() {
    _threadPoller?.cancel();
    _threadPoller = null;
  }

  void startListPolling() {
    stopListPolling();
    _listPoller = Timer.periodic(const Duration(seconds: 10), (_) => loadConversations());
  }

  void stopListPolling() {
    _listPoller?.cancel();
    _listPoller = null;
  }

  Future<bool> sendMessage(int toUserId, String message, {File? file}) async {
    if (message.trim().isEmpty && file == null) return false;
    sending = true;
    update();
    final r = await repo.send(toUserId, message.trim(), file: file);
    sending = false;
    final ok = r.isSuccess && jsonDecode(r.responseJson)['success'] == true;
    if (ok) {
      await openThread(toUserId, silent: true);
    } else {
      final decoded = r.responseJson.isNotEmpty ? jsonDecode(r.responseJson) : null;
      CustomSnackBar.error(errorList: [decoded?['message']?.toString() ?? r.message]);
    }
    update();
    return ok;
  }

  @override
  void onClose() {
    stopThreadPolling();
    stopListPolling();
    super.onClose();
  }
}
