<?php

namespace Drupal\bugherdapi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BugherdConfigurationForm.
 *
 * @package Drupal\bugherdapi\Form
 */
class BugherdConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'bugherdapi.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bugherdapi_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $config = $this->config('bugherdapi.settings');

    $form['project_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('BugHerd Project key'),
      '#default_value' => $config->get('project_key', ''),
      '#description' => $this->t('To obtain your project key login or sign up for BugHerd (http://www.bugherd.com)'),
      '#size' => 60,
      '#required' => TRUE,
    ];

    $form['disable_on_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable on admin pages'),
      '#default_value' => $config->get('disable_on_admin', FALSE),
      '#description' => $this->t('Ticking the checkbox will prevent the BugHerd button being available on admin pages'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    $this->config('bugherdapi.settings')
      ->set('project_key', $values['project_key'])
      ->set('disable_on_admin', $values['disable_on_admin'])
      ->save();

    parent::submitForm($form, $form_state);

  }

}
