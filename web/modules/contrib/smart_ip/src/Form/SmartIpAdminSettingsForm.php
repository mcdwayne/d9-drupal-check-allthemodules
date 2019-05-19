<?php

/**
 * @file
 * Contains \Drupal\smart_ip\Form\SmartIpAdminSettingsForm.
 */

namespace Drupal\smart_ip\Form;

use Drupal\smart_ip\SmartIp;
use Drupal\smart_ip\SmartIpEvents;
use Drupal\user\Entity\Role;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Smart IP main admin settings page.
 *
 * @package Drupal\smart_ip\Form
 */
class SmartIpAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smart_ip_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    $configNames = ['smart_ip.settings'];
    /** @var \Drupal\smart_ip\AdminSettingsEvent $event */
    $event = \Drupal::service('smart_ip.admin_settings_event');
    // Allow Smart IP source module to add their config names.
    $event->setEditableConfigNames($configNames);
    \Drupal::service('event_dispatcher')->dispatch(SmartIpEvents::GET_CONFIG_NAME, $event);
    $configNames = $event->getEditableConfigNames();
    return $configNames;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config     = $this->config('smart_ip.settings');
    $dataSource = $config->get('data_source');
    $thisClass  = get_class($this);

    if (!empty($dataSource)) {
      $errorSourceId = \Drupal::state()->get('smart_ip.request_db_error_source_id') ?: '';
      if (!empty($errorSourceId)) {
        // Container for update status and manual update.
        $form['smart_ip_bin_database_update'] = [
          '#type'        => 'fieldset',
          '#title'       => $this->t('Database Update Status'),
          '#collapsible' => FALSE,
          '#collapsed'   => FALSE,
          '#states'      => [
            'visible' => [
              ':input[name="smart_ip_data_source"]' => ['value' => $errorSourceId],
            ],
          ],
        ];

        $message = \Drupal::state()->get('smart_ip.request_db_error_message') ?: '';
        if (!empty($message)) {
          $message = "<div class='messages messages--error'>$message</div>";
        }
        $form['smart_ip_bin_database_update']['smart_ip_bin_update_database'] = [
          '#type'   => 'submit',
          '#value'  => $this->t('Update database now'),
          '#submit' => [[$thisClass, 'manualUpdate']],
          '#prefix' => $message,
        ];
      }

      // Container for manual lookup.
      $form['smart_ip_manual_lookup'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Manual lookup'),
        '#collapsible' => FALSE,
        '#collapsed'   => FALSE,
      ];

      $form['smart_ip_manual_lookup']['smart_ip_lookup'] = [
        '#type'  => 'textfield',
        '#title' => $this->t('IP address'),
        '#description' => $this->t(
          'An IP address may be looked up by entering the address above then 
          pressing the %lookup button below.', ['%lookup' => $this->t('Lookup')]),
      ];

      $storage = $form_state->getStorage();
      $lookupResponse = isset($storage['smart_ip_message']) ? $storage['smart_ip_message'] : '';
      $form['smart_ip_manual_lookup']['smart_ip_lookup_button'] = [
        '#type'   => 'submit',
        '#value'  => $this->t('Lookup'),
        '#submit' => [[$thisClass, 'manualLookup']],
        '#ajax' => [
          'callback' => [$thisClass, 'manualLookupAjax'],
          'effect'   => 'fade',
        ],
        '#suffix' => '<div id="smart-ip-location-manual-lookup">' . $lookupResponse . '</div>',
      ];
    }

    // Container for Smart IP source.
    $form['smart_ip_data_source_selection'] = [
      '#type'        => 'fieldset',
      '#title'       => $this->t('Smart IP source'),
      '#collapsible' => FALSE,
      '#collapsed'   => FALSE,
    ];

    // Smart IP fallback data source status.
    $fallbackDataSource = [];
    if (isset($_SERVER['GEOIP_COUNTRY_NAME'])) {
      $modGeoipStat = $this->t('available');
      $fallbackDataSource[] = 0;
    }
    else {
      $modGeoipStat = $this->t('not available');
    }
    if (isset($_SERVER['HTTP_X_GEOIP_COUNTRY'])) {
      $xHeaderStat = $this->t('available');
      $fallbackDataSource[] = 1;
    }
    else {
      $xHeaderStat = $this->t('not available');
    }
    if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
      $cfHeaderStat = $this->t('available');
      $fallbackDataSource[] = 2;
    }
    else {
      $cfHeaderStat = $this->t('not available');
    }
    $form['smart_ip_data_source_selection']['smart_ip_fallback_data_source'] = [
      '#type'        => 'checkboxes',
      '#disabled'     => TRUE,
      '#title'       => $this->t('Smart IP fallback data source status'),
      '#description' => $this->t(
        "If your selected main Smart IP data source below failed to return 
        user's geolocation data, the available Smart IP fallback data source 
        will provide the user's geolocation as fallback."),
      '#default_value' => $fallbackDataSource,
      '#options' => [
        $this->t("MaxMind's Apache module @mod_geoip (@status)", [
          '@mod_geoip' => Link::fromTextAndUrl($this->t('mod_geoip'), Url::fromUri('http://dev.maxmind.com/geoip/legacy/mod_geoip2'))->toString(),
          '@status'    => $modGeoipStat,
        ]),
        $this->t("X-GeoIP-Country: XX header, set by e.g. nginx (@status)", [
          '@status' => $xHeaderStat,
        ]),
        $this->t(
          'Cloudflare IP Geolocation: your website must be using Cloudflare CDN 
          and "IP Geolocation" option must be enabled at your @settings (@status)', [
            '@settings' => Link::fromTextAndUrl($this->t('Cloudflare settings'), Url::fromUri('https://www.cloudflare.com/cloudflare-settings'))->toString(),
            '@status'   => $cfHeaderStat,
          ]),
      ],
    ];

    // Smart IP source selection.
    $form['smart_ip_data_source_selection']['smart_ip_data_source'] = [
      '#type'    => 'radios',
      '#title'   => $this->t('Select main Smart IP data source'),
      '#options' => [],
      '#default_value' => $dataSource,
    ];

    // Container for Smart IP preference.
    $form['smart_ip_preferences'] = [
      '#type'        => 'fieldset',
      '#title'       => $this->t('Smart IP settings'),
      '#collapsible' => FALSE,
      '#collapsed'   => FALSE,
    ];
    $roles = Role::loadMultiple();
    $userRoles = [];
    /** @var \Drupal\user\Entity\Role $role */
    foreach ($roles as $roleId => $role) {
      $userRoles[$roleId] = $role->get('label');
    }
    $form['smart_ip_preferences']['smart_ip_roles_to_geolocate'] = [
      '#type'          => 'checkboxes',
      '#title'         => $this->t('Roles to Geolocate'),
      '#default_value' => $config->get('roles_to_geolocate'),
      '#options'       => $userRoles,
      '#description'   => $this->t(
        'Select the roles you wish to geolocate. Note that selecting the 
        anonymous role will add substantial overhead.'),
    ];

    $euVisitorsDontSaveLabel = $this->t("Don't save location details of visitors from GDPR countries");
    $form['smart_ip_preferences']['smart_ip_save_user_location_creation'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t("Save user's location details upon creation"),
      '#default_value' => $config->get('save_user_location_creation'),
      '#description'   => $this->t(
        "One time storing of user's location details upon registration. 
        Note: If '@label' is enabled, it will not save visitors from EU countries 
        their location details.", [
          '@label' => $euVisitorsDontSaveLabel,
        ]
      ),
    ];

    $form['smart_ip_preferences']['smart_ip_eu_visitor_dont_save'] = [
      '#type'          => 'checkbox',
      '#title'         => $euVisitorsDontSaveLabel,
      '#default_value' => $config->get('eu_visitor_dont_save'),
      '#description'   => $this->t("If a visitor's country is an GDPR country, its location details will not be saved."),
    ];

    $geotimezoneExists = \Drupal::moduleHandler()->moduleExists('geotimezone');
    if (!$geotimezoneExists) {
      $tzFormatDesc = $this->t('Please install and enable @module.', [
        '@module' => Link::fromTextAndUrl($this->t('Geo Time Zone'), Url::fromUri('https://www.drupal.org/project/geotimezone'))->toString(),
      ]);
    }
    else {
      $tzFormatDesc = $this->t('Select the format of the time zone field.');
    }
    $form['smart_ip_preferences']['smart_ip_timezone_format'] = [
      '#type'        => 'select',
      '#disabled'    => !$geotimezoneExists,
      '#title'       => $this->t('Time zone format'),
      '#description' => $tzFormatDesc,
      '#default_value' => $config->get('timezone_format'),
      '#options' => [
        'identifier' => $this->t('Identifier (E.g Asia/Manila)'),
        'offset'     => $this->t('UTC/GMT Offset (E.g +08:00)'),
      ],
    ];

    $form['smart_ip_preferences']['smart_ip_allowed_pages'] = [
      '#title'       => $this->t("Acquire/update user's geolocation on specific Drupal native pages"),
      '#type'        => 'textarea',
      '#rows'        => 5,
      '#description' => $this->t(
        "Specify pages by using their paths. Enter one path per line. The '*' 
        character is a wildcard. Example paths are %user for the current user's 
        page and %user-wildcard for every user page. %front is the front page. 
        Leave blank if all pages.", [
          '%user' => '/user',
          '%user-wildcard' => '/user/*',
          '%front' => '<front>',
        ]
      ),
      '#default_value' => $config->get('allowed_pages'),
    ];

    // Container for Smart IP debug tool.
    $form['smart_ip_debug_tool'] = [
      '#type'        => 'fieldset',
      '#title'       => $this->t('Smart IP debug tool'),
      '#description' => $this->t(
        'Note: Make sure that the debug role is also enabled in "Roles to 
        Geolocate" under "Smart IP settings" above. If a user has multiple 
        roles, the precedence of what debug IP address of a role will be used is 
        determined in alphabetical order but the "authenticated role will always 
        be the last priority. Eg. if a user has "authenticated", "editor" and 
        "moderator" roles, (assuming all debug roles are enabled) the debug IP 
        address that will be used is the "editor" role.'),
      '#collapsible' => FALSE,
      '#collapsed'   => FALSE,
      '#weight'      => 1,
    ];
    $rolesDebug   = $config->get('roles_in_debug_mode');
    $rolesDebugIp = $config->get('roles_in_debug_mode_ip');
    /** @var \Drupal\user\Entity\Role $role */
    foreach ($roles as $roleId => $role) {
      $form['smart_ip_debug_tool']["smart_ip_debug_$roleId"] = [
        '#type'  => 'checkbox',
        '#title' => $this->t('@role role in debug mode', [
          '@role' => $role->get('label'),
        ]),
        '#default_value' => (isset($rolesDebug[$roleId]) && $rolesDebug[$roleId]) ? TRUE : FALSE,
        '#description' => $this->t('Enables @role role to spoof an IP Address for debugging purposes.', [
          '@role' => $role->get('label'),
        ]),
      ];

      $form['smart_ip_debug_tool']["smart_ip_test_ip_address_$roleId"] = [
        '#type'  => 'textfield',
        '#title' => $this->t('IP address to use for @role role testing', [
          '@role' => $role->get('label'),
        ]),
        '#default_value' => isset($rolesDebugIp[$roleId]) ? $rolesDebugIp[$roleId] : NULL,
      ];
    }

    /** @var \Drupal\smart_ip\AdminSettingsEvent $event */
    $event = \Drupal::service('smart_ip.admin_settings_event');
    // Allow Smart IP source module to add their form elements.
    $event->setForm($form);
    $event->setFormState($form_state);
    \Drupal::service('event_dispatcher')->dispatch(SmartIpEvents::DISPLAY_SETTINGS, $event);
    $form = $event->getForm();
    $form_state = $event->getFormState();

    if (empty($form['smart_ip_data_source_selection']['smart_ip_data_source']['#options'])) {
      // No Smart IP data source module enabled.
      $form['smart_ip_data_source_selection']['smart_ip_data_source'] = [
        '#markup' => $this->t(
          'You do not have any Smart IP data source module enabled. Please 
          enable at least one @here.', [
            '@here' => Link::fromTextAndUrl($this->t('here'), Url::fromRoute('system.modules_list', [], ['fragment' => 'edit-modules-smart-ip-data-source']))->toString(),
          ]
        ),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Triggers manual database update event to Smart IP data source module
   * listeners.
   */
  public static function manualUpdate() {
    /** @var \Drupal\smart_ip\DatabaseFileEvent $event */
    $event = \Drupal::service('smart_ip.database_file_event');
    // Allow Smart IP source module to act on manual database update.
    \Drupal::service('event_dispatcher')->dispatch(SmartIpEvents::MANUAL_UPDATE, $event);
  }

  /**
   * Submit handler to lookup an IP address in the database.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @see \Drupal\smart_ip\Form\SmartIpAdminSettingsForm::manualLookupAjax
   */
  public static function manualLookup(array $form, FormStateInterface $form_state) {
    $ip = $form_state->getValue('smart_ip_lookup');
    $location = SmartIp::query($ip);
    if (isset($location['countryCode'])) {
      $isEuCountry   = $location['isEuCountry'] ? t('Yes') : t('No');
      $isGdprCountry = $location['isGdprCountry'] ? t('Yes') : t('No');
      $message = '<p>' . t('IP Address @ip is assigned to the following location details:', ['@ip' => $ip]) . '</p>' .
        '<dl>' .
          '<dt>' . t('Country:') . '</dt>' .
          '<dd>' . t('%country', ['%country' => $location['country']]) . '</dd>' .
          '<dt>' . t('Country code:') . '</dt>' .
          '<dd>' . t('%country_code', ['%country_code' => $location['countryCode']]) . '</dd>' .
          '<dt>' . t('Region:') . '</dt>' .
          '<dd>' . t('%region', ['%region' => $location['region']]) . '</dd>' .
          '<dt>' . t('Region code:') . '</dt>' .
          '<dd>' . t('%region_code', ['%region_code' => $location['regionCode']]) . '</dd>' .
          '<dt>' . t('City:') . '</dt>' .
          '<dd>' . t('%city', ['%city' => $location['city']]) . '</dd>' .
          '<dt>' . t('Postal code:') . '</dt>' .
          '<dd>' . t('%zip', ['%zip' => $location['zip']]) . '</dd>' .
          '<dt>' . t('Latitude:') . '</dt>' .
          '<dd>' . t('%latitude', ['%latitude' => $location['latitude']]) . '</dd>' .
          '<dt>' . t('Longitude:') . '</dt>' .
          '<dd>' . t('%longitude', ['%longitude' => $location['longitude']]) . '</dd>' .
          '<dt>' . t('Is EU member country:') . '</dt>' .
          '<dd>' . $isEuCountry . '</dd>' .
          '<dt>' . t('Is GDPR country:') . '</dt>' .
          '<dd>' . $isGdprCountry . '</dd>' .
          '<dt>' . t('Time zone:') . '</dt>' .
          '<dd>' . t('%time_zone', ['%time_zone' => $location['timeZone']]) . '</dd>' .
        '</dl>';
    }
    else {
      $message = t('IP Address @ip is not assigned to any location.', ['@ip' => $ip]);
    }
    $storage['smart_ip_message'] = $message;
    $form_state->setStorage($storage);
    $form_state->setRebuild();
  }

  /**
   * Submit handler to lookup an IP address in the database.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response representing the form and its AJAX commands.
   * @see \Drupal\smart_ip\Form\SmartIpAdminSettingsForm::manualLookup
   */
  public static function manualLookupAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $storage  = $form_state->getStorage();
    $value    = isset($storage['smart_ip_message']) ? $storage['smart_ip_message'] : '';
    $response->addCommand(new HtmlCommand('#smart-ip-location-manual-lookup', $value));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $roles = Role::loadMultiple();
    /** @var \Drupal\user\Entity\Role $role */
    foreach ($roles as $roleId => $role) {
      if ($form_state->getValue("smart_ip_debug_$roleId") == TRUE && $form_state->isValueEmpty("smart_ip_test_ip_address_$roleId")) {
        $form_state->setErrorByName("smart_ip_test_ip_address_$roleId", $this->t('Please enter the IP address to use for @role testing.', [
          '@role' => $role->get('label'),
        ]));
      }
    }
    if (!empty($form['smart_ip_data_source_selection']['smart_ip_data_source']['#options']) && empty($form_state->getValue('smart_ip_data_source'))) {
      $form_state->setErrorByName('smart_ip_data_source', $this->t('Please select a Smart IP data source.'));
    }
    /** @var \Drupal\smart_ip\AdminSettingsEvent $event */
    $event = \Drupal::service('smart_ip.admin_settings_event');
    // Allow Smart IP source module to add validation on their form elements.
    $event->setForm($form);
    $event->setFormState($form_state);
    \Drupal::service('event_dispatcher')->dispatch(SmartIpEvents::VALIDATE_SETTINGS, $event);
    $form = $event->getForm();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $rolesDebug   = [];
    $rolesDebugIp = [];
    $roles        = Role::loadMultiple();
    /** @var \Drupal\user\Entity\Role $role */
    foreach ($roles as $roleId => $role) {
      $rolesDebug[$roleId]   = $form_state->getValue("smart_ip_debug_$roleId") ? $roleId : FALSE;
      $rolesDebugIp[$roleId] = $form_state->getValue("smart_ip_test_ip_address_$roleId");
    }
    $this->config('smart_ip.settings')
      ->set('data_source', $form_state->getValue('smart_ip_data_source'))
      ->set('roles_to_geolocate', $form_state->getValue('smart_ip_roles_to_geolocate'))
      ->set('save_user_location_creation', $form_state->getValue('smart_ip_save_user_location_creation'))
      ->set('eu_visitor_dont_save', $form_state->getValue('smart_ip_eu_visitor_dont_save'))
      ->set('timezone_format', $form_state->getValue('smart_ip_timezone_format'))
      ->set('roles_in_debug_mode', $rolesDebug)
      ->set('roles_in_debug_mode_ip', $rolesDebugIp)
      ->set('allowed_pages', $form_state->getValue('smart_ip_allowed_pages'))
      ->save();
    /** @var \Drupal\smart_ip\AdminSettingsEvent $event */
    $event = \Drupal::service('smart_ip.admin_settings_event');
    // Allow Smart IP source module to add submission on their form elements.
    $event->setForm($form);
    $event->setFormState($form_state);
    \Drupal::service('event_dispatcher')->dispatch(SmartIpEvents::SUBMIT_SETTINGS, $event);
    $form = $event->getForm();
  }

}
