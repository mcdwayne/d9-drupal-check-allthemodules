<?php

namespace Drupal\ulogin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element\Tableselect;
use Drupal\Core\Url;
use Drupal\ulogin\UloginHelper;

/**
 * Settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ulogin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ulogin.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ulogin.settings')->getRawData();
    $form = [];

    $form['vtabs'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'edit-fset-display',
      '#attached' => [
        'library' => ['ulogin/admin'],
      ],
    ];

    // Tab: Widget settings.
    $form['fset_display'] = [
      '#type' => 'details',
      '#title' => t('Widget settings'),
      '#group' => 'vtabs',
    ];

    $form['fset_display']['ulogin_widget_title'] = [
      '#type' => 'textfield',
      '#title' => t('Widget title'),
      '#default_value' => isset($config['widget_title']) ? $config['widget_title'] : '',
    ];

    $form['fset_display']['ulogin_widget_id'] = [
      '#type' => 'textfield',
      '#title' => t('Widget ID'),
      '#description' => t('Enter uLogin ID of the widget if you have configured one on the uLogin site.'),
      '#default_value' => isset($config['widget_id']) ? $config['widget_id'] : '',
    ];

    $form['fset_display']['ulogin_display'] = [
      '#type' => 'radios',
      '#title' => t('Widget type'),
      '#description' => t('Select uLogin widget type.'),
      '#options' => [
        'small' => t('Small icons'),
        'panel' => t('Big icons'),
        'window' => t('Popup window'),
        'buttons' => t('Custom icons'),
      ],
      '#default_value' => isset($config['display']) ? $config['display'] : 'panel',
    ];

    $form['fset_display']['ulogin_icons_path'] = [
      '#type' => 'textfield',
      '#title' => t('Custom icons path'),
      '#description' => t('Custom icons path relative to Drupal root directory. See @link for details.',
        [
          '@link' => Link::fromTextAndUrl(
            'custom buttons page',
            Url::fromUri(
              'http://ulogin.ru/custom_buttons.html',
              [
                'attributes' => ['target' => '_blank']
              ])
          )->toString()
        ]),
      '#default_value' => isset($config['icons_path']) ? $config['icons_path'] : '',
      '#states' => [
        'visible' => [
          ':input[name="ulogin_display"]' => ['value' => 'buttons'],
        ],
      ],
    ];

    $form['fset_display']['ulogin_widget_weight'] = [
      '#type' => 'weight',
      '#title' => t('Widget weight'),
      '#description' => t('Determines the order of the elements on the form - heavier elements get positioned later.'),
      '#default_value' => isset($config['widget_weight']) ? $config['widget_weight'] : -100,
      '#delta' => 100,
    ];

    // Tab: Authentication providers.
    $form['fset_providers'] = [
      '#type' => 'details',
      '#title' => t('Authentication providers'),
      '#group' => 'vtabs'
    ];

    $header = [
      'name' => t('Name'),
      'main' => t('Main'),
      'weight' => '',
    ];

    $options = [];
    $providers = UloginHelper::providersList();
    $default_enabled_providers = [
      'vkontakte',
      'odnoklassniki',
      'mailru',
      'facebook',
      'twitter',
      'google',
      'yandex',
      'livejournal',
      'openid'
    ];
    $default_main_providers = [
      'vkontakte',
      'odnoklassniki',
      'mailru',
      'facebook',
    ];
    $enabled_providers = array_filter(isset($config['providers_enabled']) ? $config['providers_enabled'] : array_combine($default_enabled_providers, $default_enabled_providers));
    $main_providers = array_filter(isset($config['providers_main']) ? $config['providers_main'] : array_combine($default_main_providers, $default_main_providers));
    $main_providers = array_intersect_assoc($main_providers, $enabled_providers);

    foreach (array_keys($main_providers + $enabled_providers + $providers) as $weight => $provider_id) {
      $checked = in_array($provider_id, $main_providers) && in_array($provider_id, $enabled_providers);
      $options[$provider_id] = [
        'name' => $providers[$provider_id],
        'main' => [
          'data' => [
            '#tree' => FALSE,
            '#type' => 'checkbox',
            '#name' => 'ulogin_providers_main[' . $provider_id . ']',
            '#default_value' => $checked,
            '#attributes' => [
              'checked' => $checked,
            ],
            '#states' => [
              'disabled' => [
                ':input[name="ulogin_providers[' . $provider_id . ']"]' => ['checked' => FALSE],
              ],
            ],
          ]
        ],
        'weight' => [
          'data' => [
            '#tree' => FALSE,
            '#type' => 'number',
            '#name' => 'ulogin_providers_weight[' . $provider_id . ']',
            '#title' => $this->t('Weight'),
            '#value' => $weight,
            '#attributes' => [
              'class' => [
                'ulogin-providers-weight'
              ],
            ],
          ]
        ],
        '#attributes' => [
          'class' => ['draggable'],
          'id' => 'providers-' . $provider_id
        ],
      ];
    }

    $form['fset_providers']['ulogin_providers'] = [
      '#type' => 'tableselect',
      '#title' => t('Providers'),
      '#header' => $header,
      '#options' => $options,
      '#default_value' => $enabled_providers,
      '#attributes' => ['id' => 'ulogin-providers'],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'ulogin-providers-weight',
        ],
      ],
    ];

    // Tab: Fields to request.
    $form['fset_fields'] = [
      '#type' => 'details',
      '#title' => t('Fields to request'),
      '#group' => 'vtabs'
    ];

    $header = [
      'name' => t('Name'),
      'required' => t('Required'),
    ];
    $options = [];
    $fields = UloginHelper::fieldsList();
    $default_required_fields = [
      'first_name' => 'first_name',
      'last_name' => 'last_name',
      'email' => 'email',
      'nickname' => 'nickname',
      'bdate' => 'bdate',
      'sex' => 'sex',
      'photo' => 'photo',
      'photo_big' => 'photo_big',
      'country' => 'country',
      'city' => 'city',
    ];
    $default_optional_fields = [
      'phone' => 'phone',
    ];
    $required_fields = array_filter(isset($config['fields_required']) ? $config['fields_required'] : $default_required_fields);
    $optional_fields = array_filter(isset($config['fields_optional']) ? $config['fields_optional'] : $default_optional_fields);

    foreach (array_keys($fields) as $weight => $field_id) {
      $checked = in_array($field_id, $required_fields);
      $options[$field_id] = [
        'name' => $fields[$field_id],
        'required' => [
          'data' => [
            '#tree' => FALSE,
            '#type' => 'checkbox',
            '#name' => 'ulogin_fields_required[' . $field_id . ']',
            '#default_value' => $checked,
            '#attributes' => [
              'checked' => $checked,
            ],
            '#states' => [
              'disabled' => [
                ':input[name="ulogin_fields[' . $field_id . ']"]' => ['checked' => FALSE],
              ],
            ],
          ]
        ],
        '#attributes' => [
          'class' => [
            'draggable'
          ]
        ],
        '#weight' => $weight,
      ];
    }

    $form['fset_fields']['ulogin_fields'] = [
      '#type' => 'tableselect',
      '#title' => t('Fields'),
      '#header' => $header,
      '#options' => $options,
      '#default_value' => $required_fields + $optional_fields,
      '#attributes' => ['id' => 'ulogin-fields'],
    ];

    // Tab: Account settings.
    $form['fset_account'] = [
      '#type' => 'details',
      '#title' => t('Account settings'),
      '#group' => 'vtabs'
    ];

    $form['fset_account']['ulogin_disable_username_change'] = [
      '#type' => 'checkbox',
      '#title' => t('Disable username change'),
      '#description' => t('Remove username field from user account edit form for users created by uLogin.
       If this is unchecked then users should also have "Change own username" permission to actually be able to change the username.'),
      '#default_value' => isset($config['disable_username_change']) ? $config['disable_username_change'] : 1,
    ];

    $form['fset_account']['ulogin_remove_password_fields'] = [
      '#type' => 'checkbox',
      '#title' => t('Remove password fields'),
      '#description' => t('Remove password fields from user account edit form for users created by uLogin.'),
      '#default_value' => isset($config['remove_password_fields']) ? $config['remove_password_fields'] : 1,
    ];

    $form['fset_account']['ulogin_pictures'] = [
      '#type' => 'checkbox',
      '#title' => t('Save uLogin provided picture as user picture'),
      '#description' => t('Save pictures provided by uLogin as user pictures.'),
      '#default_value' => isset($config['pictures']) ? $config['pictures'] : 1,
    ];

    $form['fset_account']['ulogin_email_confirm'] = [
      '#type' => 'checkbox',
      '#title' => t('Confirm emails'),
      '#description' => t('Confirm manually entered emails - if you require email address and authentication provider does not provide one. Install @link module to make this option available.',
        [
          '@link' => Link::fromTextAndUrl(
            t('Email Change Confirmation'),
            Url::fromUri(
              'http://drupal.org/project/email_confirm',
              [
                'attributes' => [
                  'target' => '_blank',
                ],
              ]
            )
          )->toString()
        ]),
      '#default_value' => isset($config['email_confirm']) ? $config['email_confirm'] : 0,
      '#disabled' => !\Drupal::moduleHandler()->moduleExists('email_confirm'),
    ];

    $form['fset_account']['ulogin_username'] = [
      '#type' => 'textfield',
      '#title' => t('Username pattern'),
      '#description' => t('Create username for new users using this pattern; counter will be added in case of username conflict.')
      . ' ' . t(
          'Install @link module to get list of all available tokens.',
          [
            '@link' => Link::fromTextAndUrl(
              $this->t('Token'),
              Url::fromUri(
                'http://drupal.org/project/token',
                [
                  'attributes' => [
                    'target' => '_blank',
                  ],
                ]
              )
            )->toString()
          ]
      ) . ' ' . t('You should use only uLogin tokens here as the user is not created yet.'),
      '#default_value' => isset($config['username']) ? $config['username'] : '[user:ulogin:network]_[user:ulogin:uid]',
      '#required' => TRUE,
    ];

    $form['fset_account']['ulogin_display_name'] = [
      '#type' => 'textfield',
      '#title' => t('Display name pattern'),
      '#description' => t('Leave empty to not alter display name. You can use any user tokens here.')
      . ' ' . t(
        'Install @link module to get list of all available tokens.',
          [
            '@link' => Link::fromTextAndUrl(
              'Token',
              Url::fromUri(
                'http://drupal.org/project/token',
                [
                  'attributes' => [
                    'target' => '_blank',
                  ],
                ]
              )
            )->toString()
          ]
      ),
      '#default_value' => isset($config['display_name']) ? $config['display_name'] : '[user:ulogin:first_name] [user:ulogin:last_name]',
    ];

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['fset_account']['fset_token'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['user'],
        '#global_types' => FALSE,
        '#click_insert' => TRUE,
        '#show_restricted' => FALSE,
        '#recursion_limit' => 3,
        '#text' => t('Browse available tokens'),
      ];
    }

    $form['fset_account']['ulogin_override_realname'] = [
      '#type' => 'checkbox',
      '#title' => t('Override Real name'),
      '#description' => t('Override <a href="@link1">Real name settings</a> using the above display name pattern for users created by uLogin. This option is available only if @link2 module is installed.',
        [
          '@link1' => Url::fromUri('internal:/admin/config/people/realname')->toString(),
          '@link2' => Link::fromTextAndUrl(
            'Real name',
            Url::fromUri(
              'http://drupal.org/project/realname',
              [
                'attributes' => [
                  'target' => '_blank'
                ]
              ]
            )
          )->toString()
        ]),
      '#default_value' => isset($config['override_realname']) ? $config['override_realname'] : 0,
      '#disabled' => !\Drupal::moduleHandler()->moduleExists('realname'),
    ];

    // Tab: Other settings.
    $form['fset_other'] = [
      '#type' => 'details',
      '#title' => t('Other settings'),
      '#group' => 'vtabs'
    ];

    $form['fset_other']['ulogin_destination'] = [
      '#type' => 'textfield',
      '#title' => t('Redirect after login'),
      '#default_value' => isset($config['destination']) ? $config['destination'] : '',
      '#description' => t('Drupal path to redirect to, like "node/1". Leave empty to return to the same page (set to [HTTP_REFERER] for widget in modal dialogs loaded by AJAX).'),
    ];

    $form['fset_other']['ulogin_forms'] = [
      '#type' => 'checkboxes',
      '#title' => t('Drupal forms'),
      '#description' => t('Add default uLogin widget to these forms.'),
      '#options' => [
        'user_login_form' => t('User login form'),
        'user_register_form' => t('User registration form'),
        'comment_comment_form' => t('Comment form'),
      ],
      '#default_value' => isset($config['forms']) ? $config['forms'] : [
        'user_login_form'
      ],
    ];

    $form['fset_other']['ulogin_duplicate_emails'] = [
      '#type' => 'radios',
      '#title' => t('Duplicate emails'),
      '#description' => t('Select how to handle duplicate email addresses. This situation occurs when the same user is trying to authenticate using different authentication providers, but with the same email address.'),
      '#options' => [
        0 => t('Allow duplicate email addresses'),
        1 => t("Don't allow duplicate email addresses, block registration and advise to log in using the existing account"),
      ],
      '#default_value' => isset($config['duplicate_emails']) ? $config['duplicate_emails'] : 1,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form validation handler for the ulogin admin settings form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getUserInput();

    $providers_main_values = !empty($values['ulogin_providers_main']) ? $values['ulogin_providers_main'] : [];
    $providers_enabled_values = $values['ulogin_providers'];
    unset($values['ulogin_providers_main']);
    unset($values['ulogin_providers']);

    $values['ulogin_providers_enabled'] = [];
    $values['ulogin_providers_main'] = [];
    asort($values['ulogin_providers_weight']);
    foreach (array_keys($values['ulogin_providers_weight']) as $provider_id) {
      $values['ulogin_providers_enabled'][$provider_id] = $providers_enabled_values[$provider_id];
      $values['ulogin_providers_main'][$provider_id] = !empty($providers_main_values[$provider_id]) ? $provider_id : NULL;
    }
    // Remove weights so they are not saved as variables.
    unset($values['ulogin_providers_weight']);

    // Process 'required' checkboxes and remove them.
    if (empty($values['ulogin_fields_required'])) {
      $values['ulogin_fields_required'] = [];
    }
    else {
      foreach ($values['ulogin_fields_required'] as $key => $value) {
        if (!empty($value)) {
          $values['ulogin_fields_required'][$key] = $key;
        }
      }
    }

    $values['ulogin_fields_optional'] = array_diff_assoc($values['ulogin_fields'], $values['ulogin_fields_required']);
    unset($values['ulogin_fields']);
    $form_state->setValues($values);
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ulogin.settings');
    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'ulogin_') !== FALSE) {
        $config->set(str_replace('ulogin_', '', $key), $value);
      }
    }
    $config->save();

    drupal_set_message(t('Configuration was saved.'));
  }

}
