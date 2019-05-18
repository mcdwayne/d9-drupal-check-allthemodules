<?php

namespace Drupal\custom_configuration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\custom_configuration\Helper\ConfigurationHelper;
use Drupal\Core\Form\drupal_set_message;
use Drupal\Core\Path\CurrentPathStack;

/**
 * Class Edit Configuration.
 *
 * @package Drupal\custom_configuration\Form
 */
class EditConfiguration extends ConfigFormBase {

  protected $database;
  protected $configHelper;
  protected $path;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $con, ConfigurationHelper $helper, CurrentPathStack $path) {
    $this->database = $con;
    $this->configHelper = $helper;
    $this->path = $path;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('database'), $container->get('custom.configuration'), $container->get('path.current')
    );
  }

  /**
   * Get Editable of the config name.
   */
  protected function getEditableConfigNames() {
    return ['custom_configuration.edit_configuration'];
  }

  /**
   * Get the form id.
   */
  public function getFormId() {
    return 'custom_configuration_edit_configuration';
  }

  /**
   * Building a custom form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_path = $this->path->getPath();
    $pathArray = explode('/', $current_path);
    $configId = $pathArray[count($pathArray) - 1];
    // For the multilingual website.
    $languageArray = $this->configHelper->getLanguages();
    // For multi domain website.
    $domainArray = $this->configHelper->getDomains();
    if ($configId) {
      $results = $this->configHelper->getConfigList(['id' => $configId]);
      if ($results) {
        foreach ($results as $resultSet) {
          $machine_name = $this->t('Machine Name');
          $optionsData = unserialize($resultSet->custom_config_options);
          $form['custom_configuration'] = [
            '#type' => 'detail',
            '#title' => $this->t('Edit Custom Configuration'),
            '#open' => TRUE,
          ];
          $form['custom_configuration']['edit_configuration'] = [
            '#type' => 'detail',
            '#title' => $this->t('Edit Configuration'),
          ];
          $form['custom_configuration']['edit_configuration']['markup'] = [
            '#markup' => '<strong>' . $machine_name . '</strong><br>',
            '#suffix' => $resultSet->custom_config_machine_name,
          ];
          $form['custom_configuration']['edit_configuration']['machine_name'] = [
            '#type' => 'hidden',
            '#maxlength' => 50,
            '#required' => TRUE,
            '#default_value' => $resultSet->custom_config_machine_name,
          ];
          $form['custom_configuration']['edit_configuration']['id'] = [
            '#type' => 'hidden',
            '#maxlength' => 50,
            '#required' => TRUE,
            '#default_value' => $configId,
          ];
          if (count($languageArray) > 1) {
            $form['custom_configuration']['edit_configuration']['languages'] = [
              '#type' => 'select',
              '#multiple' => TRUE,
              '#attributes' => ['style' => 'min-width:300px'],
              '#title' => $this->t('Language'),
              '#required' => TRUE,
              '#options' => $languageArray,
              '#default_value' => explode(',', $resultSet->custom_config_langcode),
            ];
          }
          if (count($domainArray) > 0) {
            $form['custom_configuration']['edit_configuration']['domains'] = [
              '#type' => 'select',
              '#multiple' => TRUE,
              '#attributes' => ['style' => 'min-width:300px'],
              '#required' => TRUE,
              '#title' => $this->t('Domain'),
              '#options' => $domainArray,
              '#default_value' => explode(',', $resultSet->custom_config_domains),
            ];
          }
          $form['custom_configuration']['edit_configuration']['key'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Key name'),
            '#maxlength' => 50,
            '#required' => TRUE,
            '#default_value' => $resultSet->custom_config_name,
          ];
          $form['custom_configuration']['edit_configuration']['value'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Configuration Value'),
            '#required' => TRUE,
            '#rows' => 15,
            '#attributes' => ['style' => 'width:100%'],
            '#default_value' => $resultSet->custom_config_value,
          ];
          $form['custom_configuration']['add_configuration']['optional_value']['#tree'] = TRUE;
          $form['custom_configuration']['add_configuration']['optional_value']['value_1'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Optional Value 1'),
            '#description' => $this->t('<small>Value can be in string or json format.</small>'),
            '#required' => FALSE,
            '#rows' => 10,
            '#cols' => 58,
            '#attributes' => ['style' => 'width:auto'],
            '#default_value' => $optionsData['value_1'],
          ];
          $form['custom_configuration']['add_configuration']['optional_value']['value_2'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Optional Value 2'),
            '#description' => $this->t('<small>Value can be in string or json format.</small>'),
            '#required' => FALSE,
            '#rows' => 10,
            '#cols' => 58,
            '#attributes' => ['style' => 'width:auto'],
            '#default_value' => $optionsData['value_2'],
          ];
          $form['custom_configuration']['add_configuration']['optional_value']['value_3'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Optional Value 3'),
            '#description' => $this->t('<small>Value can be in string or json format.</small>'),
            '#required' => FALSE,
            '#rows' => 10,
            '#cols' => 58,
            '#attributes' => ['style' => 'width:auto'],
            '#default_value' => $optionsData['value_3'],
          ];
          $form['custom_configuration']['add_configuration']['optional_value']['value_4'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Optional Value 4'),
            '#description' => $this->t('<small>Value can be in string or json format.</small>'),
            '#required' => FALSE,
            '#rows' => 10,
            '#cols' => 58,
            '#attributes' => ['style' => 'width:auto'],
            '#default_value' => $optionsData['value_4'],
          ];
          $form['custom_configuration']['add_configuration']['status'] = [
            '#type' => 'select',
            '#title' => $this->t('Configuration Status'),
            '#options' => [
              '0' => $this->t('Inactive'),
              '1' => $this->t('Active'),
            ],
            '#default_value' => $resultSet->custom_config_status,
          ];
          $form['custom_configuration']['add_configuration']['edit_submission'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save Configuration'),
          ];
          $form['custom_configuration']['add_configuration']['cancel_submission'] = [
            '#type' => 'submit',
            '#value' => $this->t('Cancel'),
          ];
        }
      }
      else {
        drupal_set_message($this->t('Configuration does not exists.'), 'error');
        $form['cancel'] = [
          '#type' => 'submit',
          '#value' => $this->t('Cancel'),
        ];
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $post                 = [];
    $post['machine_name'] = $form_state->getValue('machine_name');
    $post['languages']    = ($form_state->getValue('languages')) ? $form_state->getValue('languages') : NULL;
    $post['domains']      = ($form_state->getValue('domains')) ? $form_state->getValue('domains') : NULL;
    $domains              = $this->configHelper->implodeDomains($post);
    $langcode             = $this->configHelper->implodeLanguage($post);
    $args                 = ['domain' => $domains, 'langcode' => $langcode];
    $args['machine_name'] = $post['machine_name'];
    $args['config_id']    = $form_state->getValue('id');
    if ($this->configHelper->checkDuplicateItems($args) == TRUE) {
      $form_state->setErrorByName('form', $this->t('Machine name <strong>@machineName</strong> already exists in this combination', ['@machineName' => $post['machine_name']]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('op')->getUntranslatedString() === 'Save Configuration') {
      $post = [];
      $post['config_id'] = $form_state->getValue('id');
      $post['machine_name'] = $form_state->getValue('machine_name');
      $post['name'] = $form_state->getValue('key');
      $post['config_value'] = $form_state->getValue('value');
      $post['status'] = $form_state->getValue('status');
      $post['optional_value'] = serialize($form_state->getValue('optional_value'));
      $post['languages'] = ($form_state->getValue('languages')) ? $form_state->getValue('languages') : NULL;
      $post['domains'] = ($form_state->getValue('domains')) ? $form_state->getValue('domains') : NULL;
      $msg = $this->configHelper->updateValue($post);
      drupal_set_message($this->t("@message", ['@message' => $msg['message']]), $msg['status']);
      if ($msg['status'] == 'status') {
        $form_state->setRedirectUrl(Url::fromRoute('custom_configuration.configuration_list'));
      }
      else {
        $form_state->setRedirectUrl(Url::fromRoute('custom_configuration.custom_configuration_add'));
      }
    }
    else {
      $form_state->setRedirectUrl(Url::fromRoute('custom_configuration.configuration_list'));
    }
  }

}
