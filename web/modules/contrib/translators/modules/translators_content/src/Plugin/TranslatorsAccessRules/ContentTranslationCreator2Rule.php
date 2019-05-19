<?php

namespace Drupal\translators_content\Plugin\TranslatorsAccessRules;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class ContentTranslationCreator2Rule.
 *
 * @package Drupal\translators_content\Plugin\TranslatorsAccessRules
 *
 * @TranslatorsAccessRule("translators_content_ct_creator_2")
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
