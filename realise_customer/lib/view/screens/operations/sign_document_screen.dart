import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';
import 'package:pdfx/pdfx.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/data/controller/operations/operations_controller.dart';
import 'package:realise/data/services/api_service.dart';
import 'package:signature/signature.dart';

/// Two steps: capture a signature (draw on screen, or snap a photo of a
/// physical one), then position it on the actual attached PDF page - drag
/// to move, drag the bottom-right handle to resize. Position/size are
/// stored as fractions of the page (0-1), sent to
/// Operations_api::signAttachment(), which stamps them onto the real page
/// dimensions server-side - this screen never needs to know or agree on
/// the page's actual point/mm size.
///
/// Returns true via Navigator.pop() if signing succeeded, so the caller
/// (the decision flow) knows to go on and record the approval.
class SignDocumentScreen extends StatefulWidget {
  final int attachmentId;
  final String attachmentName;

  const SignDocumentScreen({
    super.key,
    required this.attachmentId,
    required this.attachmentName,
  });

  @override
  State<SignDocumentScreen> createState() => _SignDocumentScreenState();
}

class _SignDocumentScreenState extends State<SignDocumentScreen> {
  final SignatureController _sigController = SignatureController(
    penStrokeWidth: 3,
    penColor: Colors.black,
    exportBackgroundColor: Colors.transparent,
  );

  Uint8List? _pdfBytes;
  Uint8List? _signatureBytes;
  Uint8List? _pageImageBytes;
  double _pageAspect = 1.414; // height / width, updated once the page renders
  int _page = 1;
  int _pageCount = 1;

  bool _loading = true;
  bool _renderingPage = false;
  bool _signing = false;
  String? _error;

  // Position/size as fractions of the page (0-1).
  double _x = 0.32, _y = 0.78, _w = 0.36, _h = 0.09;

  @override
  void initState() {
    super.initState();
    _downloadDocument();
  }

  @override
  void dispose() {
    _sigController.dispose();
    super.dispose();
  }

  Future<void> _downloadDocument() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final apiClient = Get.find<ApiClient>();
      apiClient.initToken();
      final controller = Get.find<OperationsController>();
      final url = controller.repo.downloadUrl(widget.attachmentId);
      final response = await http.get(Uri.parse(url));
      if (response.statusCode != 200 || response.bodyBytes.isEmpty) {
        setState(() {
          _error = 'Could not download the document to preview it.';
          _loading = false;
        });
        return;
      }
      _pdfBytes = response.bodyBytes;
      await _renderPage();
    } catch (e) {
      setState(() {
        _error = 'Could not load the document: $e';
        _loading = false;
      });
    }
  }

  Future<void> _renderPage() async {
    if (_pdfBytes == null) return;
    setState(() => _renderingPage = true);
    PdfDocument? document;
    PdfPage? page;
    try {
      document = await PdfDocument.openData(_pdfBytes!);
      _pageCount = document.pagesCount;
      if (_page > _pageCount) _page = _pageCount;
      page = await document.getPage(_page);
      final rendered = await page.render(
        width: page.width * 2,
        height: page.height * 2,
        format: PdfPageImageFormat.jpeg,
        backgroundColor: '#FFFFFF',
      );
      if (rendered != null) {
        setState(() {
          _pageImageBytes = rendered.bytes;
          _pageAspect = page!.height / page.width;
        });
      }
    } catch (e) {
      setState(() => _error = 'Could not render page $_page: $e');
    } finally {
      await page?.close();
      await document?.close();
      setState(() {
        _loading = false;
        _renderingPage = false;
      });
    }
  }

  Future<void> _goToPage(int page) async {
    if (page < 1 || page > _pageCount || page == _page) return;
    _page = page;
    await _renderPage();
  }

  Future<void> _drawSignature() async {
    final result = await showModalBottomSheet<Uint8List>(
      context: context,
      isScrollControlled: true,
      builder: (context) => _DrawSignatureSheet(controller: _sigController),
    );
    if (result != null) {
      setState(() => _signatureBytes = result);
    }
  }

  Future<void> _photoSignature() async {
    final picked =
        await ImagePicker().pickImage(source: ImageSource.camera, imageQuality: 90);
    if (picked == null) return;
    setState(() async {});
    final bytes = await picked.readAsBytes();
    setState(() => _signatureBytes = bytes);
  }

  Future<void> _confirmAndSign() async {
    final signature = _signatureBytes;
    if (signature == null) return;

    setState(() => _signing = true);
    final controller = Get.find<OperationsController>();
    final ok = await controller.signAttachment(
      widget.attachmentId,
      signature,
      page: _page,
      x: _x,
      y: _y,
      width: _w,
      height: _h,
    );
    setState(() => _signing = false);

    if (ok && mounted) {
      Navigator.of(context).pop(true);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Sign ${widget.attachmentName}')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(20),
                    child: Text(_error!, textAlign: TextAlign.center),
                  ),
                )
              : _signatureBytes == null
                  ? _captureStep()
                  : _positionStep(),
    );
  }

  Widget _captureStep() {
    return Padding(
      padding: const EdgeInsets.all(20),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.draw_outlined, size: 64, color: ColorResources.primaryColor),
          const SizedBox(height: 16),
          const Text(
            'Add your signature',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 6),
          const Text(
            'Draw it on screen, or take a photo of your handwritten signature.',
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 24),
          FilledButton.icon(
            onPressed: _drawSignature,
            icon: const Icon(Icons.draw),
            label: const Text('Draw signature'),
          ),
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: _photoSignature,
            icon: const Icon(Icons.camera_alt_outlined),
            label: const Text('Take a photo'),
          ),
        ],
      ),
    );
  }

  Widget _positionStep() {
    return Column(
      children: [
        if (_pageCount > 1)
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                IconButton(
                  onPressed: _page > 1 ? () => _goToPage(_page - 1) : null,
                  icon: const Icon(Icons.chevron_left),
                ),
                Text('Page $_page of $_pageCount'),
                IconButton(
                  onPressed: _page < _pageCount ? () => _goToPage(_page + 1) : null,
                  icon: const Icon(Icons.chevron_right),
                ),
              ],
            ),
          ),
        Expanded(
          child: _renderingPage || _pageImageBytes == null
              ? const Center(child: CircularProgressIndicator())
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(12),
                  child: LayoutBuilder(
                    builder: (context, constraints) {
                      final displayWidth = constraints.maxWidth;
                      final displayHeight = displayWidth * _pageAspect;
                      return SizedBox(
                        width: displayWidth,
                        height: displayHeight,
                        child: Stack(
                          clipBehavior: Clip.none,
                          children: [
                            Positioned.fill(
                              child: Image.memory(_pageImageBytes!, fit: BoxFit.fill),
                            ),
                            Positioned(
                              left: _x * displayWidth,
                              top: _y * displayHeight,
                              width: _w * displayWidth,
                              height: _h * displayHeight,
                              child: GestureDetector(
                                onPanUpdate: (details) {
                                  setState(() {
                                    _x = (_x + details.delta.dx / displayWidth)
                                        .clamp(0.0, 1.0 - _w);
                                    _y = (_y + details.delta.dy / displayHeight)
                                        .clamp(0.0, 1.0 - _h);
                                  });
                                },
                                child: Stack(
                                  clipBehavior: Clip.none,
                                  children: [
                                    Container(
                                      decoration: BoxDecoration(
                                        border: Border.all(
                                            color: ColorResources.primaryColor,
                                            width: 1.5),
                                      ),
                                      child: Image.memory(_signatureBytes!,
                                          fit: BoxFit.contain),
                                    ),
                                    Positioned(
                                      right: -10,
                                      bottom: -10,
                                      child: GestureDetector(
                                        onPanUpdate: (details) {
                                          setState(() {
                                            _w = (_w +
                                                    details.delta.dx / displayWidth)
                                                .clamp(0.08, 1.0 - _x);
                                            _h = (_h +
                                                    details.delta.dy / displayHeight)
                                                .clamp(0.03, 1.0 - _y);
                                          });
                                        },
                                        child: Container(
                                          width: 22,
                                          height: 22,
                                          decoration: const BoxDecoration(
                                            color: ColorResources.primaryColor,
                                            shape: BoxShape.circle,
                                          ),
                                          child: const Icon(Icons.open_in_full,
                                              size: 12, color: Colors.black),
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ],
                        ),
                      );
                    },
                  ),
                ),
        ),
        Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              Expanded(
                child: TextButton(
                  onPressed: () => setState(() => _signatureBytes = null),
                  child: const Text('Change signature'),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: FilledButton(
                  onPressed: _signing ? null : _confirmAndSign,
                  child: _signing
                      ? const SizedBox(
                          height: 18,
                          width: 18,
                          child: CircularProgressIndicator(
                              strokeWidth: 2, color: Colors.white),
                        )
                      : const Text('Place signature'),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _DrawSignatureSheet extends StatelessWidget {
  final SignatureController controller;
  const _DrawSignatureSheet({required this.controller});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: SizedBox(
        height: 340,
        child: Column(
          children: [
            const Padding(
              padding: EdgeInsets.all(12),
              child: Text('Draw your signature',
                  style: TextStyle(fontWeight: FontWeight.w700)),
            ),
            Expanded(
              child: Container(
                margin: const EdgeInsets.symmetric(horizontal: 12),
                decoration: BoxDecoration(
                  border: Border.all(color: ColorResources.borderColor),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Signature(controller: controller, backgroundColor: Colors.white),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(12),
              child: Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      onPressed: controller.clear,
                      child: const Text('Clear'),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: FilledButton(
                      onPressed: () async {
                        if (controller.isEmpty) return;
                        final bytes = await controller.toPngBytes();
                        if (bytes != null && context.mounted) {
                          Navigator.of(context).pop(bytes);
                        }
                      },
                      child: const Text('Use this'),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
