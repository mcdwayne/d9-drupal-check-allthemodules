
ABOUT

Slick Browser provides a Slick Entity Browser integration serving to enrich
variant displays for the Great Entity Browser.

REQUIREMENTS
[1] https://drupal.org/project/entity_browser (post alpha10)
[2] https://drupal.org/project/blazy (post Beta4)
[3] https://drupal.org/project/slick (post Beta4)
[4] https://www.drupal.org/node/2745491 (core patch for non-button)
[5] https://www.drupal.org/node/2715859 (core patch for hidden image fields)


FEATURES
- FieldWidgetDisplay plugins for image/file including ME.
- WidgetSelector Tabs for various tab placements: bottom, left, right, top.
- Slick Browser Views style plugin for displaying media library.
- Views filter grid/ table-like list view switcher plugin.
- Widget plugins for both Entity Browser and File Browser widgets. (WIP)
- Grid Foundation, CSS3 Columns (experimental CSS Masonry), Slick carousel, for
  both Views style and Widget displays.
- Full screen video previews.
- Colorbox/Photobox integration for previews.
- Blazingly quick image/media selection/removal, er, prior to being saved.
- AJAX (Views Infinite Scroll or Views Load More) on steroids, rather than
  loading 12 images per page, load 48 once saving a few AJAX requests. With
  Blazy delay lazy loading and Slick carousel dots and arrows navigations, 48
  images cost as much as 12 visible images.

Supports, or plays nice with:
- Core Image and File
- Media Entity, and its plugins: Facebook, Instagram, SlideShare, Twitter, etc.
- Video Embed Media included in VEF
- Inline Entity Form via EB
- DropzoneJS
- Colorbox/Photobox

The relevant Entity Browser plugins will only be enabled if the above-supported
modules, and expected config, are installed/met earlier, or later.

Specific to Media Entity, Slick Browser only expects bundles: image and video.
The Slick Browser Media Views permission is set to "Create Media". Adjust it.

If your video bundle is named "moving_picture", or image bundle named "picture",
or "photo", the related plugins won't install. They can be recreated based on
your available bundles, though. Yet having the exact bundle names help save
some time to configure.

Slick Browser provides a few default Views for: Block, File, Media, Content.

Use the provided samples to begin with.

INSTALLATION
Install the module as usual, more info can be found on:
https://drupal.org/documentation/install/modules-themes/modules-7

Enable Slick Browser module under "Slick" package:
/admin/modules#edit-modules-slick


USAGE / CONFIGURATION
1. /admin/config/content/entity_browser
   Browse supported Slick Browser plugins, edit/add new plugins accordingly.
   They are there just basic samples, and may not suit actual needs.

   For custom Entity Browser plugins, the module only respects plugins
   containing "slick_browser" in the name, e.g.:
   site_slick_browser_file, or custom_slick_browser_media, etc.

2. /admin/structure/views
   Clone or edit a Slick Browser view, adjust the filter criteria to match
   the target field. Else regular mismatched error, e.g.:
   "This entity (node: NID) cannot be referenced."
   Also adjust Views permission accordingly!

   The requirements, or limitations:
   - The view must have "slick_browser" as part of its name.
   - A global Views filter named "Slick Browser".
   - Only works with "Slick Browser" views style. It may work with "Slick Views"
     or core "HTML list" style plugins, but requires additional adjustments.
   - Must have Views field label for the grid/ list (table-like) labels.
   - For non-image entities, such as node, block, etc., add a special wrapper
     class to the title part: "views-field--selection" for a quick selection
     preview. Only relevant for Multi step selection, though.

   Using/ cloning the provided samples should reduce the above steps to 0 as
   likely there are hidden things there, and not immediately obvious such as
   custom field wrapper classes within the preview Views fields, e.g.:
   "views-field--preview" for the main image preview.

3. /admin/structure/types/manage/article/form-display,
   etc.
   Or any "Manage form display" URL containing image/file or media widgets.
   Under "Widget" for the Entity Browser, click the cog icon, and add relevant
   "Slick Browser" plugins.
   To disable Slick Browser widget (WIP), leave "Display style" option empty.


RELATED MODULES
[1] https://drupal.org/project/file_browser
[2] https://drupal.org/project/content_browser
[3] https://drupal.org/project/media_entity_browser

While they are specializing in entities, Slick Browser more in UX or cosmetics.
The basic difference is Slick Browser uses Slick library, and a little spice
for quick interaction.

SKINS
To add custom skins:
For the Slick widget part, put them under group 'widget'.
For the Slick browser Views part, put them under group 'main'.
No skins for the overall form, maybe later.
The 'widget' skins have different markups as meant for narrow real estate and to
avoid conflict of interests against its front-end fella.
See Drupal\slick_browser\SlickBrowserSkin.

Available skins:
Widget: Classic
  Only reasonable if it has Alt or Title field enabled along with images. Works
  best with one visible slide at a time. Adds dark background color over white
  caption, only good for slider (single slide visible), not carousel
  (multiple slides visible), where small captions are placed over images.

Widget: Split
  Only reasonable if it has Alt or Title field enabled along with images. Works
  best with one visible slide at a time. Puts image and caption side by side,
  related to slide layout options.

Widget: Grid
  Grid dedicated for Entity Browser field widget.

Browser: Grid
  Grid dedicated for Entity Browser View display.


WIDGETS
Slick Browser widget supports 3 Display styles:
CSS3 Columns, Grid Foundation, Slick carousel.
The first two are treated as static grid, no carousel.
Slick Browser has its own sortable elements to avoid conflict with Slick
draggability. The rest are grid items themselves acting as sortable elements.
Slick can make use of Grid Foundation grid, but not vice versa.
If no Display style is selected, will use default Entity Browser widget dislays.


CURRENT DEVELOPMENT STATUS
Still a lot of TODOs till this line is removed. Stay optimistic to get broken.

Not tested with all available plugins, yet. Patches and help are appreciated.
Alpha and Beta releases are for developers only. Be aware of possible breakage.

However if it is broken, unless an update is explicitly required, clearing cache
should fix most issues during DEV phases. Prior to any update, always visit:
/admin/config/development/performance

And hit "Clear all caches" button once the new Slick Browser is in place.
Regenerate CSS and JS as the latest fixes may contain changes to the assets.

Have the latest or similar release Blazy and Slick to avoid trouble in the first
place. They will maintain backward-compatibility till full release.

KNOWN ISSUES
[x] The widget part is not working, yet. Do not use it unless helping
  development.
  False alarm. Nothing to do with Slick Browser. Please apply the core patch:
  https://www.drupal.org/node/2745491
  https://www.drupal.org/node/2715859
  https://www.drupal.org/node/2644468

- The field UI form needs saving first, before the selected Slick Browser widget
  (Entity display plugin) form is displayed.
- CSS3 Columns is best with non-vertical, and adaptiveHeight, else cropped.
  It affects the natural order of grid items, meaning confusing for UI
  sortable when used within a draggable widget. Use it if no big-deal.
- Grid Foundation is best with regular cropped image sizes and vertical.
- Selections will be ignored until button "Select entities" alike is hit.
- Installing samples at Drupal 8.x-3 may produce schema errors. Simply
  continue, and visit Views collection page to re-save if needed. Else ignore.
  This is because Drupal core Views changed its schema for filters at 8.x-3.
  We can wait for another branch, edit and resave Views, or just ignore.

TROUBLESHOOTING
- If anything related to displays look weird, or unexpected, clear cache.
  Including to update Slick skins cache if not seeing the widget skins, or
  when adding new custom skins.
- All slick widget and browser expects Arrows and Dots options enabled.
- If the provided starters are not installed due to unmet dependencies, install
  config_update.module, and visit:
  /admin/config/development/configuration/report/module/slick_browser
  Hit (Right click open tab) "Import from source" to manually import them.
  Repeat the steps.
  The Slick Browser starters will be available at:
  /admin/config/content/entity_browser
  /admin/structure/views


AUTHOR/MAINTAINER/CREDITS
gausarts

Some is inspired by related modules, and discussions here:
https://www.drupal.org/node/2796001, https://www.drupal.org/node/2786785.
The initial concept named "Slick Widget" was created weeks before those
discussions as seen from SlickManager::$skins widget. It just has better
direction thanks to discussions. Some credits go to @kiboman for design
inspirations, and everyone else there for thorough ideas.
No designs are stolen, they are inspirations to trigger personal creativity.
The rest is just opiniated, personal taste and time limitation to cope with EB
current design challenges.

This module may be transformed when core has a media library solution, or also
extending core as needed. Until then, this is a Slick Entity Browser plugin.


READ MORE
See the project page on drupal.org: https://drupal.org/project/slick_browser.
