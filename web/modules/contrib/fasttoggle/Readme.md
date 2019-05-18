Fasttoggle Readme.

Fasttoggle is a Drupal module that simplifies and speeds the task of site
administration providing ajax toggling of both administrative settings and
content values.

The Drupal 8 release of Fasttoggle is a complete rewrite.

It implements Fasttoggle support primarily via field formatters for Boolean and
List fields which means you can now easily apply Fasttoggle to fields in views
as well as content display modes.

This is an initial alpha release of Fasttoggle, which doesn't yet implement all
of the intended functionality. At the moment, you can:

- Enable and disable the toggling of user status and node published, sticky and
  promoted functions via the sitewide settings page (admin/config/system/fasttoggle)

- Modify the view for administering content (admin/structure/views/view/content)
  or the view for administering people (admin/structure/views/view/user_admin_people)
  and change the Formatter for a field such as "Content: Published (Status)" or
  "User: User status (Status)" to Fasttoggle. After saving the view, you'll then
  be able to visit /admin/content or /admin/people as appropriate and toggle the
  field with a single click.

- Modify the display settings for existing boolean and list fields to enable
  fasttoggling (eg /admin/structure/types/manage/article/display). Change the
  format for the appropriate field from Boolean (for example) to Fasttoggle and
  save.

Things not yet implemented:

- The label type sitewide setting is currently unimplemented.
- The comment related settings are currently unimplemented.
- No block has yet been implemented for toggling published etc on the node display.
