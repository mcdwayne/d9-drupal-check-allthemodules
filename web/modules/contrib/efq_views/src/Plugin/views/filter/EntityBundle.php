<?php

/**
 * @file
 * Contains \Drupal\efq_views\Plugin\views\filter\EntityBundle.
 */

namespace Drupal\efq_views\Plugin\views\filter;


/**
 * Filter based on entity bundle.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("efq_entity_bundle")
 */
class EntityBundle extends InOperator {

  /**
   * {@inheritdoc}
   */
  function getValueOptions() {
    if (!isset($this->value_options)) {
      $bundles = field_info_bundles($this->definition['entity_type']);
      $this->value_title = t('Bundle');

      // EFQ: Mixed, display bundles for all entity types
      if (!isset($this->definition['entity_type'])) {
        foreach ($bundles as $entity_type => $entity_bundles) {
          foreach ($entity_bundles as $bundle => $info) {
            $label = isset($info['label']) ? $info['label'] : $bundle;
            $options[$bundle] = $label;
          }
        }
      }
      else { // Display bundles for the selected entity type only.
        foreach ($bundles as $bundle => $info) {
          $label = isset($info['label']) ? $info['label'] : $bundle;
          $options[$bundle] = $label;
        }
      }

      $this->value_options = $options;
    }
  }

}
