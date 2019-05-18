<?php

namespace Drupal\form_delegate;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for the entity form alter plugin.
 *
 * @package Drupal\form_delegate
 */
class EntityFormDelegatePluginBase extends PluginBase implements EntityFormDelegatePluginInterface {

  /**
   * The entity of the form.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldPreventOriginalSubmit() {
    return isset($this->pluginDefinition['preventOriginalSubmit']) ? (bool) $this->pluginDefinition['preventOriginalSubmit'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface $formState) {

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $formState) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {

  }

}
