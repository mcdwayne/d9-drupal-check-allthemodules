<?php

namespace Drupal\entity_autocomplete_plus\Entity;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityAutocompleteMatcher;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Matcher class to get autocompletion results for entity reference.
 */
class EntityAutocompletePlusMatcher extends EntityAutocompleteMatcher {

  // Injected EntityManager
  protected $entityManager;

  /**
   * Constructs a EntityAutocompletePlusMatcher object.
   *
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager
   *   The entity reference selection handler plugin manager.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(SelectionPluginManagerInterface $selection_manager, EntityManagerInterface $entity_manager) {
    $this->selectionManager = $selection_manager;
    $this->entityManager = $entity_manager;
  }

  /*
   * {@inheritdoc]
   */
  public function getMatches($target_type, $selection_handler, $selection_settings, $string = '') {

    $matches = [];

    $options = [
      'target_type' => $target_type,
      'handler' => $selection_handler,
      'handler_settings' => $selection_settings,
    ];
    $handler = $this->selectionManager->getInstance($options);
    $storage_controller = $this->entityManager->getStorage($target_type);
    
    // Global configuration.
    $config = \Drupal::config('entity_autocomplete_plus.settings');
    $n_match = $config->get('number_of_matches')? $config->get('number_of_matches') : 10;
    $token_string = $config->get('token_string')? $config->get('token_string') : '';

    // Per field, injected by entity_autocomplete_plus_field_widget_form_alter.
    if (isset($selection_settings['token_string_suffix']) && $selection_settings['token_string_suffix']) {
      $token_string = $selection_settings['token_string_suffix'];
    }

    if (isset($string)) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, $n_match);

      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $values) {
        foreach ($values as $entity_id => $label) {
          $info = $this->getEntityInfo($storage_controller, $token_string, $target_type, $entity_id);
          $key = "$label ($entity_id)"; // probably don't mess with the key in case this is saved verbatim
          $label = "$label - $info ($entity_id)";
          // Strip things like starting/trailing white spaces, line breaks and
          // tags.
          $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
          // Names containing commas or quotes must be wrapped in quotes.
          $key = Tags::encode($key);
          $matches[] = ['value' => $key, 'label' => $label];
        }
      }
    }

    return $matches;
  }

  /*
   * return information about the entity for use in the matcher UI based on the token string
   */
  private function getEntityInfo($storage_controller, $token_string, $target_type, $entity_id) {
    $info = '';

    $cid = 'entity_autocomplete_plus.' . $target_type . '.' . $entity_id . '.' . md5($token_string);
    if ($cache = \Drupal::cache()->get($cid)) {
      $info = $cache->data;
    }
    else {
      $entity = $storage_controller->load($entity_id);
      $info = \Drupal::token()->replace($token_string, [$target_type => $entity]);
      // If token replacement failed, blank the string.
      if ($info == $token_string) {
        $info = '';
      }
      \Drupal::cache()->set($cid, $info);
    }

    return $info;
  }

}