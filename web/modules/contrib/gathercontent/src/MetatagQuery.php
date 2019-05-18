<?php

namespace Drupal\gathercontent;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for querying metatag data.
 */
class MetatagQuery implements ContainerInjectionInterface {

  protected $entityFieldManager;
  protected $configFactory;

  /**
   * MetatagQuery constructor.
   */
  public function __construct(
    EntityFieldManagerInterface $entityFieldManager,
    ConfigFactoryInterface $configFactory
  ) {
    $this->entityFieldManager = $entityFieldManager;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Check if content type has any metatag fields.
   *
   * @param string $entityType
   *   Machine name of the entity type.
   * @param string $contentType
   *   Machine name of content type.
   *
   * @return bool
   *   TRUE if metatag field exists.
   */
  public function checkMetatag($entityType, $contentType) {
    $instances = $this->entityFieldManager
      ->getFieldDefinitions($entityType, $contentType);

    foreach ($instances as $name => $instance) {
      /** @var \Drupal\Core\Field\FieldDefinitionInterface $instance */
      if ($instance->getType() === 'metatag') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get the first metatag field.
   *
   * @param string $entityType
   *   Machine name of the entity type.
   * @param string $contentType
   *   Machine name of content type.
   *
   * @return string
   *   Metatag field name.
   */
  public function getFirstMetatagField($entityType, $contentType) {
    $instances = $this->entityFieldManager
      ->getFieldDefinitions($entityType, $contentType);

    foreach ($instances as $name => $instance) {
      /** @var \Drupal\Core\Field\FieldDefinitionInterface $instance */
      if ($instance->getType() === 'metatag') {
        return $instance->getName();
      }
    }
    return '';
  }

  /**
   * Get list of metatag fields.
   *
   * @param string $entityType
   *   Machine name of the entity type.
   * @param string $contentType
   *   Machine name of content type.
   *
   * @return array
   *   Array of metatag fields.
   */
  public function getMetatagFields($entityType, $contentType) {
    // Use Drupal service instead of dependency injection.
    // Because we don't want the module to be dependent on metatag module.
    $metatagManager = \Drupal::service('metatag.manager');
    // Retrieve configuration settings.
    $settings = $this->configFactory->get('metatag.settings');
    $entityTypeGroups = $settings->get('entity_type_groups');

    // See if there are requested groups for this entity type and bundle.
    $filteredGroups = !empty($entityTypeGroups[$entityType]) && !empty($entityTypeGroups[$entityType][$contentType]) ? $entityTypeGroups[$entityType][$contentType] : [];
    $groups = $metatagManager->sortedGroupsWithTags();
    $fields = [];

    foreach ($groups as $key => $group) {
      if (
        empty($group['tags'])
        || (!empty($filteredGroups) && !in_array($key, $filteredGroups))
      ) {
        continue;
      }

      $groupName = $group['label'];
      $fields[$groupName] = [];

      foreach ($group['tags'] as $tag) {
        $fields[$groupName][$tag['id']] = $tag['label'];
      }
    }

    return $fields;
  }

}
