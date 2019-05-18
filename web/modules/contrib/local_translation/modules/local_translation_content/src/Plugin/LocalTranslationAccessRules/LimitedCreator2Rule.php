<?php

namespace Drupal\local_translation_content\Plugin\LocalTranslationAccessRules;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class LimitedCreator2Rule.
 *
 * @package Drupal\local_translation_content\Plugin\LocalTranslationAccessRules
 *
 * @LocalTranslationAccessRule("local_translation_content_limited_creator_2")
 */
class LimitedCreator2Rule extends AccessRuleBase {

  /**
   * {@inheritdoc}
   */
  protected $permissions = [
    'local_translation_content create content translations',
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
