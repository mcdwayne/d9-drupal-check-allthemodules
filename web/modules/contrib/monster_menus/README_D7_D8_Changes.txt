The mm_fake_required form element has been removed. Now that the Form API's #states feature handles elements' "required" attribute, #states should be used instead.

The mm_page_wrapper theme has been removed. Instead, mm_page_wrapper() should be used.

mm_goto() returns a RedirectResponse object. It is incorrect to assume that this function is a dead end now.

The signature of hook_mm_menus_block_shown() has changed to include the block object instead of the delta.

The mm_tree_renderer theme has been replaced with the monster_menus.tree_renderer service and the mm_tree_menu #theme.

mm_redirect_to_node() and mm_redirect_to_mmtid() no longer support the $add parameter. They also return a RedirectResponse object rather than actually redirecting.

The $render_array parameter has been added to mm_content_get_users_in_group(), so that the proper Javascript code can be attached when $see_all is TRUE.

These functions now have an optional $database parameter, which is the database to act upon: mm_content_get_by_nid(), mm_content_set_cascaded_settings(), mm_content_set_perms(), mm_content_get_perms(), mm_content_set_flags(), mm_content_set_group_members(), mm_content_get_uids_in_group()

mm_content_insert_or_update() now includes the archive_mmtid setting. Before updating an existing tree entry with this function, the calling code should load the old value of this field (such as with mm_content_get($mmtid, Constants::MM_GET_ARCHIVE)) to prevent accidentally resetting it.

mm_ui_show_author() has been removed.

The mm_get_detailed_404() function has been moved into a separate module. If you use the enhanced 404 page feature, you should enable this module and remove any references to the mm_get_detailed_404() function in node bodies. The module can be configured at admin/config/system/site-information.

hook_mm_browser_navigation() must now return a <select> list or <button>.

hook_mm_browser_links_alter() has been renamed to hook_mm_browser_buttons_alter(), and its signature has changed.

hook_mm_showpage_routing() now accepts controller atttribute for page and access callbacks.  It has the same format as _controller: https://www.drupal.org/docs/8/api/routing-system/structure-of-routes