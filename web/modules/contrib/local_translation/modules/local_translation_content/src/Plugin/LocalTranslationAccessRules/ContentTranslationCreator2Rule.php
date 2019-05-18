<?php

namespace Drupal\local_translation_content\Plugin\LocalTranslationAccessRules;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class ContentTranslationCreator2Rule.
 *
 * @package Drupal\local_translation_content\Plugin\LocalTranslationAccessRules
 *
 * @LocalTranslationAccessRule("local_translation_content_ct_creator_2")
 */
class ContentTranslationCreator2Rule extends AccessRuleBase {

  /**
   * {@inheritdoc}
   */
  protected $limited = FALSE;
  /**
   * {@inheritdoc}
   */
  protected $permissions = [
    'create content translations',
    'translate any entity',
  ];

  /**
   * {@inheritdoc}
   */
  public function isAllowed($operation, ContentEntityInterface $entity, $langcode = NULL) {
    if ($operation !== 'create') {
      return FALSE;
    }
    return parent::isAllowed($operation, $entity, $langcode);
  }

}
