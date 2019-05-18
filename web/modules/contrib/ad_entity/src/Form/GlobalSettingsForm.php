<?php

namespace Drupal\ad_entity\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ad_entity\Plugin\AdTypeManager;

/**
 * Class GlobalSettingsForm.
 *
 * @package Drupal\ad_entity\Form
 */
class GlobalSettingsForm extends ConfigFormBase {

  /**
   * The Advertising type manager.
   *
   * @var \Drupal\ad_entity\Plugin\AdTypeManager
   */
  protected $typeManager;

  /**
   * The context form element builder.
   *
   * @var \Drupal\ad_entity\Form\AdContextElementBuilder
   */
  protected $contextElementBuilder;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructor method.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\ad_entity\Plugin\AdTypeManager $ad_type_manager
   *   The Advertising type manager.
   * @param \Drupal\ad_entity\Form\AdContextElementBuilder $context_element_builder
   *   The context form element builder.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AdTypeManager $ad_type_manager, AdContextElementBuilder $context_element_builder, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->typeManager = $ad_type_manager;
    $this->contextElementBuilder = $context_element_builder;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $context_element_builder = AdContextElementBuilder::create($container);
    return new static(
      $container->get('config.factory'),
      $container->get('ad_entity.type_manager'),
      $context_element_builder,
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ad_entity.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ad_entity_settings';
  }

  /**
   * Get the mutable config object which belongs to this form.
   *
   * @return \Drupal\Core\Config\Config
   *   The mutable config object.
   */
  public function getConfig() {
    return $this->config('ad_entity.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->getConfig();

    $form['common'] = [
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#title' => $this->t('Settings for any type of advertisement'),
      '#weight' => 10,
    ];
    $form['common']['frontend'] = [
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#title' => $this->t('Frontend'),
      '#weight' => 10,
    ];
    $theme_breakpoints_js_exists = $this->moduleHandler->moduleExists('theme_breakpoints_js');
    $default_behavior = $config->get('enable_responsive_behavior') !== NULL ?
      (int) $config->get('enable_responsive_behavior') : (int) $theme_breakpoints_js_exists;
    $form['common']['frontend']['enable_responsive_behavior'] = [
      '#type' => 'radios',
      '#title' => $this->t('Responsive behavior'),
      '#options' => [0 => $this->t("Disabled"), 1 => $this->t("Enabled")],
      '#description' => $this->t("When enabled, advertisement will be dynamically initialized on breakpoint changes (e.g. when switching from narrow to wide). When disabled, advertisement will only be initialized based on the initial breakpoint during page load."),
      '#default_value' => $default_behavior,
      '#weight' => 10,
    ];
    if (!$theme_breakpoints_js_exists) {
      $form['common']['frontend']['enable_responsive_behavior']['#description'] = $this->t("Install the <a href=':url' target='_blank' rel='noopener nofollow'>Theme Breakpoints JS</a> module to enable ads to be dynamically initialized on breakpoint changes.", [':url' => 'https://www.drupal.org/project/theme_breakpoints_js']);
      $form['common']['frontend']['enable_responsive_behavior']['#disabled'] = TRUE;
    }

    if ($this->moduleHandler->moduleExists('filter')) {
      $filter_format_options = [];
      foreach (filter_formats() as $format_id => $format) {
        $filter_format_options[$format_id] = $format->label();
      }
      $default_filter_format = $config->get('process_targeting_output') ? $config->get('process_targeting_output') : '_none';
      $form['common']['frontend']['process_targeting_output'] = [
        '#type' => 'select',
        '#options' => $filter_format_options,
        '#title' => $this->t('Process targeting output'),
        '#description' => $this->t('Choose a filter format which processes the targeting information before being displayed. You should use a filter which at least safely filters any markup. If none is chosen, any HTML tag inside the information would be stripped out by default.'),
        '#default_value' => $default_filter_format,
        '#empty_value' => '_none',
        '#weight' => 20,
      ];
    }

    $behavior_reset = $config->get('behavior_on_context_reset');
    $form['common']['behavior_on_context_reset'] = [
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#title' => $this->t('Behavior when backend context has been reset'),
      '#weight' => 20,
    ];
    $form['common']['behavior_on_context_reset']['info'] = [
      '#prefix' => '<div class="description">',
      '#suffix' => '</div>',
      '#markup' => $this->t('Advertising context will be reset to the scope of an entity from the route and anytime an entity is being viewed which delivers its own context.'),
      '#weight' => 10,
    ];
    $form['common']['behavior_on_context_reset']['include_entity_info'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include elementary targeting information about the entity scope (type, label, uuid)'),
      '#parents' => ['behavior_on_context_reset', 'include_entity_info'],
      '#default_value' => isset($behavior_reset['include_entity_info']) ? (int) $behavior_reset['include_entity_info'] : 1,
      '#weight' => 20,
    ];
    $form['common']['behavior_on_context_reset']['collect_default_data'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enforce the collection of default Advertising context from the entity scope'),
      '#description' => $this->t('When enabled, backend context data will be collected from the context fields, which are enabled in the <b>default view mode</b> for the entity.'),
      '#parents' => ['behavior_on_context_reset', 'collect_default_data'],
      '#default_value' => isset($behavior_reset['collect_default_data']) ? (int) $behavior_reset['collect_default_data'] : 1,
      '#weight' => 30,
    ];

    $form['common']['site_wide_context'] = [
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#title' => $this->t('Site wide context'),
      '#attributes' => ['id' => 'edit-site-wide-context'],
      '#weight' => 30,
    ];
    $form['common']['site_wide_context']['info'] = [
      '#prefix' => '<div class="description">',
      '#suffix' => '</div>',
      '#markup' => $this->t('Advertising context, which has been defined here, will be included anywhere on the website.'),
      '#weight' => 10,
    ];
    $context_values = $form_state->getValue('site_wide_context', []);
    $site_wide_context = [];
    if (empty($context_values)) {
      // No form submission values given, use config as default values.
      $site_wide_context = $config->get('site_wide_context');
    }
    else {
      // Map form submission values to context data.
      foreach ($context_values as $i => $context_value) {
        $site_wide_context[] = [
          'plugin_id' => $context_value['context']['context_plugin_id'],
          'settings' => $context_value['context']['context_settings'],
          'apply_on' => $context_value['context']['apply_on'],
        ];
      }
    }
    $triggered_add_more = FALSE;
    if ($triggering_element = $form_state->getTriggeringElement()) {
      if (!empty($triggering_element['#name']) && $triggering_element['#name'] == 'add_context') {
        $triggered_add_more = TRUE;
      }
    }
    // Provide at least one empty field,
    // or add another item in case the user triggered so.
    if (empty($site_wide_context) || $triggered_add_more) {
      $site_wide_context[] = [
        'plugin_id' => '',
        'settings' => [],
        'apply_on' => [],
      ];
    }
    foreach ($site_wide_context as $i => $context_data) {
      $this->contextElementBuilder->clearValues()
        ->setContextPluginValue($context_data['plugin_id'])
        ->setContextApplyOnValue($context_data['apply_on'])
        ->setContextSettingsValue($context_data['plugin_id'], $context_data['settings']);
      $context_form_element = [
        '#parents' => ['site_wide_context', $i],
        '#weight' => ($i + 1) * 10,
      ];
      $context_form_element = $this->contextElementBuilder->buildElement($context_form_element, $form, $form_state);
      $form['common']['site_wide_context'][$i] = $context_form_element;
    }
    $form['common']['site_wide_context']['more'] = [
      '#type' => 'button',
      '#name' => 'add_context',
      '#value' => $this->t("Add another item"),
      '#weight' => (count($site_wide_context) + 1) * 10,
      '#ajax' => [
        'callback' => [$this, 'addContextElement'],
        'wrapper' => 'edit-site-wide-context',
        'effect' => 'fade',
        'method' => 'replace',
        'progress' => [
          'type' => 'throbber',
          'message' => '',
        ],
      ],
    ];

    $default_personalization = $config->get('personalization') ? $config->get('personalization') : [];
    $form['personalization'] = [
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#title' => $this->t('Personalization'),
      '#weight' => 20,
      '#tree' => TRUE,
    ];
    if ($module_info = _ad_entity_get_module_info()) {
      $ok = [];
      $warning = [];
      foreach ($module_info as $module => $info) {
        if (!empty($info['personalization']) && !empty($info['consent_aware'])) {
          $ok[] = $module;
        }
        else {
          $warning[] = $module;
        }
      }
      $consent_info = [];
      $consent_info[] = $this->t('At this time, this module feature does <strong>not support Accelerated Mobile Pages (AMP)</strong>.<br />You need to make sure on your own, that any ads on AMP pages are compliant with existing privacy protection laws.<br /> Read more about it at Google DFP documentation <a href="@url" target="_blank" rel="noopener nofollow">Ads personalization settings for AMP pages</a>.', ['@url' => 'https://support.google.com/dfp_premium/answer/7678538?hl=en']);
      if (!empty($ok)) {
        $consent_info[] = $this->t('Following modules support personalized ads and support consent awareness: <em>@ok</em>.', ['@ok' => implode(', ', $ok)]);
      }
      if (!empty($warning)) {
        $consent_info[] = $this->t('Following modules might use personalized ads, <strong>regardless whether personalization is disabled here</strong>, and it is not known whether or how they are consent aware: <em>@warning</em>.<br/><strong>You need to make sure on your own that these integrations are compliant with existing privacy protection laws.</strong>', ['@warning' => implode(', ', $warning)]);
      }

      if (!empty($consent_info)) {
        $form['personalization']['info'] = [
          '#theme' => 'item_list',
          '#items' => $consent_info,
          '#weight' => 10,
        ];
      }
    }
    $form['personalization']['enabled'] = [
      '#type' => 'radios',
      '#title' => $this->t('Personalized ads'),
      '#options' => [0 => $this->t("Disabled"), 1 => $this->t("Enabled")],
      '#description' => $this->t("When enabling personalized ads, make sure to setup your ads compliant to existing privacy protection laws."),
      '#default_value' => !empty($default_personalization['enabled']) ? (int) $default_personalization['enabled'] : 0,
      '#weight' => 20,
    ];
    $form['personalization']['consent_awareness'] = [
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#title' => $this->t('Consent awareness'),
      '#weight' => 30,
      '#states' => [
        'visible' => [
          'input[name="personalization[enabled]"]' => ['value' => 1],
        ],
      ],
    ];
    $form['personalization']['consent_awareness']['method'] = [
      '#type' => 'select',
      '#title' => $this->t('Method'),
      '#options' => $this->getConsentAwarenessMethods(),
      '#default_value' => !empty($default_personalization['consent_awareness']['method']) ? $default_personalization['consent_awareness']['method'] : 'opt_in',
      '#weight' => 10,
    ];
    $form['personalization']['consent_awareness']['cookie'] = [
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#title' => $this->t('Consent cookie'),
      '#description' => $this->t('These settings only define which cookie to check for. This module does not create or set the cookie. This is the job of other modules like the <a href="https://drupal.org/project/consent" target="_blank" rel="noopener nofollow">Consent</a> or <a href="https://drupal.org/project/eu_cookie_compliance" target="_blank" rel="noopener nofollow">EU Cookie compliance</a> module.'),
      '#weight' => 20,
      '#states' => [
        'visible' => [
          'select[name="personalization[consent_awareness][method]"]' => [['value' => 'opt_in'], ['value' => 'opt_out']],
        ],
      ],
    ];
    $form['personalization']['consent_awareness']['cookie']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of consent cookie'),
      '#default_value' => !empty($default_personalization['consent_awareness']['cookie']['name']) ? $default_personalization['consent_awareness']['cookie']['name'] : 'cookie-agreed',
      '#weight' => 10,
    ];
    $form['personalization']['consent_awareness']['cookie']['operator'] = [
      '#type' => 'select',
      '#title' => $this->t('Cookie value operator'),
      '#options' => $this->getCookieOperators(),
      '#default_value' => !empty($default_personalization['consent_awareness']['cookie']['operator']) ? $default_personalization['consent_awareness']['cookie']['operator'] : '==',
      '#weight' => 20,
    ];
    $default_consent_value = '';
    if (!empty($default_personalization['consent_awareness']['cookie']['value'])) {
      $default_consent_value = json_decode($default_personalization['consent_awareness']['cookie']['value'], TRUE);
      $default_consent_value = implode(',', $default_consent_value);
    }
    $form['personalization']['consent_awareness']['cookie']['value_accept'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value(s) of cookie when user accepted consent'),
      '#default_value' => $default_consent_value,
      '#description' => $this->t('Enter multiple values separated with comma. Example: <b>1,2</b>'),
      '#weight' => 30,
      '#states' => [
        'visible' => [
          'select[name="personalization[consent_awareness][method]"]' => ['value' => 'opt_in'],
          'select[name="personalization[consent_awareness][cookie][operator]"]' => ['!value' => 'e'],
        ],
      ],
    ];
    $form['personalization']['consent_awareness']['cookie']['value_decline'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value(s) of cookie when user declined consent'),
      '#default_value' => $default_consent_value,
      '#description' => $this->t('Enter multiple values separated with comma. Example: <b>1,2</b>'),
      '#weight' => 40,
      '#states' => [
        'visible' => [
          'select[name="personalization[consent_awareness][method]"]' => ['value' => 'opt_out'],
          'select[name="personalization[consent_awareness][cookie][operator]"]' => ['!value' => 'e'],
        ],
      ],
    ];

    $tweaks = $config->get('tweaks');
    $form['tweaks'] = [
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#title' => $this->t('System and performance tweaks'),
      '#attributes' => ['id' => 'edit-tweaks'],
      '#weight' => 30,
      '#tree' => TRUE,
    ];
    $form['tweaks']['force_backend_appliance'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force backend appliance mode'),
      '#description' => $this->t('When checked, context data will always be applied on server-side, ignoring any given formatter settings. This needs to be checked so that any context.js code would never be loaded into the client. Frontend appliance has been shown not to be efficient at any time, so this feature has been deprecated and will not be ported to version 2.x of this module.'),
      '#default_value' => (int) !empty($tweaks['force_backend_appliance']),
    ];
    $form['tweaks']['include_preload_tags'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable script preloading'),
      '#description' => $this->t('<a href=":url_pl" target="_blank" rel="noopener noreferrer nofollow">Script preloading</a> might improve client loading performance.', [':url_pl' => 'https://www.w3.org/TR/preload/']),
      '#default_value' => (int) !empty($tweaks['include_preload_tags']),
    ];
    $form['tweaks']['use_inline_js'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use inline JavaScript to initialize Advertising containers'),
      '#description' => $this->t('For modern browsers, using inline JavaScript can slightly improve performance during ad initialization.'),
      '#default_value' => (int) !empty($tweaks['use_inline_js']),
    ];

    $type_ids = array_keys($this->typeManager->getDefinitions());
    if (!empty($type_ids)) {
      $form['settings_tabs'] = [
        '#type' => 'vertical_tabs',
        '#default_tab' => 'edit-' . key($type_ids),
        '#weight' => 40,
      ];

      foreach ($type_ids as $type_id) {
        /** @var \Drupal\ad_entity\Plugin\AdTypeInterface $type */
        $type = $this->typeManager->createInstance($type_id);
        $label = $type->getPluginDefinition()['label'];
        $form[$type_id] = [
          '#type' => 'details',
          '#group' => 'settings_tabs',
          '#attributes' => ['id' => 'edit-' . $type_id],
          '#title' => $this->t("@type types", ['@type' => $label]),
          '#tree' => TRUE,
        ] + $type->globalSettingsForm($form, $form_state, $config);
      }
    }

    return $form;
  }

  /**
   * Submit handler to add another context form element.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form array part, usually inserted via AJAX.
   */
  public function addContextElement(array &$form, FormStateInterface $form_state) {
    return $form['common']['site_wide_context'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $config = $this->getConfig();

    $type_ids = array_keys($this->typeManager->getDefinitions());
    foreach ($type_ids as $type_id) {
      /** @var \Drupal\ad_entity\Plugin\AdTypeInterface $type */
      $type = $this->typeManager->createInstance($type_id);
      $type->globalSettingsValidate($form, $form_state, $config);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->getConfig();
    $config->set('enable_responsive_behavior', (bool) $form_state->getValue('enable_responsive_behavior'));
    if (!$this->moduleHandler->moduleExists('theme_breakpoints_js')) {
      $config->set('enable_responsive_behavior', FALSE);
    }

    $chosen_targeting_filter = $form_state->getValue('process_targeting_output');
    if (!$chosen_targeting_filter || ($chosen_targeting_filter === '_none')) {
      $chosen_targeting_filter = NULL;
    }
    else {
      $filter_formats = $this->moduleHandler->moduleExists('filter') ? filter_formats() : [];
      if (!isset($filter_formats[$chosen_targeting_filter])) {
        $chosen_targeting_filter = NULL;
      }
    }
    $config->set('process_targeting_output', $chosen_targeting_filter);

    $config->set('behavior_on_context_reset', $form_state->getValue('behavior_on_context_reset'));

    $context_values = $form_state->getValue('site_wide_context');
    $context_data = [];
    foreach ($context_values as $context_value) {
      $context_value = $this->contextElementBuilder->massageFormValues($context_value);
      if (!empty($context_value['context']['context_plugin_id'])) {
        $plugin_id = $context_value['context']['context_plugin_id'];
        $context_settings = $context_value['context']['context_settings'][$plugin_id];
        $context_data[] = [
          'plugin_id' => $plugin_id,
          'settings' => $context_settings,
          'apply_on' => $context_value['context']['apply_on'],
        ];
      }
    }
    $config->set('site_wide_context', $context_data);

    $personalization = [];
    $personalization_values = $form_state->getValue('personalization');
    $personalization['enabled'] = !empty($personalization_values['enabled']);
    if ($personalization['enabled']) {
      $consent_awareness_methods = array_keys($this->getConsentAwarenessMethods());
      $cookie_operators = array_keys($this->getCookieOperators());
      $personalization['consent_awareness'] = [
        'method' => !empty($personalization_values['consent_awareness']['method']) && in_array($personalization_values['consent_awareness']['method'], $consent_awareness_methods) ? $personalization_values['consent_awareness']['method'] : 'opt_in',
      ];
      $with_cookie_settings = ['opt_in', 'opt_out'];
      if (in_array($personalization['consent_awareness']['method'], $with_cookie_settings)) {
        if ('opt_in' === $personalization['consent_awareness']['method']) {
          $consent_value_input_name = 'value_accept';
        }
        else {
          $consent_value_input_name = 'value_decline';
        }
        $cookie_consent_value = '';
        if (!empty($personalization_values['consent_awareness']['cookie'][$consent_value_input_name])) {
          $user_cookie_value = $personalization_values['consent_awareness']['cookie'][$consent_value_input_name];
          if (strpos($user_cookie_value, ',') !== FALSE) {
            $cookie_values = explode(',', $user_cookie_value);
          }
          else {
            $cookie_values = [$user_cookie_value];
          }
          foreach ($cookie_values as &$cookie_value) {
            $cookie_value = trim($cookie_value);
          }
          $cookie_consent_value = json_encode($cookie_values, JSON_UNESCAPED_UNICODE);
        }
        $personalization['consent_awareness']['cookie'] = [
          'name' => !empty($personalization_values['consent_awareness']['cookie']['name']) ? $personalization_values['consent_awareness']['cookie']['name'] : 'cookie-agreed',
          'operator' => !empty($personalization_values['consent_awareness']['cookie']['operator']) && in_array($personalization_values['consent_awareness']['cookie']['operator'], $cookie_operators) ? $personalization_values['consent_awareness']['cookie']['operator'] : '==',
          'value' => $cookie_consent_value,
        ];
      }
    }

    $config->set('personalization', $personalization);

    $tweaks = [];
    $user_tweaks = $form_state->getValue('tweaks');
    $tweaks['force_backend_appliance'] = !empty($user_tweaks['force_backend_appliance']);
    $tweaks['include_preload_tags'] = !empty($user_tweaks['include_preload_tags']);
    $tweaks['use_inline_js'] = !empty($user_tweaks['use_inline_js']);
    $config->set('tweaks', $tweaks);

    $type_ids = array_keys($this->typeManager->getDefinitions());
    foreach ($type_ids as $type_id) {
      $values = $form_state->getValue($type_id, []);
      if (!empty($values)) {
        $config->set($type_id, $values);
      }

      /** @var \Drupal\ad_entity\Plugin\AdTypeInterface $type */
      $type = $this->typeManager->createInstance($type_id);
      $type->globalSettingsSubmit($form, $form_state, $config);
    }

    $config->save();
  }

  /**
   * Returns a list of allowed consent awareness methods.
   *
   * @return array
   *   The consent awareness methods.
   */
  protected function getConsentAwarenessMethods() {
    $methods = [
      'disabled' => $this->t('Disabled: Always use personalized ads.'),
      'opt_in' => $this->t('Opt-in: Only use personalized ads when consent exists.'),
      'opt_out' => $this->t('Opt-out: Use personalized ads, unless user declined.'),
    ];
    if ($this->moduleHandler->moduleExists('consent')) {
      $methods['oil'] = $this->t('Use personalized ads when opt-in cookie is set via OIL.js.');
    }
    if ($this->moduleHandler->moduleExists('eu_cookie_compliance')) {
      $methods['eu_cookie_compliance'] = $this->t('Adapt behavior defined by the EU Cookie Compliance module.');
    }
    return $methods;
  }

  /**
   * Returns a list of allowed cookie value operators.
   *
   * @return array
   *   The cookie value operators.
   */
  protected function getCookieOperators() {
    return [
      '==' => $this->t('Equals'),
      '>' => $this->t('Greater than'),
      '<' => $this->t('Less than'),
      'c' => $this->t('Contains'),
      'e' => $this->t('Exists'),
    ];
  }

}
