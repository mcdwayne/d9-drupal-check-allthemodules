<?php
/**
 *
 */

namespace Drupal\browser_development\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\KernelTests\Component\Utility\SafeMarkupKernelTest;

/**
 * Class BrowserForm.
 *
 * @package Drupal\browser_development\Form
 */
class BrowserCssForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $configDBSettings;

  /**
   * @var \Drupal\browser_development\Form\DeleteCssFromDisk
   */
  protected $deleteCssFromDisk;

  /**
   * @var \Drupal\browser_development\Form\SavingCssToDisk
   */
  protected $saveCssToDisk;

  /**
   * BrowserCssForm constructor.
   * @param ConfigFactoryInterface $config_factory
   */
  function __construct(ConfigFactoryInterface $config_factory) {

    parent::__construct($config_factory);
    $this->configDBSettings = \Drupal::config('browser_development.settings');

    //-- Setup save and delete objects;
    $this->deleteCssFromDisk = new DeleteCssFromDisk();
    $this->saveCssToDisk = new SavingCssToDisk();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'browser_development.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'browser_development_css_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {


    $form['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => array('::newSubmissionHandlerSave'),
    ];


    $form['reset'] = [
      '#type' => 'button',
      '#value' => $this->t('Reset'),
    ];


    $form['delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#submit' => array('::newSubmissionHandlerDelete'),
    ];


    $form['background_text_field'] = [
      '#title' => '',
      '#type' => 'textarea',
      '#attributes' => array(
        'placeholder' => t('Add background css here......'),
      ),
    ];

    $form['alink_text_field'] = [
      '#title' => '',
      '#type' => 'textarea',
      '#attributes' => array(
        'placeholder' => t('Add alink css here......'),
      ),
    ];

    $form['text_field'] = [
      '#title' => '',
      '#type' => 'textarea',
      '#attributes' => array(
        'placeholder' => t('Add text css here......'),
      ),
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
  }


  /**
   * Custom submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function newSubmissionHandlerSave(array &$form, FormStateInterface $form_state) {

    if($form_state->getValue('background_text_field') != '') {
      //$this->config('browser_development.settings')
      //  ->set('css_text_field', $form_state->getValue('css_text_field'))
      //  ->save();

      $this->saveCssToDisk->setBackgroundColor($form_state->getValue('background_text_field'));

    }

    if($form_state->getValue('alink_text_field') != '') {
      $this->saveCssToDisk->setALink($form_state->getValue('alink_text_field'));
    }

    if($form_state->getValue('text_field') != '') {
      $this->saveCssToDisk->setText($form_state->getValue('text_field'));
    }


  }

  /**
   * Allows user too delete css file and start again
   * @todo this is not using drupal api properly workout how to add (must be an instance of Drupal\file\FileInterface)
   */
  public function newSubmissionHandlerDelete() {


  }

}
