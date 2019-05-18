<?php

namespace Drupal\ad_entity\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\ad_entity\Entity\AdEntityInterface;

/**
 * Defines the plugin interface for Advertising view handlers.
 */
interface AdViewInterface extends PluginInspectionInterface {

  /**
   * Builds a renderable array for viewing the given Advertising entity.
   *
   * @param \Drupal\ad_entity\Entity\AdEntityInterface $entity
   *   The Advertising entity being viewed.
   *
   * @return array
   *   The view as a render array.
   */
  public function build(AdEntityInterface $entity);

  /**
   * Returns the elements for the Advertising entity form.
   *
   * @param array $form
   *   The entity form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The corresponding form state.
   * @param \Drupal\ad_entity\Entity\AdEntityInterface $ad_entity
   *   The Advertising entity.
   *
   * @return array
   *   The form elements as array.
   */
  public function entityConfigForm(array $form, FormStateInterface $form_state, AdEntityInterface $ad_entity);

  /**
   * Validate the Advertising entity form.
   *
   * @param array &$form
   *   The entity form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The corresponding form state.
   * @param \Drupal\ad_entity\Entity\AdEntityInterface $ad_entity
   *   The Advertising entity.
   */
  public function entityConfigValidate(array &$form, FormStateInterface $form_state, AdEntityInterface $ad_entity);

  /**
   * Act on submission of the Advertising entity form.
   *
   * @param array &$form
   *   The entity form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The corresponding form state.
   * @param \Drupal\ad_entity\Entity\AdEntityInterface $ad_entity
   *   The Advertising entity.
   */
  public function entityConfigSubmit(array &$form, FormStateInterface $form_state, AdEntityInterface $ad_entity);

}
