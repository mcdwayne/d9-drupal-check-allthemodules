<?php

namespace Drupal\availability_timing;

use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedData;

/**
 * A computed property for availability timing field items.
 *
 * Required settings (below the definition's 'settings' key) are:
 *  - timing source: The timing property containing the to be computed date.
 */
class AvailabilityTimingComputed extends TypedData {

  /**
   * Cached computed availability timing.
   *
   * @var array|null
   */
  protected $availabilityTiming = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    if (!$definition->getSetting('timing source')) {
      throw new \InvalidArgumentException("The definition's 'timing source' key has to specify the name of the timing property to be computed.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    if ($this->availabilityTiming !== NULL) {
      return $this->availabilityTiming;
    }

    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    $item = $this->getParent();
    $timing = $item->{($this->definition->getSetting('timing source'))};

    $availabilityTiming['start_date_day'] = $item->start_date_day ? $item->start_date_day : NULL;
    $availabilityTiming['start_date_month'] = $item->start_date_month ? $item->start_date_month : NULL;
    $availabilityTiming['end_date_day'] = $item->end_date_day ? $item->end_date_day : NULL;
    $availabilityTiming['end_date_month'] = $item->end_date_month ? $item->end_date_month : NULL;
    $availabilityTiming['sun'] = $item->sun ? $item->sun : NULL;
    $availabilityTiming['mon'] = $item->mon ? $item->mon : NULL;
    $availabilityTiming['tue'] = $item->tue ? $item->tue : NULL;
    $availabilityTiming['wed'] = $item->wed ? $item->wed : NULL;
    $availabilityTiming['thu'] = $item->thu ? $item->thu : NULL;
    $availabilityTiming['fri'] = $item->fri ? $item->fri : NULL;
    $availabilityTiming['sat'] = $item->sat ? $item->sat : NULL;
    $availabilityTiming['timing'] = $timing;
    $this->availabilityTiming = $availabilityTiming;
    return $this->availabilityTiming;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->availabilityTiming = $value;
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}
