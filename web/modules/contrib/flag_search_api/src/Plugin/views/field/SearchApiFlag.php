<?php

namespace Drupal\flag_search_api\Plugin\views\field;

use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\FieldHandlerInterface;

/**
 * Displays a flag link.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("search_api_flag")
 */
class SearchApiFlag extends FieldPluginBase implements FieldHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // Extract the entity type id and entity id from the search api id.
    $entity_info = explode(':', $values->search_api_id)[1];
    list($entity_type_id, $entity_id) = explode('/', $entity_info);

    // Remove the 'flag_' prefix of the field name to get the flag ID.
    $flag_id = substr($this->field, 5);

    // Generate flag link.
    $flag_link = [
      '#lazy_builder' => [
        'flag.link_builder:build', [
          $entity_type_id,
          $entity_id,
          $flag_id,
        ],
      ],
      '#create_placeholder' => TRUE,
    ];

    return $flag_link;
  }

}
