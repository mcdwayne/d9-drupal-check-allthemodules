<?php

namespace Drupal\byu_resources\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


class ByuResourcesConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   * @return string
   */

  public function getFormId() {
    return 'byu_resources_settings';
  }

  /**
   * {@inheritdoc}
   * @return array
   */

  public function getEditableConfigNames()
  {
    return [
      'byu_resources.settings'
    ];
  }

  /**
   * {@inheritdoc}
   * @param array $form
   * @param FormStateInterface $formState
   * @return array
   */

  public function buildForm(array $form, FormStateInterface $formState = null) {
    $config = $this->config('byu_resources.settings');

    $defaults['fields_to_update'] = $config->get('fields_to_update');

    $form['fields_to_update'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Fields to be updated'),
      '#description' => $this->t('Select the fields you want updated on a daily basis.'),
      '#options' => [
        'readme' => $this->t('Readme <small>(Documentation used on the documentation page.)</small>'),
        'description' => $this->t('Description <small>(Short description used in the list of resources.)</small>'),
      ],
      '#default_value' => isset($defaults['fields_to_update']) ? $defaults['fields_to_update'] : ''
    ];

    return parent::buildForm($form, $formState);
  }

  /**
   * {@inheritdoc}
   * @param array $form
   * @param FormStateInterface $formState
   */

  public function submitForm(array &$form, FormStateInterface $formState) {

    $this->configFactory->getEditable('byu_resources.settings')
      ->set('fields_to_update', $formState->getValue('fields_to_update'))
      ->save();

    parent::submitForm($form, $formState);
  }
}