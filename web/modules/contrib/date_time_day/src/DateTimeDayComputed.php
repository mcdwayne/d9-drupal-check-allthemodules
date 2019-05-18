<?php

namespace Drupal\date_time_day;

use Drupal\date_time_day\Plugin\Field\FieldType\DateTimeDayItem;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedData;

/**
 * A computed property for times of date time day field items.
 *
 * Required settings (below the definition's 'settings' key) are:
 *  - date source: The date property containing the to be computed date.
 */
class DateTimeDayComputed extends TypedData {

  /**
   * Cached computed date.
   *
   * @var \DateTime|null
   */
  protected $date = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    if (!$definition->getSetting('date source')) {
      throw new \InvalidArgumentException("The definition's 'date source' key has to specify the name of the date time property to be computed.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($langcode = NULL) {
    if ($this->date !== NULL) {
      return $this->date;
    }

    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    $item = $this->getParent();
    $value = $item->{($this->definition->getSetting('date source'))};
    $datetime_type = $item->getFieldDefinition()->getSetting('datetime_type');
    $storage_format = $datetime_type === DateTimeDayItem::DATEDAY_TIME_DEFAULT_TYPE_FORMAT ? DateTimeDayItem::DATE_TIME_DAY_H_I_FORMAT_STORAGE_FORMAT : DateTimeDayItem::DATE_TIME_DAY_H_I_S_FORMAT_STORAGE_FORMAT;
    try {
      $date = DrupalDateTime::createFromFormat($storage_format, $value);
      if ($date instanceof DrupalDateTime && !$date->hasErrors()) {
        $this->date = $date;
      }
    }
    catch (\Exception $e) {
      // @todo Handle this.
    }
    return $this->date;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->date = $value;
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}
