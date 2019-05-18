<?php

namespace Drupal\library\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LibraryGeneralSettings.
 */
class LibraryGeneralSettings extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'library_general_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('library.settings');
    $form['header'] = [
      '#markup' => '<h2>' . $this->t('Getting started') . '</h2>',
    ];
    $form['notice'] = [
      '#markup' => '<p>' . $this->t('To make use of this module add at least one <em>library item entry</em> to a content type.') . '</p>',
    ];
    $form['barcode_starting_point'] = [
      '#type' => 'number',
      '#title' => $this->t('Barcode starting point'),
      '#description' => $this->t('The value to begin the auto-incrementation for barcodes from. Only effective if you do not have a value set in items, yet.'),
      '#default_value' => $config->get('barcode_starting_point'),
    ];
    $form['anonymize_transactions'] = [
      '#type' => 'select',
      '#title' => $this->t('Anonymize transactions'),
      '#description' => $this->t('Whether to remove patron information for returned items periodically.'),
      '#options' => [
        'never' => $this->t('never'),
        'daily' => $this->t('daily'),
        'weekly' => $this->t('weekly'),
        'monthly' => $this->t('monthly'),
      ],
      '#default_value' => $config->get('anonymize_transactions'),
      '#size' => 1,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('library.settings');
    $config->set('barcode_starting_point', $form_state->getValue('barcode_starting_point'));
    $config->set('anonymize_transactions', $form_state->getValue('anonymize_transactions'));
    $config->save();
  }

}
