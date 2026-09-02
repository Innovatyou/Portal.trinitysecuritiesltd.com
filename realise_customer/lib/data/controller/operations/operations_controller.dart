import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:realise/data/model/operations/operations_models.dart';
import 'package:realise/data/repo/operations/operations_repo.dart';
import 'package:realise/view/components/snack_bar/show_custom_snackbar.dart';

class OperationsController extends GetxController {
 final OperationsRepo repo; OperationsController({required this.repo});
 bool loading=false,submitting=false; Map<String,dynamic> stats={}; List<OperationsRequest> mine=[],inbox=[];List<OperationsWorkflow> workflowList=[];OperationsDetail? detailData;
 Future<void> load()async{loading=true;update();await Future.wait([loadStats(),loadMine(),loadInbox()]);loading=false;update();}
 Future<void> loadStats()async{final r=await repo.dashboard();if(r.isSuccess)stats=Map<String,dynamic>.from(jsonDecode(r.responseJson)['data']??{});}
 Future<void> loadMine()async{final r=await repo.requests();if(r.isSuccess)mine=(jsonDecode(r.responseJson)['data'] as List? ?? []).map((e)=>OperationsRequest.fromJson(Map<String,dynamic>.from(e))).toList();}
 Future<void> loadInbox()async{final r=await repo.pending();if(r.isSuccess)inbox=(jsonDecode(r.responseJson)['data'] as List? ?? []).map((e)=>OperationsRequest.fromJson(Map<String,dynamic>.from(e))).toList();}
 Future<void> loadWorkflows()async{final r=await repo.workflows();if(r.isSuccess)workflowList=(jsonDecode(r.responseJson)['data'] as List? ?? []).map((e)=>OperationsWorkflow.fromJson(Map<String,dynamic>.from(e))).toList();update();}
 Future<void> loadDetail(int id)async{loading=true;update();final r=await repo.detail(id);if(r.isSuccess)detailData=OperationsDetail(Map<String,dynamic>.from(jsonDecode(r.responseJson)['data']));else CustomSnackBar.error(errorList:[r.message]);loading=false;update();}
 Future<bool> create(OperationsWorkflow flow,String title,String priority,Map<String,String> values)async{submitting=true;update();final data=<String,dynamic>{'workflow_id':'${flow.id}','title':title,'priority':priority};values.forEach((k,v)=>data['field_$k']=v);final r=await repo.create(data);submitting=false;update();if(r.isSuccess&&jsonDecode(r.responseJson)['success']==true){await load();return true;}CustomSnackBar.error(errorList:[jsonDecode(r.responseJson)['message']?.toString()??r.message]);return false;}
 Future<void> decide(String decision,String comment)async{final d=detailData;if(d==null)return;submitting=true;update();final r=await repo.decide(d.request.id,{'stage_instance_id':'${d.raw['current_stage_instance_id']}','lock_version':'${d.raw['stage_lock_version']}','decision':decision,'comment':comment});submitting=false;if(r.isSuccess&&jsonDecode(r.responseJson)['success']==true){CustomSnackBar.success(successList:['Decision recorded']);await loadDetail(d.request.id);await loadInbox();}else CustomSnackBar.error(errorList:[jsonDecode(r.responseJson)['message']?.toString()??r.message]);update();}
 Future<void> addComment(String value)async{if(detailData==null||value.trim().isEmpty)return;final r=await repo.comment(detailData!.request.id,value.trim());if(r.isSuccess)await loadDetail(detailData!.request.id);update();}
}
