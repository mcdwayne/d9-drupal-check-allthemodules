<?php

namespace Drupal\customers_canvas\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for finishing Builder.
 *
 * @package Drupal\customers_canvas\Form
 */
class Builder extends FormBase {

  /**
   * The service used to get the current path from the current session.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $path;

  /**
   * Builder constructor.
   *
   * @param \Drupal\Core\Path\CurrentPathStack $path
   *   Helps define the arguments used in the form array.
   */
  public function __construct(CurrentPathStack $path) {
    $this->path = $path;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['customers_canvas.builder'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Path\CurrentPathStack $path */
    $path = $container->get('path.current');

    return new static($path);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'customers_canvas_finish';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('result'))) {
      $form_state->setValue('result', '');
    }
    $form['result'] = [
      '#type' => 'hidden',
      '#default_value' => $form_state->getValue('result'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Finish'),
      '#attributes' => ['id' => 'editorFinish'],
    ];
    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $result = $form_state->getValue('result');
    $result = json_decode($result, TRUE);
    $this->messenger()->addStatus($this->t('User <strong>@user_id</strong> successfully saved state <strong>@state_id</strong>', [
      '@user_id' => $result['userId'],
      '@state_id' => $result['stateId'],
    ]));
    $form_state->setRedirect('<front>');
  }

}
