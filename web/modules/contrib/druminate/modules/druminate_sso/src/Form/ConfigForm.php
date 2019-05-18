<?php

namespace Drupal\druminate_sso\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\druminate\Luminate\DruminateApi;

/**
 * Class ConfigForm.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * Drupal\druminate\Luminate\DruminateApi definition.
   *
   * @var \Drupal\druminate\Luminate\DruminateApi
   */
  protected $druminateDruminateApi;

  /**
   * Constructs a new ConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The current service configuration factory.
   * @param \Drupal\druminate\Luminate\DruminateApi $druminate_druminate_api
   *   The Druminate API service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DruminateApi $druminate_druminate_api) {
    parent::__construct($config_factory);
    $this->druminateDruminateApi = $druminate_druminate_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('druminate.druminate_api')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'druminate_sso.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('druminate_sso.settings');
    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Debugging'),
      '#description' => $this->t('Writes debugging information to Drupal log.'),
      '#default_value' => $config->get('debug'),
    ];
    $form['login_link_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Login Link Enabled'),
      '#description' => $this->t('Show a link to login via LO in the User Login form.'),
      '#default_value' => $config->get('login_link_enabled'),
    ];
    $form['login_link_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login Link Label'),
      '#default_value' => $config->get('login_link_label'),
      '#states' => [
        'visible' => [
          ':input[name="login_link_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['establish_session_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Establish Session Sharing'),
      '#description' => $this->t('Session sharing allows the user to be automatically 
        logged into Luminate Online by pushing a session cookie to the client browser. 
        See http://open.convio.com/api/#main.sso_convio_as_client for more information.'),
      '#default_value' => $config->get('establish_session_enabled'),
    ];
    $form['establish_session_uri'] = [
      '#type' => 'url',
      '#title' => $this->t('Establish Session Servlet URI'),
      '#description' => $this->t("The EstablishSession servlet URI, ie: <em>https://secure2.convio.net/sitename/site/EstablishSession</em>. Do not leave a trailing slash. (/)"),
      '#default_value' => $config->get('establish_session_uri'),
      '#states' => [
        'visible' => [
          ':input[name="establish_session_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['role'] = [
      '#type' => 'details',
      '#title' => $this->t('Role Mapping Settings'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];

    $form['role']['deny_no_match'] = [
      '#type' => 'radios',
      '#title' => $this->t('Deny login if no roles mapped?'),
      '#description' => $this->t('If this is set, users who do not receive roles from the role mappings 
      configured below will be denied login access.'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('role.deny_no_match'),
    ];

    $form['role']['mappings'] = [
      '#type' => 'details',
      '#title' => $this->t('Role Mappings'),
      '#description' => $this->t('Each role mapping is a relationship between a role that is to be granted,
      an LO constituent group name, and a method to use for matching.
      The \'match\' method will only validate a value if it (case-insensitively) matches exactly. 
      The \'contains\' method will validate a value if it exists within a group name.'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];

    $role_mappings = $config->get('role.role_mapping');
    $roles_available = user_roles();
    unset($roles_available[RoleInterface::AUTHENTICATED_ID]);
    unset($roles_available[RoleInterface::ANONYMOUS_ID]);
    $roles_options = [];
    $count = empty($role_mappings) ? 0 : count($role_mappings);
    if (empty($role_mappings)) {
      $role_mappings = [];
    }
    foreach ($roles_available as $role_id => $role) {
      $roles_options[$role_id] = $role->label();
    }

    foreach ($role_mappings as $index => $condition) {
      $form['role']['mappings'][$index] = [
        '#type' => 'fieldset',
        '#attributes' => ['class' => ['container-inline']],
      ];
      $form['role']['mappings'][$index]['rid'] = [
        '#type' => 'select',
        '#title' => $this->t('Role'),
        '#title_display' => 'invisible',
        '#options' => $roles_options,
        '#default_value' => $condition['rid'],
      ];
      $form['role']['mappings'][$index]['group'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Constituent User Group'),
        '#default_value' => $condition['group'],
        '#size' => 30,
      ];
      $form['role']['mappings'][$index]['method'] = [
        '#type' => 'select',
        '#title' => $this->t('Method'),
        '#options' => [
          'match' => $this->t('Match'),
          'contains' => $this->t('Contains'),
        ],
        '#title_display' => 'invisible',
        '#default_value' => $condition['method'],
      ];

      $form['role']['mappings'][$index]['delete'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Delete this mapping?'),
        '#default_value' => 0,
        '#title_display' => 'before',
      ];
    }

    $form['role']['mappings'][$count] = [
      '#type' => 'fieldset',
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['role']['mappings'][$count]['rid'] = [
      '#type' => 'select',
      '#title' => $this->t('Role'),
      '#options' => $roles_options,
      '#title_display' => 'invisible',
    ];
    $form['role']['mappings'][$count]['group'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Constituent User Group'),
      '#size' => 30,
    ];
    $form['role']['mappings'][$count]['method'] = [
      '#type' => 'select',
      '#title' => $this->t('Method'),
      '#options' => [
        'match' => $this->t('Match'),
        'contains' => $this->t('Contains'),
      ],
      '#title_display' => 'invisible',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $role_data = $form_state->getValue('role');
    $role_map = [];
    foreach ($role_data['mappings'] as $mapping) {
      if (isset($mapping['delete']) && $mapping['delete']) {
        continue;
      }
      if (empty($mapping['group'])) {
        continue;
      }
      $role_map[] = $mapping;
    }

    $this->config('druminate_sso.settings')
      ->set('role.role_mapping', $role_map)
      ->set('role.deny_no_match', $role_data['deny_no_match'])
      ->set('debug', $form_state->getValue('debug'))
      ->set('login_link_enabled', $form_state->getValue('login_link_enabled'))
      ->set('login_link_label', $form_state->getValue('login_link_label'))
      ->set('establish_session_uri', $form_state->getValue('establish_session_uri'))
      ->set('establish_session_enabled', $form_state->getValue('establish_session_enabled'))
      ->save();
  }

}
