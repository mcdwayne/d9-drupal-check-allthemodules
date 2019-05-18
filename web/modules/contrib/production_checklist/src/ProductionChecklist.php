<?php

namespace Drupal\production_checklist;

use Drupal\checklistapi\ChecklistapiChecklist;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Link;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;

/**
 * Class ProductionChecklist.
 */
class ProductionChecklist implements ProductionChecklistInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Drupal\Core\Render\Renderer definition.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * Constructs a new ProductionChecklist object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandler $module_handler, Renderer $renderer, ConfigFactory $config) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableSections() {
    return [
      'drupal_system' => t('System wide status and reports'),
      'drupal_codebase' => t('Contributed projects review'),
      'other_codebase' => t('Vendors and custom code'),
      'spam_prevention' => t('Spam prevention'),
      'security_access' => t('Security and access'),
      'content' => t('Content model review and proofreading'),
      'frontend' => t('Frontend'),
      'database' => t('Database and configuration'),
      'performance' => t('Performance and caching'),
      'test' => t('Testing'),
      'analytics' => t('Analytics'),
      'sysadmin' => t('Sysadmin and backups'),
      'seo' => t('Basic SEO'),
      'legal' => t('Legal aspects'),
      'documentation' => t('Project documentation'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableSectionsItems() {
    $sections = [];
    // Drupal system.
    $sections['drupal_system'] = [
      '#title' => t('Drupal system'),
      '#description' => '<h2>' . t('System wide status and reports') . '</h2>',
      'status_report' => [
        '#title' => t('Review status report'),
        '#description' => t('Contains general system information.'),
        'path' => [
          '#text' => t('Status report'),
          '#url' => Url::fromUserInput('/admin/reports/status'),
        ],
      ],
      'site_information' => [
        '#title' => t('Site information'),
        '#description' => t('Make sure the email address and site name are correct. Check the homepage title.'),
        'path' => [
          '#text' => t('Basic site settings'),
          '#url' => Url::fromUserInput('/admin/config/system/site-information'),
        ],
      ],
      'recent_logs' => [
        '#title' => t('Review recent logs'),
        // @todo add service for log errors based on a config threshold
        '#description' => t('Monitor your site or debug site problems.'),
        'path' => [
          '#text' => t('Recent log messages'),
          '#url' => Url::fromUserInput('/admin/reports/dblog'),
        ],
      ],
      'error_display' => [
        '#title' => t('Disable error display'),
        // @todo add service for log errors based on a config threshold
        '#description' => t('Disable any errors output on frontend.'),
        'path' => [
          '#text' => t('Logging and errors'),
          '#url' => Url::fromUserInput('/admin/config/development/logging'),
        ],
      ],
      'core_search' => [
        '#title' => t('Core search'),
        '#description' => t('Disable core search if not relevant or if a replacement search is used (Search API, ...).'),
        'path' => $this->getModulesPageTextUrl(),
      ],
      'syslog' => [
        '#title' => t('Enable Syslog core module and optionally disable Database Logging for performance.'),
        '#description' => t('Logs and records system events to syslog.'),
        // @todo check modules status
        'path' => $this->getModulesPageTextUrl(),
      ],
    ];

    // Drupal codebase.
    $sections['drupal_codebase'] = [
      '#title' => t('Drupal code base'),
      '#description' => '<h2>' . t('Contributed projects review') . '</h2><p>' . t('Core, modules and themes.') . '</p>',
      'development_modules' => [
        '#title' => t('Uninstall development modules like Devel (Devel, Devel generate, Kint, Webprofiler)'),
        // @todo check modules status
        '#description' => $this->getDevelopmentModulesStatusLink(),
        'path' => $this->getModulesUninstallPageTextUrl(),
      ],
      'unused_modules' => [
        '#title' => t('Unused modules'),
        '#description' => 'Uninstall and remove unused modules.',
        'path' => $this->getModulesUninstallPageTextUrl(),
      ],
      'unused_themes' => [
        '#title' => t('Unused themes'),
        '#description' => 'Uninstall and remove unused themes.',
        'path' => [
          '#text' => t('Appearance'),
          '#url' => Url::fromRoute('system.themes_page'),
        ],
      ],
    ];

    // Other codebase.
    $sections['other_codebase'] = [
      '#title' => t('Other code base'),
      '#description' => '<h2>' . t("Vendors, custom code and libraries") . '</h2>',
      'development_vendors' => [
        '#title' => t('Remove development vendors like PHPUnit, Behat'),
        '#description' => t('Run <code>composer install --no-dev</code>'),
        'documentation' => [
          '#text' => t('Composer install documentation'),
          '#url' => Url::fromUri(t('https://getcomposer.org/doc/03-cli.md#install')),
        ],
      ],
      'custom_libraries' => [
        '#title' => t('Remove unused libraries'),
        '#description' => t('Check the /libraries directory.'),
      ],
      'node_modules' => [
        '#title' => t('Node modules'),
        '#description' => t('Check if Node modules dedicated to SASS build, ... are not in the codebase.'),
      ],
      'phpmd' => [
        '#title' => t('PHP Mess Detector'),
        '#description' => t('Run PHP Mess Detector on custom code.'),
        'documentation' => [
          '#text' => t('PHP Mess Detector'),
          '#url' => Url::fromUri(t('https://phpmd.org/')),
        ],
      ],
      'phpcs' => [
        '#title' => t('PHPCS'),
        '#description' => t('Run the <code>phpcs</code> command on custom code.'),
        'documentation' => [
          '#text' => t('PHP Coding Standards'),
          '#url' => Url::fromUri(t('https://www.drupal.org/docs/develop/standards/coding-standards')),
        ],
      ],
    ];

    // Spam prevention.
    $sections['spam_prevention'] = [
      '#title' => t('Spam prevention'),
      '#description' => '<h2>' . t('Spam related configuration and modules') . '</h2>',
      'user_registration' => [
        '#title' => t('Review user registration'),
        // @todo add service for current setting
        '#description' => t('Depending on the use case, new account creations can be limited to administrators.'),
      ],
      'content_permissions' => [
        '#title' => t('Check permissions for <em>content</em> creation'),
        '#description' => t('Node related permissions.'),
      ],
      'comment_permissions' => [
        '#title' => t('Check permissions for <em>comment</em> creation'),
        '#description' => 'Comment related permissions.',
      ],
      'forms' => [
        '#title' => t('Check contact form and webform configuration'),
        '#description' => t('Are the main contact form and personal contact form enabled? Is Webform installed?'),
      ],
      'antispam' => [
        '#title' => t('Are the forms protected with Honeypot and Captcha (and optionally reCaptcha)?'),
        // @todo depend on forms settings
        '#description' => $this->getAntiSpamStatusLink(),
      ],
      'email_obfuscation' => [
        '#title' => t('Email obfuscation'),
        '#description' => $this->getEmailObfuscationDescription(),
      ],
    ];

    // Security and access control.
    $sections['security_access'] = [
      '#title' => t('Security and access'),
      '#description' => '<h2>' . t('Security and access control') . '</h2><p>' . t('This topic can be extended with @site_audit_link and @security_review_link. Basically, test simultaneous and consequent anonymous access scenarios and behavior when every cache is enabled.',
          [
            '@site_audit_link' => $this->getProjectLink('site_audit'),
            '@security_review_link' => $this->getProjectLink('security_review'),
          ]
      ) . '</p>',
      'security_update' => $this->getSecurityUpdatesChecklistArray(),
      'permission' => [
        '#title' => t('Review the permissions'),
        '#description' => t('This should be done for each role.'),
        'path' => [
          '#text' => t('Permissions'),
          '#url' => Url::fromUserInput('/admin/people/permissions'),
        ],
      ],
      'input_format' => [
        '#title' => t('Input format'),
        '#description' => t('Make sure that input formats are correctly configured. <em>Full HTML</em> should be avoided for untrusted users.'),
        'path' => [
          '#text' => t('Text formats and editors'),
          '#url' => Url::fromUserInput('/admin/config/content/formats'),
        ],
      ],
      'admin_username' => [
        '#title' => t('Admin user name'),
        '#description' => t("The user 1 name (or other users that have the administrator role) should not be defined as 'admin' so it will be harder to guess for attackers."),
      ],
      'password' => [
        '#title' => t('Check passwords'),
        '#description' => t('Passwords should be hard to guess, especially for author and admin roles. Use a module like Password Policy.'),
        'password_policy' => [
          '#text' => t('Password Policy'),
          '#url' => Url::fromUri('https://www.drupal.org/project/password_policy'),
        ],
      ],
      'access_denied' => [
        '#title' => t('Review access denied errors'),
        '#description' => t('If needed block IP addresses with the core Ban module. This process can be completed with the recent log messages.'),
        'path' => [
          '#text' => t('Top access denied errors'),
          '#url' => Url::fromUserInput('/admin/reports/access-denied'),
        ],
      ],
      'changelog' => [
        '#title' => t('Changelog'),
        '#description' => t('Do not publish CHANGELOG.txt and other .txt files at the root of the code base.'),
      ],
      'staging_local' => [
        '#title' => t('Staging and dev environments'),
        '#description' => t('Make sure that your staging and dev environments does not contain sensitive data and are protected with @shield_link if accessible from the outside.', [
          '@shield_link' => $this->getProjectLink('shield'),
        ]),
        'documentation' => [
          '#text' => t('Securing Non-Production Environments'),
          '#url' => Url::fromUri('https://dev.acquia.com/blog/securing-nonproduction-environments/09/03/2018/19251'),
        ],
      ],
    ];

    // Content model and content.
    $sections['content'] = [
      '#title' => t('Content'),
      '#description' => '<h2>' . t('Content model review and proofreading') . '</h2>',
      'content_model' => [
        '#title' => t('Review content model'),
        '#description' => t('Remove unused content types, vocabularies, roles, fields, ...'),
      ],
      'dummy_content' => [
        '#title' => t('Remove dummy content'),
        '#description' => t('Content, terms, users, ... dedicated to site building (e.g. devel generated) should not be there.'),
      ],
      'proofreading' => [
        '#title' => t('Proofreading'),
        '#description' => t('Content proofreading.'),
      ],
      'forms_test' => [
        '#title' => t('Remove forms tests'),
        '#description' => t('Webform provides a test deletion tab for each webform.'),
      ],
      'files' => [
        '#title' => t('Files sub directories'),
        '#description' => t('Configure file and media fields for storing files in sub directories instead of the <em>sites/default/files</em> root.'),
      ],
      'date_format' => [
        '#title' => t('Date formats, locale and timezone'),
        '#description' => t('Check if these settings are configured to match your site regions.'),
        'path' => [
          '#text' => t('Regional and language'),
          '#url' => Url::fromUserInput(t('/admin/config/regional')),
        ],
      ],
    ];

    // Append translation items to the content section if multilingual applies.
    // @todo reset checked items if multilingual is unset.
    if ($this->isSiteMultilingual()) {
      $sections['content'] += [
        'content_translation' => [
          '#title' => t('Content translation'),
          '#description' => t('Are all the necessary content translated?'),
        ],
        'entity_translation' => [
          '#title' => t('Entity and field translation'),
          '#description' => t('Are all the entities and fields configured properly?'),
          'path' => [
            '#text' => t('Content language'),
            '#url' => Url::fromUserInput(t('/admin/config/regional/content-language')),
          ],
        ],
        'localization' => [
          '#title' => t('Localization'),
          '#description' => t('Is the localization up to date?'),
          'path' => [
            '#text' => t('Available translation updates'),
            '#url' => Url::fromUserInput(t('/admin/reports/translations')),
          ],
        ],
      ];
    }

    // Frontend.
    $sections['frontend'] = [
      '#title' => t('Frontend'),
      '#description' => '<h2>' . t('Frontend basic checks') . '</h2>',
      'maintenance_page' => [
        '#title' => t('Provide a maintenance page'),
        '#description' => t('Check the maintenance page layout.'),
      ],
      'not_found' => [
        '#title' => t('Provide a good 404 page'),
        '#description' => t('Check the 404 page layout. Optionally provide a dedicated design and improve it (smart 404, search engine, ...).'),
      ],
      'access_denied' => [
        '#title' => t('Provide a good 403 page'),
        '#description' => t('Check the 403 page layout. Provide options to login and redirect to the accessed route.'),
      ],
      'favicon' => [
        '#title' => t('Favicon'),
        '#description' => t('Provide favicons in several formats.'),
      ],
    ];

    // Database and configuration.
    $sections['database'] = [
      '#title' => t('Database'),
      '#description' => '<h2>' . t('Database and configuration') . '</h2>',
      'db_update' => [
        '#title' => t('Check database update'),
        '#description' => t('Run /update.php, get a backup first.'),
      ],
      'entity_update' => [
        '#title' => t('Check entity update'),
        '#description' => t('Run <code>drush entup</code>.'),
      ],
      'configuration_export' => [
        '#title' => t('Export current configuration'),
        '#description' => t('Run <code>drush cex</code>.'),
      ],
    ];

    // Performance.
    $sections['performance'] = [
      '#title' => t('Performance'),
      '#description' => '<h2>' . t('Performance and caching configuration') . '</h2><p>' . t('To go deeper, consider using @varnish_link, @memcached_link, @advagg_link.',
          [
            '@varnish_link' => $this->getProjectLink('varnish_purge'),
            '@memcached_link' => $this->getProjectLink('memcache'),
            '@advagg_link' => $this->getProjectLink('advagg'),
          ]) . '</p>',
      'caching' => [
        '#title' => t('Caching'),
        '#description' => t('Are page caching and CSS/JS aggregation enabled?'),
        'masquerade' => [
          '#text' => t('Performance'),
          '#url' => Url::fromUserInput(t('/admin/config/development/performance')),
        ],
      ],
      'big_pipe' => [
        '#title' => t('BigPipe'),
        '#description' => t('Consider enabling the BigPipe module.'),
        'path' => $this->getModulesPageTextUrl(),
      ],
      // @todo improve
      'views' => [
        '#title' => t('Views'),
        '#description' => t('Checks views caching (on a View edit: Advanced > Other > Caching).'),
      ],
      'custom_code' => [
        '#title' => t('Custom code'),
        '#description' => t('Check cache tags and invalidation, make use of Drupal cache for heavy tasks.'),
      ],
      'audit' => [
        '#title' => t('Audit performances'),
        '#description' => t('Use tools like Google PageSpeed Insight or Acquia Insight.'),
        'google_page_speed' => [
          '#text' => 'Google PageSpeed Insights',
          '#url' => Url::fromUri('https://developers.google.com/speed/pagespeed/insights/'),
        ],
        'acquia_insight' => [
          '#text' => 'Acquia Insight',
          '#url' => Url::fromUri('https://www.acquia.com/resources/collateral/acquia-insight-data-sheet'),
        ],
      ],
      'profiler' => [
        '#title' => t('Profilers'),
        '#description' => t('Use profilers like XHProf or Blackfire.'),
        'xhprof' => [
          '#text' => 'XHProf Drupal.org documentation',
          '#url' => Url::fromUri('https://www.drupal.org/docs/develop/development-tools/xhprof-code-profiler'),
        ],
        'blackfire' => [
          '#text' => 'Blackfire.io',
          '#url' => Url::fromUri('https://blackfire.io/'),
        ],
      ],
    ];

    // Test.
    $sections['test'] = [
      '#title' => t('Testing'),
      '#description' => '<h2>' . t('Various test coverages') . '</h2>',
      'manual_test' => [
        '#title' => t('Manual test'),
        '#description' => t('Test the website for each role, anonymous included. Test with caches enabled. Use @masquerade_link to substitute as other users.', [
          '@masquerade_link' => $this->getProjectLink('masquerade'),
        ]),
      ],
      'behat' => [
        '#title' => t('Behat tests'),
        '#description' => t('BDD tests with Behat. Fast and affordable.'),
        'path' => [
          '#text' => t('Behat'),
          '#url' => Url::fromUri(t('http://behat-drupal-extension.readthedocs.io/en/3.1/intro.html')),
        ],
      ],
      'php_unit' => [
        '#title' => t('Run PHPUnit tests'),
        '#description' => t('Use them before a deployment: Functional, Kernel, Javascript, ...'),
        '#text' => t('PHP Unit'),
        '#url' => Url::fromUri(t('https://www.drupal.org/docs/8/phpunit')),
      ],
      'ci' => [
        '#title' => t('Continuous Integration system'),
        '#description' => t('Use tools that fits your needs like Travis CI, CircleCI, Jenkins.'),
        'travis_ci' => [
          '#text' => t('Travis CI'),
          '#url' => Url::fromUri(t('https://travis-ci.org/')),
        ],
        'circle_ci' => [
          '#text' => t('CircleCI'),
          '#url' => Url::fromUri(t('https://circleci.com/')),
        ],
        'jenkins' => [
          '#text' => t('Jenkins'),
          '#url' => Url::fromUri(t('https://jenkins.io/')),
        ],
      ],
    ];

    // Analytics.
    $sections['analytics'] = [
      '#title' => t('Analytics'),
      '#description' => '<h2>' . t('Analytics') . '</h2>',
      'google_analyics' => [
        '#title' => t('Google Analytics'),
        '#description' => t('Is the @ga_link module installed and configured?', [
          '@ga_link' => $this->getProjectLink('google_analytics'),
        ]),
      ],
      'google_webmaster_tools' => [
        '#title' => t('Google webmaster tools'),
        '#description' => t('Are Google webmaster tools configured?'),
      ],
      'heatmap' => [
        '#title' => t('Heatmap'),
        '#description' => t('Is Hotjar or another heatmap service installed and configured?'),
        'hotjar' => [
          '#text' => t('Hotjar'),
          '#url' => Url::fromUri('https://www.hotjar.com/'),
        ],
      ],
    ];

    // Sysadmin.
    $sections['sysadmin'] = [
      '#title' => t('Sysadmin and backups'),
      '#description' => '<h2>' . t('Server configuration and backups') . '</h2>',
      'backup' => [
        '#title' => t('Backups'),
        '#description' => t('Make sure that you have database and files backups enabled. Use a module like @bam_link.', [
          '@bam_link' => $this->getProjectLink('backup_migrate'),
        ]),
        // @todo review NodeSquirell status with backup and migrate
        // https://www.drupal.org/project/backup_migrate/issues/2845676
        // 'nodesquirrel' => [
        // '#text' => 'NodeSquirrel',
        // '#url' => Url::fromUri('https://www.nodesquirrel.com/'),
        // ],
      ],
      'mail' => [
        '#title' => t('Mails'),
        '#description' => t('Have mails being tested for each form (e.g. password reset). Is a third party needed, like Mandrill or Sendgrid? Are SPF and PTR ok?'),
      ],
      'ssl' => [
        '#title' => t('SSL certificate'),
        '#description' => t("Free SSL certificates are available from Let's Encrypt."),
        'lets_encrypt' => [
          '#text' => t("Let's Encrypt"),
          '#url' => Url::fromUri('https://letsencrypt.org/'),
        ],
      ],
      // @todo check max upload size for the current environment.
      'max_upload' => [
        '#title' => t('Maximum file upload size'),
        '#description' => t("This should be set in your per vhost php.ini configuration if available. Set <code>post_max_size</code> and <code>upload_max_filesize</code> according to your needs."),
      ],
      'resources' => [
        '#title' => t('Maximum memory and execution time'),
        '#description' => t("This should be set in your per vhost php.ini configuration if available. Set <code>memory_limit</code> and <code>max_execution_time</code> according to your needs."),
      ],
      'file_permission' => [
        '#title' => t('Check files / directories permissions and ownership'),
        '#description' => t('Usually one owner and group per virtual host, files at 644 and directories at 755.'),
      ],
      'cron_job' => [
        '#title' => t('Check cron jobs'),
        '#description' => t('Modules like Scheduler should work properly. If you have custom cron jobs, check if your system cron is executed on startup and configured properly for your user.'),
        // @todo check if Automated Cron is enabled
      ],
      'load_monitor' => [
        '#title' => t('Monitor your server and check the server load'),
        '#description' => t('Configure your monitoring, optionally use a service like New Relic. Will your server support peaks?'),
        'new_relic' => [
          '#text' => t("New Relic"),
          '#url' => Url::fromUri('https://newrelic.com/'),
        ],
      ],
      'reverse_proxy' => [
        '#title' => t('Reverse proxy'),
        '#description' => t('If your production server uses a proxy or load balancer, configure it in your settings.php.'),
      ],
    ];

    // Basic SEO.
    $sections['seo'] = [
      '#title' => t('SEO'),
      '#description' => '<h2>' . t('Basic SEO') . '</h2><p>' . t('For an extended list, use @seo_checklist_link.',
          ['@seo_checklist_link' => $this->getProjectLink('seo_checklist')]) . '</p>',
      'url_rewriting' => [
        '#title' => t('URL rewriting'),
        '#description' => t('Is URL rewriting enabled and @pathauto_link configured?', [
          '@pathauto_link' => $this->getProjectLink('pathauto'),
        ]),
      ],
      'not_found' => [
        '#title' => t("Review 404 errors and redirect 301 legacy URL's"),
        '#description' => t('Consider using the @redirect_link module.',
          [
            '@redirect_link' => $this->getProjectLink('redirect'),
          ]),
        'path' => [
          '#text' => t("Top 'page not found' errors"),
          '#url' => Url::fromUserInput('/admin/reports/page-not-found'),
        ],
      ],
      'htaccess' => [
        '#title' => t('Review your .htaccess'),
        '#description' => t('There should be a single accessible URL for your site, redirect non www prefix to www (or the opposite) and http to https.'),
      ],
      'robots' => [
        '#title' => t('Review your robots.txt'),
        '#description' => t('Especially if some paths should be excluded.'),
      ],
      'sitemap' => [
        '#title' => t('Sitemap'),
        '#description' => t('Configure your sitemap.xml with a module like Simple Sitemap.'),
        'simple_sitemap' => [
          '#text' => t('Simple Sitemap module'),
          '#url' => Url::fromUri('https://drupal.org/project/simple_sitemap'),
        ],
      ],
      'submit_url' => [
        '#title' => t('Submit the URL'),
        '#description' => t('For new sites only.'),
        'google_submit' => [
          '#text' => t('Submit URL to Google'),
          '#url' => Url::fromUri('https://www.google.com/webmasters/tools/submit-url'),
        ],
      ],
    ];

    // Legal.
    $sections['legal'] = [
      '#title' => t('Legal'),
      '#description' => '<h2>' . t('Legal aspects') . '</h2>',
      'cookie_control' => [
        '#title' => t('Cookie compliance with regulations'),
        '#description' => t('Install a cookie validation module and provide explanation about cookie usage.'),
        'project' => [
          '#text' => t('Cookie control module'),
          '#url' => Url::fromUri('https://www.drupal.org/project/cookiecontrol'),
        ],
      ],
      'conditions' => [
        '#title' => t('Privacy policy and general conditions'),
        '#description' => t('Provide also extra legal information (delivery, cancellation, ...) for commerce use cases.'),
      ],
      'gdpr' => [
        '#title' => t('General Data Protection Regulation'),
        '#description' => t("Regularly check this site's compliance with the upcoming changes to the European privacy regulations (GDPR) and continuously adjust your organization's internal data handling processes regarding the new specifications."),
        'project' => [
          '#text' => t('GDPR module'),
          '#url' => Url::fromUri('https://drupal.org/project/gdpr'),
        ],
      ],
    ];

    // Project documentation.
    $sections['documentation'] = [
      '#title' => t('Project documentation'),
      '#description' => '<h2>' . t('Documentation related to the persona') . '</h2>',
      'author_documentation' => [
        '#title' => t('Author documentation'),
        '#description' => t('Leave Drupal and custom use cases documentation to the authors, accessible from the Help section.'),
      ],
      'developer_documentation' => [
        '#title' => t('Developer onboarding'),
        '#description' => t('Create a developer onboarding documentation at the root of your repo (README), provide wiki and UML diagrams.'),
      ],
    ];

    return $sections;
  }

  /**
   * {@inheritdoc}
   */
  public function getSectionTitles(array $sections) {
    $result = [];
    foreach ($this->getAvailableSections() as $sectionKey => $sectionTitle) {
      if (in_array($sectionKey, $sections)) {
        $result[] = $sectionTitle;
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function clearItems(array $sections) {
    $checklistConfig = $this->config->getEditable('checklistapi.progress.' . ProductionChecklistInterface::CHECKLIST_ID);
    $savedProgress = $checklistConfig->get(ChecklistapiChecklist::PROGRESS_CONFIG_KEY);
    $deletedItems = [];
    $amountItemsDeleted = 0;
    if (isset($savedProgress['#items'])) {
      foreach ($this->getAvailableSectionsItems() as $sectionKey => $sectionItems) {
        if (in_array($sectionKey, $sections) && $sections[$sectionKey] === 0) {
          foreach ($sectionItems as $itemKey => $itemValue) {
            if (array_key_exists($itemKey, $savedProgress['#items'])) {
              $deletedItems[] = $itemValue['#title'];
              unset($savedProgress['#items'][$itemKey]);
              ++$amountItemsDeleted;
            }
          }
        }
      }
      $savedProgress['#completed_items'] -= $amountItemsDeleted;
      $checklistConfig->set(ChecklistapiChecklist::PROGRESS_CONFIG_KEY, $savedProgress);
      $checklistConfig->save();
    }
    return $deletedItems;
  }

  /**
   * {@inheritdoc}
   */
  public function isModuleInstalled($module) {
    return $this->moduleHandler->moduleExists($module);
  }

  /**
   * {@inheritdoc}
   */
  public function isSiteMultilingual() {
    // @todo dependency injection
    /** @var \Drupal\Core\Language\LanguageManagerInterface $languageManager */
    $languageManager = \Drupal::service('language_manager');
    return $languageManager->isMultilingual();
  }

  /**
   * {@inheritdoc}
   */
  public function getProjectLink($project) {
    $uri = 'https://drupal.org/project/' . $project;
    $projectName = str_replace('_', ' ', $project);
    $projectName = ucwords($projectName);
    $url = Url::fromUri($uri);
    $link = Link::fromTextAndUrl($projectName, $url);
    $link = $link->toRenderable();
    return $this->renderer->renderRoot($link);
  }

  /**
   * {@inheritdoc}
   */
  public function getProjectStatusLink($project, $should_install = TRUE) {
    // @todo improve UI, with should install hint.
    $status = t('Is *not* installed');
    // @todo check if the project is a module, a theme or a distro.
    if ($this->isModuleInstalled($project)) {
      $status = t('Is installed');
    }
    $build = [
      '#theme' => 'project_status_link',
      '#link' => $this->getProjectLink($project),
      '#status' => $status,
    ];
    return $this->renderer->renderRoot($build);
  }

  /**
   * {@inheritdoc}
   */
  public function getProjectsListStatusLink(array $projects, $should_install = TRUE) {
    $items = [];
    foreach ($projects as $project) {
      $items[] = $this->getProjectStatusLink($project, $should_install);
    }
    $build['status-link-list'] = [
      '#theme' => 'item_list',
      '#items' => $items,
      '#type' => 'ul',
    ];
    return $this->renderer->renderRoot($build);
  }

  /**
   * {@inheritdoc}
   */
  public function getAntiSpamStatusLink() {
    $projects = ['honeypot', 'captcha', 'recaptcha'];
    return $this->getProjectsListStatusLink($projects);
  }

  /**
   * {@inheritdoc}
   */
  public function getDevelopmentModulesStatusLink() {
    $projects = ['devel', 'coder'];
    return $this->getProjectsListStatusLink($projects, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableUpdates($type = 'security') {
    $build = [];
    if ($this->isModuleInstalled('update')) {
      $available = update_get_available(TRUE);
      $this->moduleHandler->loadInclude('update', 'compare.inc');
      $build['#data'] = update_calculate_project_data($available);
    }
    return $this->renderer->renderRoot($build);
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableSecurityUpdatesAmount() {
    $result = 0;
    // @todo implement
    // $updates = $this->getAvailableUpdates('security');
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getSecurityUpdatesChecklistArray() {
    if ($this->isModuleInstalled('update')) {
      return [
        '#title' => t('Check available updates'),
        'path' => [
          '#text' => t('Available updates'),
          '#url' => Url::fromRoute('update.status'),
        ],
      ];
    }
    else {
      return [
        '#title' => t('Check available updates'),
        '#description' => t('Update notifications are not enabled. It is <strong>highly recommended</strong> that you enable the Update Manager module from the <a href=":module">module administration page</a> in order to stay up-to-date on new releases. For more information, <a href=":update">Update status handbook page</a>.', [
          ':update' => 'https://www.drupal.org/documentation/modules/update',
          ':module' => Url::fromRoute('system.modules_list')->toString(),
        ]),
        'path' => $this->getModulesPageTextUrl(),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getModulesPageLink() {
    $route = 'system.modules_list';
    $link = Link::createFromRoute(t('Modules'), $route);
    $link = $link->toRenderable();
    return $this->renderer->renderRoot($link);
  }

  /**
   * {@inheritdoc}
   */
  public function getModulesPageTextUrl() {
    return [
      '#text' => t('Modules'),
      '#url' => Url::fromRoute('system.modules_list'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getModulesUninstallPageTextUrl() {
    return [
      '#text' => t('Uninstall modules'),
      '#url' => Url::fromRoute('system.modules_uninstall'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldsFromType($type) {
    $fields = [];
    try {
      $fields = $this->entityTypeManager->getStorage('field_storage_config')
        ->loadByProperties(['type' => 'email']);
    }
    catch (InvalidPluginDefinitionException $exception) {
      // @todo use messenger (available >= 8.5.0)
      drupal_set_message($exception->getMessage(), 'error');
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmailObfuscationDescription() {
    $output = '';
    $output .= t('Are the email addresses protected against bots harvesting? Email addresses can be present in fields, WYSIWYG, Twig.');
    // @todo get email fields then report usage and review formatter.
    // $fields = $this->getFieldsFromType('email');
    return $output;
  }

}
