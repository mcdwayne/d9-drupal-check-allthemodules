<?php

namespace Drupal\form_delegate\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\form_delegate\EntityFormDelegateManager;
use Drupal\form_delegate\Event\EntityFormInitEvent;

/**
 * Entity form trait that delegates the primary methods to alter plugins.
 *
 * @package Drupal\form_delegate\Form
 */
trait EntityFormDelegationTrait {

  /**
   * The form delegate manager.
   *
   * @var \Drupal\form_delegate\EntityFormDelegateManager
   */
  protected $delegateManager;

  /**
   * Sets the delegate manager.
   *
   * @param \Drupal\form_delegate\EntityFormDelegateManager $delegateManager
   *   The entity form delegate manager.
   *
   * @return $this
   */
  public function setDelegateManager(EntityFormDelegateManager $delegateManager) {
    $this->delegateManager = $delegateManager;
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Entity\EntityForm::init()
   */
  public function init(FormStateInterface $formState) {
    /** @var \Drupal\Core\Entity\EntityForm $this */
    parent::init($formState);

    $eventDispatcher = \Drupal::service('event_dispatcher');

    $event = new EntityFormInitEvent($this, $formState);
    $eventDispatcher->dispatch(EntityFormInitEvent::EVENT, $event);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // This method might be replaced by the dynamic declaration.
    // @see \Drupal\form_delegate\EntityTypeManager.
    $form += parent::buildForm($form, $form_state);

    $alterPlugins = $this->getAlterPlugins($this->getEntity(), $form_state);
    if ($this->shouldPreventOriginalSubmit($alterPlugins)) {
      // @TODO remove submit action from all submit buttons.
      unset($form['actions']['submit']['#submit'][1]);
    }

    $this->delegateFormMethod('buildForm', $form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $this->delegateFormMethod('validateForm', $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $alterPlugins = $this->getAlterPlugins($this->getEntity(), $form_state);
    // As a first debug without these lines the form submit doesn't work well.
    // A second check works without these lines.
    // $form_state->cleanValues();.
    // $this->entity = $this->buildEntity($form, $form_state);.
    if (!$this->shouldPreventOriginalSubmit($alterPlugins)) {
      parent::submitForm($form, $form_state);
    }
    $this->delegateFormMethod('submitForm', $form, $form_state);
  }

  /**
   * Calls method on all entity form alter plugins.
   *
   * @param string $method
   *   One of: formSubmit, formValidate, formBuild.
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current form state.
   */
  protected function delegateFormMethod($method, array &$form, FormStateInterface $formState) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->getEntity();
    $alterPlugins = $this->getAlterPlugins($entity, $formState);

    foreach ($alterPlugins as $formAlter) {
      $formAlter->setEntity($entity);
      $formAlter->$method($form, $formState);
    }
  }

  /**
   * Checks if original submit is interrupted.
   *
   * @param \Drupal\form_delegate\EntityFormDelegatePluginInterface[] $alterPlugins
   *   The alter plugins.
   *
   * @return bool
   *   TRUE if original submit is interrupted or FALSE otherwise.
   */
  protected function shouldPreventOriginalSubmit(array $alterPlugins) {
    $original_prevent = FALSE;
    foreach ($alterPlugins as $alterPlugin) {
      if ($alterPlugin->shouldPreventOriginalSubmit() == TRUE) {
        $original_prevent = TRUE;
        break;
      }
    }

    return $original_prevent;
  }

  /**
   * Gets all alters from definition.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity interface.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return \Drupal\form_delegate\EntityFormDelegatePluginInterface[]
   *   The alter plugins.
   */
  protected function getAlterPlugins(EntityInterface $entity, FormStateInterface $form_state) {
    if ($display = $form_state->get('form_display')) {
      $display = $display->getMode();
    }

    $alterPlugins = $this->delegateManager->getAlters(
      $entity->getEntityTypeId(),
      $entity->bundle(),
      $this->getOperation(),
      $display
    );

    return $alterPlugins;
  }

}
