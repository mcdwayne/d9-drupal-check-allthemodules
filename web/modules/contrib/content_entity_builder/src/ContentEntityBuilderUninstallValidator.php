<?php

namespace Drupal\content_entity_builder;

use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;

/**
 * Validates module uninstall readiness based on existing content entities.
 */
class ContentEntityBuilderUninstallValidator implements ModuleUninstallValidatorInterface {
  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new ContentUninstallValidator.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntityManagerInterface $entity_manager, TranslationInterface $string_translation) {
    $this->entityManager = $entity_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    if ($module != 'content_entity_builder') {
      return [];
    }

    $entity_types = $this->entityManager->getDefinitions();
    $reasons = [];
    foreach ($entity_types as $entity_type) {
      if ($module == $entity_type->getProvider() && $entity_type instanceof ContentEntityTypeInterface) {
        $reasons[] = $this->t('You need delete the entity type config first: @entity_type. <a href=":url">Remove @entity_type</a>.', [
          '@entity_type' => $entity_type->getLabel(),
          ':url' => Url::fromRoute('entity.content_type.collection')->toString(),
        ]);
      }
    }
    return $reasons;
  }

}
