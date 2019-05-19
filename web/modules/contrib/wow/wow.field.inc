<?php

/**
 * @file
 * Implements field related hooks.
 */

/**
 * Implements hook_field_info().
 *
 * Provides the description of the field.
 */
function wow_field_info() {
  return array(
      'wow_character_race' => array(
          'label' => t('Character race'),
          'description' => t('The character race name.'),
          'default_widget' => 'wow_character_race',
          'default_formatter' => 'wow_character_race',
      ),
      'wow_character_class' => array(
          'label' => t('Character class'),
          'description' => t('The character class name.'),
          'default_widget' => 'wow_character_class',
          'default_formatter' => 'wow_character_class',
      ),
      'wow_item' => array(
          'label' => t('Item'),
          'description' => t('The item name and description.'),
          'default_widget' => 'wow_item',
          'default_formatter' => 'wow_item',
          'no_ui' => TRUE,
      ),
  );
}

/**
 * Implements hook_field_is_empty().
 */
function wow_field_is_empty($item, $field) {
  switch ($field['type']) {

    case 'wow_item':
      $empty = empty($item['name']);
      break;

    default:
      $empty = TRUE;
      break;
  }

  return $empty;
}

/**
 * Implements hook_field_formatter_info().
 */
function wow_field_formatter_info() {
  require_once 'modules/wow_item/wow_item.module';

  return array(
      'wow_item' => wow_item_field_formatter_callback(),
  );
}

/**
 * Implements hook_field_formatter_view().
 */
function wow_field_formatter_view($entity_type, $entity, $field, $instance, $langcode, $items, $display) {
  switch ($entity_type) {
    case 'wow_item':
      $element = wow_item_field_formatter_view($entity_type, $entity, $field, $instance, $langcode, $items, $display);
      break;

    default:
      $element = array();
      break;
  }

  return $element;
}

/**
 * Implements hook_field_widget_info().
 */
function wow_field_widget_info() {
  require_once 'modules/wow_item/wow_item.module';

  return array(
      'wow_item' => wow_item_field_widget_callback(),
  );
}

/**
 * Implements hook_field_widget_form().
 */
function wow_field_widget_form(&$form, &$form_state, $field, $instance, $langcode, $items, $delta, $element) {
  switch ($entity_type) {
    case 'wow_item':
      $element = wow_item_field_widget_form($form, $form_state, $field, $instance, $langcode, $items, $delta, $element);
      break;

    default:
      break;
  }

  return $element;
}
