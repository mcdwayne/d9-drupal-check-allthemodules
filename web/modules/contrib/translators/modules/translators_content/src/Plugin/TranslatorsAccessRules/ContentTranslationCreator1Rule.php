<?php

namespace Drupal\translators_content\Plugin\TranslatorsAccessRules;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class ContentTranslationCreator1Rule.
 *
 * @package Drupal\translators_content\Plugin\TranslatorsAccessRules
 *
 * @TranslatorsAccessRule("translators_content_ct_creator_1")
 */
class ContentTranslationCreator1Rule extends AccessRuleBase {

  /**
   * {@inheritdoc}
   */
  protected $limited = FALSE;
  /**
   * {@inheritdoc}
   */
  protected $permissions = ['create content translations'];

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
    if ($operation !== 'create') {
      return FALSE;
    }
    return parent::isAllowed($operation, $entity, $langcode);
  }

}
