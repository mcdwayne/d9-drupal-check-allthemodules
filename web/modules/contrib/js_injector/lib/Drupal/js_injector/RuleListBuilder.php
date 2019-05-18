<?php

/**
 * @file
 * Contains \Drupal\js_injector\RuleListBuilder.
 */

namespace Drupal\js_injector;

use Drupal\Component\Utility\String;
use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of js_injector_rule entities.
 *
 * @see \Drupal\js_injector\Entity\Rule
 */
class RuleListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $weightKey = 'weight';

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'js_injector_admin_rules_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Name');
    $header['roles'] = t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = String::placeholder($entity->get('label'));
    $description = String::checkPlain($entity->get('description'));
    $row['roles'] = !empty($this->weightKey) ? array('#markup' => $description) : $description;
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = t('Edit rule');
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#value'] = t('Save changes');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $entities = $this->load();
    // If there are not multiple vocabularies, disable dragging by unsetting the
    // weight key.
    if (count($entities) <= 1) {
      unset($this->weightKey);
    }
    $build = parent::render();
    $build['#empty'] = t('No rules available. <a href="@link">Add rule</a>.', array('@link' => url('admin/config/development/js-injector/add-rule')));
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    parent::submitForm($form, $form_state);
    drupal_set_message(t('The rule order has been saved.'));
  }
}
