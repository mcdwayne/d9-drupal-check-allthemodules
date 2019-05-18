<?php

namespace Drupal\drd;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface RemoteActionsInterface.
 *
 * @package Drupal\drd
 */
interface RemoteActionsInterface {

  const MODE_HOST = 'drd_host';
  const MODE_CORE = 'drd_core';
  const MODE_DOMAIN = 'drd_domain';

  /**
   * Set the action manager mode.
   *
   * @param string $mode
   *   The mode.
   *
   * @return $this
   */
  public function setMode($mode);

  /**
   * Set the action manager term.
   *
   * @param string|\Drupal\taxonomy\Entity\Term $term
   *   The term.
   *
   * @return $this
   */
  public function setTerm($term);

  /**
   * Get all action plugins.
   *
   * @return \Drupal\drd\Plugin\Action\BaseInterface[]
   *   All action plugins depending on mode and/or term.
   */
  public function getActionPlugins();

  /**
   * Get the selected action.
   *
   * @return \Drupal\system\ActionConfigEntityInterface
   *   The selected action.
   */
  public function getSelectedAction();

  /**
   * Get the number of executed actions.
   *
   * @return int
   *   Number of executed actions.
   */
  public function getExecutedCount();

  /**
   * Build a settings form for remote actions.
   *
   * @param array $form
   *   The form array .
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param array $options
   *   Options for the form.
   */
  public function buildForm(array &$form, FormStateInterface $form_state, array $options = []);

  /**
   * Validate the action settings.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function validateForm(array &$form, FormStateInterface $form_state);

  /**
   * Set the selected entities for execution.
   *
   * @param \Drupal\drd\Entity\BaseInterface|\Drupal\drd\Entity\BaseInterface[] $entities
   *   The selected entities.
   *
   * @return $this
   */
  public function setSelectedEntities($entities);

  /**
   * Submit the action execution form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function submitForm(array &$form, FormStateInterface $form_state);

}
