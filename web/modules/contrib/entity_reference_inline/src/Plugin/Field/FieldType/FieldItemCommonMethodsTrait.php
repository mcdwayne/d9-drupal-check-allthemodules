<?php

namespace Drupal\entity_reference_inline\Plugin\Field\FieldType;

/**
 * Provides a trait for common methods defined in the field item.
 *
 * The methods are provided through a trait in order for them to be reusable.
 */
trait FieldItemCommonMethodsTrait {

  /**
   * Whether to skip the pre-save method.
   *
   * @var bool
   */
  public $skipPreSave;

  /**
   * Whether to enforce saving the entity.
   *
   * @internal
   *
   * @var bool
   */
  public $needsSave;

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    $this->skipPreSave = NULL;
    $this->needsSave = NULL;
    return parent::postSave($update);
  }

}
