# Paragraphs Table of Contents

If you have a long page built with paragraphs (as in the
[Paragraphs](https://www.drupal.org/project/paragraphs) module)
then you can use this to generate a table of contents for in-page navigation.

## Configuration

The configuration page is `/admin/structure/paragraphs_type/ptoc`. You can use
this page to configure which fields of which paragraph types are shown in ToC
mode. You can also enable "debug mode", which puts a border around each
paragraph on the page.

## Getting started

1. Install the module.
2. Go to Administration > Structure > Block (`/admin/structure/block`) and
   place the ToC block in a region. (If you are using the core Bartik theme,
   then the block should already be in the "Sidebar first" region.)
3. Create a node of type "Page with sections" (`node/add/ptoc_page`).
   Add at least one paragraph to the Sections field. Make the paragraphs long
   enough that the page will scroll.
4. Save and view your node. You should have a table of contents with a link to
   each paragraph that you have created.

## Adding a table of contents to another content type

For example, suppose you want to add a table of contents (ToC) to the "Basic
page" content type.

1. Go to Home > Administration > Structure > Content types > Page
   > Manage fields
   (`/admin/structure/types/manage/page/fields`).
2. Click the "Add field" button.
3. Under "Re-use an existing field", choose `field_ptoc_sections`.
   Complete this two-step form.
4. On the "Manage form display" tab
   (`/admin/structure/types/manage/page/form-display`)
   click the "Save" button.
   (This works around a known bug:  see https://www.drupal.org/node/2719593
   and https://www.drupal.org/node/2717319 .)
5. On the "Manage display" tab (`/admin/structure/types/manage/page/display`)
   hide the Sections label if you like;
   open the "Custom display settings" section, check off "Table of contents",
   and save.
6. On the "Table of contents" sub-tab
   (`/admin/structure/types/manage/page/display/ptoc`)
   enable the Sections field and hide the others. Configure the Sections field
   to use the "Table of contents" view mode. Hide the label. Save.
7. Edit the "Table of contents" view
   (`/admin/structure/views/view/ptoc`).
8. Adjust the "Filter criteria" to allow all content types, or at least Basic
   page.
9. Adjust the contextual filter so that the validation criteria allow Basic page.
10. Save the view.
11. Edit the ToC block form
    (`/admin/structure/block/manage/views_block__ptoc_block_1`).
12. Under "Content types", check off "Basic page".
13. Save the block.

Instead of editing the view, you can add a second block display or clone the
view. Then position the
new block from Administration > Structure > Block and configure it to appear
on the appropriate pages.

Note that the contextual filter is hidden (by default) until
you expand the Advanced options while editing the view.

## Adding additional paragraph types

1. Edit the Sections field (`field_ptoc_sections`) on the appropriate content
   type and check off the paragraph types you want to add.
2. Go to Home > Administration > Structure > Paragraphs types and choose the
   "Configure Paragraphs ToC" tab (`/admin/structure/paragraphs_type/ptoc`).
3. Check off each paragraph type you want to use. Save.
4. Select fields for each newly enabled paragraph type. Save again.

After the first step, you could do this instead:

2. Go to Home > Administration > Structure > Paragraphs types
   (`/admin/structure/paragraphs_type`) and choose "Manage display" for the
   additional paragraph types (one at a time).
3. Under "Custom display settings" check "Table of contents".
4. Configure the "Table of contents" view mode to show only the fields you
   want in the table of contents.

For most paragraph types, you will show only the title field in the table of
contents. If you want to show nested paragraphs in the table of contents, then
see how the Bundle paragraph type is configured.
