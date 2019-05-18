<?php

namespace Drupal\custom_configuration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_configuration\Helper\ConfigurationHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\drupal_set_message;

/**
 * Class Configuration Setting.
 *
 * @package Drupal\custom_configuration\Form
 */
class ConfigurationSetting extends ConfigFormBase {

  protected $configHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigurationHelper $configHelper) {
    $this->configHelper = $configHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('custom.configuration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $jsonData = serialize($form_state->getValue('optional_value'));
    $post = [
      'name' => $form_state->getValue('key'),
      'config_value' => $form_state->getValue('value'),
      'optional_value' => $jsonData,
      'languages' => ($form_state->getValue('languages')) ? $form_state->getValue('languages') : NULL,
      'domains' => ($form_state->getValue('domains')) ? $form_state->getValue('domains') : NULL,
      'status' => $form_state->getValue('status'),
    ];
    $domains = $this->configHelper->implodeDomains($post);
    $langcode = $this->configHelper->implodeLanguage($post);
    $machine_name = $this->configHelper->createMachineName($post['name']);
    $args = ['domain' => $domains, 'langcode' => $langcode];
    $args['machine_name'] = $machine_name;
    if ($this->configHelper->checkDuplicateItems($args) == TRUE) {
      $msg = $this->t('Machine name <strong>@machineName</strong> already exists in this combination', ['@machineName' => $machine_name]);
      $form_state->setErrorByName('form', $msg);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('custom.configuration');
    // For the multilingual website.
    $activeLanguage = $this->configHelper->getActiveLanguage();
    $languageArray = $this->configHelper->getLanguages();
    // For multi domain website.
    $domainArray = $this->configHelper->getDomains();
    $activeDomain = $this->configHelper->getActiveDomain();
    $form['custom_configuration'] = [
      '#type' => 'detail',
      '#title' => $this->t('Custom Configuration'),
      '#open' => TRUE,
    ];
    $form['custom_configuration']['add_configuration'] = [
      '#type' => 'detail',
      '#title' => $this->t('Add Configuration'),
    ];
    if (count($languageArray) > 1) {
      $form['custom_configuration']['add_configuration']['languages'] = [
        '#type' => 'select',
        '#multiple' => TRUE,
        '#attributes' => ['style' => 'min-width:300px'],
        '#title' => $this->t('Language'),
        '#required' => TRUE,
        '#options' => $languageArray,
        '#default_value' => $activeLanguage,
      ];
    }
    if (count($domainArray) > 0) {
      $form['custom_configuration']['add_configuration']['domains'] = [
        '#type' => 'select',
        '#multiple' => TRUE,
        '#attributes' => ['style' => 'min-width:300px'],
        '#required' => TRUE,
        '#title' => $this->t('Domain'),
        '#options' => $domainArray,
        '#default_value' => $activeDomain,
      ];
    }
    $form['custom_configuration']['add_configuration']['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key name'),
      '#maxlength' => 50,
      '#required' => TRUE,
      '#default_value' => $config->get('key') ? $config->get('key') : '',
    ];
    $form['custom_configuration']['add_configuration']['value'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Configuration Value'),
      '#description' => '<small>' . $this->t('Value can be in string or json format.') . '</small>',
      '#required' => TRUE,
      '#rows' => 15,
      '#attributes' => ['style' => 'width:100%'],
      '#default_value' => $config->get('value') ? $config->get('key') : '',
    ];
    $form['custom_configuration']['add_configuration']['optional_value']['#tree'] = TRUE;
    $form['custom_configuration']['add_configuration']['optional_value']['value_1'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Optional Value 1'),
      '#description' => '<small>' . $this->t('Value can be in string or json format.') . '</small>',
      '#required' => FALSE,
      '#rows' => 10,
      '#cols' => 58,
      '#attributes' => ['style' => 'width:auto'],
      '#default_value' => $config->get('value') ? $config->get('key') : '',
    ];
    $form['custom_configuration']['add_configuration']['optional_value']['value_2'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Optional Value 2'),
      '#description' => '<small>' . $this->t('Value can be in string or json format.') . '</small>',
      '#required' => FALSE,
      '#rows' => 10,
      '#cols' => 58,
      '#attributes' => ['style' => 'width:auto'],
      '#default_value' => $config->get('value') ? $config->get('key') : '',
    ];
    $form['custom_configuration']['add_configuration']['optional_value']['value_3'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Optional Value 3'),
      '#description' => '<small>' . $this->t('Value can be in string or json format.') . '</small>',
      '#required' => FALSE,
      '#rows' => 10,
      '#cols' => 58,
      '#attributes' => ['style' => 'width:auto'],
      '#default_value' => $config->get('value') ? $config->get('key') : '',
    ];
    $form['custom_configuration']['add_configuration']['optional_value']['value_4'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Optional Value 4'),
      '#description' => '<small>' . $this->t('Value can be in string or json format.') . '</small>',
      '#required' => FALSE,
      '#rows' => 10,
      '#cols' => 58,
      '#attributes' => ['style' => 'width:auto'],
      '#default_value' => $config->get('value') ? $config->get('key') : '',
    ];
    $form['custom_configuration']['add_configuration']['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Configuration Status'),
      '#options' => [
        '0' => $this->t('Inactive'),
        '1' => $this->t('Active'),
      ],
      '#default_value' => $config->get('key') ? $config->get('key') : 1,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_configuration';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $jsonData = serialize($form_state->getValue('optional_value'));
    $post = [
      'name' => $form_state->getValue('key'),
      'config_value' => $form_state->getValue('value'),
      'optional_value' => $jsonData,
      'languages' => ($form_state->getValue('languages')) ? $form_state->getValue('languages') : NULL,
      'domains' => ($form_state->getValue('domains')) ? $form_state->getValue('domains') : NULL,
      'status' => $form_state->getValue('status'),
    ];
    $return = $this->configHelper->createConfiguration($post);
    drupal_set_message($this->t("@message", ['@message' => $return['message']]), $return['status']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['custom.configuration'];
  }

}
