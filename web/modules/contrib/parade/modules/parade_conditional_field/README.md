# Parade conditional field

Parade specific conditional fields to manage field dependencies:

`if Layout has values [a, b, c] then set View mode to [v] and restrict Color
schemes to [x, y, z]`

for Paragraphs bundle fields referenced with 'Sections'
(parade_onepage_sections) paragraphs reference field: 
- Layout (parade_layout) - dependee field
  - Classy paragraph style with machine name starts with 'layout_'
- View mode (parade_view_mode) - target field
  - View Mode Selector field type
- Color scheme (parade_color_scheme) - target field
  - Classy paragraph style with machine name starts with 'color_'

Classy paragraph style: https://www.drupal.org/project/classy_paragraphs

View Mode Selector: https://www.drupal.org/project/view_mode_selector 

## Requirements
- Patch for Classy paragraphs style module:
https://www.drupal.org/files/issues/choose_and_order-2830403-20.patch

## Installation
- Enable module

## How it works
You can create multiple conditions per Paragraphs bundle on the admin UI under
'Parade field conditions' tab.

If Layout has configured values then:
  - hide 'View mode' field
  - set 'View mode' field to selected value
  - (optional) enable set 'Color scheme' values (hide other)

If Layout hasn't configured values then:
  - hide 'View mode' field
  - set 'View mode' field to 'Default' value
  - enable all 'Color scheme' values

## Limitations

#### Layout (parade_layout) field
Works only with parade's 'Paragraphs with preview' entity_reference_revisions
widget.

#### Layout (parade_layout) field
Allowed number of values: Limited = 1

Form widget should be one of these:
- Autocomplete
- Check boxes/radio buttons
- Select list

#### View mode (parade_view_mode) field
Allowed number of values: Limited = 1

Form widget should be one of these:
- Check boxes/radio buttons
- Select list

#### Color scheme (parade_color_scheme) field
Allowed number of values: Unlimited

Form widget should be:
- Select list
