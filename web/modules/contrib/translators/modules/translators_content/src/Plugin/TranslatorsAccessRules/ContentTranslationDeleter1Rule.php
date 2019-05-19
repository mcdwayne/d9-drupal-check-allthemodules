<?php

namespace Drupal\translators_content\Plugin\TranslatorsAccessRules;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class ContentTranslationDeleter1Rule.
 *
 * @package Drupal\translators_content\Plugin\TranslatorsAccessRules
 *
 * @TranslatorsAccessRule("translators_content_ct_deleter_1")
 */
class ContentTranslationDeleter1Rule extends AccessRuleBase {

  /**
   * {@inheritdoc}
   */
  protected $limited = FALSE;
  /**
   * {@inheritdoc}
   */
  protected $permissions = ['delete content translations'];

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
    if ($operation !== 'delete') {
      return FALSE;
    }
    return parent::isAllowed($operation, $entity, $langcode);
  }

}
