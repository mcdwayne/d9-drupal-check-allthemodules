<?php

namespace Drupal\Tests\sir_trevor\Unit\TestDoubles;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use IteratorAggregate;
use Traversable;

class FieldItemListDummy implements IteratorAggregate, FieldItemListInterface {

  /**
   * {@inheritdoc}
   */
  public function access($operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($offset) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet($offset) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($offset, $value) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($offset) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function setLangcode($langcode) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinition() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($setting_name) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function defaultAccess($operation = 'view', AccountInterface $account = NULL) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function filterEmptyItems() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function __get($property_name) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function __set($property_name, $value) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function __isset($property_name) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function __unset($property_name) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function view($display_options = array()) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function generateSampleItems($count = 1) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, FormStateInterface $form_state) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormValidate(array $element, array &$form, FormStateInterface $form_state) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public static function processDefaultValue($default_value, FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function equals(FieldItemListInterface $list_to_compare) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function getDataDefinition() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefinition() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function get($index) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function set($index, $value) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function first() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function appendItem($value = NULL) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function removeItem($index) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function filter($callback) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($name) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function getString() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function getParent() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function getRoot() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyPath() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function setContext($name = NULL, TraversableTypedDataInterface $parent = NULL) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function hasAffectingChanges(FieldItemListInterface $original_items, $langcode) {
    // Intentionally left empty. Dummies don't do anything.
  }
}
