<?php

namespace Drupal\prefetcher\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\prefetcher\PrefetcherCrawlerManager;

/**
 * Class PrefetcherConfigForm.
 *
 * @package Drupal\prefetcher\Form
 */
class PrefetcherConfigForm extends ConfigFormBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The prefetcher crawler manager.
   *
   * @var \Drupal\prefetcher\PrefetcherCrawlerManager
   */
  protected $crawlerManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('form_builder'),
      $container->get('plugin.manager.prefetcher_crawler')
    );
  }

  /**
   * Constructs a PrefetcherConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\prefetcher\PrefetcherCrawlerManager $crawler_manager
   *   The prefetcher crawler manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FormBuilderInterface $form_builder, PrefetcherCrawlerManager $crawler_manager) {
    parent::__construct($config_factory);
    $this->formBuilder = $form_builder;
    $this->crawlerManager = $crawler_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'prefetcher.settings',
    ];
  }

  /**
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\Config
   */
  public function getConfig() {
    return $this->config('prefetcher.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'prefetcher_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('prefetcher.settings');

    $form['#tree'] = TRUE;

    $form['general'] = [
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#title' => $this->t('Global settings'),
    ];
    $form['general']['cron_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum URLs to crawl on each cron run'),
      '#description' => $this->t("Set to 0 if you don't want Cron to run the prefetcher."),
      '#default_value' => $config->get('cron_limit'),
      '#parents' => ['cron_limit'],
    ];
    $form['general']['retry_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum amount of retries for any errors given for http requests status >= 400'),
      '#description' => $this->t("Set to 0 if you don't want this feature."),
      '#default_value' => $config->get('retry_threshold'),
      '#parents' => ['retry_threshold'],
    ];
    $form['general']['expiry'] = [
      '#type' => 'number',
      '#title' => $this->t('Uris with maximum expiry for prefetching'),
      '#description' => $this->t('Include uris to prefetch with the given maximum time in seconds until expiry.'),
      '#default_value' => $config->get('expiry'),
      '#field_suffix' => $this->t('Seconds'),
      '#parents' => ['expiry'],
    ];

    if (!$form_state->isRebuilding()) {
      $form_state->setValue('crawler', (array) $config->get('crawler'));
    }
    $plugins = $this->crawlerManager->getDefinitions();
    $default_crawler = $this->crawlerManager->getDefaultCrawlerId();
    $selected_crawler_id = $form_state->getValue(['crawler', 'plugin_id'], $default_crawler);
    $selected_crawler = !empty($selected_crawler_id) ? $this->crawlerManager->createInstance($selected_crawler_id) : NULL;
    $crawler_options = [];
    foreach ($plugins as $definition) {
      $crawler_options[$definition['id']] = $definition['label'];
    }
    $crawler_edit_id = 'edit-crawler-erth45g';
    $crawler_settings_id = 'edit-crawler-settings-rthe35';
    $form['crawler'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Crawler settings'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#prefix' => '<div id="' . $crawler_edit_id . '">',
      '#suffix' => '</div>',
    ];
    $form['crawler']['plugin_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Crawler implementation'),
      '#options' => $crawler_options,
      '#required' => TRUE,
      '#empty_value' => '',
      '#default_value' => $selected_crawler_id,
      '#ajax' => [
        'callback' => [$this, 'switchCrawlerSubmit'],
        'wrapper' => $crawler_settings_id,
        'effect' => 'fade',
        'method' => 'replace',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Removing...'),
        ],
      ],
    ];
    $form['crawler']['plugin_settings'] = [
      '#prefix' => '<div id="' . $crawler_settings_id . '">',
      '#suffix' => '</div>',
      '#markup' => '',
    ];
    if ($selected_crawler) {
      unset($form['crawler']['plugin_settings']['#markup']);
      $form['crawler']['plugin_settings'] += $selected_crawler->buildConfigurationForm($form, $form_state);
    }

    if (!$form_state->isRebuilding()) {
      $form_state->setValue('hosts', (array) $config->get('hosts'));
    }
    $hosts = $form_state->getValue('hosts', []);
    $num_hosts = count($hosts);

    $hosts_edit_id = 'edit-hosts-area-dfv24g';
    $form['hosts'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Hosts'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#prefix' => '<div id="' . $hosts_edit_id . '">',
      '#suffix' => '</div>',
    ];

    foreach ($hosts as $delta => $host) {
      $id = Html::getUniqueId('edit-hosts-' . $delta);
      $settings = [
        '#type' => 'fieldset',
        '#title' => t('Host #@num', ['@num' => ($delta+1)]),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        '#weight' => $delta,
        '#attributes' => ['id' => $id],
      ];
      $settings['host'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Hostname or IP'),
        '#default_value' => $host['host'],
        '#required' => FALSE,
        '#weight' => 20,
      ];
      $settings['scheme'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Protocol scheme'),
        '#description' => $this->t('When not specified, http will be used.'),
        '#default_value' => !empty($host['scheme']) ? $host['scheme'] : 'http',
        '#required' => FALSE,
        '#weight' => 30,
      ];
      $settings['auth'] = [
        '#type' => 'fieldset',
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        '#title' => t('Basic authentication'),
        '#weight' => 50,
      ];
      $settings['auth']['use_auth'] = [
        '#type' => 'checkbox',
        '#title' => t('Enable basic authentication'),
        '#default_value' => isset($host['auth']['use_auth']) ? $host['auth']['use_auth'] : FALSE,
      ];
      $settings['auth']['username'] = [
        '#type' => 'textfield',
        '#title' => t('Username'),
        '#default_value' => !empty($host['auth']['username']) ? $host['auth']['username'] : '',
        '#states' => [
          'visible' => [
            'input[name="hosts[' . $delta . '][auth][use_auth]"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $settings['auth']['password'] = [
        '#type' => 'textfield',
        '#title' => t('Password'),
        '#states' => [
          'visible' => [
            'input[name="hosts[' . $delta . '][auth][use_auth]"]' => ['checked' => TRUE],
          ],
        ],
      ];
      if (!empty($settings['auth']['password'])) {
        $settings['auth']['password']['#description'] = t('<em>Password has been set before, type in a new one to reset it.</em>');
      }

      $settings['http_header'] = [
        '#tree' => TRUE,
        '#type' => 'fieldset',
        '#title' => $this->t('Custom HTTP headers'),
        '#description' => $this->t('These HTTP headers will be included on any crawl request. Example: <strong>X-Prefetcher: refresh</strong>'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        '#weight' => 60,
      ];
      if (!empty($host['http_header'])) {
        foreach ($host['http_header'] as $key => $header) {
          $settings['http_header'][$key] = [
            '#type' => 'textfield',
            '#title' => '',
            '#default_value' => $header,
            '#required' => FALSE,
            '#weight' => $key * 10,
          ];
        }
      }
      $header_placeholder_id = 'add-http-header-placeholder-' . $delta;
      $settings['http_header']['add_placeholder'] = [
        '#tree' => FALSE,
        '#markup' => '<div id="' . $header_placeholder_id . '"></div>',
        '#weight' => !empty($host['http_header']) ? count($host['http_header']) * 40 : 100,
      ];
      $settings['http_header']['add'] = [
        '#tree' => FALSE,
        '#parents' => ['host_buttons', $delta],
        '#type' => 'button',
        '#name' => 'add_http_header_' . $delta,
        '#value' => $this->t('Add another one'),
        '#ajax' => [
          'callback' => [$this, 'addHttpHeaderSubmit'],
          'wrapper' => $header_placeholder_id,
          'effect' => 'fade',
          'method' => 'before',
          'progress' => [
            'type' => 'throbber',
            'message' => '...',
          ],
        ],
        '#weight' => !empty($host['http_header']) ? count($host['http_header']) * 50 : 200,
      ];

      $settings['domains'] = [
        '#tree' => TRUE,
        '#type' => 'fieldset',
        '#title' => $this->t('Domains'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        '#weight' => 70,
      ];
      if (!empty($host['domains'])) {
        foreach ($host['domains'] as $key => $domain) {
          $settings['domains'][$key] = [
            '#type' => 'textfield',
            '#title' => '',
            '#default_value' => $domain,
            '#required' => FALSE,
            '#weight' => $key * 10,
          ];
        }
      }
      $domain_placeholder_id = 'add-domain-placeholder-' . $delta;
      $settings['domains']['add_placeholder'] = [
        '#tree' => FALSE,
        '#markup' => '<div id="' . $domain_placeholder_id . '"></div>',
        '#weight' => !empty($host['domains']) ? count($host['domains']) * 40 : 100,
      ];
      $settings['domains']['add'] = [
        '#tree' => FALSE,
        '#parents' => ['host_buttons', $delta],
        '#type' => 'button',
        '#name' => 'add_domain_' . $delta,
        '#value' => $this->t('Add another one'),
        '#ajax' => [
          'callback' => [$this, 'addDomainSubmit'],
          'wrapper' => $domain_placeholder_id,
          'effect' => 'fade',
          'method' => 'before',
          'progress' => [
            'type' => 'throbber',
            'message' => '...',
          ],
        ],
        '#weight' => !empty($host['domains']) ? count($host['domains']) * 50 : 200,
      ];

      $settings['remove'] = [
        '#tree' => FALSE,
        '#parents' => ['hosts', $delta],
        '#type' => 'button',
        '#name' => 'remove_item_' . $delta,
        '#value' => t('Remove this host'),
        '#ajax' => [
          'callback' => [$this, 'removeHostSubmit'],
          'wrapper' => $hosts_edit_id,
          'effect' => 'fade',
          'method' => 'replace',
          'progress' => [
            'type' => 'throbber',
            'message' => t('Removing...'),
          ],
        ],
        '#weight' => 80,
      ];

      $form['hosts'][$delta] = $settings;
    }

    $form['hosts']['add_placeholder'] = [
      '#markup' => '<div id="prefetcher-add-host"></div>',
      '#weight' => $num_hosts * 90,
    ];
    $form['hosts']['add'] = [
      '#tree' => FALSE,
      '#type' => 'button',
      '#name' => 'item_add',
      '#value' => t('Add another host'),
      '#weight' => $num_hosts * 100,
      '#ajax' => [
        'callback' => [$this, 'addHostSubmit'],
        'wrapper' => 'prefetcher-add-host',
        'effect' => 'fade',
        'method' => 'before',
        'progress' => [
          'type' => 'throbber',
          'message' => '...',
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Let the crawler plugin react on form validation.
    if ($crawler_id = $form_state->getValue(['crawler', 'plugin_id'])) {
      if ($this->crawlerManager->hasDefinition($crawler_id)) {
        $this->crawlerManager->createInstance($crawler_id)
          ->validateConfigurationForm($form, $form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Let the crawler plugin react on form submission.
    if ($crawler_id = $form_state->getValue(['crawler', 'plugin_id'])) {
      if ($this->crawlerManager->hasDefinition($crawler_id)) {
        $this->crawlerManager->createInstance($crawler_id)
          ->submitConfigurationForm($form, $form_state);
      }
    }

    $config = $this->config('prefetcher.settings');
    $config->set('cron_limit', $form_state->getValue('cron_limit'));
    $config->set('expiry', $form_state->getValue('expiry'));
    $config->set('retry_threshold', $form_state->getValue('retry_threshold'));

    $crawler = $form_state->getValue('crawler', []);
    $config->set('crawler', $crawler);

    $hosts = $form_state->getValue('hosts', []);
    $previous_hosts = $config->get('hosts');
    foreach ($hosts as $delta => $host) {
      // Keep the previously stored password in case it hasn't been reset.
      if (empty($hosts[$delta]['auth']['password'])) {
        if (!empty($previous_hosts[$delta]['auth']['password'])) {
          $password = $previous_hosts[$delta]['auth']['password'];
          $hosts[$delta]['auth']['password'] = $password;
        }
      }

      if (!empty($host['domains'])) {
        foreach ($host['domains'] as $index => $domain) {
          $hosts[$delta]['domains'][$index] = trim($domain);
          if (empty($hosts[$delta]['domains'][$index])) {
            unset($hosts[$delta]['domains'][$index]);
          }
        }
      }
      if (!empty($host['http_header'])) {
        foreach ($host['http_header'] as $index => $header) {
          $hosts[$delta]['http_header'][$index] = trim($header);
          if (empty($hosts[$delta]['http_header'][$index])) {
            unset($hosts[$delta]['http_header'][$index]);
          }
          else {
            $header = explode(':', $hosts[$delta]['http_header'][$index]);
            if (count($header) > 1) {
              $hosts[$delta]['http_header'][$index] = trim($header[0]) . ': ' . trim($header[1]);
            }
            else {
              unset($hosts[$delta]['http_header'][$index]);
            }
          }
        }
      }
    }
    $config->set('hosts', $hosts);

    $config->save();
  }

  /**
   * Submit callback to add another host.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addHostSubmit(array &$form, FormStateInterface $form_state) {
    $hosts = $form_state->getValue('hosts', []);
    $delta = count($hosts);
    $hosts[$delta] = [
      'host' => '',
      'scheme' => '',
      'auth' => ['use_auth' => FALSE, 'username' => '', 'password' => ''],
      'http_header' => [],
      'domains' => [''],
    ];
    $form_state->setValue('hosts', $hosts);
    $form = $this->formBuilder->rebuildForm($this->getFormId(), $form_state, $form);
    return $form['hosts'][$delta];
  }

  /**
   * Submit callback to remove a prefetcher host.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function removeHostSubmit(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $delta = $trigger['#parents'][1];
    $hosts = $form_state->getValue('hosts', []);
    unset($hosts[$delta]);
    $form_state->setValue('hosts', $hosts);
    $form = $this->formBuilder->rebuildForm($this->getFormId(), $form_state, $form);
    return $form['hosts'];
  }

  /**
   * Submit callback to switch the used prefetcher plugin.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function switchCrawlerSubmit(array &$form, FormStateInterface $form_state) {
    $form = $this->formBuilder->rebuildForm($this->getFormId(), $form_state, $form);
    return $form['crawler']['plugin_settings'];
  }

  /**
   * Submit callback to add another domain.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addDomainSubmit(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $delta = $trigger['#parents'][1];
    $domains = $form_state->getValue(['hosts', $delta, 'domains'], []);
    $index = count($domains);
    $domains[] = '';
    $form_state->setValue(['hosts', $delta, 'domains'], $domains);
    $form = $this->formBuilder->rebuildForm($this->getFormId(), $form_state, $form);
    return $form['hosts'][$delta]['domains'][$index];
  }

  /**
   * Submit callback to add another Http header.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addHttpHeaderSubmit(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $delta = $trigger['#parents'][1];
    $headers = $form_state->getValue(['hosts', $delta, 'http_header'], []);
    $index = count($headers);
    $headers[] = '';
    $form_state->setValue(['hosts', $delta, 'http_header'], $headers);
    $form = $this->formBuilder->rebuildForm($this->getFormId(), $form_state, $form);
    return $form['hosts'][$delta]['http_header'][$index];
  }

}
