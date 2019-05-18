<?php

namespace Drupal\myjdownloader\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\myjdownloader\MyJdHelper;

/**
 * Add Links API.
 */
class MyJdAddLinks extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'myjd_addlinks';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $settings_default = [
      // (boolean|null).
      "assignJobID" => NULL,
      // (boolean|null).
      "autoExtract" => NULL,
      // (boolean|null).
      "autostart" => TRUE,
      // (String[]).
      "dataURLs" => NULL,
      // (boolean|null).
      "deepDecrypt" => NULL,
      // (String).
      "destinationFolder" => NULL,
      // (String).
      "downloadPassword" => NULL,
      // (String).
      "extractPassword" => NULL,
      // (boolean|null).
      "overwritePackagizerRules" => NULL,
      // (String).
      "packageName" => NULL,
      // (Priority).
      "priority" => NULL,
      // (String).
      "sourceUrl" => NULL,
    ];

    $form['links'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Link or links to add'),
      '#description' => $this->t('One link by line'),
      '#default_value' => $form_state->getValue('links', ""),
      '#required' => TRUE,
    ];

    $form['destinationFolder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Destination Folder'),
      '#default_value' => $form_state->getValue('destinationFolder', ""),
    ];
    $form['packageName'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Package name'),
      '#default_value' => $form_state->getValue('packageName', ""),
    ];
    $form['downloadPassword'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Download Password'),
      '#default_value' => $form_state->getValue('downloadPassword', ""),
    ];
    $form['packageName'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Package name'),
      '#default_value' => $form_state->getValue('packageName', ""),
    ];

    $form['autostart'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto start'),
      '#default_value' => $form_state->getValue('autostart', $settings_default['autostart']),
    ];
    $form['autoExtract'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto Extract'),
      '#default_value' => $form_state->getValue('autoExtract', ""),
    ];
    $form['deepDecrypt'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Deep Decrypt'),
      '#default_value' => $form_state->getValue('deepDecrypt', ""),
    ];
    $form['overwritePackagizerRules'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Overwrite Packagizer Rules'),
      '#default_value' => $form_state->getValue('overwritePackagizerRules', TRUE),
    ];
    // Priority.
    $form['priority'] = [
      '#type' => 'select',
      '#title' => $this->t('Priority'),
      '#options' => [
        'HIGHEST' => 'HIGHEST',
        'HIGHER' => 'HIGHER',
        'HIGH' => 'HIGH',
        'DEFAULT' => 'DEFAULT',
        'LOW' => 'LOW',
        'LOWER' => 'LOWER',
        'LOWEST' => 'LOWEST',
      ],
      '#default_value' => $form_state->getValue('priority', "DEFAULT"),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $links = $form_state->getValue('links');
    if (!$links) {
      $form_state->setErrorByName('links', "Links is mandatory");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $links = $form_state->getValue('links');

    $fields = [
      "autoExtract" => TRUE,
      "autostart" => TRUE,
      "destinationFolder" => NULL,
      "downloadPassword" => NULL,
      "extractPassword" => NULL,
      "overwritePackagizerRules" => NULL,
      "packageName" => NULL,
      "priority" => NULL,
    ];

    $settings = [];
    foreach ($fields as $field => $default) {
      $value = $form_state->getValue($field, $default);
      if ($value) {
        $settings[$field] = $value;
      }
    }

    $res = MyJdHelper::addLink(explode("\r\n", $links), $settings);
    if ($res) {
      $this->messenger()->addMessage("Link(s) added to downloader");
    }
    else {
      $this->messenger()
        ->addMessage("Link(s) NOT added to downloader", 'error');
    }

    $form_state->setRebuild();

  }

}
