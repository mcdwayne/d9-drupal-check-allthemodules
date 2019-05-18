<?php

namespace Drupal\gtm_datalayer_forms\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\gtm_datalayer\Plugin\DataLayerProcessorBase;

/**
 * Provides a form base class for a GTM dataLayer Processor.
 */
class DataLayerProcessorFormBase extends DataLayerProcessorBase implements DataLayerProcessorFormBaseInterface {

  /**
   * The nested array of form elements that comprise the form.
   *
   * @var array
   */
  protected $form;

  /**
   * The name of the form handler.
   *
   * @var string
   */
  protected $formHandler;

  /**
   * The name of the form itself.
   *
   * @var string
   */
  protected $formId;

  /**
   * The current state of the form.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * {@inheritdoc}
   */
  public function configure(array &$form, FormStateInterface $form_state, string $form_id, string $form_handler) {
    $this->setForm($form);
    $this->setFormHandler($form_handler);
    $this->setFormId($form_id);
    $this->setformState($form_state);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    if ($this->currentRequest->attributes->has('exception')) {
      $this->statusCode = $this->currentRequest->attributes->get('exception')->getStatusCode();
      $this->addTag(['status_code'], $this->statusCode);
    }

    if (!$this->isRequestException() && $this->getForm() !== NULL) {
      $this->addTag(['forms', $this->getFormId()], $this->getFormId());
    }

    return $this->getTags();
  }

  /**
   * Gets the form array.
   *
   * @return array|null
   */
  protected function getForm() {
    return $this->form;
  }

  /**
   * Sets the form array.
   *
   * @param array $form
   *   The form array.
   *
   * @return $this
   */
  protected function setForm(&$form) {
    $this->form = &$form;

    return $this;
  }

  /**
   * Gets the name of the form handler.
   *
   * @return string
   */
  protected function getFormHandler() {
    return $this->formHandler;
  }

  /**
   * Sets the name of the form handler.
   *
   * @param string $form_handler
   *   The name of the form handler.
   *
   * @return $this
   */
  protected function setFormHandler($form_handler) {
    $this->formHandler = $form_handler;

    return $this;
  }

  /**
   * Gets the name of the form itself.
   *
   * @return string
   */
  protected function getFormId() {
    return $this->formId;
  }

  /**
   * Sets the name of the form itself.
   *
   * @param string $form_id
   *   The name of the form itself.
   *
   * @return $this
   */
  protected function setFormId($form_id) {
    $this->formId = $form_id;

    return $this;
  }

  /**
   * Gets the current state of the form.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   */
  protected function getFormState() {
    return $this->formState;
  }

  /**
   * Sets the current state of the form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return $this
   */
  protected function setFormState(FormStateInterface $form_state) {
    $this->formState = $form_state;

    return $this;
  }

}
