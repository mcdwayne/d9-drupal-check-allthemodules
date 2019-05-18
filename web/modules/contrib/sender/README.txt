TABLE OF CONTENTS
-----------------

 * Introduction
 * Requirements
 * Installation
 * Basic usage
 * Theme hook suggestions
 * More information
 * Issues


INTRODUCTION
------------

Sender is a module to create customizable messages that can be sent by email 
or other methods to users.

This module offers an API to allow developers to send customizable messages 
to users. The module comes with a plugin to send messages by e-mail 
out-of-the-box, but plugins for other delivery methods can be added.

The messages can be customized by authorized users through a form that allows 
tokens to be used. Moreover, custom templates for messages may be provided by 
modules or themes.


REQUIREMENTS
------------

The following contrib modules are required by Sender:

 * Entity API (https://drupal.org/project/entity)
 * Token (https://drupal.org/project/token)

Please refer to each of these projects' documentation for instructions on how 
to install each of them.


INSTALLATION
------------

With the required modules added to your Drupal installation, just add the 
Sender's folder under the Drupal's modules folder and enable it in the 
"admin/modules" page as usual.


CONFIGURATION
-------------

The module's settings can be found at Configuration > System > Sender 
(at path admin/config/system/sender).

The module settings page has also a tab to manage messages located at the path
admin/config/system/sender/messages.

Specific permissions must be provided to manage module settings and perform 
operations on messages (create, view, update and delete).


BASIC USAGE
-----------

Instantiating the service:

$sender = \Drupal::service('sender.sender');


Sending a message to a single user:

$user = User::load(1);
$sender->send('my_message_id', $user);


Sending a message to multiple users:

$users = [ User::load(1), User::load(2), User::load(3) ];
$sender->send('my_message_id', $users);


Sending a message with token replacement data:

$users = [ User::load(1), User::load(2), User::load(3) ];
$data = [ 'node' => Node::load(1) ];
$sender->send('my_message_id', $users, $data);


THEME HOOK SUGGESTIONS
----------------------

A module may provide a custom template named "sender-message.html.twig" that can 
be used for all messages.

More granular customization may be achieved by providing one or more of the 
following templates (from higher to lower priority):

 * sender-message--MESSAGE_ID-METHOD_ID.html.twig
 Replace MESSAGE_ID with the message's ID and METHOD_ID by the method's ID, both 
 with underscores replaced by hyphens. This template will be used only when 
 sending the specified message with the specified method.

 * sender-message--MESSAGE_ID.html.twig
 This template allows providing a template for a specific message by replacing 
 MESSAGE_ID by the message's ID with underscores replaced by hyphens.

 * sender-message--METHOD_ID.html.twig
 Replace MESSAGE_ID by the method's ID with underscores replaced by hyphens. 
 This template will be used whenever a message is sent by the method with the 
 specified ID.

All of the templates above have higher priority than the default 
"sender-message.html.twig" template.


MORE INFORMATION
----------------

For more detailed documentation, please refer to the online manuals:

https://www.drupal.org/docs/8/modules/sender


ISSUES
------

Issues should be reported at https://www.drupal.org/project/issues/sender.
