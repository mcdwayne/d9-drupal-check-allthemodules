#Better entity reference formatter (berf)

##Description

The module provides an advanced field formatter for entity reference fields.
Please follow the installation instructions and examples sections below in order
to get started.

##Benefits

The default formatter for entity reference provided by Drupal core isn't very 
flexible. It can just render all referenced entities in a certain view mode.
This module introduces more flexible options like for example rendering only
the first or last entity or support for even more complicated use cases.
The module also supports the file_entity module in case you want to display 
files or images using entity references.

##Installation

1. Install the module as usual using the UI or drush.
2. Navigate to the manage display config of the content/entity type you
   would like to adapt, i.e. /admin/structure/types/manage/mynodetype/display.
3. Switch the format of your entity reference field to
   "Advanced Rendered Entity".
4. Configure the formatter settings as needed, see examples section.

##Examples

1. Display only the first entity: Select the selection mode "First entity". 
2. Display only the last entity: Select the selection mode "Last entity".
3. Display the first x entities: Select the selection mode "Advanced",
   enter x for amount, 0 for offset.
4. Display the last x entities: Select the selection mode "Advanced",
   enter x for amount, 0 for offset and enable the reverse order option.
5. Display two entities after the first one: Select the selection
   mode "Advanced", enter 2 for amount, 1 for offset.
6. Display two entities before the last one: Select the selection
   mode "Advanced", enter 2 for amount, 1 for offset and enable the
   reverse order option.

##Credits:

Current maintainers:

- Sebastian Leu - https://www.drupal.org/u/s_leu
