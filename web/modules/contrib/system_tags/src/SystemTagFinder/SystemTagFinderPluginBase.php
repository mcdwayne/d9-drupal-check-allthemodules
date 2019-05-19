<?php

namespace Drupal\system_tags\SystemTagFinder;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\system_tags\SystemTagHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SystemTagFinderPluginBase.
 *
 * @package Drupal\system_tags\SystemTagFinder
 */
abstract class SystemTagFinderPluginBase extends PluginBase implements SystemTagFinderInterface, ContainerFactoryPluginInterface {

  /**
   * The system tag helper.
   *
   * @var \Drupal\system_tags\SystemTagHelperInterface
   */
  protected $systemTagHelper;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * SystemTagFinderPluginBase constructor.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\system_tags\SystemTagHelperInterface $system_tag_helper
   *   The system tag helper.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    SystemTagHelperInterface $system_tag_helper,
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->systemTagHelper = $system_tag_helper;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('system_tags.system_tag_helper'),
      $container->get('entity_type.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function findByTag($systemTagId) {
    $entities = [];
    $entityTypeId = $this->pluginDefinition['entity_type'];

    if ($fields = $this->systemTagHelper->getReferenceFieldNames($entityTypeId)) {
      $langcode = $this->languageManager->getCurrentLanguage()->getId();
      $storage = $this->entityTypeManager->getStorage($entityTypeId);

      $query = $storage->getQuery()
        ->sort('changed', 'DESC', $langcode);
      $orCondition = $query->orConditionGroup();
      foreach ($fields as $field) {
        $condition = $query->andConditionGroup()
          ->exists($field)
          ->condition($field, $systemTagId);
        $orCondition->condition($condition);
      }
      $query->condition($orCondition);

      if ($result = $query->execute()) {
        $entities = $storage->loadMultiple($result);

        // Replace entities with translated version.
        foreach ($entities as &$entity) {
          if (($entity instanceof TranslatableInterface) && $entity->hasTranslation($langcode)) {
            $entity = $entity->getTranslation($langcode);
          }
        }
      }
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function findOneByTag($systemTagId) {
    $entities = $this->findByTag($systemTagId);

    return reset($entities) ?: NULL;
  }

}
