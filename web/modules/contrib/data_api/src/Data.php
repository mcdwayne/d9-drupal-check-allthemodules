<?php

namespace Drupal\data_api;

use AKlump\Data\Data as DataWithoutEntitySupport;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class Data.
 *
 * Provides a Drupal implementation, which handles language translation on
 * entities and catches errors throw in entity classes.
 *
 * Global Drupal functions have been wrapped in class methods for encapsulation
 * reasons.  Take a look at the class DataMock for how you would decouple this
 * class from Drupal for unit testing, by extending those methods.
 *
 * @package Drupal\data_api
 */
class Data extends DataWithoutEntitySupport implements DrupalDataInterface {

  /**
   * The Drupal entity type to used with this instance.
   *
   * @var null
   */
  protected $entityType = NULL;

  /**
   * {@inheritdoc}
   */
  public function get($subject, $path, $defaultValue = NULL, $valueCallback = NULL) {
    $this->cacheSet(__FUNCTION__, $subject, $path, $defaultValue, $valueCallback);
    if (empty($subject)) {
      return $this->postGet($defaultValue, $defaultValue, $valueCallback, FALSE);
    }

    // Support for d8now entity wrappers.
    if ($subject instanceof EntityInterface) {
      $this->setEntityType($subject->getEntityTypeId());
      $subject = $subject->toArray();
    }

    // This will make sure $path is an array.
    $this->validate($subject, $path);

//    // When we know the entity type, we can use Drupal's field api functions.
//    // This will take care of the translation.
//    if ($subject instanceof EntityInterface
//      && empty($this->cache['get']['level'])
//      && ($field_name = reset($path))
//      && ($bundle = $this->getBundleType($subject))
//      && $this->isField($bundle, $field_name)
//    ) {
//      array_shift($path);
//      if (!($subject = $this->field_get_items($this->getEntityType(), $subject, $field_name))) {
//        return $this->postGet($defaultValue, $defaultValue, $valueCallback, FALSE);
//      }
//      elseif (count($path) === 0) {
//        return $this->postGet($subject, $defaultValue, $valueCallback, TRUE);
//      }
//    }

    try {
      $return = parent::get($subject, $path, $defaultValue, $valueCallback);
    }
    catch (\Exception $exception) {
      watchdog_exception('data_api', $exception);
      $return = $defaultValue;
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function set(&$subject, $path = NULL, $value = NULL, $childTemplate = NULL) {
    static $recursionLevel;
    $recursionLevel++;

    if ($this->cache['carry']['abort']) {
      return $this->resetChain();
    }

    $this->writeArgHandler(func_num_args());
    $this->useCarry($path, $value);
    $this->cacheSet(__FUNCTION__, $subject, $path, $value, $childTemplate);

    // Support for d8now entity wrappers.
    // Future support for other objects can be inserted here; all they need to do is set; data_api.set.original and all will be well
    if ($subject instanceof \Drupal\d8now\Entity\Entity) {
      $this->cache['data_api.set.original'] = $subject;
      $subject = $subject->getEntity();
    }

    $this->validate($subject, $path);

    // Determine if we need to add the language component to the path.
    if (empty($this->cache['set']['level']) && is_object($subject) && ($field_name = reset($path))) {
      $temp = clone $subject;;
      $temp->{$field_name} = isset($temp->{$field_name}) ? $temp->{$field_name} : $childTemplate;
      if (($bundle = $this->getBundleType($temp))
        && $this->isField($bundle, $field_name)
      ) {
        $langcode = $this->field_language($this->getEntityType(), $subject, $field_name);
        if (empty($path[1]) || $path[1] !== $langcode) {
          array_splice($path, 1, 0, array($langcode));

          // Empty item in drupal should always be an array.
          $value = empty($value) && count($path) === 2 ? array() : $value;
          $childTemplate = array();
        }
      }
    };

    try {
      $this->cacheSet('data_api.set');
      $return = parent::set($subject, $path, $value, $childTemplate);

      // This is how we detect that the set process is complete.
      if ($recursionLevel === 1 && !empty($this->cache['data_api.set.original'])) {
        // Now we put back the original subject which was cached during the setting.
        $subject = $this->cache['data_api.set.original'];
        unset($this->cache['data_api.set.original']);
      }

    }
    catch (\Exception $exception) {
      watchdog_exception('data_api', $exception);
      $return = $this->resetChain();
    }

    --$recursionLevel;

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityType($entity_type) {
    $this->entityType = $entity_type;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDate($subject, $path, $defaultValue = NULL, $valueCallback = NULL) {
    $original = $path;
    $key = $this->pathPop($path);
    if (!in_array($key, array(
      'value',
      'value2',
    ))
    ) {
      throw new \InvalidArgumentException("The final component of \"$original\" must be one of: \"value\", \"value2\"");
    }

    list($date_type, $tz_handling) = $this->getDateFieldSettings($path);
    // @see date_default_value().
    $defaults = array(
      'timezone' => $this->date_get_timezone($tz_handling),
      'timezone_db' => $this->date_get_timezone_db($tz_handling),
      'date_type' => $date_type,
    );

    return $this->get($subject, $path, $defaultValue, function ($item, $default, $exists) use ($key, $valueCallback, $path, $defaults) {
      $value = $default;
      if ($exists) {

        $item += $defaults;
        if (!isset($item['value2'])) {
          $item['value2'] = $item['value'];
        }

        // The cached date object.
        if (isset($item['db'][$key])) {
          $value = $item['db'][$key];
        }
        elseif ($item['date_type'] !== 'datetime') {
          throw new \RuntimeException('date_type "' . $item['data_type'] . '" is not yet supported');
        }
        elseif (isset($item[$key]) && isset($item['timezone_db'])) {
          $date_class = class_exists('DateObject') ? '\DateObject' : '\DateTime';
          $value = new $date_class($item[$key], new \DateTimeZone($item['timezone_db']));
        }
      }

      if (is_callable($valueCallback)) {
        $value = $valueCallback($value, $default, $exists);
      }

      return $value;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function setDate(&$subject, $path = NULL, $value = NULL) {
    if (is_object($value) && !method_exists($value, 'getTimeZone')) {
      throw new \InvalidArgumentException("\$value must have getTimeZone() method.");
    }
    elseif (is_object($value) && !method_exists($value, 'format')) {
      throw new \InvalidArgumentException("\$value must have format() method.");
    }
    $original = $path;
    $key = $this->pathPop($path);
    if (!in_array($key, array(
      'value',
      'value2',
    ))
    ) {
      throw new \InvalidArgumentException("The final component of \"$original\" must be one of: \"value\", \"value2\"");
    }

    list($date_type, $tz_handling, $date_format) = $this->getDateFieldSettings($path);
    $item = $this->get($subject, $path, array()) + array(
        'value' => NULL,
        // TODO Support for value2?
        'value2' => NULL,
        'timezone' => $this->date_get_timezone($tz_handling),
        'timezone_db' => $this->date_get_timezone_db($tz_handling),
        'date_type' => $date_type,
      );
    $item['timezone'] = $value ? $value->getTimeZone()
      ->getName() : $item['timezone'];
    $alt_key = $key === 'value' ? 'value2' : 'value';

    if ($item[$alt_key] === $item[$key]) {
      $item[$alt_key] = NULL;
    }

    $item[$key] = $value ? $value->setTimeZone(timezone_open($item['timezone_db']))
      ->format($date_format) : NULL;

    // Collapse to 'und'.
    if (empty($item['value']) && empty($item['value2'])) {
      $this->pathPop($path);
      $item = array();
    }
    if (!empty($item[$key])) {
      $item[$alt_key] = !empty($item[$alt_key]) ? $item[$alt_key] : $item[$key];
    }

    // TODO Handle this correctly.
    unset($item['db'][$key]);

    return $this->set($subject, $path, $item, array());
  }

  /**
   * Return the Drupal settings for a date field.
   *
   * @param string|array $path
   *   The path to the date item.
   *
   * @return array
   *   - 0 The date field type, e.g. datetime
   *   - 1 The timezone handling setting.
   *   - 2 The string for the format method.
   */
  protected function getDateFieldSettings($path) {
    $parts = $this->pathExplode($path);
    $field_name = reset($parts);
    $field = (array) $this->field_info_field($field_name) + array(
        'type' => 'datetime',
        'settings' => array('tz_handling' => 'UTC'),
      );

    return array(
      $field['type'],
      $field['settings']['tz_handling'],
      $this->date_type_format($field['type']),
    );
  }

  /**
   * @codeCoverageIgnore
   */
  protected function date_type_format($type) {
    return date_type_format($type);
  }

  /**
   * Return the bundle type of the entity $subject.
   *
   * @param string $subject
   *
   * @return null|bool
   */
  protected function getBundleType(EntityInterface $subject) {
    return $subject->bundle();
  }

  /**
   * @codeCoverageIgnore
   */
  protected function date_get_timezone($handling, $timezone = '') {
    return date_get_timezone($handling, $timezone = '');
  }

  /**
   * @codeCoverageIgnore
   */
  protected function date_get_timezone_db($handling, $timezone = '') {
    return date_get_timezone_db($handling, $timezone = '');
  }

  /**
   * @codeCoverageIgnore
   */
  protected function entity_extract_ids($entity_type, $entity) {
    return entity_extract_ids($entity_type, $entity);
  }

  /**
   * Tests if $field is a field instance on $bundle.
   *
   * @param string $bundle
   * @param string $field_name
   *
   * @return bool
   */
  protected function isField($bundle, $field_name) {
    return array_key_exists($field_name, $this->field_info_instances($this->getEntityType(), $bundle));
  }

  /**
   * @codeCoverageIgnore
   */
  protected function field_info_field($field_name) {
    // This will allow us to use this when Drupal doesn't know about the field.
    return field_info_field($field_name);
  }

  /**
   * @codeCoverageIgnore
   */
  protected function field_info_instances($entity_type = NULL, $bundle_name = NULL) {
    return field_info_instances($entity_type, $bundle_name);
  }

  /**
   * @codeCoverageIgnore
   */
  protected function field_get_items($entity_type, $entity, $field_name, $langcode = NULL) {
    return field_get_items($entity_type, $entity, $field_name, $langcode);
  }

  /**
   * @codeCoverageIgnore
   */
  protected function field_language($entity_type, $entity, $field_name = NULL, $langcode = NULL) {
    return field_language($entity_type, $entity, $field_name, $langcode);
  }
}
