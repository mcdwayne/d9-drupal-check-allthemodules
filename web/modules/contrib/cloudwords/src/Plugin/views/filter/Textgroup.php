<?php

namespace Drupal\cloudwords\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\ManyToOne;

/**
 * Filter based on translatable translation status.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("cloudwords_translatable_textgroup_filter")
 */
class Textgroup extends ManyToOne {

  /**
   * Gets the values of the options.
   *
   * @return array
   *   Returns options.
   */
  public function getValueOptions() {
    $textgroups = [];

    // @todo textgroup population needs to happen by submodules.. this can be a hook that submodules utilize
    $entity_types = \Drupal::entityTypeManager()->getDefinitions();
    // @todo entityManager is deprecated, change
    $bundles = \Drupal::entityManager()->getAllBundleInfo();
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if (!$entity_type instanceof \Drupal\Core\Entity\ContentEntityType || !$entity_type->hasKey('langcode') || !isset($bundles[$entity_type_id])) {
        continue;
      }
      foreach ($bundles[$entity_type_id] as $bundle => $bundle_info) {
        $config = \Drupal\language\Entity\ContentLanguageSettings::loadByEntityTypeBundle($entity_type_id, $bundle);
        $content_translation_settings = $config->getThirdPartySettings('content_translation');
        if(isset($content_translation_settings['enabled']) && $content_translation_settings['enabled'] == 1) {
          $additional = $entity_type->get('additional');
          // @todo find better mechanism for filtering out entity reference revision item and other translatable items with parent id
          if(!isset($additional['entity_revision_parent_type_field'])) {
            $textgroups[$entity_type_id] = $entity_type->getLabel();
          }
        }
      }
    }

    $this->valueOptions = $textgroups;
    return $this->valueOptions;
  }
}
