import 'package:realise/core/utils/method.dart';
import 'package:realise/core/utils/url_container.dart';
import 'package:realise/data/model/global/response_model/response_model.dart';
import 'package:realise/data/model/ticket/ticket_create_model.dart';
import 'package:realise/data/services/api_service.dart';

class TicketRepo {
  ApiClient apiClient;
  TicketRepo({required this.apiClient});

  Future<ResponseModel> getAllTickets() async {
    String url = "${UrlContainer.baseUrl}${UrlContainer.ticketsUrl}";
    ResponseModel responseModel =
        await apiClient.request(url, Method.getMethod, null, passHeader: true);
    return responseModel;
  }

  Future<ResponseModel> getTicketTypes() async {
    String url =
        "${UrlContainer.baseUrl}${UrlContainer.ticketsUrl}/ticket_types";
    ResponseModel responseModel =
        await apiClient.request(url, Method.getMethod, null, passHeader: true);
    return responseModel;
  }

  Future<ResponseModel> createTicket(TicketCreateModel ticketModel) async {
    apiClient.initToken();

    String url = "${UrlContainer.baseUrl}${UrlContainer.ticketsUrl}";

    Map<String, String> params = {
      "title": ticketModel.title,
      "description": ticketModel.description,
      "ticket_type_id": ticketModel.ticketTypeId,
    };

    ResponseModel responseModel = await apiClient
        .request(url, Method.postMethod, params, passHeader: true);

    return responseModel;
  }

  Future<ResponseModel> getTicketDetails(ticketId) async {
    String url = "${UrlContainer.baseUrl}${UrlContainer.ticketsUrl}/$ticketId";
    ResponseModel responseModel =
        await apiClient.request(url, Method.getMethod, null, passHeader: true);
    return responseModel;
  }

  Future<ResponseModel> postTicketReply(ticketId, String description) async {
    apiClient.initToken();

    String url =
        "${UrlContainer.baseUrl}${UrlContainer.ticketsUrl}/$ticketId/comment";

    Map<String, String> params = {"description": description};

    ResponseModel responseModel = await apiClient
        .request(url, Method.postMethod, params, passHeader: true);
    return responseModel;
  }
}
