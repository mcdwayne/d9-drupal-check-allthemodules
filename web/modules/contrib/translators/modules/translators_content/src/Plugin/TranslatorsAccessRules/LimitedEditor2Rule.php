<?php

namespace Drupal\translators_content\Plugin\TranslatorsAccessRules;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class LimitedEditor2Rule.
 *
 * @package Drupal\translators_content\Plugin\TranslatorsAccessRules
 *
 * @TranslatorsAccessRule("translators_content_limited_editor_2")
 */
class LimitedEditor2Rule extends AccessRuleBase {

  /**
   * {@inheritdoc}
   */
  protected $permissions = [
    'translators_content update content translations',
    'translate any entity',
  ];

  /**
   * {@inheritdoc}
   */
  public function isAllowed($operation, ContentEntityInterface $entity, $langcode = NULL) {
    if ($operation !== 'edit' && $operation !== 'update') {
      return FALSE;
    }
    return parent::isAllowed($operation, $entity, $langcode);
  }

}
