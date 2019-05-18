<?php

namespace Drupal\measuremail\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\measuremail\ConfigurableMeasuremailElementInterface;
use Drupal\measuremail\MeasuremailElementsInterface;
use Drupal\measuremail\MeasuremailInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a base form for measuremail elements.
 */
abstract class MeasuremailElementFormBase extends FormBase {

  /**
   * The measuremail form.
   *
   * @var \Drupal\measuremail\MeasuremailInterface
   */
  protected $measuremail;

  /**
   * The measuremail element.
   *
   * @var \Drupal\measuremail\MeasuremailElementsInterface|\Drupal\measuremail\ConfigurableMeasuremailElementInterface
   */
  protected $measuremailElement;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'measuremail_element_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\measuremail\MeasuremailInterface $measuremail
   *   The measuremail form.
   * @param string $measuremail_element
   *   The measuremail element ID.
   *
   * @return array
   *   The form structure.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function buildForm(array $form, FormStateInterface $form_state, MeasuremailInterface $measuremail = NULL, $measuremail_element = NULL) {
    $this->measuremail = $measuremail;
    try {
      $this->measuremailElement = $this->prepareMeasuremailElement($measuremail_element);
    } catch (PluginNotFoundException $e) {
      throw new NotFoundHttpException("Invalid element id: '$measuremail_element'.");
    }
    $request = $this->getRequest();

    if (!($this->measuremailElement instanceof ConfigurableMeasuremailElementInterface)) {
      throw new NotFoundHttpException();
    }

    $form['uuid'] = [
      '#type' => 'value',
      '#value' => $this->measuremailElement->getUuid(),
    ];
    $form['id'] = [
      '#type' => 'value',
      '#value' => $this->measuremailElement->getPluginId(),
    ];

    $form['data'] = [];
    $subform_state = SubformState::createForSubform($form['data'], $form, $form_state);
    $form['data'] = $this->measuremailElement->buildConfigurationForm($form['data'], $subform_state);
    $form['data']['#tree'] = TRUE;

    // Check the URL for a weight, then the measuremail element, otherwise use default.
    $form['weight'] = [
      '#type' => 'hidden',
      '#value' => $request->query->has('weight') ? (int) $request->query->get('weight') : $this->measuremailElement->getWeight(),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => $this->measuremail->toUrl('edit-form'),
      '#attributes' => ['class' => ['button']],
    ];
    return $form;
  }

  /**
   * Converts a measuremail element ID into an object.
   *
   * @param string $measuremail_element
   *   The measuremail element ID.
   *
   * @return \Drupal\measuremail\MeasuremailElementsInterface
   *   The measuremail element object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  abstract protected function prepareMeasuremailElement($measuremail_element);

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // The measuremail element configuration is stored in the 'data' key in the form,
    // pass that through for validation.
    $this->measuremailElement->validateConfigurationForm($form['data'], SubformState::createForSubform($form['data'], $form, $form_state));
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //$form_state->cleanValues();

    // The measuremail element configuration is stored in the 'data' key in the form,
    // pass that through for submission.
    $this->measuremailElement->submitConfigurationForm($form['data'], SubformState::createForSubform($form['data'], $form, $form_state));

    $this->measuremailElement->setWeight($form_state->getValue('weight'));
    if (!$this->measuremailElement->getUuid()) {
      $this->measuremail->addMeasuremailElement($this->measuremailElement->getConfiguration());
    }
    $this->measuremail->save();

    drupal_set_message($this->t('The measuremail element was successfully saved.'));
    $form_state->setRedirectUrl($this->measuremail->urlInfo('edit-form'));
  }

}
