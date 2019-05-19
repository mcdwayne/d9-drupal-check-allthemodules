<?php

namespace Drupal\webpay\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webpay\Entity\WebpayConfig;
use Drupal\Core\StreamWrapper\PrivateStream;

/**
 * Class WebpayConfigForm.
 */
class WebpayConfigForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $webpay_config = $this->entity;

    $form['commerce_code'] = [
      '#type' => 'textfield',
      '#maxlength' => 12,
      '#size' => 12,
      '#title' => $this->t('Commerce Code'),
      '#description' => $this->t('The commerce code hiven from transbank team.'),
      '#default_value' => $webpay_config->get('commerce_code'),
      '#required' => TRUE,
      '#disabled' => !$webpay_config->isNew(),
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Configuration name'),
      '#maxlength' => 255,
      '#default_value' => $webpay_config->label(),
      '#description' => $this->t("The name of the configuration."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $webpay_config->id(),
      '#machine_name' => [
        'exists' => '\Drupal\webpay\Entity\WebpayConfig::load',
        'source' => ['name'],
      ],
      '#disabled' => !$webpay_config->isNew(),
    ];

    $form['environment'] = [
      '#type' => 'select',
      '#title' => $this->t('Environment'),
      '#options' => WebpayConfig::environments(),
      '#default_value' => $webpay_config->get('environment'),
      '#required' => TRUE,
    ];

    $form['log'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log system'),
      '#description' => $this->t('Only recommended for transbank certification.'),
      '#default_value' => $webpay_config->get('log'),
    ];

    $form['files'] = [
      '#type' => 'details',
      '#title' => $this->t('Files'),
      '#open' => TRUE,
    ];

    $files = [
      'client_certificate' => ['extensions' => ['crt'], 'label' => $this->t('Client Certificate')],
      'private_key' => ['extensions' => ['key'], 'label' => $this->t('Private Key')],
      'server_certificate' => ['extensions' => ['pem'], 'label' => $this->t('Server Certificate')],
    ];

    foreach ($files as $file_key => $file_config) {
      $form['files']['group_' . $file_key] = [
        '#type' => 'fieldset',
        '#title' => $file_config['label'],
      ];
      $form['files']['group_' . $file_key][$file_key] = [
        '#type' => 'textfield',
        '#title' => $this->t('Path'),
        '#default_value' => $webpay_config->get($file_key),
      ];
      $form['files']['group_' . $file_key]['upload_' . $file_key] = [
        '#type' => 'file',
        '#title' => $this->t('Upload'),
        '#upload_validators' => [
          'file_validate_extensions' => $file_config['extensions'],
        ],
        '#description' => $this->t('Here upload the file.'),
      ];
    }

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['actions']['submit_test'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and test'),
      '#submit' => ['::submitForm', '::save', '::submitAndTest'],
      '#weight' => 6,
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $webpay_config = $this->entity;
    $files = [
      'client_certificate',
      'private_key',
      'server_certificate',
    ];

    foreach ($files as $key) {
      $file = _file_save_upload_from_form($form['files']['group_' . $key]['upload_' . $key], $form_state, 0);
      if ($file) {
        $form_state->setValue('upload_' . $key, $file);
      }
      // Check the path.
      elseif ($path = $form_state->getValue($key)) {
        if (!file_exists($path)) {
          $form_state->setErrorByName($key, $this->t('The file does not exists.'));
        }
      }
      else {
        $form_state->setErrorByName('upload_' . $key, $this->t('You must upload a file.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $files = [
      'client_certificate' => 'crt',
      'private_key' => 'pem',
      'server_certificate' => 'crt',
    ];

    if ($path = WebpayConfig::getPathFiles($values['id'], TRUE)) {
      foreach ($files as $file => $ext) {
        if (!empty($values['upload_' . $file])) {
          $filename = file_unmanaged_copy($values['upload_' . $file]->getFileUri(), $path . '/' . $file . '.' . $ext, FILE_EXISTS_REPLACE);
          $form_state->setValue($file, $filename);
        }
      }
    }

    parent::submitForm($form, $form_state);
  }


  /**
   * Redirect to test.
   */
  public function submitAndTest(array &$form, FormStateInterface $form_state) {
    \Drupal::request()->query->remove('destination');
    $form_state->setRedirect('entity.webpay_config.test', ['webpay_config' => $this->entity->id()]);
  }


  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $webpay_config = $this->entity;
    $status = $webpay_config->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Webpay config.', [
          '%label' => $webpay_config->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Webpay config.', [
          '%label' => $webpay_config->label(),
        ]));
    }
    $form_state->setRedirectUrl($webpay_config->toUrl('collection'));
  }

}
