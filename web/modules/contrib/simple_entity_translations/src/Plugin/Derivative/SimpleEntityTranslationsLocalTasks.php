<?php

namespace Drupal\simple_entity_translations\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic local tasks for entity translation.
 */
class SimpleEntityTranslationsLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The base plugin ID.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ContentTranslationLocalTasks.
   */
  public function __construct($base_plugin_id, ContentTranslationManagerInterface $content_translation_manager, TranslationInterface $string_translation, EntityTypeManagerInterface $entity_type_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->contentTranslationManager = $content_translation_manager;
    $this->stringTranslation = $string_translation;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('content_translation.manager'),
      $container->get('string_translation'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($basePluginDefinition) {
    // Create tabs for all possible entity types.
    foreach ($this->contentTranslationManager->getSupportedEntityTypes() as $entityTypeId => $entityType) {
      $translationRouteName = "entity.$entityTypeId.simple_entity_translations_entity_edit";

      $baseRouteName = "entity.$entityTypeId.canonical";
      $this->derivatives[$translationRouteName] = [
        'entity_type' => $entityTypeId,
        'title' => $this->t('Translate list'),
        'route_name' => $translationRouteName,
        'base_route' => $baseRouteName,
      ] + $basePluginDefinition;

      $bundleEntityTypeId = $entityType->getBundleEntityType();
      if (isset($bundleEntityTypeId)) {
        $definition = $this->entityTypeManager->getDefinition($bundleEntityTypeId);
        if ($definition->getLinkTemplate('overview-form')) {
          $baseRouteName = "entity.$bundleEntityTypeId.overview_form";
        }
        else {
          $baseRouteName = "entity.$bundleEntityTypeId.edit_form";
        }
        $translationRouteName = "entity.$bundleEntityTypeId.simple_entity_translations_list_edit";

        $this->derivatives[$translationRouteName] = [
          'entity_type' => $bundleEntityTypeId,
          'title' => $this->t('Translate @entity_plural_label', ['@entity_plural_label' => $entityType->getPluralLabel()]),
          'route_name' => $translationRouteName,
          'base_route' => $baseRouteName,
        ] + $basePluginDefinition;
      }

    }
    return parent::getDerivativeDefinitions($basePluginDefinition);
  }

}
