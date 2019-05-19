<?php

namespace Drupal\translators_content\Plugin\TranslatorsAccessRules;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class LimitedCreator2Rule.
 *
 * @package Drupal\translators_content\Plugin\TranslatorsAccessRules
 *
 * @TranslatorsAccessRule("translators_content_limited_creator_2")
 */
class LimitedCreator2Rule extends AccessRuleBase {

  /**
   * {@inheritdoc}
   */
  protected $permissions = [
    'translators_content create content translations',
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
