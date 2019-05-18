<?php

namespace Drupal\entity_form_block\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides entity form block definitions for each entity type.
 */
class EntityFormDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs new EntityFormDeriver.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface && !$entity_type->isInternal() && $entity_type->hasFormClasses()) {
        $this->derivatives[$entity_type_id] = $base_plugin_definition;
        $this->derivatives[$entity_type_id]['admin_label'] = $this->t('Entity form (@label)', ['@label' => $entity_type->getLabel()]);
        $this->derivatives[$entity_type_id]['entity_type_id'] = $entity_type_id;
        $this->derivatives[$entity_type_id]['context'] = [
          'entity' => new ContextDefinition('entity:' . $entity_type_id),
        ];
      }
      if ($entity_type instanceof ContentEntityTypeInterface && !$entity_type->isInternal() && $entity_type->hasKey('uid') && $entity_type_id != 'user') {
        $this->derivatives[$entity_type_id . '_user'] = $base_plugin_definition;
        $this->derivatives[$entity_type_id . '_user']['admin_label'] = $this->t('Entity form (@label) by Owner', ['@label' => $entity_type->getLabel()]);
        $this->derivatives[$entity_type_id . '_user']['entity_type_id'] = $entity_type_id;
        $this->derivatives[$entity_type_id . '_user']['entity_key'] = 'uid';
        $this->derivatives[$entity_type_id . '_user']['context'] = [
          'entity' => new ContextDefinition('entity:user'),
        ];
      }
    }
    return $this->derivatives;
  }

}
