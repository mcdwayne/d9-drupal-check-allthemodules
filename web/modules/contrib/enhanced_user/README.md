# Enhanced user

Provides enhanced user features for core user module.

# Features

- Add base fields to `user` entity.
  - `nick_name` 
  - `nick_name` 
  - `birthday`

- Add 4 REST plugins for user data interaction.
  - `enhanced_user_reset_password` 修改用户密码
  - `enhanced_user_upload_avatar` 修改用户头像
  - `enhanced_user_user_info` 读取用户资料
  - `enhanced_user_user_profile` 修改用户个人信息，包括nick_name\nick_name\birthday\email

- Add a service named `enhanced_user.user_creator`, for creating new user with username and email.
