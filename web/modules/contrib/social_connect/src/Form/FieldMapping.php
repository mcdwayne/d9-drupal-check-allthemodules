<?php

namespace Drupal\social_connect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Social connect configuration form.
 */
class FieldMapping extends ConfigFormBase {

  /**
   * Global settings for Social Connect.
   *
   * @var globalSetting
   */
  private $globalSettings;

  /**
   * All connection settings.
   *
   * @var connections
   */
  private $connections;

  /**
   * The Connection setting.
   *
   * @var connectionSettings
   */
  private $connectionSettings;

  /**
   * Field mapping settings.
   *
   * @var fieldMapping
   */
  private $fieldMappings;

  /**
   * Source Facebook/Google.
   *
   * @var source
   */
  private $source;

  /**
   * FieldMapping constructor.
   */
  public function __construct() {
    $configs = $this->config('social_connect.settings');
    $this->globalSettings = $configs->get('global');

    $current_url = Url::fromRoute('<current>');
    $path = $current_url->toString();
    //OR of you want to get the url without language prefix
    $path = $current_url->getInternalPath();
    $path_args = explode('/', $path);
    $this->source = (isset($path_args[5])) ? $path_args[5] : 'facebook';

    switch ($this->source) {
      case "google":
        $this->source = 'google';
        break;
    }
    $this->connections = $configs->get('connections');
    $this->connectionSettings = $this->connections[$this->source];

    $this->fieldMappings = $this->connectionSettings['field_maps'];
  }

  /**
   * Determines the ID of a form.
   */
  public function getFormId() {
    return 'social_connect_admin_settings_field_mapping';
  }

  /**
   * Gets the configuration names that will be editable.
   */
  public function getEditableConfigNames() {
    return [
      'social_connect.settings'
    ];
  }

  /**
   * Form constructor.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $profile_fields = \Drupal::entityManager()->getFieldDefinitions('user', 'user');
    $form['maps'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Profile field'),
        $this->t('Source field'),
        $this->t('Override profile value?')
      ],
      '#empty' => $this->t('There are currently no field in user profile.')
    ];
    $options = $this->sourceFields();
    foreach ($profile_fields as $field_name => $field) {
      $lable = $field->getLabel();
      if (!is_object($lable)) {
        $form['maps'][$field_name]['profile_field'] = [
          '#tree' => FALSE,
          'data' => [
            'label' => [
              '#plain_text' => $lable
            ]
          ]
        ];

        $form['maps'][$field_name]['source_field'] = [
          '#type' => 'select',
          '#empty_option' => $this->t('Select'),
          '#empty_value' => NULL,
          '#options' => $options,
          '#default_value' => $this->getMapValue('source_field', $field_name)
        ];

        $form['maps'][$field_name]['override'] = [
          '#type' => 'checkbox',
          '#default_value' => $this->getMapValue('override', $field_name)
        ];
      }
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save field mapping')
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $mappings = [];
    foreach ($values['maps'] as $profile_field => $mapping) {
      if (!empty($mapping['source_field'])) {
        $mappings[] = [
          'profile_field' => $profile_field,
          'source_field' => $mapping['source_field'],
          'override' => $mapping['override']
        ];
      }
    }

    $configs = $this->connections;
    $configs[$this->source]['field_maps'] = $mappings;
    $this->config('social_connect.settings')
        ->set('connections', $configs)
        ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Returns the field map value.
   *
   * @param string $key
   *   The key.
   * @param string $field
   *   Field name.
   *
   * @return string
   *   Returns mapping field.
   */
  private function getMapValue($key, $field) {
    foreach ($this->fieldMappings as $mapping) {
      if ($mapping['profile_field'] == $field) {
        return $mapping[$key];
      }
    }
    return NULL;
  }

  /**
   * Returns soruce fields of social connect.
   *
   * @return array
   *   Rerurns fields array.
   */
  private function sourceFields() {
    // Facebook fields.
    $fields = [
      'facebook' => [
        ['id', 'User ID (used for login)'],
        ['age_min', "Age range (Min)"],
        ['age_max', "Age range (Max)"],
        ['email', 'Email'],
        ['first_name', 'First name'],
        ['gender', "Gender (return values: 'M', 'F')"],
        ['last_name', 'Last name'],
        ['link', 'Profile url'],
        ['locale', 'Locale'],
        ['middle_name', 'Middle name'],
        ['name', 'Full name'],
        ['picture', 'Picture'],
        ['timezone', 'Timezone ID'],
        ['verified', 'Verified']
      ],
      'google' => [
        ['sub', 'User ID (used for login)'],
        ['given_name', 'First name'],
        ['name', 'Full name'],
        ['family_name', 'Last name'],
        ['gender', "Gender (return values: 'M', 'F')"],
        ['locale', 'Locale'],
        ['picture', 'Picture'],
        ['profile', 'Profile url'],
        ['email_verified', 'Verified']
      ]
    ];
    $options = [];
    foreach ($fields[$this->source] as $field) {
      $options[$field[0]] = $field[1];
    }
    return $options;
  }

}
