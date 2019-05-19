<?php

/**
 * @file
 * Contains \Drupal\textmarketer_contacts\TMSettingsForm.
 */

namespace Drupal\textmarketer_contacts\Form;

use Drupal\Core\Entity;
use Drupal\Core\Field;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure hello settings for this site.
 */
class TMSettingsForm extends ConfigFormBase {

  protected $configFactory;
  private $configuration;

  /**
   * TMSettingsForm constructor.
   */
  public function __construct(ContentEntityBase $field_manager, ConfigFactory $config_factory, array $configuration) {
    parent::__construct($configuration);

    // $config = \Drupal::config('textmarketer_contacts.settings');
    $config = $this->config('textmarketer_contacts.settings');

    $credentials = "{$config->get('username')}:{$config->get('password')}";
    $url = "https://{$credentials}@api.textmarketer.co.uk";
    $this->configuration = [
      'enabled'  => $config->get('enabled'),
      'username'  => $config->get('username'),
      'password'  => $config->get('password'),
      'groupid'   => $config->get('group_id'),
      'groupname' => $config->get('group_name'),
      'apiurl'   => $url,
    ];
    $this->configFactory = $config_factory;
    $this->fieldService = $field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'textmarketer_contacts_admin_form_settings';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    // Instantiates this form class.
    // Loads the required service and passes it to the class constructor.
    $field_manager = $container->get('entity_field.manager');

    return new static($field_manager);
  }

  /**
   * Gets a list of the current fields in the user profile.
   *
   * @return array mixed
   *   Array of field names.
   */
  protected function getFields() {

    $fields = $this->fieldService->getFieldDefinitions('user', 'user');

    foreach ($fields as $field => $definition) {
      if (stripos($field, 'field_') !== FALSE) {
        $fields_array[$field] = $field;
      }
    }

    return $fields_array;
  }

  /**
   * Helper function returns the API URL string.
   */
  protected function apiHost() {

    return 'api.textmarketer.co.uk';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    global $base_url;

    $config = $this->config('textmarketer_contacts.settings');
    $message = t('Ensure you have created a telephone number field in
     the !account_settings_page. The field will be used for collecting user
     telephone numbers.');
    $options = $this->getFields() ? $this->getFields() : array();

    if (empty($this->getFields())) {
      drupal_set_message($message);
    }

    $form['settings_fieldset'] = array(
      '#type' => 'fieldset',
      '#title' => t('Text Marketer API Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['settings_fieldset']['enabled'] = array(
      '#type' => 'checkbox',
      '#default_value' => $config->get('enabled'),
      '#title' => '<strong>' . t('Enable Text Marketer on this site') .
      '</strong>',
      '#description' => t('Must be enabled to start sending user mobile numbers
      from %this_site to Text Marketer.',
        array('%this_site' => $base_url)),
    );
    $form['settings_fieldset']['group_id'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('group_id'),
      '#title' => '<strong>' . t('Text Marketer group ID') . '</strong>',
      '#description' => t('Enter the Text Marketer ID of the group to which
      you want to send numbers.'),
      '#size' => 14,
      '#maxlength' => 14,
      '#required' => TRUE,
    );
    $form['settings_fieldset']['group_name'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('group_name'),
      '#title' => '<strong>' . t('Text Marketer group name') . '</strong>',
      '#description' => t('Enter the Text Marketer name of the group to which
       you want to send numbers. This is needed for updating mobile numbers.'),
      '#size' => 36,
      '#maxlength' => 128,
    );
    $form['settings_fieldset']['username'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('username'),
      '#title' => '<strong>' . t('Text Marketer username') . '</strong>',
      '#description' => t('Enter the Text Marketer username.'),
      '#size' => 36,
      '#maxlength' => 128,
      '#required' => TRUE,
    );
    $form['settings_fieldset']['password'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('password'),
      '#title' => '<strong>' . t('Text Marketer password') . '</strong>',
      '#description' => t('Enter the Text Marketer password.'),
      '#size' => 36,
      '#maxlength' => 128,
      '#required' => TRUE,
    );
    $form['settings_fieldset']['host'] = array(
      '#type' => 'textfield',
      '#default_value' => $this->apiHost(),
      '#title' => '<strong>' . t('Text Marketer API Host') . '</strong>',
      '#description' => t('The Text Marketer API host. Do not include a slash
       in the URL.'),
      '#size' => 36,
      '#maxlength' => 128,
      '#required' => TRUE,
    );
    $form['settings_fieldset']['field_telephone'] = array(
      '#type' => 'select',
      '#title' => '<strong>' . t('Text Marketer telephone field') . '</strong>',
      '#description' => t('Select the telephone number field that will be used
        by the site to send numbers to Text Marketer.'),
      '#options' => $options,
      '#default_value' => $config->get('field_telephone'),
      '#required' => TRUE,
    );
    $form['settings_fieldset']['field_subscribe'] = [
      '#type' => 'select',
      '#title' => '<strong>' . t('Text Marketer subscribe to SMS field') .
        '</strong>',
      '#description' => t('If you want to send contact numbers only when a
        user ticks a checkbox, then select a checkbox field from the list.
        You must first create the field in the !account_settings_page.'),
      '#options' => array_merge(
        ['field not required' => 'Field notrequired'],
        $options
      ),
      '#default_value' => $config->get('field_subscribe'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('textmarketer_contacts.settings')
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('group_id', $form_state->getValue('group_id'))
      ->set('group_name', $form_state->getValue('group_name'))
      ->set('password', $form_state->getValue('password'))
      ->set('username', $form_state->getValue('username'))
      ->set('field_telephone', $form_state->getValue('field_telephone'))
      ->set('field_subscribe', $form_state->getValue('field_subscribe'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['textmarketer_contacts.settings'];
  }

}
