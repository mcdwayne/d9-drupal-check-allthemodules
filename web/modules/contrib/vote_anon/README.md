Vote Anonymous for Drupal 8
=================

Contents:
 * Introduction
 * History and Maintainers
 * Installation
 * Configuration

Introduction
------------

The Vote Anonymous module allows you to set up the voting feature for anonymous users on the node entity type. Sometimes we have the requirement that anonymous user can vote only once among the nodes listing or only one vote per node. Using this module we can manage voting functionality very easily. There is an admin setting page where we can configure messages, cookie, content type & other settings. On the basis of the user cookie, we can control the voting entry in the database.

If session/cookie already exist then module display a message that 'You have already submitted!'. We can also disable voting link from admin configuration if the user has already voted.

Voting can be configured on a global level or individual node level. 

For global level voting, keep unchecking "Single Node Voting". Now a user cannot vote on any node if already voted even a single node of select content type.

For node level voting, keep checked "Single Node Voting". Now a user can vote on the node if not voted yet.

History and Maintainers
-----------------------

There are other contrib modules available which also provide the voting feature but this one is easy to implement on node listing as well as on node page, that's why I have developed it & I will keep improving it.

Current Vote Anonymous Maintainers:
 * Devendra Kumar Mishra

Installation
------------

Vote Anonymous 8.x can be installed easily

1. Download the module to your DRUPAL_ROOT/modules/contribe/ directory, or where ever you
install contrib modules on your site.
2. Go to Admin > Extend and enable the module.

Configuration
-------------

Configuration of Vote Anonymous module.

1. Go to Admin > Configuration > Vote Configuration Form.
2. Fill the message which trigger after voting
3. Put some name for Voting cookie.
4. Select content type.
5. Click "Save Configuration".
6. Go to node page of selected content type, there we will see a link 'Vote for <node title>'. When you click on that link, it will say 'Thank you for your vote!'.
7. Admin can also access vote count page at /vote-count. This page is created using view so we can add remove fields here as per requirement.
