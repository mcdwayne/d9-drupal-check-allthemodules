<?php

namespace Drupal\smallads_geo\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Represents a configurable entity path field.
 */
class OwnerCoordinatesItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * The field name of the first geofield found on the user entity.
   *
   * @var string
   */
  protected $previous = [];

  /**
   * @var string
   */
  protected $fallback;

  public function __construct($definition, $name, $parent, ConfigFactoryInterface $config_factory) {
    parent::__construct($definition, $name, $parent);
    $this->fallback = $config_factory->get('smallads_index.settings')->get('fallback_point');
  }

  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL) {
    return new static(
      $definition,
      $name,
      $parent,
      \Drupal::getContainer()->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @todo, for multiple instances with the same owner, offset the field a bit each time.
   */
  protected function computeValue() {
    $fieldnames = smallads_geo_user_field_map();
    $owner = $this->getEntity()->getOwner();
    $this->previous[] = $owner->id();
    if ($value = $owner->{reset($fieldnames)}->value) {
      // Vary the location by this multiple of the count
      $scale = array_count_values($this->previous)[$owner->id()];
    }
    else {
      $value = $this->fallback;
      $scale = 10;
    }
    $this->displace($value, $scale);
    $this->list[0] = $this->createItem(0, $value);
  }

  /**
   * Helper to move a point randomly
   *
   * @param string $point
   * @param integer $scale
   */
  private function displace(&$point, $scale) {
    extract(smallads_geo_convert_point($point));
    foreach (['lat', 'lon'] as $val) {
      $$val += rand(-5, 5)* 0.001*$scale;
    }
    $point = smallads_geo_convert_latlon($lat, $lon);
  }

}
