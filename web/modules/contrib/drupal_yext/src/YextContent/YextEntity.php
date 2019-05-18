<?php

namespace Drupal\drupal_yext\YextContent;

use Drupal\Core\Entity\Entity;
use Drupal\drupal_yext\traits\CommonUtilities;

/**
 * Represents a Yext-specific entity, wrapper around Drupal entities.
 *
 * Any Drupal node which has a corresponding Yext entity can be
 * represented as a subclass of this, for example, see YextTargetNode.
 */
class YextEntity {

  use CommonUtilities;

  /**
   * The domain-specific alias, for example /about-us, or an empty string.
   *
   * @return string
   *   If empty, then no alias could be found, otherwise an alias string
   *   like /about-us. This does not necessary correspond to the internal
   *   alias for an entity, as many hospitals can use, for example,
   *   /about-us, which is then stored internally as
   *   /domain-specific/<hospital-nid>/about-us. See ./README.md for details.
   *
   * @throws Exception
   */
  public function domainAlias() : string {
    return '';
  }

  /**
   * Getter for $this->drupal_entity.
   *
   * @throws \Exception
   */
  public function drupalEntity() {
    if (empty($this->drupal_entity)) {
      throw new \Exception('Please generate or set an entity before calling drupalEntity().');
    }
    return $this->drupal_entity;
  }

  /**
   * Given a field name, return from the value all mails separated by commas.
   *
   * @param string $field
   *   A field name which can exist in this entity.
   *
   * @return string
   *   A list of mails such as '', 'one@example.com' or
   *   'one@example.com,two@example.com'.
   */
  public function fieldToCommaSeparatedMailAddresses(string $field) : string {
    $mails = [];
    $entity = $this->drupalEntity();

    $field = $entity->get($field);
    if (!$field) {
      return '';
    }
    $value = $field->getValue();
    foreach ($value as $row) {
      if (!empty($row['value'])) {
        $comma_separated = $row['value'];
        $candidates = explode(',', $comma_separated);
        foreach ($candidates as $candidate) {
          $trimmed_candidate = trim($candidate);
          if (\Drupal::service('email.validator')->isValid($trimmed_candidate)) {
            $mails[] = $trimmed_candidate;
          }
        }
      }
    }

    return implode(',', $mails);
  }

  /**
   * Get a field value.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return mixed
   *   Value of the field or an empty string.
   *
   * @throws Exception
   */
  public function fieldValue(string $field_name) {
    $field = $this->drupalEntity()->get($field_name)->getValue();
    return isset($field[0]['value']) ? $field[0]['value'] : '';
  }

  /**
   * Generate a new entity, and save it.
   */
  public function generate() {
    throw new \Exception('I do not know how to generate an entity of this type.');
  }

  /**
   * Set the entity.
   *
   * @param Entity $drupal_entity
   *   A Drupal entity.
   */
  public function setEntity(Entity $drupal_entity) {
    $this->drupal_entity = $drupal_entity;
  }

  /**
   * Set a simple text field value.
   *
   * @param string $field_name
   *   The field name.
   * @param string $field_value
   *   The string value to set.
   *
   * @throws Exception
   */
  public function setFieldValue(string $field_name, string $field_value) {
    $this->drupal_entity->set($field_name, $field_value);
  }

  /**
   * The entity id.
   *
   * @return int
   *   The entity id.
   *
   * @throws Exception
   */
  public function id() : int {
    return $this->drupal_entity->id();
  }

  /**
   * Saves this entity.
   *
   * @throws Exception
   */
  public function save() {
    return $this->drupal_entity->save();
  }

  /**
   * A single boolean value.
   *
   * @param string $field
   *   A field name.
   *
   * @return bool
   *   A value.
   *
   * @throws Exception
   */
  public function singleBoolValue(string $field) : bool {
    $value = $this->drupalEntity()->get($field)->getValue();
    return (!empty($value[0]['value']));
  }

  /**
   * Get a single string value from a field of this entity.
   *
   * @param string $field
   *   A field name.
   *
   * @return string
   *   A field value, or ''.
   */
  public function singleStringValue(string $field) : string {
    try {
      $value = $this->drupalEntity()->get($field)->getValue();
      return !empty($value[0]['value']) ? $value[0]['value'] : '';
    }
    catch (\Throwable $t) {
      $this->watchdogThrowable($t);
      return 't';
    }
  }

  /**
   * Get an array of spoke sites where to view this entity if possible.
   *
   * @return array
   *   Associative array with "spokes", itself an array, and "error", a string.
   *
   * @throws Exception
   */
  public function spoke() : array {
    return [
      'error' => 'You can only view certain nodes on the spoke site, not this one.',
    ];
  }

  /**
   * The system alias, for example /about-us, or an empty string.
   *
   * @return string
   *   If empty, then no alias could be found, otherwise an system alias string
   *   like /domain-specific/<hospital-nid>/about-us. This does not necessary
   *   correspond to the string actually used to access content, as many
   *   hospitals can use, for example, /about-us, which is then stored
   *   internally as /domain-specific/<hospital-nid>/about-us. See ./README.md
   *   for details.
   *
   * @throws Exception
   */
  public function systemAlias() {
    return '';
  }

}
