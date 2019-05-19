<?php

namespace Drupal\translators_content\Plugin\TranslatorsAccessRules;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class LimitedDeleter2Rule.
 *
 * @package Drupal\translators_content\Plugin\TranslatorsAccessRules
 *
 * @TranslatorsAccessRule("translators_content_limited_deleter_2")
 */
class LimitedDeleter2Rule extends AccessRuleBase {

  /**
   * {@inheritdoc}
   */
  protected $permissions = [
    'translators_content delete content translations',
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
