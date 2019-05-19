<?php

namespace Drupal\synergy\Plugin\Derivative;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides dynamic local tasks for synergy.
 */
class SynergyLocalTasks extends DeriverBase implements ContainerDeriverInterface {
  use StringTranslationTrait;

  /**
   * The base plugin ID
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The content translation manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ContentTranslationLocalTasks.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->basePluginId = $base_plugin_id;
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Create tabs for all possible entity types.
    foreach ($this->entityTypeManager->getDefinitions()  as $entity_type_id => $entity_type) {
      // Find the route name for the translation overview.
      $translation_route_name = "entity.$entity_type_id.synergy";

      $base_route_name = "entity.$entity_type_id.canonical";
      $this->derivatives[$translation_route_name] = array(
        'entity_type' => $entity_type_id,
        'title' => $this->t('Synergy'),
        'route_name' => $translation_route_name,
        'base_route' => $base_route_name,
      ) + $base_plugin_definition;
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
