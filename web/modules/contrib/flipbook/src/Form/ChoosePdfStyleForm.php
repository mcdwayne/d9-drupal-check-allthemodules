<?php

namespace Drupal\flipbook\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure custom settings for this site.
 */
class ChoosePdfStyleForm extends ConfigFormBase {

  /**
   * Constructor for SocialFeedsBlockForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {

    parent::__construct($config_factory);
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'choose_pdf_style_form';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['config.flipbook_chooseconfig'];
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
    $config = $this->config('config.flipbook_chooseconfig');
    $form['choice'] = [
      '#type' => 'radios',
      '#title' => $this->t('Popup Pdf'),
      '#options' => [0 => 'No', 1 => 'Yes'],
      '#default_value' => (!$config->get('pdf.choice')) ? 0 : $config->get('pdf.choice'),
      '#description' => $this->t('Choose if you want pop-up pdf'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
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

    $config = $this->config('config.flipbook_chooseconfig');
    $config->set('pdf.choice', $form_state->getValue('choice'));
    $config->save();
    drupal_set_message($this->t('Changes Saved'));
  }

}
