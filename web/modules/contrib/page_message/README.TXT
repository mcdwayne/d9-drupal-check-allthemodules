

CONTENTS OF THIS FILE
---------------------
* Introduction
* Requirements
* Installation
* Configuration
* Admin pages only
* Acknowledgements
* Maintainers

INTRODUCTION
------------
 * Page Message module allows administrators to create messages that appear
   on specific pages. Messages might include rationales, warnings,
   relationships, ar anything else that might be helpful to page users. The
   module is envisioned to be of use for admin pages, but any page on the
   site can get a message.

 * Messages are placed on the page with drupal_set_message(). This is the same
   method used by Drupal for warnings and informational messages. Page Message
   messages have their own styling to differentiate them from Drupal messages.

 * There are two components for each page message.
    1. The page URL. This is the 'clean' relative URL for the page you want to
    show a message on. URL aliases don't work in this field. A message defined
    for a clean URL, however, will show up whether you visit it using the URL
    alias or the clean url.
    2. The message. In general, messages should be brief.

 * Examples:
      URL: admin/structure/block/manage/block/1/configure
      Message: Don't modify this block without consulting Jan.

      URL: admin/structure/types/manage/product-news
      Message: See specification for this content type in Jira ticket PM-3456.

      URL: admin/structure/views/view/backlinks/edit
      Message: Note: This view's query is altered in modules/alter/alter.module

 * The message is shown on every visit to the page. If you can access the
   page, you see the message.


REQUIREMENTS
------------
 * Page Message has no requirements.


INSTALLATION
------------
 * Install as you would normally install a contributed drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-7
   for further information.

 * The installation process creates a page_message table in the database and
   sets some page_message site variables.

 * Permissions: Enable permissions for "Administer Page Messages"
   at Home » Administration » People >> Permissions.
   Note: Viewing Page Message messages displayed on pages has no permissions.
   If you can access a page, you can read any Page Message message defined for
   it.

 * CSS: page_message.css sets some styling for its messages. You can override the
   styling as described at https://www.drupal.org/node/263967


CONFIGURATION
-------------
 * View messages you have created at Home » Administration » Configuration »
   User interface >> Page Message

 * Add, edit and delete messages at Home » Administration » Configuration »
   User interface >> Page Message >> Add/edit

 * Configure Page Message settings at Home » Administration » Configuration »
   User interface » Page Message >> Settings. Currently there is only one
   setting - "Allow messages for admin pages only". The default setting is to
   have the box checked, so that Page messages are only shown on paths that
   start with '/admin'.

ADMIN PAGES ONLY
----------------
Unchecking this box:
- causes an extra database lookup on non-admin pages.
- prevents the page from being cached, since Drupal sends a no-cache header
  when there is a message on the page.

Note: You can create messages for non-admin pages regardless of this
 setting. Then the display of those messages can be turned on and off by
 changing the setting. 

ACKNOWLEDGEMENTS
----------------
Thanks to these sources for providing starting points for the conversion of
the Drupal 7 version to Drupal 8:

Examples for Developers
    https://www.drupal.org/project/examples

Responding to Events in Drupal 8:
    https://drupalize.me/blog/201502/responding-events-drupal-8

MAINTAINERS
-----------
Current maintainers:
* Joe Casey (joe casey) - https://drupal.org/user/823758
