<?php

namespace Drupal\straw\Element;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Component\Utility\Tags;

/**
 * Provides an entity autocomplete form element.
 *
 * The #default_value accepted by this element is either an entity object or an
 * array of entity objects.
 *
 * @FormElement("super_term_reference_autocomplete")
 */
class SuperTermReferenceAutocomplete extends EntityAutocomplete {

  /**
   * Gets the term label with its hierarchy.
   *
   * Converts an array of term objects into a string of term labels including
   * the full tree path to the term. This method is also responsible for
   * checking the 'view label' access on the passed-in terms.
   *
   * @param \Drupal\taxonomy\Entity\Term[] $terms
   *   An array of term objects.
   *
   * @return string
   *   A string of term labels separated by commas.
   */
  public static function getEntityLabels(array $terms) {
    $term_labels = [];

    /** @var \Drupal\taxonomy\TermStorage $term_storage */
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

    foreach ($terms as $term) {
      // Use the special view label, since some entities allow the label to be
      // viewed, even if the entity is not allowed to be viewed.
      $label = t('- Restricted access -');
      if ($term->access('view label')) {
        // For Straw widgets, we want to show the full tree path to the current
        // term rather than just the current term's label.
        $label = $term->label();
        $current = $term;
        while ($parents = $term_storage->loadParents($current->id())) {
          $parent = reset($parents);
          $label = $parent->label() . ' >> ' . $label;
          $current = $parent;
        }
      }

      // Take into account "autocreated" entities.
      if (!$term->isNew()) {
        $label .= ' (' . $term->id() . ')';
      }

      // Labels containing commas or quotes must be wrapped in quotes.
      $term_labels[] = Tags::encode($label);
    }

    return implode(', ', $term_labels);
  }

}
