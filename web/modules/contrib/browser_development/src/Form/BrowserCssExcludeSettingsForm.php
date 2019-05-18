<?php
/**
 *
 */

namespace Drupal\browser_css\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BrowserForm.
 *
 * @package Drupal\browser_css\Form
 * @todo the module will need a admin forms in the future
 */
class BrowserCssExcludeSettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $configDBSettings;

  /**
   * BrowserCssForm constructor.
   * @param ConfigFactoryInterface $config_factory
   */
  function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->configDBSettings = \Drupal::config('browser_css.settings');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'browser_css.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'browser_css_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {


    $form['exclude_text_area'] = [
      '#title' => '',
      '#type' => 'textarea',
      '#attributes' => array(
        'placeholder' => t('Add id to exclude'),
      ),
      '#required' => TRUE,
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
    $this->config('browser_css.settings')
      ->set('exclude_text_area', $form_state->getValue('exclude_text_area'))
      ->save();

  }

}
