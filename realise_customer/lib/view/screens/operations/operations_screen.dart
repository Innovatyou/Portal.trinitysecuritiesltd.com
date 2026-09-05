import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:realise/core/route/route.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/data/controller/operations/operations_controller.dart';
import 'package:realise/data/repo/operations/operations_repo.dart';
import 'package:realise/data/services/api_service.dart';
import 'package:realise/view/components/operations/depth_card.dart';

class OperationsScreen extends StatefulWidget {
  const OperationsScreen({super.key});
  @override State<OperationsScreen> createState() => _OperationsState();
}

class _OperationsState extends State<OperationsScreen> {
  late final OperationsController controller;
  int tab = 0;
  // Set when a stat card is tapped (e.g. "Completed") so My requests shows
  // just that subset; cleared when the bottom nav's own tabs are used
  // directly, since those should show everything.
  String? statusFilter;
  @override void initState() {
    super.initState();
    controller = Get.put(OperationsController(repo: OperationsRepo(api: Get.find<ApiClient>())));
    // load() calls update() immediately (loading=true) before its first
    // await - calling it synchronously here, during initState's build
    // phase, threw "setState() called during build" (reproduced directly:
    // the four API calls all completed fine per the debug log, but the
    // widget's rebuild tracking was left broken by that error, so the
    // loading spinner never went away). Every other screen in this app
    // already defers its initial load the same way.
    WidgetsBinding.instance.addPostFrameCallback((_) => controller.load());
  }
  @override Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Operations'), actions: [IconButton(onPressed: controller.load, icon: const Icon(Icons.refresh))]),
    floatingActionButton: FloatingActionButton.extended(onPressed: () async { await controller.loadWorkflows(); Get.toNamed(RouteHelper.operationsCreateScreen); }, icon: const Icon(Icons.add), label: const Text('New request')),
    bottomNavigationBar: NavigationBar(selectedIndex: tab, onDestinationSelected: (v) => setState(() { tab = v; statusFilter = null; }), destinations: const [
      NavigationDestination(icon: Icon(Icons.dashboard_outlined), label: 'Overview'),
      NavigationDestination(icon: Icon(Icons.receipt_long_outlined), label: 'My requests'),
      NavigationDestination(icon: Icon(Icons.approval_outlined), label: 'Approvals'),
    ]),
    body: GetBuilder<OperationsController>(builder: (state) {
      if (state.loading) return const Center(child: CircularProgressIndicator());
      final items = tab == 2
          ? state.inbox
          : (statusFilter == null ? state.mine : state.mine.where((r) => r.status == statusFilter).toList());
      void goTo(int newTab, String? filter) => setState(() { tab = newTab; statusFilter = filter; });
      return RefreshIndicator(onRefresh: state.load, child: ListView(padding: const EdgeInsets.all(18), children: [
        if (tab == 0) ...[
          Container(padding: const EdgeInsets.all(24), decoration: BoxDecoration(borderRadius: BorderRadius.circular(30), gradient: const LinearGradient(colors: [Color(0xff182848), Color(0xff3a7bd5), Color(0xff00d2ff)]), boxShadow: const [BoxShadow(color: Color(0x553a7bd5), blurRadius: 30, offset: Offset(0, 16))]), child: const Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Icon(Icons.hub_rounded, color: Colors.white, size: 40), SizedBox(height: 18), Text('Work moves with you', style: TextStyle(color: Colors.white, fontSize: 25, fontWeight: FontWeight.w900)), Text('Submit, review and approve securely from anywhere.', style: TextStyle(color: Colors.white70))])),
          const SizedBox(height: 18),
          GridView.count(crossAxisCount: 2, shrinkWrap: true, physics: const NeverScrollableScrollPhysics(), childAspectRatio: 1.35, children: [
            _metric('My requests', state.stats['total'], Colors.blue, () => goTo(1, null)),
            _metric('Awaiting me', state.stats['pending_approval'], Colors.orange, () => goTo(2, null)),
            _metric('Completed', state.stats['completed'], Colors.green, () => goTo(1, 'completed')),
            _metric('Returned', state.stats['returned'], Colors.purple, () => goTo(1, 'returned')),
          ]),
        ] else Row(children: [
          Expanded(child: Text(tab == 1 ? (statusFilter == null ? 'My requests' : 'My requests — ${statusFilter![0].toUpperCase()}${statusFilter!.substring(1)}') : 'Approval inbox', style: const TextStyle(fontSize: 25, fontWeight: FontWeight.w900))),
          if (statusFilter != null) TextButton(onPressed: () => setState(() => statusFilter = null), child: const Text('Clear filter')),
        ]),
        const SizedBox(height: 12),
        if (tab != 0 && items.isEmpty) const Padding(padding: EdgeInsets.only(top: 40), child: Center(child: Text('Nothing here.', style: TextStyle(color: Colors.black45)))),
        ...items.take(tab == 0 ? 4 : items.length).map((r) => DepthCard(accent: ColorResources.primaryColor, onTap: () => Get.toNamed(RouteHelper.operationsDetailScreen, arguments: r.id), child: ListTile(contentPadding: EdgeInsets.zero, leading: const Icon(Icons.description, color: ColorResources.primaryColor), title: Text(r.title, style: const TextStyle(fontWeight: FontWeight.w800)), subtitle: Text('${r.number} | ${r.workflow}'), trailing: const Icon(Icons.chevron_right)))),
      ]));
    }),
  );
  Widget _metric(String label, dynamic value, Color color, VoidCallback onTap) => DepthCard(accent: color, onTap: onTap, child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Icon(Icons.auto_awesome, color: color), const Spacer(), Text('${value ?? 0}', style: const TextStyle(fontSize: 25, fontWeight: FontWeight.w900)), Text(label, style: const TextStyle(fontSize: 11))]));
}
