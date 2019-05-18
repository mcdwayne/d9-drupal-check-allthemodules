<?php

namespace Drupal\google_feeds\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Plugin implementation of the 'entity reference label' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_google_shopping_label",
 *   label = @Translation("Google shopping term"),
 *   description = @Translation("Display the label of taxonomy term and its parents."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class GoogleShoppingTermFormatter extends EntityReferenceFormatterBase {

  private function cleanLabel($label) {
    $label = htmlentities($label);
    $label = str_replace(',', ' &amp;', $label);
    return $label;
  }
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $labels = [];
      $entity_type = $entity->getEntityType();
      if($entity_type->id() == 'taxonomy_term') {
        $parent_terms = \Drupal::service('entity_type.manager')->getStorage("taxonomy_term")->loadAllParents($entity->id());
        foreach ($parent_terms as $term) {
          if($term->id() != $entity->id()) {
            $labels[] = $this->cleanLabel($term->label());
          }
        }
      }
      $labels = array_reverse($labels);
      $labels[] = $this->cleanLabel($entity->label());
      $value = implode(' &gt; ', $labels);
      $elements[$delta] = ['#markup' => $value];
      $elements[$delta]['#cache']['tags'] = $entity->getCacheTags();
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity) {
    return $entity->access('view label', NULL, TRUE);
  }
}