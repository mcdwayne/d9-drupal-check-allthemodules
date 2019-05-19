## TWO-FACTOR LOGIN (TFL)


#INTRODUCTION
------------

Two-factor login for Drupal sites. As you know, Drupal provides login 
authentication via an username and a password while TFL module adds a 
second step of login authentication with a check for something as a 
OTP (One-time password) sent to your mobile phone.

TFL is a module for providing two-factor login for your Drupal site. TFL 
handles the work of integrating with Drupal, providing flexible and well
tested configurations to enable your choice of two-factor login solutions
One Time Passwords (OTP) via SMS-delivery or VOICE calling to your mobile 
phone.

TFL needs https://2factor.in API key to work this module. https://2factor.in 
does not provide free OTP service but you can create your trial account and
get free 50 OTP service with SMS-delivery and VOICE-calling both.

Read more about the features and use of TFL at its Drupal.org project page at
https://drupal.org/project/tfl


#GENERATE API KEY 
------------------

* You need to create an account in https://2factor.in 
* Follow instructions to verify your created account after that you have to
  login. 
* You can see the API KEY on profile page. Right now (August 2018) 
  https://2factor.in provides free (50 SMS/VOICE) OTP services for trial
  account only. 
* For more information you need to go through https://2factor.in.  
  

#REQUIREMENTS:
--------------
* Telephone module should be enabled.
* Need API Key from https://2factor.in to complete tfl configurations.


#INSTALLATION:
--------------
None.


#CONFIGURATION:
---------------

Now you should be ready to configure the TFA module.

* Install the TFL module
* Visit the TFL module's configuration page.
    * Enable CLEAR cache so that your cached previous login page would be clear.
	* Copy API key from https://2factor.in and paste in your API key field.
	* Enable TFL
	* Select message type (SMS or VOICE) as your choice.
    * and Save.
* Now create a required field for mobile number in your account settings from 
  manage fields. Make sure that this field's machine name would be 
  field_mobile_number.   
  
After that your two factor login will be completely ready. 


#MAINTAINER
------------
 
* Piyush Rai (Piyush_Rai) - https://www.drupal.org/u/Piyush_Rai 
