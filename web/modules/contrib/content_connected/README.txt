The Content connected module allows the user to know
whether content is connected with some other content.

New tab(Content connected) is added in node pages to view content 
connection with other contents. This can be restricted 
by permissions(See below).
 
Content connected is also shown when single node is about
to be deleted.

Content connected block is also provided so that user
can place it anywhere. It required node context so 
block needs to be placed in node pages.

Content connection based on entity reference field
,link field and long text field for any content type.

Connected content is listed in a table where
it can be identified how it is connected
(Reference field/Link field/long text field).

Installation
------------
Standard module installation applies. You need to set
the permission to view it in delete confirmation page.

Permissions
-----------
Accessibility of the content connected table is controlled
by "access content connected" in node delete form.

View of the content connected page is
accessed by "view content connected page" in node view page.

Administer content connected settings is
accessed by "administer content connected settings" in configuration.


Configuration
-------------
Configuration page(admin/config/content/content-connected-settings)
where you can excludes fields which you do not want to search
any connection with content.

Limitation
-----------
If you want to delete multiple nodes then 
no content connected table is showing.

It is only found connection with node entity, 
no others entities(eg: users, terms) are not 
included in search.
