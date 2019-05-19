<?php

namespace Drupal\stacks\Services;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Class TaxonomyHelper.
 * @package Drupal\stacks\Services
 */
class TaxonomyHelper {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * TaxonomyHelper constructor.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Returns all taxonomy fields by content type.
   *
   * @param array
   * @return array
   */
  public function getTaxonomyFieldsForContentTypes($content_types) {
    $tag_fields = [];

    if (empty($content_types) || !is_array($content_types)) {
      return $tag_fields;
    }

    foreach ($content_types as $content_type) {
      $fields = $this->entityFieldManager->getFieldDefinitions('node', $content_type);

      foreach ($fields as $field_name => $field) {
        $field_settings = $field->getSettings();
        if ($field->getType() != 'entity_reference'
          || $field_settings['target_type'] != 'taxonomy_term'
          || empty($field_settings['handler_settings']['target_bundles'])
        ) {
          continue;
        }

        foreach ($field_settings['handler_settings']['target_bundles'] as $bundle) {
          // Add this field to the array.
          if (!isset($tag_fields[$bundle])) {
            $tag_fields[$bundle] = [];
          }

          $tag_fields[$bundle][] = $field_name;
        }
      }
    }

    return $tag_fields;
  }

  /**
   * Returns an array of terms from a certain vocabulary.
   *
   * @TODO: Find another approach on serving terms
   */
  static function getTermsFromVocab(&$filters, $vocab_machine_name, $cfeed_taxonomy_terms = []) {
    // This is here because terms filter was not working: Warning cannot
    // use a scalar (1) as array.
    if (!is_array($filters['taxonomy_terms'])) {
      $filters['taxonomy_terms'] = [];
    }

    $terms = [];
    $vids = Vocabulary::loadMultiple();

    foreach ($vids as $vid) {
      if ($vid->id() == $vocab_machine_name) {
        $filters['taxonomy_vocab_names'][$vocab_machine_name] = $vid->label();
        $vocab_terms = \Drupal::getContainer()
          ->get('entity.manager')
          ->getStorage('taxonomy_term')
          ->loadTree($vid->id());
        if (!empty($vocab_terms)) {
          foreach ($vocab_terms as $term) {
            $id = $term->tid;
            if (count($cfeed_taxonomy_terms) > 0) {
              if (in_array($id, $cfeed_taxonomy_terms)) {
                $terms[$id] = $term->name;

                // Removing standalone term from the filters' array.
                unset($filters['taxonomy_terms'][$id]);
              }
            }
            else {
              $terms[$id] = $term->name;
            }
          }
        }
        break;
      }
    }

    $filters['taxonomy_terms'][$vocab_machine_name] = $terms;
  }

}
