<?php

namespace Drupal\tracking_number;

use Drupal\Core\TypedData\TypedData;
use Drupal\tracking_number\Plugin\TrackingNumberTypeManager;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * A computed property for a tracking number's human-readable type label.
 */
class TypeLabelComputed extends TypedData {

  /**
   * Cached label.
   *
   * @var string|null
   */
  protected $label = NULL;

  /**
   * The tracking number type manager service.
   *
   * @var \Drupal\tracking_number\Plugin\TrackingNumberTypeManager
   */
  protected $trackingNumberTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);

    // @todo Use dependency injection to obtain this service when it becomes
    // possible.
    // @see https://www.drupal.org/project/drupal/issues/2053415
    $this->trackingNumberTypeManager = \Drupal::service('plugin.manager.tracking_number_type');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    if ($this->label !== NULL) {
      return $this->label;
    }

    $item = $this->getParent();

    // Use the TrackingNumberType plugin's label if we can get it.
    if (isset($item->type)
      && $item->type !== ''
      && ($definition = $this->trackingNumberTypeManager->getDefinition($item->type, FALSE))) {
      $this->label = $definition['label'];
    }

    return $this->label;
  }

}
