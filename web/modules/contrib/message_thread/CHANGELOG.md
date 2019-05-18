8.x-1.0-alpha2
Hide private messages tab
Disable to field in message

8.x-1.0-alpha3
Fix user page problem on install: https://www.drupal.org/node/2906376#comment-12245045

8.x-1.0-alpha4
Enable permissions for conversation tab outside of admin

8.x-1.0-alpha5
Fix access to conversation view

8.x-1.0-alpha6
Test and ensure permissions for users are correct
Add suitable breadcrumb for navigation
Modify contextual dropdowns for proper access

8.x-1.0-alpha7
Remove reference to MessagePrivateAccessControlHandler which doesn't exist yet

8.x-1.0-alpha8
Add route check on message_ui edit links
Add cache context to breadcrumbs
Add message history functionality

8.x-1.0-alpha9
Fix thread delete function

8.x-1.0-alpha10
Make the threads control area tidier adding tabs and improving workflow

8.x-1.0-alpha11
Fix thread urls and redirects

8.x-1.0-alpha12
Refine tabs and access rules

8.x-1.0-beta1
Add tests along with corrections to scripts

8.x-1.0-beta2
Remove dpm() in hook_token_info()

8.x-1.0-beta3
Fix access control handler: https://www.drupal.org/project/message_thread/issues/2988298
Improve conversation view to properly filter threads
Fix form error: https://www.drupal.org/project/message_thread/issues/2988297
Breadcrumb error: https://www.drupal.org/project/message_thread/issues/2986743

8.x-1.0-beta4
Add reply form to display

8.x-1.0-beta5
Add template file missed from last commit

8.x-1.0-beta6
Fix breadcrumb error when message not in thread https://www.drupal.org/project/message_thread/issues/2986743
Improve reply form https://www.drupal.org/project/message_thread/issues/2997062
Fix reply submit redirect https://www.drupal.org/project/message_thread/issues/2992929
Improve views fields https://www.drupal.org/project/message_thread/issues/2992948
Remove reference to non existen field https://www.drupal.org/project/message_thread/issues/2997107
Create / fix message thread overview page https://www.drupal.org/project/message_thread/issues/2996360

8.x-1.0-beta7
Apply configurable display fields: https://www.drupal.org/project/message_thread/issues/2998013

8.x-1.0-beta7
Thread label Caps https://www.drupal.org/project/message_thread/issues/3006190
Dependency namespacing https://www.drupal.org/project/message_thread/issues/3003252
Conversation action link https://www.drupal.org/project/message_thread/issues/3003037
Core version in .info file https://www.drupal.org/project/message_thread/issues/3002777
Readme layout https://www.drupal.org/project/message_thread/issues/2971498
Add travis script and fix drupal standards

TodDo
Delete messages in thread when thread is deleted
