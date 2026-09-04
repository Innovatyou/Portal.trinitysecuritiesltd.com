import 'dart:io';import 'package:file_picker/file_picker.dart';import 'package:flutter/material.dart';import 'package:get/get.dart';import 'package:realise/data/controller/operations/operations_controller.dart';import 'package:realise/data/model/operations/operations_models.dart';import 'package:realise/view/components/operations/depth_card.dart';
class OperationsCreateScreen extends StatefulWidget{const OperationsCreateScreen({super.key});@override State<OperationsCreateScreen> createState()=>_S();}
class _S extends State<OperationsCreateScreen>{OperationsWorkflow? flow;final title=TextEditingController();String priority='normal';final values=<String,TextEditingController>{};final pickedFiles=<File>[];
  Future<void> _pickFile() async {
    final result = await FilePicker.platform.pickFiles();
    final path = result?.files.single.path;
    if (path == null) return;
    setState(() => pickedFiles.add(File(path)));
  }
  Future<void> _submit(OperationsController c) async {
    if (title.text.trim().isEmpty) return;
    final id = await c.create(flow!, title.text.trim(), priority, values.map((k, v) => MapEntry(k, v.text)));
    if (id == null) return;
    if (pickedFiles.isNotEmpty) {
      await c.loadDetail(id);
      for (final file in pickedFiles) {
        await c.uploadAttachment(file);
      }
    }
    if (mounted) Get.back();
  }
  @override Widget build(BuildContext context)=>Scaffold(appBar:AppBar(title:const Text('New request')),body:GetBuilder<OperationsController>(builder:(c)=>ListView(padding:const EdgeInsets.all(18),children:[const Text('Start a workflow',style:TextStyle(fontSize:25,fontWeight:FontWeight.w900)),const Text('Choose a request type and provide the required information.',style:TextStyle(color:Colors.black54)),const SizedBox(height:18),DropdownButtonFormField<OperationsWorkflow>(value:flow,decoration:const InputDecoration(labelText:'Workflow'),items:c.workflowList.map((w)=>DropdownMenuItem(value:w,child:Text(w.name))).toList(),onChanged:(v)=>setState((){flow=v;values.clear();for(final f in v?.fields??[])values['${f['field_key']}']=TextEditingController();})),if(flow!=null)...[const SizedBox(height:14),DepthCard(accent:Theme.of(context).primaryColor,child:Column(children:[TextField(controller:title,decoration:const InputDecoration(labelText:'Request title')),const SizedBox(height:12),DropdownButtonFormField<String>(value:priority,decoration:const InputDecoration(labelText:'Priority'),items:['low','normal','high','urgent'].map((x)=>DropdownMenuItem(value:x,child:Text(x))).toList(),onChanged:(v)=>setState(()=>priority=v??'normal')),const SizedBox(height:12),...flow!.fields.map((f)=>Padding(padding:const EdgeInsets.only(bottom:12),child:_field(f))),Align(alignment:Alignment.centerLeft,child:TextButton.icon(onPressed:_pickFile,icon:const Icon(Icons.attach_file),label:const Text('Attach a file'))),...pickedFiles.map((f)=>ListTile(dense:true,contentPadding:EdgeInsets.zero,leading:const Icon(Icons.insert_drive_file_outlined),title:Text(f.path.split(Platform.pathSeparator).last,overflow:TextOverflow.ellipsis),trailing:IconButton(icon:const Icon(Icons.close,size:18),onPressed:()=>setState(()=>pickedFiles.remove(f))))),const SizedBox(height:6),SizedBox(width:double.infinity,child:FilledButton.icon(onPressed:c.submitting?null:()=>_submit(c),icon:const Icon(Icons.rocket_launch_rounded),label:Text(c.submitting?'Submitting…':'Submit request')))]))] ])));
  Widget _field(Map<String,dynamic> f){final key='${f['field_key']}',config=f['config_json'] is String?f['config_json']:'';return TextField(controller:values[key],keyboardType:['number','currency'].contains(f['field_type'])?TextInputType.number:TextInputType.text,maxLines:f['field_type']=='textarea'?4:1,decoration:InputDecoration(labelText:'${f['label']}${'${f['is_required']}'=='1'?' *':''}',helperText:config.toString().contains('help')?null:null));}
}
