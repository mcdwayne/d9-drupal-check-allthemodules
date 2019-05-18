Riddle - Quizzes, lists polls and more
======================================

Enabling Riddles in the Text Editor:
------------------------------------
** note: Riddles only show when in FULL HTML editing mode **

1. Prepare the module by unzipping in /modules/riddle_marketplace directory
2. Goto Riddle.com and get a token from the Account->Token page 
( you may need to reset to get the first token ). 
To get a free riddle basic account use this voucher "THUNDER_3eX4_freebasic".
3. Copy the token into 
/modules/riddle_marketplace/config/install/riddle_marketplace.settings.yml
4. Goto Drupal admin and enable the module in the Admin/Extend menu.
5. Enable the CKeditor plugin in the Admin/Content Authoring menu.
6. The riddle_marketplace editor is now available in the content screen:
	/admin/content
7. And as a dropdown button in the CKEditor 
(riddles only show in FULL HTML editing mode).

Enabling Riddles in the Paragraphs Editor:
------------------------------------------
1. Prepare the module as above.
2. Goto Drupal admin and enable the Riddle Paragraphs 
module in the Admin/Extend menu.
3. Create a new paragraphed article and add riddle_marketplace paragraph type.
4. Enter the url of the riddle you want to embed. Note it will look like this 
"http://riddle.com/a/?????"
5. To find url of a riddle use the riddle editor in Drupal8 ( see above ). 
When editing a riddle click on the publish button to view the url. 
