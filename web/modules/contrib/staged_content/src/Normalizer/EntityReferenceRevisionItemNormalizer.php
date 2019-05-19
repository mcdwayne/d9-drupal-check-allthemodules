<?php

namespace Drupal\staged_content\Normalizer;

use Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem;

/**
 * Defines a class for normalizing EntityReferenceRevisionItems.
 */
class EntityReferenceRevisionItemNormalizer extends EntityReferenceFieldItemNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $format = ['storage_json'];

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = EntityReferenceRevisionsItem::class;

  /**
   * {@inheritdoc}
   */
  protected function constructValue($data, $context) {
    $value = parent::constructValue($data, $context);
    if ($value) {
      $value['target_revision_id'] = $this->loadTargetEntity($data, $context)->getRevisionId();
    }
    return $value;
  }

}
