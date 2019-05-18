<?php

namespace Drupal\piwik\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Configure Piwik settings for this site.
 */
class PiwikAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'piwik_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['piwik.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('piwik.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];

    $form['general']['piwik_site_id'] = [
      '#default_value' => $config->get('site_id'),
      '#description' => $this->t('The user account number is unique to the websites domain. Click the <strong>Settings</strong> link in your Piwik account, then the <strong>Websites</strong> tab and enter the appropriate site <strong>ID</strong> into this field.'),
      '#maxlength' => 20,
      '#required' => TRUE,
      '#size' => 15,
      '#title' => $this->t('Piwik site ID'),
      '#type' => 'textfield',
    ];
    $form['general']['piwik_url_http'] = [
      '#default_value' => $config->get('url_http'),
      '#description' => $this->t('The URL to your Piwik base directory. Example: "http://www.example.com/piwik/".'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#size' => 80,
      '#title' => $this->t('Piwik HTTP URL'),
      '#type' => 'textfield',
    ];
    $form['general']['piwik_url_https'] = [
      '#default_value' => $config->get('url_https'),
      '#description' => $this->t('The URL to your Piwik base directory with SSL certificate installed. Required if you track a SSL enabled website. Example: "https://www.example.com/piwik/".'),
      '#maxlength' => 255,
      '#size' => 80,
      '#title' => $this->t('Piwik HTTPS URL'),
      '#type' => 'textfield',
    ];
    // Required for automated form save testing only.
    $form['general']['piwik_url_skiperror'] = [
      '#type' => 'hidden',
      '#default_value' => FALSE,
    ];

    // Visibility settings.
    $form['tracking_scope'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Tracking scope'),
      '#attached' => [
        'library' => [
          'piwik/piwik.admin',
        ],
      ],
    ];

    $form['tracking']['domain_tracking'] = [
      '#type' => 'details',
      '#title' => $this->t('Domains'),
      '#group' => 'tracking_scope',
    ];

    global $cookie_domain;
    $multiple_sub_domains = [];
    foreach (['www', 'app', 'shop'] as $subdomain) {
      if (count(explode('.', $cookie_domain)) > 2 && !is_numeric(str_replace('.', '', $cookie_domain))) {
        $multiple_sub_domains[] = $subdomain . $cookie_domain;
      }
      // IP addresses or localhost.
      else {
        $multiple_sub_domains[] = $subdomain . '.example.com';
      }
    }

    $form['tracking']['domain_tracking']['piwik_domain_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('What are you tracking?'),
      '#options' => [
        0 => $this->t('A single domain (default)'),
        1 => $this->t('One domain with multiple subdomains'),
      ],
      0 => [
        '#description' => $this->t('Domain: @domain', ['@domain' => $_SERVER['HTTP_HOST']]),
      ],
      1 => [
        '#description' => $this->t('Examples: @domains', ['@domains' => implode(', ', $multiple_sub_domains)]),
      ],
      '#default_value' => $config->get('domain_mode'),
    ];

    // Page specific visibility configurations.
    $account = \Drupal::currentUser();
    $php_access = $account->hasPermission('use PHP for piwik tracking visibility');
    $visibility_request_path_pages = $config->get('visibility.request_path_pages');

    $form['tracking']['page_visibility_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Pages'),
      '#group' => 'tracking_scope',
    ];

    if ($config->get('visibility.request_path_mode') == 2 && !$php_access) {
      // No permission to change PHP snippets, but keep existing settings.
      $form['tracking']['page_visibility_settings'] = [];
      $form['tracking']['page_visibility_settings']['piwik_visibility_request_path_mode'] = ['#type' => 'value', '#value' => 2];
      $form['tracking']['page_visibility_settings']['piwik_visibility_request_path_pages'] = ['#type' => 'value', '#value' => $visibility_request_path_pages];
    }
    else {
      $options = [
        t('Every page except the listed pages'),
        t('The listed pages only'),
      ];
      $description = t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", ['%blog' => '/blog', '%blog-wildcard' => '/blog/*', '%front' => '<front>']);

      if (\Drupal::moduleHandler()->moduleExists('php') && $php_access) {
        $options[] = t('Pages on which this PHP code returns <code>TRUE</code> (experts only)');
        $title = t('Pages or PHP code');
        $description .= ' ' . t('If the PHP option is chosen, enter PHP code between %php. Note that executing incorrect PHP code can break your Drupal site.', ['%php' => '<?php ?>']);
      }
      else {
        $title = t('Pages');
      }
      $form['tracking']['page_visibility_settings']['piwik_visibility_request_path_mode'] = [
        '#type' => 'radios',
        '#title' => $this->t('Add tracking to specific pages'),
        '#options' => $options,
        '#default_value' => $config->get('visibility.request_path_mode'),
      ];
      $form['tracking']['page_visibility_settings']['piwik_visibility_request_path_pages'] = [
        '#type' => 'textarea',
        '#title' => $title,
        '#title_display' => 'invisible',
        '#default_value' => !empty($visibility_request_path_pages) ? $visibility_request_path_pages : '',
        '#description' => $description,
        '#rows' => 10,
      ];
    }

    // Render the role overview.
    $visibility_user_role_roles = $config->get('visibility.user_role_roles');

    $form['tracking']['role_visibility_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Roles'),
      '#group' => 'tracking_scope',
    ];

    $form['tracking']['role_visibility_settings']['piwik_visibility_user_role_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Add tracking for specific roles'),
      '#options' => [
        t('Add to the selected roles only'),
        t('Add to every role except the selected ones'),
      ],
      '#default_value' => $config->get('visibility.user_role_mode'),
    ];
    $form['tracking']['role_visibility_settings']['piwik_visibility_user_role_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#default_value' => !empty($visibility_user_role_roles) ? $visibility_user_role_roles : [],
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', user_role_names()),
      '#description' => $this->t('If none of the roles are selected, all users will be tracked. If a user has any of the roles checked, that user will be tracked (or excluded, depending on the setting above).'),
    ];

    // Standard tracking configurations.
    $visibility_user_account_mode = $config->get('visibility.user_account_mode');

    $form['tracking']['user_visibility_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Users'),
      '#group' => 'tracking_scope',
    ];
    $t_permission = ['%permission' => $this->t('opt-in or out of tracking')];
    $form['tracking']['user_visibility_settings']['piwik_visibility_user_account_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow users to customize tracking on their account page'),
      '#options' => [
        t('No customization allowed'),
        t('Tracking on by default, users with %permission permission can opt out', $t_permission),
        t('Tracking off by default, users with %permission permission can opt in', $t_permission),
      ],
      '#default_value' => !empty($visibility_user_account_mode) ? $visibility_user_account_mode : 0,
    ];
    $form['tracking']['user_visibility_settings']['piwik_trackuserid'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Track User ID'),
      '#default_value' => $config->get('track.userid'),
      '#description' => $this->t('User ID enables the analysis of groups of sessions, across devices, using a unique, persistent, and non-personally identifiable ID string representing a user. <a href=":url">Learn more about the benefits of using User ID</a>.', [':url' => 'http://piwik.org/docs/user-id/']),
    ];

    // Link specific configurations.
    $form['tracking']['linktracking'] = [
      '#type' => 'details',
      '#title' => $this->t('Links and downloads'),
      '#group' => 'tracking_scope',
    ];
    $form['tracking']['linktracking']['piwik_trackmailto'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Track clicks on mailto links'),
      '#default_value' => $config->get('track.mailto'),
    ];
    $form['tracking']['linktracking']['piwik_trackfiles'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Track clicks on outbound links and downloads (clicks on file links) for the following extensions'),
      '#default_value' => $config->get('track.files'),
    ];
    $form['tracking']['linktracking']['piwik_trackfiles_extensions'] = [
      '#title' => $this->t('List of download file extensions'),
      '#title_display' => 'invisible',
      '#type' => 'textfield',
      '#default_value' => $config->get('track.files_extensions'),
      '#description' => $this->t('A file extension list separated by the | character that will be tracked as download when clicked. Regular expressions are supported. For example: @extensions', ['@extensions' => PIWIK_TRACKFILES_EXTENSIONS]),
      '#maxlength' => 500,
      '#states' => [
        'enabled' => [
          ':input[name="piwik_trackfiles"]' => ['checked' => TRUE],
        ],
        // Note: Form required marker is not visible as title is invisible.
        'required' => [
          ':input[name="piwik_trackfiles"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $colorbox_dependencies = '<div class="admin-requirements">';
    $colorbox_dependencies .= t('Requires: @module-list', ['@module-list' => (\Drupal::moduleHandler()->moduleExists('colorbox') ? t('@module (<span class="admin-enabled">enabled</span>)', ['@module' => 'Colorbox']) : t('@module (<span class="admin-missing">disabled</span>)', ['@module' => 'Colorbox']))]);
    $colorbox_dependencies .= '</div>';

    $form['tracking']['linktracking']['piwik_trackcolorbox'] = [
      '#type' => 'checkbox',
      '#title' => t('Track content in colorbox modal dialogs'),
      '#description' => t('Enable to track the content shown in colorbox modal windows.') . $colorbox_dependencies,
      '#default_value' => $config->get('track.colorbox'),
      '#disabled' => (\Drupal::moduleHandler()->moduleExists('colorbox') ? FALSE : TRUE),
    ];

    // Message specific configurations.
    $form['tracking']['messagetracking'] = [
      '#type' => 'details',
      '#title' => $this->t('Messages'),
      '#group' => 'tracking_scope',
    ];
    $track_messages = $config->get('track.messages');
    $form['tracking']['messagetracking']['piwik_trackmessages'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Track messages of type'),
      '#default_value' => !empty($track_messages) ? $track_messages : [],
      '#description' => $this->t('This will track the selected message types shown to users. Tracking of form validation errors may help you identifying usability issues in your site. Every message is tracked as one individual event. Messages from excluded pages cannot be tracked.'),
      '#options' => [
        'status' => $this->t('Status message'),
        'warning' => $this->t('Warning message'),
        'error' => $this->t('Error message'),
      ],
    ];

    $form['tracking']['search'] = [
      '#type' => 'details',
      '#title' => $this->t('Search'),
      '#group' => 'tracking_scope',
    ];

    $site_search_dependencies = '<div class="admin-requirements">';
    $site_search_dependencies .= t('Requires: @module-list', ['@module-list' => (\Drupal::moduleHandler()->moduleExists('search') ? t('@module (<span class="admin-enabled">enabled</span>)', ['@module' => 'Search']) : t('@module (<span class="admin-missing">disabled</span>)', ['@module' => 'Search']))]);
    $site_search_dependencies .= '</div>';

    $form['tracking']['search']['piwik_site_search'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Track internal search'),
      '#description' => $this->t('If checked, internal search keywords are tracked.') . $site_search_dependencies,
      '#default_value' => $config->get('track.site_search'),
      '#disabled' => (\Drupal::moduleHandler()->moduleExists('search') ? FALSE : TRUE),
    ];

    // Privacy specific configurations.
    $form['tracking']['privacy'] = [
      '#type' => 'details',
      '#title' => $this->t('Privacy'),
      '#group' => 'tracking_scope',
    ];
    $form['tracking']['privacy']['piwik_privacy_donottrack'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Universal web tracking opt-out'),
      '#description' => $this->t('If enabled and your Piwik server receives the <a href="http://donottrack.us/">Do-Not-Track</a> header from the client browser, the Piwik server will not track the user. Compliance with Do Not Track could be purely voluntary, enforced by industry self-regulation, or mandated by state or federal law. Please accept your visitors privacy. If they have opt-out from tracking and advertising, you should accept their personal decision.'),
      '#default_value' => $config->get('privacy.donottrack'),
    ];

    // Piwik page title tree view settings.
    $form['page_title_hierarchy'] = [
      '#type' => 'details',
      '#title' => $this->t('Page titles hierarchy'),
      '#description' => $this->t('This functionality enables a dynamically expandable tree view of your site page titles in your Piwik statistics. See in Piwik statistics under <em>Actions</em> > <em>Page titles</em>.'),
      '#group' => 'page_title_hierarchy',
    ];
    $form['page_title_hierarchy']['piwik_page_title_hierarchy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Show page titles as hierarchy like breadcrumbs"),
      '#description' => $this->t('By default Piwik tracks the current page title and shows you a flat list of the most popular titles. This enables a breadcrumbs like tree view.'),
      '#default_value' => $config->get('page_title_hierarchy'),
    ];
    $form['page_title_hierarchy']['piwik_page_title_hierarchy_exclude_home'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide home page from hierarchy'),
      '#description' => $this->t('If enabled, the "Home" item will be removed from the hierarchy to flatten the structure in the Piwik statistics. Hits to the home page will still be counted, but for other pages the hierarchy will start at level Home+1.'),
      '#default_value' => $config->get('page_title_hierarchy_exclude_home'),
    ];

    // Custom variables.
    $form['piwik_custom_var'] = [
      '#description' => $this->t('You can add Piwiks <a href=":custom_var_documentation">Custom Variables</a> here. These will be added to every page that Piwik tracking code appears on. Custom variable names and values are limited to 200 characters in length. Keep the names and values as short as possible and expect long values to get trimmed. You may use tokens in custom variable names and values. Global and user tokens are always available; on node pages, node tokens are also available.', [':custom_var_documentation' => 'http://piwik.org/docs/custom-variables/']),
      '#title' => $this->t('Custom variables'),
      '#tree' => TRUE,
      '#type' => 'details',
    ];

    $form['piwik_custom_var']['slots'] = [
      '#type' => 'table',
      '#header' => [
        ['data' => $this->t('Slot')],
        ['data' => $this->t('Name')],
        ['data' => $this->t('Value')],
        ['data' => $this->t('Scope')],
      ],
    ];

    $piwik_custom_vars = $config->get('custom.variable');

    // Piwik supports up to 5 custom variables.
    for ($i = 1; $i < 6; $i++) {
      $form['piwik_custom_var']['slots'][$i]['slot'] = [
        '#default_value' => $i,
        '#description' => $this->t('Slot number'),
        '#disabled' => TRUE,
        '#size' => 1,
        '#title' => $this->t('Custom variable slot #@slot', ['@slot' => $i]),
        '#title_display' => 'invisible',
        '#type' => 'textfield',
      ];
      $form['piwik_custom_var']['slots'][$i]['name'] = [
        '#default_value' => isset($piwik_custom_vars[$i]['name']) ? $piwik_custom_vars[$i]['name'] : '',
        '#description' => $this->t('The custom variable name.'),
        '#maxlength' => 100,
        '#size' => 20,
        '#title' => $this->t('Custom variable name #@slot', ['@slot' => $i]),
        '#title_display' => 'invisible',
        '#type' => 'textfield',
      ];
      $form['piwik_custom_var']['slots'][$i]['value'] = [
        '#default_value' => isset($piwik_custom_vars[$i]['value']) ? $piwik_custom_vars[$i]['value'] : '',
        '#description' => $this->t('The custom variable value.'),
        '#maxlength' => 255,
        '#title' => $this->t('Custom variable value #@slot', ['@slot' => $i]),
        '#title_display' => 'invisible',
        '#type' => 'textfield',
        '#element_validate' => [[get_class($this), 'tokenElementValidate']],
        '#token_types' => ['node'],
      ];
      if (\Drupal::moduleHandler()->moduleExists('token')) {
        $form['piwik_custom_var']['slots'][$i]['value']['#element_validate'][] = 'token_element_validate';
      }
      $form['piwik_custom_var']['slots'][$i]['scope'] = [
        '#default_value' => isset($piwik_custom_vars[$i]['scope']) ? $piwik_custom_vars[$i]['scope'] : '',
        '#description' => $this->t('The scope for the custom variable.'),
        '#title' => $this->t('Custom variable slot #@slot', ['@slot' => $i]),
        '#title_display' => 'invisible',
        '#type' => 'select',
        '#options' => [
          'visit' => $this->t('Visit'),
          'page' => $this->t('Page'),
        ],
      ];
    }

    $form['piwik_custom_var']['piwik_custom_var_description'] = [
      '#type' => 'item',
      '#description' => $this->t("You can supplement Piwiks' basic IP address tracking of visitors by segmenting users based on custom variables. Make sure you will not associate (or permit any third party to associate) any data gathered from your websites (or such third parties' websites) with any personally identifying information from any source as part of your use (or such third parties' use) of the Piwik' service."),
    ];
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['piwik_custom_var']['piwik_custom_var_token_tree'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['node'],
      ];
    }

    // Advanced feature configurations.
    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => FALSE,
    ];

    $form['advanced']['piwik_cache'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Locally cache tracking code file'),
      '#description' => $this->t('If checked, the tracking code file is retrieved from your Piwik site and cached locally. It is updated daily to ensure updates to tracking code are reflected in the local copy.'),
      '#default_value' => $config->get('cache'),
    ];

    // Allow for tracking of the originating node when viewing translation sets.
    if (\Drupal::moduleHandler()->moduleExists('content_translation')) {
      $form['advanced']['piwik_translation_set'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Track translation sets as one unit'),
        '#description' => $this->t('When a node is part of a translation set, record statistics for the originating node instead. This allows for a translation set to be treated as a single unit.'),
        '#default_value' => $config->get('translation_set'),
      ];
    }

    $user_access_add_js_snippets = !$this->currentUser()->hasPermission('add JS snippets for piwik');
    $user_access_add_js_snippets_permission_warning = $user_access_add_js_snippets ? ' <em>' . $this->t('This field has been disabled because you do not have sufficient permissions to edit it.') . '</em>' : '';
    $form['advanced']['codesnippet'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom JavaScript code'),
      '#open' => TRUE,
      '#description' => $this->t('You can add custom Piwik <a href=":snippets">code snippets</a> here. These will be added to every page that Piwik appears on. <strong>Do not include the &lt;script&gt; tags</strong>, and always end your code with a semicolon (;).', [':snippets' => 'http://piwik.org/docs/javascript-tracking/']),
    ];
    $form['advanced']['codesnippet']['piwik_codesnippet_before'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Code snippet (before)'),
      '#default_value' => $config->get('codesnippet.before'),
      '#disabled' => $user_access_add_js_snippets,
      '#rows' => 5,
      '#description' => $this->t('Code in this textarea will be added <strong>before</strong> _paq.push(["trackPageView"]).') . $user_access_add_js_snippets_permission_warning,
    ];
    $form['advanced']['codesnippet']['piwik_codesnippet_after'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Code snippet (after)'),
      '#default_value' => $config->get('codesnippet.after'),
      '#disabled' => $user_access_add_js_snippets,
      '#rows' => 5,
      '#description' => $this->t('Code in this textarea will be added <strong>after</strong> _paq.push(["trackPageView"]). This is useful if you\'d like to track a site in two accounts.') . $user_access_add_js_snippets_permission_warning,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Custom variables validation.
    foreach ($form_state->getValue(['piwik_custom_var', 'slots']) as $custom_var) {
      $form_state->setValue(['piwik_custom_var', 'slots', $custom_var['slot'], 'name'], trim($custom_var['name']));
      $form_state->setValue(['piwik_custom_var', 'slots', $custom_var['slot'], 'value'], trim($custom_var['value']));

      // Validate empty names/values.
      if (empty($custom_var['name']) && !empty($custom_var['value'])) {
        $form_state->setErrorByName("piwik_custom_var][slots][" . $custom_var['slot'] . "][name", t('The custom variable @slot-number requires a <em>Name</em> if a <em>Value</em> has been provided.', ['@slot-number' => $custom_var['slot']]));
      }
      elseif (!empty($custom_var['name']) && empty($custom_var['value'])) {
        $form_state->setErrorByName("piwik_custom_var][slots][" . $custom_var['slot'] . "][value", t('The custom variable @slot-number requires a <em>Value</em> if a <em>Name</em> has been provided.', ['@slot-number' => $custom_var['slot']]));
      }
    }
    $form_state->setValue('piwik_custom_var', $form_state->getValue(['piwik_custom_var', 'slots']));

    // Trim some text area values.
    $form_state->setValue('piwik_site_id', trim($form_state->getValue('piwik_site_id')));
    $form_state->setValue('piwik_visibility_request_path_pages', trim($form_state->getValue('piwik_visibility_request_path_pages')));
    $form_state->setValue('piwik_codesnippet_before', trim($form_state->getValue('piwik_codesnippet_before')));
    $form_state->setValue('piwik_codesnippet_after', trim($form_state->getValue('piwik_codesnippet_after')));
    $form_state->setValue('piwik_visibility_user_role_roles', array_filter($form_state->getValue('piwik_visibility_user_role_roles')));
    $form_state->setValue('piwik_trackmessages', array_filter($form_state->getValue('piwik_trackmessages')));

    if (!preg_match('/^\d{1,}$/', $form_state->getValue('piwik_site_id'))) {
      $form_state->setErrorByName('piwik_site_id', t('A valid Piwik site ID is an integer only.'));
    }

    $url = $form_state->getValue('piwik_url_http') . 'piwik.php';
    try {
      $result = \Drupal::httpClient()->get($url);
      if ($result->getStatusCode() != 200 && $form_state->getValue('piwik_url_skiperror') == FALSE) {
        $form_state->setErrorByName('piwik_url_http', t('The validation of "@url" failed with error "@error" (HTTP code @code).', [
          '@url' => UrlHelper::filterBadProtocol($url),
          '@error' => $result->getReasonPhrase(),
          '@code' => $result->getStatusCode(),
        ]));
      }
    }
    catch (RequestException $exception) {
      $form_state->setErrorByName('piwik_url_http', t('The validation of "@url" failed with an exception "@error" (HTTP code @code).', [
        '@url' => UrlHelper::filterBadProtocol($url),
        '@error' => $exception->getMessage(),
        '@code' => $exception->getCode(),
      ]));
    }

    $piwik_url_https = $form_state->getValue('piwik_url_https');
    if (!empty($piwik_url_https)) {
      $url = $piwik_url_https . 'piwik.php';
      try {
        $result = \Drupal::httpClient()->get($url);
        if ($result->getStatusCode() != 200 && $form_state->getValue('piwik_url_skiperror') == FALSE) {
          $form_state->setErrorByName('piwik_url_https', t('The validation of "@url" failed with error "@error" (HTTP code @code).', [
            '@url' => UrlHelper::filterBadProtocol($url),
            '@error' => $result->getReasonPhrase(),
            '@code' => $result->getStatusCode(),
          ]));
        }
      }
      catch (RequestException $exception) {
        $form_state->setErrorByName('piwik_url_https', t('The validation of "@url" failed with an exception "@error" (HTTP code @code).', [
          '@url' => UrlHelper::filterBadProtocol($url),
          '@error' => $exception->getMessage(),
          '@code' => $exception->getCode(),
        ]));
      }
    }

    // Verify that every path is prefixed with a slash, but don't check PHP
    // code snippets.
    if ($form_state->getValue('piwik_visibility_request_path_mode') != 2) {
      $pages = preg_split('/(\r\n?|\n)/', $form_state->getValue('piwik_visibility_request_path_pages'));
      foreach ($pages as $page) {
        if (strpos($page, '/') !== 0 && $page !== '<front>') {
          $form_state->setErrorByName('piwik_visibility_request_path_pages', t('Path "@page" not prefixed with slash.', ['@page' => $page]));
          // Drupal forms show one error only.
          break;
        }
      }
    }

    // Clear obsolete local cache if cache has been disabled.
    if ($form_state->isValueEmpty('piwik_cache') && $form['advanced']['piwik_cache']['#default_value']) {
      piwik_clear_js_cache();
    }

    // This is for the Newbie's who cannot read a text area description.
    if (preg_match('/(.*)<\/?script(.*)>(.*)/i', $form_state->getValue('piwik_codesnippet_before'))) {
      $form_state->setErrorByName('piwik_codesnippet_before', t('Do not include the &lt;script&gt; tags in the javascript code snippets.'));
    }
    if (preg_match('/(.*)<\/?script(.*)>(.*)/i', $form_state->getValue('piwik_codesnippet_after'))) {
      $form_state->setErrorByName('piwik_codesnippet_after', t('Do not include the &lt;script&gt; tags in the javascript code snippets.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('piwik.settings');
    $config
      ->set('site_id', $form_state->getValue('piwik_site_id'))
      ->set('url_http', $form_state->getValue('piwik_url_http'))
      ->set('url_https', $form_state->getValue('piwik_url_https'))
      ->set('codesnippet.before', $form_state->getValue('piwik_codesnippet_before'))
      ->set('codesnippet.after', $form_state->getValue('piwik_codesnippet_after'))
      ->set('custom.variable', $form_state->getValue('piwik_custom_var'))
      ->set('domain_mode', $form_state->getValue('piwik_domain_mode'))
      ->set('track.files', $form_state->getValue('piwik_trackfiles'))
      ->set('track.files_extensions', $form_state->getValue('piwik_trackfiles_extensions'))
      ->set('track.colorbox', $form_state->getValue('piwik_trackcolorbox'))
      ->set('track.userid', $form_state->getValue('piwik_trackuserid'))
      ->set('track.mailto', $form_state->getValue('piwik_trackmailto'))
      ->set('track.messages', $form_state->getValue('piwik_trackmessages'))
      ->set('track.site_search', $form_state->getValue('piwik_site_search'))
      ->set('privacy.donottrack', $form_state->getValue('piwik_privacy_donottrack'))
      ->set('cache', $form_state->getValue('piwik_cache'))
      ->set('visibility.request_path_mode', $form_state->getValue('piwik_visibility_request_path_mode'))
      ->set('visibility.request_path_pages', $form_state->getValue('piwik_visibility_request_path_pages'))
      ->set('visibility.user_account_mode', $form_state->getValue('piwik_visibility_user_account_mode'))
      ->set('visibility.user_role_mode', $form_state->getValue('piwik_visibility_user_role_mode'))
      ->set('visibility.user_role_roles', $form_state->getValue('piwik_visibility_user_role_roles'))
      ->save();

    if ($form_state->hasValue('piwik_translation_set')) {
      $config->set('translation_set', $form_state->getValue('piwik_translation_set'))->save();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Validate a form element that should have tokens in it.
   *
   * For example:
   * @code
   * $form['my_node_text_element'] = [
   *   '#type' => 'textfield',
   *   '#title' => $this->t('Some text to token-ize that has a node context.'),
   *   '#default_value' => 'The title of this node is [node:title].',
   *   '#element_validate' => [[get_class($this), 'tokenElementValidate']],
   * ];
   * @endcode
   */
  public static function tokenElementValidate(&$element, FormStateInterface $form_state) {
    $value = isset($element['#value']) ? $element['#value'] : $element['#default_value'];

    if (!Unicode::strlen($value)) {
      // Empty value needs no further validation since the element should depend
      // on using the '#required' FAPI property.
      return $element;
    }

    $tokens = \Drupal::token()->scan($value);
    $invalid_tokens = static::getForbiddenTokens($tokens);
    if ($invalid_tokens) {
      $form_state->setError($element, t('The %element-title is using the following forbidden tokens with personal identifying information: @invalid-tokens.', ['%element-title' => $element['#title'], '@invalid-tokens' => implode(', ', $invalid_tokens)]));
    }

    return $element;
  }

  /**
   * Get an array of all forbidden tokens.
   *
   * @param array $value
   *   An array of token values.
   *
   * @return array
   *   A unique array of invalid tokens.
   */
  protected static function getForbiddenTokens(array $value) {
    $invalid_tokens = [];
    $value_tokens = is_string($value) ? \Drupal::token()->scan($value) : $value;

    foreach ($value_tokens as $tokens) {
      if (array_filter($tokens, 'static::containsForbiddenToken')) {
        $invalid_tokens = array_merge($invalid_tokens, array_values($tokens));
      }
    }

    array_unique($invalid_tokens);
    return $invalid_tokens;
  }

  /**
   * Validate if string contains forbidden tokens not allowed by privacy rules.
   *
   * @param string $token_string
   *   A string with one or more tokens to be validated.
   *
   * @return bool
   *   TRUE if blacklisted token has been found, otherwise FALSE.
   */
  protected static function containsForbiddenToken($token_string) {
    // List of strings in tokens with personal identifying information not
    // allowed for privacy reasons. See section 8.1 of the Google Analytics
    // terms of use for more detailed information.
    //
    // This list can never ever be complete. For this reason it tries to use a
    // regex and may kill a few other valid tokens, but it's the only way to
    // protect users as much as possible from admins with illegal ideas.
    //
    // User tokens are not prefixed with colon to catch 'current-user' and
    // 'user'.
    //
    // TODO: If someone have better ideas, share them, please!
    $token_blacklist = [
      ':account-name]',
      ':author]',
      ':author:edit-url]',
      ':author:url]',
      ':author:path]',
      ':current-user]',
      ':current-user:original]',
      ':display-name]',
      ':fid]',
      ':mail]',
      ':name]',
      ':uid]',
      ':one-time-login-url]',
      ':owner]',
      ':owner:cancel-url]',
      ':owner:edit-url]',
      ':owner:url]',
      ':owner:path]',
      'user:cancel-url]',
      'user:edit-url]',
      'user:url]',
      'user:path]',
      'user:picture]',
      // addressfield_tokens.module
      ':first-name]',
      ':last-name]',
      ':name-line]',
      ':mc-address]',
      ':thoroughfare]',
      ':premise]',
      // realname.module
      ':name-raw]',
      // token.module
      ':ip-address]',
    ];

    return preg_match('/' . implode('|', array_map('preg_quote', $token_blacklist)) . '/i', $token_string);
  }

}
