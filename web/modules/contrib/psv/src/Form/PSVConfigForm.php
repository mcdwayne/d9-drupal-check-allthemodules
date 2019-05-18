<?php

namespace Drupal\psv\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Class PSVConfigForm.
 *
 * @package Drupal\psv\Form
 */
class PSVConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'psv.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'psv_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('psv.config');
    $user = $this->config('user.settings');

    if ($user->get('password_strength') == FALSE
        || $user->get('register') == 'admin_only'
        || $user->get('verify_mail') == TRUE
    ) {
      $url = Link::createFromRoute(
        t('Account settings'),
        'entity.user.admin_form'
      )->toString();
      drupal_set_message(
        t(
            'You need to verify your "@link"!<br>
        "Enable password strength indicator" must be active<br>
        "Register Accounts" all options except "Administrators only"<br>
        "Require email verification when a visitor creates an account"
         must be inactive', ['@link' => $url]
      ),
          'warning',
        FALSE
      );
      $form['#access'] = FALSE;
    }

    $form['psv'] = [
      '#type'        => 'fieldset',
      '#title'       => 'Password Strength Visualization',
      '#collapsible' => TRUE,
      '#collapsed'   => FALSE,
    ];

    $form['psv']['psv_enable'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Enable'),
      '#default_value' => $config->get('psv_enable'),
    ];

    $form['psv']['psv_reverse'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Reverse Image'),
      '#default_value' => $config->get('psv_reverse'),
    ];

    $form['psv']['psv_image'] = [
      '#type' => 'managed_file',
      '#title' => t('Image with preview'),
      '#upload_validators' => [
        'file_validate_extensions' => ['gif png jpg jpeg'],
        'file_validate_size' => [25600000],
      ],
      '#theme' => 'image_widget',
      '#preview_image_style' => 'medium',
      '#upload_location' => 'public://',
      '#required' => FALSE,
      '#default_value' => $config->get('psv_image'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('psv.config')
      ->set('psv_image', $form_state->getValue('psv_image'))
      ->set('psv_enable', $form_state->getValue('psv_enable'))
      ->set('psv_reverse', $form_state->getValue('psv_reverse'))
      ->save();
  }

}
