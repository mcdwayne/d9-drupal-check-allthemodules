<?php

namespace Drupal\form_delegate;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for the entity form alter plugin.
 *
 * @package Drupal\form_delegate
 */
interface EntityFormDelegatePluginInterface {

  /**
   * Alters the entity form build.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Current form state.
   */
  public function buildForm(array &$form, FormStateInterface $formState);

  /**
   * Extends entity form validation.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Current form state.
   */
  public function validateForm(array &$form, FormStateInterface $formState);

  /**
   * Extends entity form submissions.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Current form state.
   */
  public function submitForm(array &$form, FormStateInterface $formState);

  /**
   * Set the form entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  public function setEntity(EntityInterface $entity);

  /**
   * Get the form entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  public function getEntity();

  /**
   * Checks if original submit is interrupted.
   *
   * @return mixed
   *   The alter plugin value.
   */
  public function shouldPreventOriginalSubmit();

}
