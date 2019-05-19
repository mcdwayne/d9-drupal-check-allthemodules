<?php

namespace Drupal\translation_views;

use Drupal\content_translation\ContentTranslationHandlerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Class EntityTranslationInfo.
 *
 * Stores entity translation related propertie used by
 * operations link builder functions as a trait.
 *
 * @package Drupal\translation_views
 */
class EntityTranslationInfo {

  /**
   * Entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  public $entity;
  /**
   * Entity type id.
   *
   * @var string
   */
  public $entityTypeId;
  /**
   * Entity type instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  public $entityType;
  /**
   * Translation access handler.
   *
   * @var \Drupal\content_translation\ContentTranslationHandlerInterface
   */
  public $accessHandler;
  /**
   * Source language.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  public $sourceLanguage;
  /**
   * Target language.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  public $targetLanguage;

  /**
   * {@inheritdoc}
   */
  public function __construct(ContentEntityInterface $entity, ContentTranslationHandlerInterface $accessHandler, LanguageInterface $targetLanguage, LanguageInterface $sourceLanguage) {
    $this->entity = $entity;
    $this->accessHandler = $accessHandler;

    $this->sourceLanguage = $sourceLanguage;
    $this->targetLanguage = $targetLanguage;

    $this->entityTypeId = $entity->getEntityTypeId();
    $this->entityType   = $entity->getEntityType();
  }

  /**
   * A wrapper for getting translation access per operation.
   *
   * @see \Drupal\content_translation\ContentTranslationHandlerInterface::getTranslationAccess,
   * for available operations.
   */
  public function getTranslationAccess($operation) {
    $access = $this->accessHandler->getTranslationAccess($this->entity, $operation);
    return $access->isAllowed();
  }

}
