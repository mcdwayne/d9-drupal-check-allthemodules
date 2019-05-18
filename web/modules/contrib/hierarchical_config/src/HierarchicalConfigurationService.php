<?php

/**
 * @file
 * Contains \Drupal\hierarchical_config\HierarchicalConfigService.
 */

namespace Drupal\hierarchical_config;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\hierarchical_config\Entity\HierarchicalConfiguration;
use Drupal\taxonomy\Entity\Term;


/**
 * Class RegisterService.
 *
 * @package Drupal\hierarchical_config
 */
class HierarchicalConfigurationService {

  protected $currentRoutematch;

  /**
   * HierarchicalConfigService constructor.
   *
   * @param $currentRoutematch
   */
  public function __construct(CurrentRouteMatch $currentRoutematch) {
    $this->currentRoutematch = $currentRoutematch;
  }


  public function getSetting($name) {
    /**
     * @var CurrentRouteMatch $currentRoutematch
     */
    $parameters = ['node', 'taxonomy_term'];
    $entity = NULL;
    foreach ($parameters as $parameter) {
      /**
       * @var \Drupal\Core\Entity\ContentEntityInterface $entity
       */
      if ($entity = $this->currentRoutematch->getParameter($parameter)) {
        /**
         * Search for ad_integration_settings field
         */
        foreach ($entity->getFieldDefinitions() as $fieldDefinition) {
          $fieldType = $fieldDefinition->getType();

          /**
           * If settings are found, check if an overridden value for the
           * given setting is found and return that
           */
          $overriddenSetting = $this->getOverridden($name, $fieldDefinition, $entity);
          if (isset($overriddenSetting)) {
            return $overriddenSetting;
          }


          /**
           * Check for fallback categories if no ad_integration_setting is found
           */
          if (!isset($termOverride) && $fieldType === 'entity_reference' && $fieldDefinition->getSetting('target_type') === 'taxonomy_term') {
            $fieldName = $fieldDefinition->getName();


            if ($fieldName == 'parent' && $entity instanceof Term) {
              $parents = \Drupal::entityTypeManager()
                ->getStorage('taxonomy_term')
                ->loadParents($entity->id());
              $term = reset($parents);
            }
            else {
              $tid = $entity->$fieldName->get(0)->target_id;
              $term = Term::load($tid);
            }

            if ($term) {
              $termOverride = $this->getOverriddenFromTerm($name, $term);
            }
          }
        }
        /**
         * If we not returned before, it is possible, that we found a termOverride
         */
        if (isset($termOverride)) {
          return $termOverride;
        }
      }
    }

    return '';
  }

  /**
   * @param $name
   * @param $fieldDefinition
   * @param $entity
   */
  protected function getOverridden($name, $fieldDefinition, Entity $entity) {
    if ($fieldDefinition->getType() === 'entity_reference' && $fieldDefinition->getSetting('target_type') == 'hierarchical_configuration') {
      $fieldName = $fieldDefinition->getName();

      foreach ($entity->$fieldName as $field) {
        $config = HierarchicalConfiguration::load($field->target_id);

        if (!empty($config->$name)) {
          return $config->$name->get(0)->value;
        }
      }
    }
  }

  protected function getOverriddenFromTerm($name, Term $term) {
    foreach ($term->getFieldDefinitions() as $fieldDefinition) {
      $override = $this->getOverridden($name, $fieldDefinition, $term);
      if (isset($override)) {
        return $override;
      }
    }
    foreach (\Drupal::entityTypeManager()
               ->getStorage('taxonomy_term')
               ->loadParents($term->id()) as $parent) {
      $override = $this->getOverriddenFromTerm($name, $parent);
      if (isset($override)) {
        return $override;
      }
    }
  }

}
