CONTENTS OF THIS FILE
---------------------
 
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers
 
INTRODUCTION
------------

The Message Thread module enables messages to be grouped into threads or
conversations. For instance, it is used with the Message Private module to
create conversations between individuals and groups of individuals.


 * For a full description of the module visit:
   https://www.drupal.org/project/message_thread

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/message_thread


REQUIREMENTS
------------

This module requires the following outside of Drupal core.
 
 Message stack modules including:
 * Message - https://www.drupal.org/project/message
 * Message Private - https://www.drupal.org/project/message_private


INSTALLATION
------------

 * Install the Message Thread module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.
 
 
CONFIGURATION
-------------
 
-Install module as normal or using composer or drush
    1. Navigate to Administration > Extend and enable the module and its
       dependencies.
    2. Along with the Private Message module, the Message Thread module creates
       a default message thread called "Conversation".
    3. Navigate to Administration > People > [User to edit] > Conversations and
       start a new conversation.
    4. Add the conversation's participants and select "Create".
    5. Once the conversation is created, send a message. All messages sent
       within that conversation are shown on the conversation page.
    6. Set permissions to allow roles to create message threads and private
       message.
 
Features:
 * The module keeps track of who is (and was) in the conversation.
 * The module enables the user to add fields such as title to a conversation.
 * The module enables an individual to have simultaneous conversations with
   different people and keep the conversations separate in the inbox.
 
MAINTAINERS
-----------
 
 * Kent Shelley (New Zeal) - https://www.drupal.org/u/new-zeal
