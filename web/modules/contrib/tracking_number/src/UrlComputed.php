<?php

namespace Drupal\tracking_number;

use Drupal\Core\TypedData\TypedData;
use Drupal\Core\Url;
use Drupal\tracking_number\Plugin\TrackingNumberTypeManager;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * A computed property for a tracking number's Url object.
 */
class UrlComputed extends TypedData {

  /**
   * Cached Url.
   *
   * @var \Drupal\Core\Url|null
   */
  protected $url = NULL;

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
    if ($this->url !== NULL) {
      return $this->url;
    }

    $item = $this->getParent();

    // If we have a type, a number, and the type maps to a valid plugin, get the
    // tracking URL from the plugin.
    $type_not_empty = (isset($item->type) && $item->type !== '');
    $value_not_empty = (isset($item->value) && $item->value !== '');
    if ($type_not_empty && $value_not_empty && $this->trackingNumberTypeManager->getDefinition($item->type, FALSE)) {
      $type_plugin = $this->trackingNumberTypeManager->createInstance($item->type);
      $this->url = $type_plugin->getTrackingUrl($item->value);
    }

    return $this->url;
  }

}
