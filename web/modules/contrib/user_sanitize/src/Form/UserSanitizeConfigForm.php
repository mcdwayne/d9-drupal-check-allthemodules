<?php

namespace Drupal\user_sanitize\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class UserSanitizeConfigForm.
 */
class UserSanitizeConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->get('user_sanitize.settings');

    $form['description'] = [
      '#markup' => t('Use this form to configure User Sanitize. Once configured, use the button below or drush to sanitize users.<br /><br /><b>Usage:</b><br /><pre>drush user-sanitize</pre>'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    $form['manual'] = [
      '#type' => 'submit',
      '#value' => t('Save and sanitize users'),
      '#submit' => [
        [$this, 'submitForm'],
        'user_sanitize_trigger_sanitization',
      ],
    ];

    $form['settings'] = [
      '#type' => 'details',
      '#tree' => TRUE,
      '#open' => TRUE,
      '#title' => t('User Exclusion'),
    ];

    $default_roles = [];
    foreach ($config->get('settings.exclusion.excluded_roles') as $role => $value) {
      if ($value) {
        $default_roles[] = $role;
      }
    }

    $form['settings']['exclusion']['excluded_roles'] = [
      '#type' => 'checkboxes',
      '#title' => t('Exclude users with Roles'),
      '#options' => $this->getRoles(),
      '#default_value' => $default_roles,
    ];

    $form['settings']['exclusion']['excluded_ids'] = [
      '#type' => 'textfield',
      '#title' => t('Exclude users by uid'),
      '#description' => t('Provide a comma separated list, e.g. 2,3,4. Uid 1 will NEVER be sanitized.'),
      '#default_value' => $config->get('settings.exclusion.excluded_ids'),
    ];

    $form['fields'] = [
      '#type' => 'details',
      '#tree' => 'false',
      '#open' => TRUE,
      '#title' => t('Sanitization Fields'),
    ];

    foreach ($this->getUserFields() as $fieldId => $fieldDefinition) {
      $form['fields'][$fieldId] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => $fieldId,
      ];
      $form['fields'][$fieldId]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => t('Sanitize this field'),
        '#default_value' => $config->get('fields.' . $fieldId . '.enabled'),
      ];

      $form['fields'][$fieldId]['params'] = [
        '#type' => 'container',
        '#title' => $fieldId . ' sanitization settings',
        '#open' => TRUE,
        '#tree' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="fields[' . $fieldId . '][enabled]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['fields'][$fieldId]['params']['sanitizer'] = [
        '#type' => 'select',
        '#title' => 'Sanitization type',
        '#default_value' => $config->get('fields.' . $fieldId . '.params.sanitizer'),
        '#options' => [
          'blank' => 'Set to empty/user defined string',
          'name' => 'Name',
          'word' => 'Random Word',
          'sentence' => 'Random Sentence',
        ],
      ];

      $form['fields'][$fieldId]['params']['word_count'] = [
        '#type' => 'number',
        '#title' => 'Number of words to generate',
        '#default_value' => $config->get('fields.' . $fieldId . '.params.word_count'),
        '#states' => [
          'visible' => [
            ':input[name="fields[' . $fieldId . '][params][sanitizer]"]' => ['value' => 'word'],
          ],
          'required' => [
            ':input[name="fields[' . $fieldId . '][params][sanitizer]"]' => ['value' => 'word'],
          ],
        ],
      ];

      $form['fields'][$fieldId]['params']['sentence_count'] = [
        '#type' => 'number',
        '#title' => 'Number of sentences to generate',
        '#default_value' => $config->get('fields.' . $fieldId . '.params.sentence_count'),
        '#states' => [
          'visible' => [
            ':input[name="fields[' . $fieldId . '][params][sanitizer]"]' => ['value' => 'sentence'],
          ],
          'required' => [
            ':input[name="fields[' . $fieldId . '][params][sanitizer]"]' => ['value' => 'sentence'],
          ],
        ],
      ];

      $form['fields'][$fieldId]['params']['lowercase'] = [
        '#type' => 'checkbox',
        '#title' => 'Enforce lowercase?',
        '#default_value' => $config->get('fields.' . $fieldId . '.params.lowercase'),
      ];

      $form['fields'][$fieldId]['params']['suffix'] = [
        '#type' => 'checkbox',
        '#title' => 'Add suffix/set field to user defined string',
        '#default_value' => $config->get('fields.' . $fieldId . '.params.suffix'),
      ];

      $form['fields'][$fieldId]['params']['suffix_text'] = [
        '#type' => 'textfield',
        '#title' => 'Suffix to append to ALL values',
        '#description' => t('e.g. @localhost (use this field to add a suffix to any generated field or set the field to specific text - enforce lowercase is ignored for values here.)'),
        '#default_value' => $config->get('fields.' . $fieldId . '.params.suffix_text'),
        '#states' => [
          'visible' => [
            ':input[name="fields[' . $fieldId . '][params][suffix]"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['user_sanitize.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_sanitize_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check that the admin hasn't tried to exclude uid 1.
    $excluded_users = str_getcsv($form_state->getValue([
      'settings',
      'exclusion',
      'excluded_ids',
    ]));
    // NO!
    if (in_array(1, $excluded_users)) {
      $form_state->setError($form['settings']['exclusion']['excluded_ids'], 'You cannot exclude uid 1, please remove this from the list!');
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $settings_store = [];

    foreach ($values['settings'] as $setting_id => $setting) {
      $settings_store['settings'][$setting_id] = $setting;
    }

    foreach ($values['fields'] as $field_id => $setting) {
      $settings_store['fields'][$field_id] = $setting;
    }

    $editibleConfig = $this->configFactory()->getEditable('user_sanitize.settings');
    $editibleConfig->initWithData($settings_store);
    $editibleConfig->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Method to get roles in the system. Excludes excluded roles.
   */
  protected function getRoles() {
    $roles = user_role_names();
    foreach ($this->getExcludedRoles() as $remove) {
      unset($roles[$remove]);
    }
    return $roles;
  }

  /**
   * Method to get default user fields. Excludes excluded fields.
   */
  protected function getUserFields() {
    /** @var \Drupal\user\UserStorage $user_storage */
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    $fields = $user_storage->getFieldStorageDefinitions();
    foreach ($this->getExcludedUserFields() as $remove) {
      unset($fields[$remove]);
    }
    return $fields;
  }

  /**
   * Method to get array of roles to exclude from the list of options.
   *
   * @todo add a hook to allow modules to alter this list.
   */
  protected function getExcludedRoles() {
    $roles = [
      'anonymous',
    ];
    \Drupal::moduleHandler()->alter('user_sanitize_excluded_roles', $roles);
    return $roles;
  }

  /**
   * Method to get array of fields to exclude from the list of fields.
   */
  protected function getExcludedUserFields() {
    $fields = [
      'uid',
      'langcode',
      'preferred_langcode',
      'preferred_admin_langcode',
      'timezone',
      'created',
      'changed',
      'access',
      'login',
      'init',
      'roles',
      'default_langcode',
      'uuid',
      'status',
    ];
    \Drupal::moduleHandler()->alter('user_sanitize_excluded_user_fields', $fields);
    return $fields;
  }

}
