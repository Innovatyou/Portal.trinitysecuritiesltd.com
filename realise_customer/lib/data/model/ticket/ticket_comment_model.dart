import 'dart:io';

class TicketCommentModel {
  final String message;
  final File? image;

  TicketCommentModel({
    required this.message,
    required this.image,
  });
}
