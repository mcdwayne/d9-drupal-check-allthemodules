<?php

namespace Drupal\local_translation_content\Plugin\LocalTranslationAccessRules;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class ContentTranslationDeleter2Rule.
 *
 * @package Drupal\local_translation_content\Plugin\LocalTranslationAccessRules
 *
 * @LocalTranslationAccessRule("local_translation_content_ct_deleter_2")
 */
class ContentTranslationDeleter2Rule extends AccessRuleBase {

  /**
   * {@inheritdoc}
   */
  protected $limited = FALSE;
  /**
   * {@inheritdoc}
   */
  protected $permissions = [
    'delete content translations',
    'translate any entity',
  ];

  /**
   * {@inheritdoc}
   */
  public function isAllowed($operation, ContentEntityInterface $entity, $langcode = NULL) {
    if ($operation !== 'delete') {
      return FALSE;
    }
    return parent::isAllowed($operation, $entity, $langcode);
  }

}
