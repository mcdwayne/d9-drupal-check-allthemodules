<?php

namespace Drupal\doc_to_html\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigManager;

/**
 * Class BasicSettings.
 *
 * @package Drupal\doc_to_html\Form
 */
class BasicSettings extends ConfigFormBase {

  /**
   * Drupal\Core\Config\ConfigManager definition.
   *
   * @var \Drupal\Core\Config\ConfigManager
   */
  protected $basicsettings;
  public function __construct(
    ConfigFactoryInterface $config_factory,
      ConfigManager $config_manager
    ) {
    parent::__construct($config_factory);
        $this->basicsettings = $config_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
            $container->get('config.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'doc_to_html.basicsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basic_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('doc_to_html.basicsettings');
    $form['wrapper_folder'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Path to save html file'),
    ];

    $form['wrapper_folder']['doc_to_html_folder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Doc to html folder'),
      '#description' => $this->t('This folder start at public://'),
      '#maxlength' => 100,
      '#size' => 100,
      '#default_value' => $config->get('doc_to_html_folder'),
    ];

    $form['wrapper_file'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Supportted File')
    ];

    $form['wrapper_file']['doc'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('DOC'),
      '#default_value' => $config->get('doc'),
    ];
    $form['wrapper_file']['docx'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('DOCX'),
      '#default_value' => $config->get('docx'),
    ];

    $form['parse_data'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Parse Data'),
    ];
    $form['parse_data']['utf_8_encode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('UTF-8 Encode'),
      '#description' => $this->t('Parse content with utf8'),
      '#default_value' => $config->get('utf_8_encode'),
    ];
    $form['parse_data']['extract_content_of_html_body'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Extract content of HTML Body'),
      '#description' => $this->t('Return only markup inner tag &lt;body&gt;'),
      '#default_value' => $config->get('extract_content_of_html_body'),
    ];
    $form['parse_data']['regex_to_parse_body'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Regex to parse body'),
      '#description' => $this->t('Use Regex system to perse content of html, thi regex extract content wrapped in a body tag'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('regex_to_parse_body'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // set doc or docx to validate form.
    $verify = array(
      'doc' => $form_state->getValue('doc'),
      'docx' => $form_state->getValue('docx')
    );
    if($verify['doc']  == 0 && $verify['docx'] == 0){
      if($verify['doc'] == 0){
        $form_state->setErrorByName('doc', $this->t('SET DOC or DOCX'));
      }
      if($verify['docx'] == 0) {
        $form_state->setErrorByName('docx', $this->t('SET DOC or DOCX'));
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('doc_to_html.basicsettings')
      ->set('doc_to_html_folder', $form_state->getValue('doc_to_html_folder'))
      ->set('doc', $form_state->getValue('doc'))
      ->set('docx', $form_state->getValue('docx'))
      ->set('parse_data', $form_state->getValue('parse_data'))
      ->set('utf_8_encode', $form_state->getValue('utf_8_encode'))
      ->set('extract_content_of_html_body', $form_state->getValue('extract_content_of_html_body'))
      ->set('regex_to_parse_body', $form_state->getValue('regex_to_parse_body'))
      ->save();
  }

}
