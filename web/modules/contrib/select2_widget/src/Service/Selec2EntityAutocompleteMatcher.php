<?php

namespace Drupal\select2_widget\Service;

use Drupal\Core\Entity\EntityAutocompleteMatcher;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;

class Selec2EntityAutocompleteMatcher extends EntityAutocompleteMatcher {

  public function __construct(SelectionPluginManagerInterface $selection_manager) {
    parent::__construct($selection_manager);
  }

  public function getMatches($target_type, $selection_handler, $selection_settings, $string = '') {
    $matches = [];

    $options = [
      'target_type' => $target_type,
      'handler' => $selection_handler,
      'handler_settings' => $selection_settings,
    ];
    $handler = $this->selectionManager->getInstance($options);

    if (isset($string)) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, 50);

      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $values) {
        foreach ($values as $entity_id => $label) {
          $entity = \Drupal::entityTypeManager()->getStorage($target_type)->load($entity_id);

          $matches[] = [
            'id' => $entity_id,
            'text' => $label,
            'label' => $entity->label(),
            'status' => $entity->get('status')->value
          ];

        }
      }
    }

    return $matches;
  }
}