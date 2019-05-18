<?php

/**
 * @file
 * Contains ....
 */

namespace Drupal\list_formatter\Plugin\list_formatter;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\list_formatter\Plugin\ListFormatterListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Component\Utility\Html;

/**
 * Default list implementation plugin.
 *
 * @ListFormatter(
 *   id = "entityreference",
 *   module = "entityreference",
 *   settings = {
 *     "entityreference_link" = "1"
 *   }
 * )
 */
class EntityReferenceList implements ListFormatterListInterface {

  /**
   * Implements \Drupal\list_formatter\Plugin\ListFormatterListInterface::createList().
   */
  public function createList(FieldItemListInterface $items, FieldDefinitionInterface $field_definition, $langcode) {
    // Load the target type for the field instance.
    $target_type = $field_definition->getSetting('target_type');
    $contrib_settings = [];//$display['settings']['list_formatter_contrib'];
    $list_items = $target_ids = $target_entities = [];

    // Get an array of entity ids.
    foreach ($items as $delta => $item) {
      $target_ids[] = $item->target_id;
    }

    // Load them all.
    if ($target_ids) {
      $target_entities = \Drupal::entityTypeManager()->getStorage($target_type)->loadMultiple($target_ids);
    }

    // Create a list item for each entity.
    foreach ($target_entities as $id => $entity) {
      // Only add entities to the list that the user will have access to.
      if ($entity->access('view')) {
        $label = $entity->label();
        if ($contrib_settings['entityreference_link']) {
          $url = $entity->toUrl();
          $target_type_class = Html::getClass($target_type);
          $classes = [$target_type_class, $target_type_class . '-' . $id, 'entityreference'];

          $list_items[$id] = [
            '#type' => 'link',
            '#title' => $label,
            '#url' => $url,
            '#options' => [
              'attributes' => [
                'class' => $classes,
              ],
            ]
          ];
        }
        else {
          $list_items[$id] = [
            '#markup' => $label,
            '#allowed_tags' => FieldFilteredMarkup::allowedTags(),
          ];
        }
      }
    }

    return $list_items;
  }

  /**
   * @todo.
   */
  public function additionalSettings(&$elements, FieldDefinitionInterface $field_definition, FormatterInterface $formatter) {
    if ($field_definition->getType() == 'entityreference') {
      $settings = $field_definition->getSetting('contrib');
      $form['list_formatter_contrib']['entityreference_link'] = [
        '#type' => 'checkbox',
        '#title' => t('Link list items to their @entity entity.', ['@entity' => $field_definition->getType()]),
        '#description' => t('Generate item list with links to the node page'),
        '#default_value' => $settings['entityreference_link'],
      ];
    }
  }

}
