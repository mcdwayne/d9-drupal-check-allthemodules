<?php

/**
 * @file
 * Contains \Drupal\entity_pdf\Form\EntityPdfSettingsForm
 */
namespace Drupal\entity_pdf\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EntityPdfSettingsForm
 */
class EntityPdfSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_pdf_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'entity_pdf.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('entity_pdf.settings');

    $form['filename'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filename for generated PDF documents.'),
      '#default_value' => $config->get('filename') ?: '[node:nid].pdf',
      '#description' => $this->t('You can use node tokens.')
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('entity_pdf.settings');
    $config->set('filename', $form_state->getValue('filename'));
    $config->save();
    parent::submitForm($form, $form_state);
  }
}
