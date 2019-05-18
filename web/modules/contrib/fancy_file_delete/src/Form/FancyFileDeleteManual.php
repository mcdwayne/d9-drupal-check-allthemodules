<?php

namespace Drupal\fancy_file_delete\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class FancyFileDeleteManual extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fancy_file_delete_manual';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('fancy_file_delete.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['fancy_file_delete.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $form['force'] = [
      '#type' => 'checkbox',
      '#title' => t('FORCE file deletion?'),
    ];

    $form['delete_textarea'] = [
      '#type' => 'textarea',
      '#title' => t('FID Numbers'),
      '#default_value' => '',
      '#description' => t('Provide the fid numbers, one per line.'),
      '#attributes' => [
        'style' => 'font-family:"Courier New", Courier, monospace;'
        ],
      '#rows' => 10,
    ];

    // $form['#validate'][] = 'fancy_file_delete_manual_validate';
    // $form['#submit'][] = 'fancy_file_delete_manual_submit';

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // Required doesn't work well with states it seemz.
    if (!$form_state->getValue(['delete_textarea'])) {
      $form_state->setErrorByName('delete_textarea', t('You can\'t leave this blank, what\'cha thinking?'));
    }
  }

  public function _submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $operations = [];

    $force = ($form_state->getValue(['force'])) ? TRUE : FALSE;
    $fids = explode("\n", $form_state->getValue(['delete_textarea']));
    foreach ($fids as $fid) {
      $operations[] = ['fancy_file_delete_batch', [$fid, $force]];
    }
    // Send to batch.
    _fancy_file_delete_batch_run($operations);
  }

}
