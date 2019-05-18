
Installation
--------------
1. Put the bestreply directory in your site modules directory.  
   See http://drupal.org/node/70151 for tips on where to
   install contributed modules.
2. Enable bestreply via admin/modules.
	
Settings page
--------------
admin/config/content/bestreply/list
Lists the best replys, author, who they were marked by,
and when they were marked.

admin/config/content/bestreply
Set the name, text you wish to use for best reply links.  
Check the node types you want to be able to mark a comment as the best reply.


Access Control  
---------------
view bestreply: User can see link to view the best reply.
mark bestreply: User can mark best reply if they are the node author.
clear bestreply: User can clear best reply if they are the node author
moderate bestreply: User can mark, change and clear best reply at any time.
administer bestreply: User can change admin settings for best reply.
