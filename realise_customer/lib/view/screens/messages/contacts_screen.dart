import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:realise/core/route/route.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/data/controller/messages/messages_controller.dart';
import 'package:realise/data/model/messages/messages_models.dart';
import 'package:realise/view/components/custom_loader/custom_loader.dart';
import 'package:realise/view/components/no_data.dart';

class ContactsScreen extends StatefulWidget {
  const ContactsScreen({super.key});
  @override
  State<ContactsScreen> createState() => _ContactsScreenState();
}

class _ContactsScreenState extends State<ContactsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => Get.find<MessagesController>().loadContacts());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('New message')),
      body: GetBuilder<MessagesController>(builder: (c) {
        if (c.loadingContacts) return const CustomLoader();
        if (c.contacts.isEmpty) return const NoDataWidget(text: 'Nobody is available to message right now.');
        return ListView.builder(
          itemCount: c.contacts.length,
          itemBuilder: (_, index) {
            final Contact contact = c.contacts[index];
            return ListTile(
              leading: CircleAvatar(
                radius: 24,
                backgroundColor: ColorResources.lineColor,
                backgroundImage: NetworkImage(contact.image),
              ),
              title: Text(contact.name),
              subtitle: Text(contact.jobTitle.isNotEmpty ? contact.jobTitle : (contact.userType == 'client' ? 'Client' : 'Team member')),
              trailing: contact.isOnline ? const Icon(Icons.circle, size: 10, color: Colors.green) : null,
              onTap: () {
                Get.back();
                Get.toNamed(RouteHelper.chatScreen, arguments: contact.id);
              },
            );
          },
        );
      }),
    );
  }
}
