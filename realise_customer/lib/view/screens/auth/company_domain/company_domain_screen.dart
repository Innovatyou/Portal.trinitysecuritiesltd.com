import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:http/http.dart' as http;
import 'package:realise/core/route/route.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/url_container.dart';
import 'package:realise/view/components/snack_bar/show_custom_snackbar.dart';

/// Shown once, before login, so this single installed app can be pointed at
/// any company's own domain - this is a multi-tenant SaaS, each company has
/// its own separate database reachable only on its own domain, so there is
/// no one "the" server to hardcode. Reachable again later from the login
/// screen ("Not your company?") in case of a typo or a company switch.
class CompanyDomainScreen extends StatefulWidget {
  const CompanyDomainScreen({super.key});
  @override
  State<CompanyDomainScreen> createState() => _CompanyDomainScreenState();
}

class _CompanyDomainScreenState extends State<CompanyDomainScreen> {
  final _domain = TextEditingController();
  bool _checking = false;

  Future<void> _continue() async {
    final input = _domain.text.trim();
    if (input.isEmpty) {
      CustomSnackBar.error(errorList: const ['Enter your company\'s domain']);
      return;
    }

    final normalized = UrlContainer.normalizeDomainInput(input);
    setState(() => _checking = true);

    try {
      final response = await http
          .get(Uri.parse('$normalized/customersapi/overview'))
          .timeout(const Duration(seconds: 12));

      if (response.statusCode != 200) {
        throw const FormatException();
      }
      final body = jsonDecode(response.body);
      if (body is! Map || body['success'] != true) {
        throw const FormatException();
      }

      await UrlContainer.setDomain(Get.find(), normalized);
      if (!mounted) return;
      Get.offAllNamed(RouteHelper.loginScreen);
    } on TimeoutException {
      CustomSnackBar.error(
          errorList: const ['Could not reach that domain. Check it and try again.']);
    } on SocketException {
      CustomSnackBar.error(
          errorList: const ['Could not reach that domain. Check it and try again.']);
    } catch (_) {
      CustomSnackBar.error(errorList: const [
        'That doesn\'t look like a company set up on this app yet.'
      ]);
    } finally {
      if (mounted) setState(() => _checking = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        body: SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.domain_rounded,
                    size: 48, color: ColorResources.primaryColor),
                const SizedBox(height: 18),
                const Text('Find your company',
                    style:
                        TextStyle(fontSize: 26, fontWeight: FontWeight.w900)),
                const SizedBox(height: 8),
                const Text(
                    'Enter the web address your company gave you to sign in.',
                    style: TextStyle(color: Colors.black54)),
                const SizedBox(height: 24),
                TextField(
                  controller: _domain,
                  autofocus: true,
                  keyboardType: TextInputType.url,
                  textInputAction: TextInputAction.done,
                  onSubmitted: (_) => _checking ? null : _continue(),
                  decoration: const InputDecoration(
                    labelText: 'Company domain',
                    hintText: 'yourcompany.com',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton(
                    onPressed: _checking ? null : _continue,
                    child: _checking
                        ? const SizedBox(
                            height: 20,
                            width: 20,
                            child: CircularProgressIndicator(strokeWidth: 2))
                        : const Text('Continue'),
                  ),
                ),
              ],
            ),
          ),
        ),
      );
}
