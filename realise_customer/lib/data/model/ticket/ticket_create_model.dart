class TicketCreateModel {
  final String title;
  final String description;
  final String ticketTypeId;

  TicketCreateModel(
      {required this.title,
      required this.description,
      required this.ticketTypeId});
}
