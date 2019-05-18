<?php

namespace Drupal\gtm_datalayer\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures GTM dataLayer settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gtm_datalayer_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'gtm_datalayer.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $datalayer_config = $this->config('gtm_datalayer.settings');

    $form['container_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Container ID'),
      '#placeholder' => 'GTM-XXXX',
      '#description' => $this->t('The Google Tag Manager container ID'),
      '#default_value' => $datalayer_config->get('container_id'),
      '#required' => TRUE,
      '#pattern' => '^GTM-\w{4,}$',
    ];
    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#description' => $this->t('If checked, evaluated conditions and created tags will be displayed onscreen to all users.'),
      '#default_value' => $datalayer_config->get('debug'),
    ];
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#default_value' => $datalayer_config->get('status'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('gtm_datalayer.settings')
      ->set('container_id', $values['container_id'])
      ->set('debug', $values['debug'])
      ->set('status', $values['status'])
      ->save();

    parent::submitForm($form, $form_state);

    $form_state->setRedirect('entity.gtm_datalayer.collection');
  }

}
