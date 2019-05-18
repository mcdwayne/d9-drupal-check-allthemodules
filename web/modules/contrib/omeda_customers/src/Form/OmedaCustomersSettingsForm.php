<?php

namespace Drupal\omeda_customers\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\user\Entity\User;
use Drupal\encryption\EncryptionService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;

/**
 * Configure Omeda Customers settings for this site.
 */
class OmedaCustomersSettingsForm extends ConfigFormBase {

  /**
   * The encryption service.
   *
   * @var \Drupal\encryption\EncryptionService
   */
  protected $encryption;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a \Drupal\omeda\Form\OmedaSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\encryption\EncryptionService $encryption
   *   The encryption service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EncryptionService $encryption, StateInterface $state) {
    parent::__construct($config_factory);
    $this->encryption = $encryption;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('encryption'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'omeda_customers_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['omeda_customers.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($brand_info = $this->state->get('omeda.brand_lookup')) {
      $config = $this->config('omeda_customers.settings');

      $roles = user_roles(TRUE);
      $role_options = [];
      foreach ($roles as $key => $role) {
        $role_options[$key] = $key;
      }

      $form['general'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('General Settings'),
      ];
      $form['general']['user_sync_enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('User Sync Enabled'),
        '#description' => $this->t('If this is enabled, then users of roles configured below will attempt to sync to Omeda Customers whenever that user is saved.'),
        '#default_value' => $config->get('user_sync_enabled'),
      ];
      $form['general']['force_immediate_execution'] = [
        '#type' => 'checkbox',
        '#description' => $this->t('If this is enabled, the Omeda Run Processor call will be invoked immediately after running the Save Customer and Order API call.'),
        '#title' => $this->t('Force Immediate Execution'),
        '#default_value' => $config->get('force_immediate_execution'),
      ];
      $form['general']['external_customer_id_namespace'] = [
        '#type' => 'textfield',
        '#description' => $this->t('When making the Store Customer and Order API call, if the External Customer Id Namespace setting is populated it is sent as the ExternalCustomerIdNamespace and the UUID of the user being saved gets sent as the ExternalCustomerId. If this setting is not populated, and the Drupal user email field is set to sync, Customer Lookup By Email is first called and if a match is found, the OmedaCustomerID is sent along.'),
        '#title' => $this->t('External Customer ID Namespace'),
        '#default_value' => $config->get('external_customer_id_namespace') ? $this->encryption->decrypt($config->get('external_customer_id_namespace'), TRUE) : '',
      ];

      $form['roles_to_sync_wrapper'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Roles to Sync'),
      ];
      $form['roles_to_sync_wrapper']['roles_to_sync'] = [
        '#type' => 'checkboxes',
        '#description' => $this->t('If the User Sync is enabled above, then users of roles configured below will attempt to sync to Omeda Customers whenever that user is saved.'),
        '#options' => $role_options,
        '#default_value' => $config->get('roles_to_sync'),
      ];

      $form['field_mappings'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Field Mappings'),
      ];
      $user_field_data = User::load($this->currentUser()->id())->getFieldDefinitions();

      $contact_types = [];
      foreach ($brand_info['ContactTypes'] as $contact_type) {
        $contact_types[$contact_type['Id']] = $contact_type['Description'];
      }

      $demo_fields = [];
      $demo_values = [];
      foreach ($brand_info['Demographics'] as $demo) {
        $demo_fields[$demo['Id']] = $demo['Description'] . ' (' . $demo['Id'] . ')';
        $demo_values[$demo['Id']] = [];
        foreach ($demo['DemographicValues'] as $demo_value) {
          $demo_values[$demo['Id']][$demo_value['Id']] = $demo_value['ShortDescription'] ?? '';
        }
      }

      $field_mappings = $config->get('field_mappings');

      foreach ($user_field_data as $field_name => $field_data) {
        if (substr($field_name, 0, 6) === 'field_' || $field_name === 'mail' || $field_name === 'created') {
          $field_mapping = isset($field_mappings[$field_name]) ? $field_mappings[$field_name] : [
            'omeda_field_type' => 'base',
            'sync_enabled' => 0,
          ];

          if (isset($field_mapping['omeda_demographic_value_mapping'])) {
            foreach ($field_mapping['omeda_demographic_value_mapping'] as &$row) {
              $row = $row['omeda'] . '|' . $row['drupal'];
            }
            $field_mapping['omeda_demographic_value_mapping'] = implode("\n", $field_mapping['omeda_demographic_value_mapping']);
          }

          $form['field_mappings']['mapping_' . $field_name] = [
            '#type' => 'fieldset',
            '#title' => (string) $field_data->getLabel(),
            '#attributes' => ['class' => ['mapping-field']],
          ];
          $form['field_mappings']['mapping_' . $field_name][$field_name . '_enabled'] = [
            '#type' => 'select',
            '#options' => [1 => 'yes', 0 => 'no'],
            '#title' => $this->t('Sync'),
            '#attributes' => ['class' => ['sync-enabled']],
            '#default_value' => $field_mapping['sync_enabled'],
          ];
          if ($field_data->getType() === 'address') {
            $form['field_mappings']['mapping_' . $field_name][$field_name . '_omeda_field_type'] = [
              '#type' => 'select',
              '#title' => $this->t('Field Type'),
              '#options' => ['address' => 'address'],
              '#attributes' => ['class' => ['omeda-field-type']],
              '#default_value' => 'address',
              '#disabled' => TRUE,
            ];
          }
          else {
            $form['field_mappings']['mapping_' . $field_name][$field_name . '_omeda_field_type'] = [
              '#type' => 'select',
              '#title' => $this->t('Field Type'),
              '#options' => [
                'base' => 'base',
                'email' => 'email',
                'phone' => 'phone',
                'demographic' => 'demographic',
              ],
              '#attributes' => ['class' => ['omeda-field-type']],
              '#default_value' => $field_mapping['omeda_field_type'],
            ];
          }
          $form['field_mappings']['mapping_' . $field_name][$field_name . '_omeda_field'] = [
            '#type' => 'select',
            '#options' => [
              'Salutation' => 'Salutation',
              'FirstName' => 'FirstName',
              'MiddleName' => 'MiddleName',
              'LastName' => 'LastName',
              'Suffix' => 'Suffix',
              'Title' => 'Title',
              'Gender' => 'Gender',
              'SignupDate' => 'SignupDate',
            ],
            '#title' => $this->t('Omeda Field'),
            '#attributes' => ['class' => ['omeda-field']],
            '#default_value' => $field_mapping['omeda_field'] ?? '',
          ];
          $form['field_mappings']['mapping_' . $field_name][$field_name . '_omeda_contact_type'] = [
            '#type' => 'select',
            '#options' => $contact_types,
            '#title' => $this->t('Omeda Contact Type'),
            '#attributes' => ['class' => ['omeda-contact-type']],
            '#default_value' => $field_mapping['omeda_contact_type'] ?? '',
          ];
          // This is used to store json from js-based value mapping selection.
          $form['field_mappings']['mapping_' . $field_name][$field_name . '_omeda_demographic_field'] = [
            '#type' => 'select',
            '#options' => $demo_fields,
            '#title' => $this->t('Omeda Field'),
            '#attributes' => ['class' => ['omeda-demographic-field']],
            '#default_value' => $field_mapping['omeda_field_type'] === 'demographic' ? $field_mapping['omeda_demographic_field'] : '',
          ];
          $form['field_mappings']['mapping_' . $field_name][$field_name . '_omeda_demographic_value_mapping'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Demographic Value Mappings'),
            '#description' => $this->t('Enter one value per line, in the pipe-separated format <em>Omeda Demographic field value</em>|<em>Drupal user field value</em>.'),
            '#attributes' => ['class' => ['omeda-demographic-values']],
            '#default_value' => $field_mapping['omeda_field_type'] === 'demographic' ? $field_mapping['omeda_demographic_value_mapping'] : '',
          ];
        }
      }

      $form['#attached']['drupalSettings']['omeda']['contact_types'] = $contact_types;
      $form['#attached']['drupalSettings']['omeda']['demographic_values'] = $demo_values;

      $form['#attached']['library'][] = 'omeda_customers/settings_form';
    }
    else {
      $form['no_lookup'] = [
        '#type' => 'markup',
        '#markup' => $this->t('Brand comprehensive lookup needs to be run before you can configure customer settings. <a href="@url"> Click here to run it manually</a>', [
          '@url' => Url::fromRoute('omeda.manual_brand_comprehensive_lookup')->toString(),
        ]),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Validate demographic field mappings.
    $brand_info = $this->state->get('omeda.brand_lookup', []);
    $demo_values = [];
    foreach ($brand_info['Demographics'] as $demo) {
      $demo_values[$demo['Id']] = [];
      foreach ($demo['DemographicValues'] as $demo_value) {
        $demo_values[$demo['Id']][] = $demo_value['Id'];
      }
    }

    foreach ($form['field_mappings'] as $field_name => $fields) {
      if (substr($field_name, 0, 8) === 'mapping_') {
        $drupal_field = str_replace('mapping_', '', $field_name);
        $omeda_field_type = $form_state->getValue($drupal_field . '_omeda_field_type');
        if ($omeda_field_type === 'demographic') {
          $omeda_field = $form_state->getValue($drupal_field . '_omeda_demographic_field');
          $demo_values_submitted = $form_state->getValue($drupal_field . '_omeda_demographic_value_mapping');
          $demo_value_rows = preg_split('/\r\n|\r|\n/', $demo_values_submitted);
          foreach ($demo_value_rows as $row) {
            $row_values = explode('|', $row);
            if (count($row_values) !== 2) {
              $form_state->setErrorByName($drupal_field . '_omeda_demographic_value_mapping', $this->t('Demographic value mappings are either missing or improperly formatted. Please confirm.'));
            }
            elseif (!in_array($row_values[0], $demo_values[$omeda_field])) {
              $form_state->setErrorByName($drupal_field . '_omeda_demographic_value_mapping', $this->t('Demographic value mappings have invalid Omeda values. Please confirm.'));
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('omeda_customers.settings');

    $external_customer_id_namespace = $form_state->getValue('external_customer_id_namespace');
    if ($submitted_external_customer_id_namespace = $form_state->getValue('external_customer_id_namespace')) {
      if ($encrypted_external_customer_id_namespace = $this->encryption->encrypt($submitted_external_customer_id_namespace, TRUE)) {
        $external_customer_id_namespace = $encrypted_external_customer_id_namespace;
      }
      else {
        $this->messenger()->addError($this->t('Failed to encrypt the external customer namespace ID. Please ensure that the Encryption module is enabled and that an encryption key is set.'));
      }
    }

    // Handle field mappings.
    $field_mappings = [];
    foreach ($form['field_mappings'] as $field_name => $fields) {
      if (substr($field_name, 0, 8) === 'mapping_') {
        $drupal_field = str_replace('mapping_', '', $field_name);
        $sync_enabled = $form_state->getValue($drupal_field . '_enabled');
        $omeda_field_type = $form_state->getValue($drupal_field . '_omeda_field_type');
        $omeda_field = $form_state->getValue($drupal_field . '_omeda_field');
        $field_mappings[$drupal_field] = [
          'sync_enabled' => $sync_enabled,
          'omeda_field_type' => $omeda_field_type,
        ];
        if (in_array($omeda_field_type, ['base', 'demographic'])) {
          $field_mappings[$drupal_field]['omeda_field'] = $omeda_field;
        }
        if (in_array($omeda_field_type, ['address', 'email', 'phone'])) {
          $field_mappings[$drupal_field]['omeda_contact_type'] = $form_state->getValue($drupal_field . '_omeda_contact_type');
        }
        if ($omeda_field_type === 'demographic') {
          $demo_string_values = $form_state->getValue($drupal_field . '_omeda_demographic_value_mapping');
          $demo_string_values_rows = preg_split('/\r\n|\r|\n/', $demo_string_values);
          $demo_array_values = [];
          foreach ($demo_string_values_rows as $row) {
            $row = explode('|', $row);
            $demo_array_values[] = [
              'omeda' => $row[0],
              'drupal' => $row[1],
            ];
          }
          $field_mappings[$drupal_field]['omeda_demographic_value_mapping'] = $demo_array_values;
          $field_mappings[$drupal_field]['omeda_demographic_field'] = $form_state->getValue($drupal_field . '_omeda_demographic_field');
        }
      }
    }

    $config->set('force_immediate_execution', $form_state->getValue('force_immediate_execution'));
    $config->set('roles_to_sync', $form_state->getValue('roles_to_sync'));
    $config->set('external_customer_id_namespace', $external_customer_id_namespace);
    $config->set('user_sync_enabled', $form_state->getValue('user_sync_enabled'));
    $config->set('field_mappings', $field_mappings);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
