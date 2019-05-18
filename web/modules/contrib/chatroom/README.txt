CHATROOM
=========

Chatroom allows site administrators to create chatrooms that users can join and
talk to each other in real time. The chatroom's permissions can be set to
restrict who can access the chatroom and post messages. Chatrooms are entities,
which means they integrate with Views and other entity specific features.

INSTALLATION
------------

1. Download the module from http://drupal.org/project/chatroom and save it to
   your modules folder.
2. Ensure you have enabled and configured Chatroom module's dependency, Node.js
   integration module (http://drupal.org/project/nodejs).
3. Review Node.js module's configuration and ensure that Node.js is enabled on
   chatroom pages (chatroom/*).
4. Enable the module at admin/modules.

USAGE
-----

1. Go to Content/Chatrooms (admin/content/chatrooms) to create a new chatroom.
2. When creating a new chatroom, specify which roles should be able to view the
   chatroom and post new messages.
3. Visit the new chatroom at chatroom/[chatroom_id], and start chatting!
