-- SUMMARY --

This module provides an ordered list form element and field widget. In contrast
to other list elements/widgets, the ordered list preserves the order of the
selected items.

Field widget is available for all built-in list field types.

Form element example:

<?php
$form['list'] = [
  '#type' => 'ordered_list',
  '#title' => t('List'),
  '#title_display' => 'invisible',
  '#description' => t('Description.'),
  '#options' => [
    'item1' => t('Item 1'),
    'item2' => t('Item 2'),
    'item3' => t('Item 3'),
    'item4' => t('Item 4'),
  ],
  '#default_value' => ['item4', 'item2'],
  '#required' => TRUE,
  '#disabled' => FALSE,
  '#labels' => [
    'items_available' => t('Available'),
    'items_selected' => t('Selected'),
    'control_select' => t('Select'),
    'control_deselect' => t('Deselect'),
    'control_moveup' => t('Move Up'),
    'control_movedown' => t('Move Down'),
  ],
];
?>


For a full description of the module, visit the project page:
  http://drupal.org/project/ordered_list

To submit bug reports and feature suggestions, or to track changes:
  http://drupal.org/project/issues/ordered_list


-- REQUIREMENTS --

None.


-- INSTALLATION --

* Install as usual, see https://drupal.org/node/2781613 for further information.


-- CONTACT --

Current maintainers:
* Alex Zhulin (Alex Zhulin) - https://drupal.org/user/2659881
