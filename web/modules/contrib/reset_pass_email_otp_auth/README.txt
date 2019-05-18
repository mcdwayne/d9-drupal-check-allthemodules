Reset Password Email OTP Auth

**Introductory**

This module helps in achieving Two-factor authentication (2FA) by sending and validation OTP via email. This process is often referred to as two-step verification, which enables a security process in which the user provides two authentication factors to verify they are who they say they are.

This will enhance the authentication process of reset password workflow by adding email OTP verification.

**How it will work?**

- Process will begin from resetting password. Once user has initiated password reset workflow he/she will receive two email one is reset password email and other is Email OTP, which will contain six digit verification code.
- After clicking on reset password link user has to add its Email OTP into provided OTP verification text-field. Once provided it will be verified to the OTP saved for current user. If provided OTP has not expired and is validated as correct OTP only then user password will be reset else it will be prompt "Either OTP has expired or invalid OTP provided.".

**Backend Configuration**

 User should be able to set following configurations:
 - Should have ability to enable or disable the functionality.
 - Should have ability to set subject and message of the email to be sent.
 - Should have ability to set OTP expiration time.
 - User should have capability to reset OTP from reset screen itself.
