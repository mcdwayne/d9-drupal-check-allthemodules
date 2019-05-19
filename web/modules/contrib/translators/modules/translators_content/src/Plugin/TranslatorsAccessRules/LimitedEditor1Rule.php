<?php

namespace Drupal\translators_content\Plugin\TranslatorsAccessRules;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class LimitedEditor1Rule.
 *
 * @package Drupal\translators_content\Plugin\TranslatorsAccessRules
 *
 * @TranslatorsAccessRule("translators_content_limited_editor_1")
 */
class LimitedEditor1Rule extends AccessRuleBase {

  /**
   * {@inheritdoc}
   */
  protected $permissions = [
    'translators_content update content translations',
  ];

  /**
   * {@inheritdoc}
   */
  protected function addDynamicPermissions(ContentEntityInterface $entity) {
    $bundle         = $entity->bundle();
    $entity_type_id = $entity->getEntityTypeId();

    $this->permissions[] = "translate $bundle $entity_type_id";
  }

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
