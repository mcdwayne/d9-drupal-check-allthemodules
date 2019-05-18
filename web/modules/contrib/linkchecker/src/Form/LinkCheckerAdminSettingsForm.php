<?php

namespace Drupal\linkchecker\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Drupal\filter\FilterPluginCollection;
use Drupal\user\Entity\User;

/**
 * Configure Linkchecker settings for this site.
 */
class LinkCheckerAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'linkchecker_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['linkchecker.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('linkchecker.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#description' => $this->t('Configure the <a href=":url">content types</a> that should be scanned for broken links.', [':url' => Url::fromRoute('entity.node_type.collection')->toString()]),
      '#open' => TRUE,
    ];

    $block_custom_dependencies = '<div class="admin-requirements">';
    $block_custom_dependencies .= $this->t('Requires: @module-list',
      [
        '@module-list' => (\Drupal::moduleHandler()->moduleExists('block') ? $this->t('@module (<span class="admin-enabled">enabled</span>)', ['@module' => 'Block']) : $this->t('@module (<span class="admin-disabled">disabled</span>)', ['@module' => 'Block']))]);
    $block_custom_dependencies .= '</div>';

    $form['general']['linkchecker_scan_blocks'] = [
      '#default_value' => $config->get('scan_blocks'),
      '#type' => 'checkbox',
      '#title' => $this->t('Scan blocks for links'),
      '#description' => $this->t('Enable this checkbox if links in blocks should be checked.') . $block_custom_dependencies,
      '#disabled' => !\Drupal::moduleHandler()->moduleExists('block'),
    ];
    $form['general']['linkchecker_check_links_types'] = [
      '#type' => 'select',
      '#title' => $this->t('What type of links should be checked?'),
      '#description' => $this->t('A full qualified link (http://example.com/foo/bar) to a page is considered external, whereas an absolute (/foo/bar) or relative link (node/123) without a domain is considered internal.'),
      '#default_value' => $config->get('check_links_types'),
      '#options' => [
        '0' => $this->t('Internal and external'),
        '1' => $this->t('External only (http://example.com/foo/bar)'),
        '2' => $this->t('Internal only (node/123)'),
      ],
    ];

    $form['tag'] = [
      '#type' => 'details',
      '#title' => $this->t('Link extraction'),
      '#open' => TRUE,
    ];
    $form['tag']['linkchecker_extract_from_a'] = [
      '#default_value' => $config->get('extract.from_a'),
      '#type' => 'checkbox',
      '#title' => $this->t('Extract links in <code>&lt;a&gt;</code> and <code>&lt;area&gt;</code> tags'),
      '#description' => $this->t('Enable this checkbox if normal hyperlinks should be extracted. The anchor element defines a hyperlink, the named target destination for a hyperlink, or both. The area element defines a hot-spot region on an image, and associates it with a hypertext link.'),
    ];
    $form['tag']['linkchecker_extract_from_audio'] = [
      '#default_value' => $config->get('extract.from_audio'),
      '#type' => 'checkbox',
      '#title' => $this->t('Extract links in <code>&lt;audio&gt;</code> tags including their <code>&lt;source&gt;</code> and <code>&lt;track&gt;</code> tags'),
      '#description' => $this->t('Enable this checkbox if links in audio tags should be extracted. The audio element is used to embed audio content.'),
    ];
    $form['tag']['linkchecker_extract_from_embed'] = [
      '#default_value' => $config->get('extract.from_embed'),
      '#type' => 'checkbox',
      '#title' => $this->t('Extract links in <code>&lt;embed&gt;</code> tags'),
      '#description' => $this->t('Enable this checkbox if links in embed tags should be extracted. This is an obsolete and non-standard element that was used for embedding plugins in past and should no longer used in modern websites.'),
    ];
    $form['tag']['linkchecker_extract_from_iframe'] = [
      '#default_value' => $config->get('extract.from_iframe'),
      '#type' => 'checkbox',
      '#title' => $this->t('Extract links in <code>&lt;iframe&gt;</code> tags'),
      '#description' => $this->t('Enable this checkbox if links in iframe tags should be extracted. The iframe element is used to embed another HTML page into a page.'),
    ];
    $form['tag']['linkchecker_extract_from_img'] = [
      '#default_value' => $config->get('extract.from_img'),
      '#type' => 'checkbox',
      '#title' => $this->t('Extract links in <code>&lt;img&gt;</code> tags'),
      '#description' => $this->t('Enable this checkbox if links in image tags should be extracted. The img element is used to add images to the content.'),
    ];
    $form['tag']['linkchecker_extract_from_object'] = [
      '#default_value' => $config->get('extract.from_object'),
      '#type' => 'checkbox',
      '#title' => $this->t('Extract links in <code>&lt;object&gt;</code> and <code>&lt;param&gt;</code> tags'),
      '#description' => $this->t('Enable this checkbox if multimedia and other links in object and their param tags should be extracted. The object tag is used for flash, java, quicktime and other applets.'),
    ];
    $form['tag']['linkchecker_extract_from_video'] = [
      '#default_value' => $config->get('extract.from_video'),
      '#type' => 'checkbox',
      '#title' => $this->t('Extract links in <code>&lt;video&gt;</code> tags including their <code>&lt;source&gt;</code> and <code>&lt;track&gt;</code> tags'),
      '#description' => $this->t('Enable this checkbox if links in video tags should be extracted. The video element is used to embed video content.'),
    ];

    // Get all filters available on the system.
    $manager = \Drupal::service('plugin.manager.filter');
    $bag = new FilterPluginCollection($manager, []);
    $filter_info = $bag->getAll();
    $filter_options = [];
    $filter_descriptions = [];
    foreach ($filter_info as $name => $filter) {
      if (in_array($name, explode('|', LINKCHECKER_DEFAULT_FILTER_BLACKLIST))) {
        $filter_options[$name] = $this->t('@title <span class="marker">(Recommended)</span>', ['@title' => $filter->getLabel()]);
      }
      else {
        $filter_options[$name] = $filter->getLabel();
      }
      $filter_descriptions[$name] = [
        '#description' => $filter->getDescription(),
      ];
    }
    $form['tag']['linkchecker_filter_blacklist'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Text formats disabled for link extraction'),
      '#default_value' => $config->get('extract.filter_blacklist'),
      '#options' => $filter_options,
      '#description' => $this->t('If a filter has been enabled for an input format it runs first and afterwards the link extraction. This helps the link checker module to find all links normally created by custom filters (e.g. Markdown filter, Bbcode). All filters used as inline references (e.g. Weblink filter <code>[link: id]</code>) to other content and filters only wasting processing time (e.g. Line break converter) should be disabled. This setting does not have any effect on how content is shown on a page. This feature optimizes the internal link extraction process for link checker and prevents false alarms about broken links in content not having the real data of a link.'),
    ];
    $form['tag']['linkchecker_filter_blacklist'] = array_merge($form['tag']['linkchecker_filter_blacklist'], $filter_descriptions);

    $count_lids_enabled = db_query("SELECT count(lid) FROM {linkchecker_link} WHERE status = :status", [':status' => 1])->fetchField();
    $count_lids_disabled = db_query("SELECT count(lid) FROM {linkchecker_link} WHERE status = :status", [':status' => 0])->fetchField();

    // httprl module does not exists yet for D8
    /* $form['check'] = [
      '#type' => 'details',
      '#title' => $this->t('Check settings'),
      '#description' => $this->t('For simultaneous link checks it is recommended to install the <a href=":httprl">HTTP Parallel Request & Threading Library</a>. This may be <strong>necessary</strong> on larger sites with very many links (30.000+), but will also improve overall link check duration on smaller sites. Currently the site has @count links (@count_enabled enabled / @count_disabled disabled).', [':httprl' => 'http://drupal.org/project/httprl', '@count' => $count_lids_enabled+$count_lids_disabled, '@count_enabled' => $count_lids_enabled, '@count_disabled' => $count_lids_disabled]),
      '#open' => TRUE,
    ];*/
    $form['check']['linkchecker_check_library'] = [
      '#type' => 'select',
      '#title' => $this->t('Check library'),
      '#description' => $this->t('Defines the library that is used for checking links.'),
      '#default_value' => $config->get('check.library'),
      '#options' => [
        'core' => $this->t('Drupal core'),
        // 'httprl' => $this->t('HTTP Parallel Request & Threading Library'),
      ],
    ];
    $form['check']['linkchecker_check_connections_max'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of simultaneous connections'),
      '#description' => $this->t('Defines the maximum number of simultaneous connections that can be opened by the server. <em>HTTP Parallel Request & Threading Library</em> make sure that a single domain is not overloaded beyond RFC limits. For small hosting plans with very limited CPU and RAM it may be required to reduce the default limit.'),
      '#default_value' => $config->get('check.connections_max'),
      '#options' => array_combine([2, 4, 8, 16, 24, 32, 48, 64, 96, 128], [2, 4, 8, 16, 24, 32, 48, 64, 96, 128]),
      '#states' => [
        // Hide the setting when Drupal core check library is selected.
        'invisible' => [
          ':input[name="check_library"]' => ['value' => 'core'],
        ],
      ],
    ];
    $form['check']['linkchecker_check_useragent'] = [
      '#type' => 'select',
      '#title' => $this->t('User-Agent'),
      '#description' => $this->t('Defines the user agent that will be used for checking links on remote sites. If someone blocks the standard Drupal user agent you can try with a more common browser.'),
      '#default_value' => $config->get('check.useragent'),
      '#options' => [
        'Drupal (+http://drupal.org/)' => 'Drupal (+http://drupal.org/)',
        'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; Touch; rv:11.0) like Gecko' => 'Windows 8.1 (x64), Internet Explorer 11.0',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2486.0 Safari/537.36 Edge/13.10586' => 'Windows 10 (x64), Edge',
        'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0' => 'Windows 8.1 (x64), Mozilla Firefox 47.0',
        'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0' => 'Windows 10 (x64), Mozilla Firefox 47.0',
      ],
    ];
    $intervals = [86400, 172800, 259200, 604800, 1209600, 2419200, 4838400, 7776000];
    $period = array_map([\Drupal::service('date.formatter'), 'formatInterval'], array_combine($intervals, $intervals));
    $form['check']['linkchecker_check_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Check interval for links'),
      '#description' => $this->t('This interval setting defines how often cron will re-check the status of links.'),
      '#default_value' => $config->get('check.interval'),
      '#options' => $period,
    ];
    $form['check']['linkchecker_disable_link_check_for_urls'] = [
      '#default_value' => $config->get('check.disable_link_check_for_urls'),
      '#type' => 'textarea',
      '#title' => $this->t('Do not check the link status of links containing these URLs'),
      '#description' => $this->t('By default this list contains the domain names reserved for use in documentation and not available for registration. See <a href=":rfc-2606">RFC 2606</a>, Section 3 for more information. URLs on this list are still extracted, but the link setting <em>Check link status</em> becomes automatically disabled to prevent false alarms. If you change this list you need to clear all link data and re-analyze your content. Otherwise this setting will only affect new links added after the configuration change.', [':rfc-2606' => 'http://www.rfc-editor.org/rfc/rfc2606.txt']),
    ];
    // @fixme: constants no longer exists.
    $form['check']['linkchecker_logging_level'] = [
      '#default_value' => $config->get('logging.level'),
      '#type' => 'select',
      '#title' => $this->t('Log level'),
      '#description' => $this->t('Controls the severity of logging.'),
      '#options' => [
        RfcLogLevel::DEBUG => $this->t('Debug messages'),
        RfcLogLevel::INFO => $this->t('All messages (default)'),
        RfcLogLevel::NOTICE => $this->t('Notices and errors'),
        RfcLogLevel::WARNING => $this->t('Warnings and errors'),
        RfcLogLevel::ERROR => $this->t('Errors only'),
      ],
    ];

    $form['error'] = [
      '#type' => 'details',
      '#title' => $this->t('Error handling'),
      '#description' => $this->t('Defines error handling and custom actions to be executed if specific HTTP requests are failing.'),
      '#open' => TRUE,
    ];
    $linkchecker_default_impersonate_account = User::load(1);
    $form['error']['linkchecker_impersonate_account'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Impersonate user account'),
      '#description' => $this->t('If below error handling actions are executed they can be impersonated with a custom user account. By default this is user %name, but you are able to assign a custom user to allow easier identification of these automatic revision updates. Make sure you select a user with <em>full</em> permissions on your site or the user may not able to access and save all content.', ['%name' => $linkchecker_default_impersonate_account->getAccountName()]),
      '#size' => 30,
      '#maxlength' => 60,
      '#autocomplete_path' => 'user/autocomplete',
      '#default_value' => $config->get('error.impersonate_account'),
    ];
    $form['error']['linkchecker_action_status_code_301'] = [
      '#title' => $this->t('Update permanently moved links'),
      '#description' => $this->t('If enabled, outdated links in content providing a status <em>Moved Permanently</em> (status code 301) are automatically updated to the most recent URL. If used, it is recommended to use a value of <em>three</em> to make sure this is not only a temporarily change. This feature trust sites to provide a valid permanent redirect. A new content revision is automatically created on link updates if <em>create new revision</em> is enabled in the <a href=":content_types">content types</a> publishing options. It is recommended to create new revisions for all link checker enabled content types. Link updates are nevertheless always logged in <a href=":dblog">recent log entries</a>.', [':dblog' => Url::fromRoute('dblog.overview')->toString(), ':content_types' => Url::fromRoute('entity.node_type.collection')->toString()]),
      '#type' => 'select',
      '#default_value' => $config->get('error.action_status_code_301'),
      '#options' => [
        0 => $this->t('Disabled'),
        1 => $this->t('After one failed check'),
        2 => $this->t('After two failed checks'),
        3 => $this->t('After three failed checks'),
        5 => $this->t('After five failed checks'),
        10 => $this->t('After ten failed checks'),
      ],
    ];
    $form['error']['linkchecker_action_status_code_404'] = [
      '#title' => $this->t('Unpublish content on file not found error'),
      '#description' => $this->t('If enabled, content with one or more broken links (status code 404) will be unpublished and moved to moderation queue for review after the number of specified checks failed. If used, it is recommended to use a value of <em>three</em> to make sure this is not only a temporarily error.'),
      '#type' => 'select',
      '#default_value' => $config->get('error.action_status_code_404'),
      '#options' => [
        0 => $this->t('Disabled'),
        1 => $this->t('After one file not found error'),
        2 => $this->t('After two file not found errors'),
        3 => $this->t('After three file not found errors'),
        5 => $this->t('After five file not found errors'),
        10 => $this->t('After ten file not found errors'),
      ],
    ];
    $form['error']['linkchecker_ignore_response_codes'] = [
      '#default_value' => $config->get('error.ignore_response_codes'),
      '#type' => 'textarea',
      '#title' => $this->t("Don't treat these response codes as errors"),
      '#description' => $this->t('One HTTP status code per line, e.g. 403.'),
    ];

    // Buttons are only required for testing and debugging reasons.
    $description = '<p>' . $this->t('These actions will either clear all link checker tables in the database and/or analyze all selected content types, blocks and fields (see settings above) for new/updated/removed links. Normally there is no need to press one of these buttons. Use this only for immediate cleanup tasks and to force a full re-build of the links to be checked in the linkchecker tables. Keep in mind that all custom link settings will be lost if you clear link data!') . '</p>';
    $description .= '<p>' . $this->t('<strong>Note</strong>: These functions ONLY collect the links, they do not evaluate the HTTP response codes, this will be done during normal cron runs.') . '</p>';

    $form['clear'] = [
      '#type' => 'details',
      '#title' => $this->t('Maintenance'),
      '#description' => $description,
      '#open' => FALSE,
    ];
    $form['clear']['linkchecker_analyze'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reanalyze content for links'),
      '#submit' => ['::submitForm', '::submitAnalyzeLinks'],
    ];
    $form['clear']['linkchecker_clear_analyze'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear link data and analyze content for links'),
      '#submit' => ['::submitForm', '::submitClearAnalyzeLinks'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $form_state->setValue('linkchecker_disable_link_check_for_urls', trim($form_state->getValue('linkchecker_disable_link_check_for_urls')));
    $form_state->setValue('linkchecker_ignore_response_codes', trim($form_state->getValue('linkchecker_ignore_response_codes')));
    $ignore_response_codes = preg_split('/(\r\n?|\n)/', $form_state->getValue('linkchecker_ignore_response_codes'));
    foreach ($ignore_response_codes as $ignore_response_code) {
      if (!_linkchecker_isvalid_response_code($ignore_response_code)) {
        $form_state->setErrorByName('linkchecker_ignore_response_codes', $this->t('Invalid response code %code found.', ['%code' => $ignore_response_code]));
      }
    }

    // @fixme: remove constant?
    // Prevent the removal of RFC documentation domains. This are the official and
    // reserved documentation domains and not "example" hostnames!
    $linkchecker_disable_link_check_for_urls = array_filter(preg_split('/(\r\n?|\n)/', $form_state->getValue('linkchecker_disable_link_check_for_urls')));
    $form_state->setValue('linkchecker_disable_link_check_for_urls', implode("\n", array_unique(array_merge(explode("\n", LINKCHECKER_RESERVED_DOCUMENTATION_DOMAINS), $linkchecker_disable_link_check_for_urls))));

    // Validate impersonation user name.
    $linkchecker_impersonate_account = user_load_by_name($form_state->getValue('linkchecker_impersonate_account'));
// @TODO: Cleanup
//    if (empty($linkchecker_impersonate_account->id())) {
    if ($linkchecker_impersonate_account && empty($linkchecker_impersonate_account->id())) {
      $form_state->setErrorByName('linkchecker_impersonate_account', $this->t('User account %name cannot found.', ['%name' => $form_state->getValue('linkchecker_impersonate_account')]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('linkchecker.settings');
    $config
      ->set('scan_blocks', $form_state->getValue('linkchecker_scan_blocks'))
      ->set('check_links_types', $form_state->getValue('linkchecker_check_links_types'))
      ->set('extract.from_a', $form_state->getValue('linkchecker_extract_from_a'))
      ->set('extract.from_audio', $form_state->getValue('linkchecker_extract_from_audio'))
      ->set('extract.from_embed', $form_state->getValue('linkchecker_extract_from_embed'))
      ->set('extract.from_iframe', $form_state->getValue('linkchecker_extract_from_iframe'))
      ->set('extract.from_img', $form_state->getValue('linkchecker_extract_from_img'))
      ->set('extract.from_object', $form_state->getValue('linkchecker_extract_from_object'))
      ->set('extract.from_video', $form_state->getValue('linkchecker_extract_from_video'))
      ->set('extract.filter_blacklist', $form_state->getValue('linkchecker_filter_blacklist'))
      ->set('check.connections_max', $form_state->getValue('linkchecker_check_connections_max'))
      ->set('check.disable_link_check_for_urls', $form_state->getValue('linkchecker_disable_link_check_for_urls'))
      ->set('check.library', $form_state->getValue('linkchecker_check_library'))
      ->set('check.interval', $form_state->getValue('linkchecker_check_interval'))
      ->set('check.useragent', $form_state->getValue('linkchecker_check_useragent'))
      ->set('error.action_status_code_301', $form_state->getValue('linkchecker_action_status_code_301'))
      ->set('error.action_status_code_404', $form_state->getValue('linkchecker_action_status_code_404'))
      ->set('error.ignore_response_codes', $form_state->getValue('linkchecker_ignore_response_codes'))
      ->set('error.impersonate_account', $form_state->getValue('linkchecker_impersonate_account'))
      ->set('logging.level', $form_state->getValue('linkchecker_logging_level'))
      ->save();

    // If block scanning has been selected.
    if ($form_state->getValue('linkchecker_scan_blocks') > $form['general']['linkchecker_scan_blocks']['#default_value']) {
      module_load_include('inc', 'linkchecker', 'linkchecker.batch');
      batch_set(_linkchecker_batch_import_block_custom());
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Analyze fields in all node types, comments, custom blocks.
   */
  function submitAnalyzeLinks(array &$form, FormStateInterface $form_state) {
    // Start batch and analyze all nodes.
    $node_types = linkchecker_scan_node_types();
    if (!empty($node_types)) {
      module_load_include('inc', 'linkchecker', 'linkchecker.batch');
      batch_set(_linkchecker_batch_import_nodes($node_types));
    }

    $comment_types = linkchecker_scan_comment_types();
    if (!empty($comment_types)) {
      module_load_include('inc', 'linkchecker', 'linkchecker.batch');
      batch_set(_linkchecker_batch_import_comments($comment_types));
    }

    if ($this->config('linkchecker.settings')->get('scan_blocks')) {
      module_load_include('inc', 'linkchecker', 'linkchecker.batch');
      batch_set(_linkchecker_batch_import_block_custom());
    }
  }

  /**
   * Clear link data and analyze fields in all content types, comments, custom
   * blocks.
   */
  function submitClearAnalyzeLinks(array &$form, FormStateInterface $form_state) {
    \Drupal::database()->truncate('linkchecker_block_custom')->execute();
    \Drupal::database()->truncate('linkchecker_comment')->execute();
    \Drupal::database()->truncate('linkchecker_node')->execute();
    \Drupal::database()->truncate('linkchecker_link')->execute();

    // Start batch and analyze all nodes.
    $node_types = linkchecker_scan_node_types();
    if (!empty($node_types)) {
      module_load_include('inc', 'linkchecker', 'linkchecker.batch');
      batch_set(_linkchecker_batch_import_nodes($node_types));
    }

    $comment_types = linkchecker_scan_comment_types();
    if (!empty($comment_types)) {
      module_load_include('inc', 'linkchecker', 'linkchecker.batch');
      batch_set(_linkchecker_batch_import_comments($comment_types));
    }

    if ($this->config('linkchecker.settings')->get('scan_blocks')) {
      module_load_include('inc', 'linkchecker', 'linkchecker.batch');
      batch_set(_linkchecker_batch_import_block_custom());
    }
  }

}
