import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';
import 'package:realise/core/helper/shared_preference_helper.dart';
import 'package:realise/data/model/global/response_model/response_model.dart';
import 'package:realise/data/model/profile/profile_model.dart';
import 'package:realise/data/repo/profile/profile_repo.dart';
import 'package:realise/view/components/snack_bar/show_custom_snackbar.dart';

class ProfileController extends GetxController {
  ProfileRepo profileRepo;
  ProfileController({required this.profileRepo});

  bool isLoading = true;
  bool isUploadingAvatar = false;
  ProfileModel profileModel = ProfileModel();

  Future<void> initialData({bool shouldLoad = true}) async {
    isLoading = shouldLoad ? true : false;
    update();

    await loadData();
    isLoading = false;
    update();
  }

  Future<void> loadData() async {
    ResponseModel responseModel = await profileRepo.getProfileData();
    if (responseModel.statusCode == 200) {
      profileModel =
          ProfileModel.fromJson(jsonDecode(responseModel.responseJson));
    }
    // A failure here (e.g. "account_disabled" for a staff login, which
    // this endpoint doesn't support) is expected, not an error to surface -
    // ProfileScreen already falls back to the name/email/avatar saved at
    // login time in that case.

    isLoading = false;
    update();
  }

  Future<void> pickAndUploadAvatar() async {
    final picked =
        await ImagePicker().pickImage(source: ImageSource.gallery, imageQuality: 85);
    if (picked == null) return;

    isUploadingAvatar = true;
    update();

    final result = await profileRepo.uploadAvatar(File(picked.path));
    if (result.statusCode == 200) {
      final decoded = jsonDecode(result.responseJson);
      if (decoded['success'] == true) {
        final avatar = decoded['data']?['avatar']?.toString() ?? '';
        await profileRepo.apiClient.sharedPreferences
            .setString(SharedPreferenceHelper.userAvatarKey, avatar);
        CustomSnackBar.success(successList: ['Profile picture updated']);
      } else {
        CustomSnackBar.error(
            errorList: [decoded['message']?.toString() ?? 'Upload failed']);
      }
    } else {
      CustomSnackBar.error(errorList: [result.message]);
    }

    isUploadingAvatar = false;
    update();
  }
}
