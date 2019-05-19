<?php

namespace Drupal\simple_entity_translations\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic contextual links for entity translation.
 */
class SimpleEntityTranslationsContextualLinks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;
  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  /**
   * Constructs a new SimpleEntityTranslationsContextualLinks.
   *
   * @param \Drupal\content_translation\ContentTranslationManagerInterface $content_translation_manager
   *   The content translation manager.
   */
  public function __construct(ContentTranslationManagerInterface $content_translation_manager) {
    $this->contentTranslationManager = $content_translation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('content_translation.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Create contextual links for translatable entity types.
    foreach ($this->contentTranslationManager->getSupportedEntityTypes() as $entityTypeId => $entityType) {
      $this->derivatives[$entityTypeId]['title'] = $this->t('Translate list');
      $this->derivatives[$entityTypeId]['route_name'] = "entity.$entityTypeId.simple_entity_translations_entity_edit";
      $this->derivatives[$entityTypeId]['group'] = $entityTypeId;

      $bundleEntityTypeId = $entityType->getBundleEntityType();
      if (isset($bundleEntityTypeId)) {
        $this->derivatives[$bundleEntityTypeId]['title'] = $this->t('Translate @entity_plural_label', ['@entity_plural_label' => $entityType->getPluralLabel()]);
        $this->derivatives[$bundleEntityTypeId]['route_name'] = "entity.$bundleEntityTypeId.simple_entity_translations_list_edit";
        $this->derivatives[$bundleEntityTypeId]['group'] = $bundleEntityTypeId;
      }
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
