
About
-----

The Simple Twilio module is used for sent text messages using the
Twilio SMS service.

OTP Verification
-----------------

Using this module all authenticated users can save their mobile number in site.
To avoid invalid or non-authenticate mobile numbers, the module will send an OTP
to given number. The number is only saved after completing the OTP verification.

Sent Message to User
--------------------

We can sent message for all registered users by calling the function the
simple_twilio_send_sms_user(UID, MESSAGE)) with the unique id of user and
message for the user.

Example:
simple_twilio_send_sms(10, 'Hello Arunkumar!');

Sent Message to Number
----------------------

We can sent message for any valid mobile number by calling the function
simple_twilio_send_sms(NUMBER, MESSAGE) with the valid mobile number
and message.

Example:
simple_twilio_send_sms(918098641508, 'Hello Arunkumar!');
