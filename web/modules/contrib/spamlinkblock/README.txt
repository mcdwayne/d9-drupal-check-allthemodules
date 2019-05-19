INTRODUCTION
------------
SpamLinkBlock is a simple module which blocks form submissions
containing links and/or stop words. If visitor tries to post
a link or text containing selected stop words, he/she will get
a message explaining why the submission has been blocked.

REQUIREMENTS
------------
No special requirements

INSTALLATION
------------
Install as you would normally install a contributed Drupal module.
- Upload the module and enable it in Drupal or use drush.

CONFIGURATION
-------------
Configure module to your specifications You can visit the
configuration page directly at /admin/config/content/spamlinkblock.

CUSTOMIZATION
-------------
The default warning for blocked link is:
"Links in form submissions are not allowed. This is an anti-spam measure."

The default warning for blocked stop word is:
"Usage of "@stopword" word in form submissions is not allowed.
This is an anti-spam measure."

You can customize these warnings using the translation features of Drupal.
