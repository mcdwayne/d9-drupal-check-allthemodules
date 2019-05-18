<?php

namespace Drupal\entity_autocomplete_extended\Entity;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\EntityAutocompleteMatcher;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;

/**
 * Matcher class to get autocomplete results for entity reference.
 *
 * This extends the matcher to allow for a variable limit of matched results.
 */
class EntityAutocompleteExtendedMatcher extends EntityAutocompleteMatcher {

  /**
   * The entity autocomplete matcher service.
   *
   * @var \Drupal\Core\Entity\EntityAutocompleteMatcher
   */
  protected $entityAutocompleteMatcher;

  /**
   * Constructs a EntityAutocompleteMatcher object.
   *
   * @param \Drupal\Core\Entity\EntityAutocompleteMatcher $entity_autocomplete_matcher
   *   The entity autocomplete matcher service.
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager
   *   The entity reference selection handler plugin manager.
   */
  public function __construct(EntityAutocompleteMatcher $entity_autocomplete_matcher, SelectionPluginManagerInterface $selection_manager) {
    parent::__construct($selection_manager);
    $this->entityAutocompleteMatcher = $entity_autocomplete_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public function getMatches($target_type, $selection_handler, $selection_settings, $string = '') {
    // Retrieve the limit of shown results setting if available, otherwise use
    // default service.
    if (empty($selection_settings['entity_autocomplete_extended_results_limit'])) {
      return $this->entityAutocompleteMatcher->getMatches($target_type, $selection_handler, $selection_settings, $string);
    }

    $limit = $selection_settings['entity_autocomplete_extended_results_limit'];
    unset($selection_settings['entity_autocomplete_extended_results_limit']);

    $matches = [];

    $options = $selection_settings + [
      'target_type' => $target_type,
      'handler' => $selection_handler,
    ];
    $handler = $this->selectionManager->getInstance($options);

    if (isset($string)) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, $limit);

      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $values) {
        foreach ($values as $entity_id => $label) {
          $key = "$label ($entity_id)";
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

}
