<?php

namespace Drupal\migrate_gathercontent\Plugin\migrate\process;

use Drupal\migrate_plus\Plugin\migrate\process\EntityLookup;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * This plugin looks for existing entities.
 *
 * This extends the EntityLookup Plugin from Migrate Plus in order to add
 * support for multiple bundles. Once that issue is resolved this plugin can
 * be removed.
 *
 * @MigrateProcessPlugin(
 *   id = "gathercontent_entity_lookup",
 *   handle_multiples = TRUE
 * )
 */
class GatherContentEntityLookup extends EntityLookup implements ContainerFactoryPluginInterface {

  /**
   * Checks for the existence of some value.
   *
   * @param mixed $value
   *   The value to query.
   *
   * @return mixed|null
   *   Entity id if the queried entity exists. Otherwise NULL.
   */
  protected function query($value) {
    // Entity queries typically are case-insensitive. Therefore, we need to
    // handle case sensitive filtering as a post-query step. By default, it
    // filters case insensitive. Change to true if that is not the desired
    // outcome.
    $ignoreCase = !empty($this->configuration['ignore_case']) ?: FALSE;

    $multiple = is_array($value);

    $query = $this->entityManager->getStorage($this->lookupEntityType)
      ->getQuery()
      ->condition($this->lookupValueKey, $value, $multiple ? 'IN' : NULL);

    if ($this->lookupBundleKey) {
      if (!is_array($this->lookupBundle)) {
        $this->lookupBundle = [$this->lookupBundle];
      }
      $query->condition($this->lookupBundleKey, $this->lookupBundle, "IN");
    }
    $results = $query->execute();

    if (empty($results)) {
      return NULL;
    }

    // By default do a case-sensitive comparison.
    if (!$ignoreCase) {
      // Returns the entity's identifier.
      foreach ($results as $k => $identifier) {
        $entity = $this->entityManager->getStorage($this->lookupEntityType)->load($identifier);
        $result_value = $entity instanceof ConfigEntityInterface ? $entity->get($this->lookupValueKey) : $entity->get($this->lookupValueKey)->value;
        if (($multiple && !in_array($result_value, $value, TRUE)) || (!$multiple && $result_value !== $value)) {
          unset($results[$k]);
        }
      }
    }

    if ($multiple && !empty($this->destinationProperty)) {
      array_walk($results, function (&$value) {
        $value = [$this->destinationProperty => $value];
      });
    }

    return $multiple ? array_values($results) : reset($results);
  }

}
