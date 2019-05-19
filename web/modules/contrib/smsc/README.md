About module
=====
Module integrates the [SMSC library](https://github.com/awd-studio/smsc) into Drupal.


About SMSC library
-----
The library allows you to use the following services:

- https://smsc.ua
- https://smsc.ru
- https://smsc.kz
- https://smsc.tj
- https://smscentre.com

You can send any messages messages (SMS, MMS, Viber, E-mail, Ping / HLR / Flash / Push, etc).

Control your balance and administer any settings (Sender IDs, Phone lists, Contacts, Jobs actions, Subclients, and any action from an [API](http://smsc.ua/api/http)).


Features
-----
- Simple sending from form.
- Automatic sending from Rules.
- Programmatically sending.


Dependencies
-----
- PHP 5.6+
- [Composer](https://getcomposer.org/download/) *(Drupal 8 only)*
- [SMSC library](https://github.com/awd-studio/smsc)
- [Libraries API](https://www.drupal.org/project/libraries) 2.x module *(Drupal 7 only)*
- [X Autoload](https://www.drupal.org/project/xautoload) module *(Drupal 7 only)*
- [Rules](https://www.drupal.org/project/rules) module *(optional, if you need to automate actions)*


Installation
=====

Drupal 8
-----
**Drupal 8 only supports installation with [Composer](https://getcomposer.org/download/)!**

Just run:
```sh
composer require drupal/smsc
```
After installation, enable the SMSC module.


Drupal 7
-----
1. Make sure that you have downloaded and installed all dependencies of the module ([Libraries API](https://www.drupal.org/project/libraries) and [X Autoload](https://www.drupal.org/project/xautoload)). 
2. Download the lasted [SMSC library](https://github.com/awd-studio/smsc/archive/master.zip).
3. Unzip the file and rename the folder to "smsc".
4. Put the folder in a libraries directory (sites/all/libraries)
   - Drush users can use the command `drush smsc-dl`
5. Enable dependencies and the SMSC modules.


Usage
======

Set up the SMSC module
-----
After installation, you need to settings up your SMSC account.
(If you do not have an account - create it in a suitable service.)

Go to `admin/config/smsc/settings` and fill your data.


Simple sending messages
-----
When your account settings are saved, you can send an simple messages from special form. 
The form is located at `admin/config/smsc/send` page.

Just type your message and recipient phone\[s\].


Automatic sending from Rules
-----
- Ensure you have installed [Rules](https://www.drupal.org/project/rules) module.
- Create new rule / component (or use an existing one). Configure it as you need it.
- Add action "Send SMSC message" from "SMSC" group, and fill with the message data. 


Programmatically sending.
-----
If you need to use SMSC sending in your custom module, just add usage namespaces:
```php
use Drupal\smsc\Smsc;
```
and call a static method that sends your data:
```php
// Single phone, or coma-separated phones list
$phones = '380001234567'; // International telephone number

// Text message
$message = 'My message';

// Options array. See http://smsc.ua/api/http/
$options = [
  'translit' => TRUE,
];

// Send message
DrupalSmsc::sendSms($phones, $message, $options);
```
