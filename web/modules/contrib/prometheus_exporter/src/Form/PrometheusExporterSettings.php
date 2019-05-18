<?php

namespace Drupal\prometheus_exporter\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\prometheus_exporter\MetricsCollectorPluginCollection;
use Drupal\prometheus_exporter\MetricsCollectorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for managing Prometheus Exporter settings.
 */
class PrometheusExporterSettings extends FormBase {

  /**
   * The plugins.
   *
   * @var \Drupal\prometheus_exporter\MetricsCollectorPluginCollection
   */
  protected $plugins;

  /**
   * PrometheusExporterSettings constructor.
   *
   * @param \Drupal\prometheus_exporter\MetricsCollectorPluginManager $pluginManager
   *   The plugin manager.
   */
  public function __construct(MetricsCollectorPluginManager $pluginManager) {
    $collectors = $this->config('prometheus_exporter.settings')->get('collectors') ?: [];
    $definitions = NestedArray::mergeDeep($pluginManager->getDefinitions(), $collectors);
    $this->plugins = new MetricsCollectorPluginCollection($pluginManager, $definitions);
    $this->plugins->sort();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.metrics_collector')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'prometheus_exporter.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;
    // Check enabled.
    $form['collectors']['enabled'] = [
      '#type' => 'item',
      '#title' => $this->t('Enabled collectors'),
      '#prefix' => '<div id="collectors-enabled-wrapper">',
      '#suffix' => '</div>',
      // This item is used as a pure wrapping container with heading. Ignore its
      // value, since 'collectors' should only contain check definitions.
      // See https://www.drupal.org/node/1829202.
      '#input' => FALSE,
    ];
    // Check order (tabledrag).
    $form['collectors']['order'] = [
      '#type' => 'table',
      '#attributes' => ['id' => 'check-order'],
      '#title' => $this->t('Metrics Collector order'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'check-order-weight',
        ],
      ],
      '#tree' => FALSE,
      '#input' => FALSE,
      '#theme_wrappers' => ['form_element'],
    ];
    // Check settings.
    $form['collection_settings'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Metrics Collector settings'),
    ];

    /** @var \Drupal\prometheus_exporter\Plugin\MetricsCollectorInterface $plugin */
    foreach ($this->plugins as $plugin_id => $plugin) {
      if (!$plugin->applies()) {
        $this->logger('prometheus_exporter')->info("Skipping plugin $plugin_id");
        continue;
      }
      $form['collectors']['enabled'][$plugin_id] = [
        '#type' => 'checkbox',
        '#title' => $plugin->getLabel(),
        '#default_value' => $plugin->isEnabled(),
        '#parents' => ['collectors', $plugin_id, 'enabled'],
        '#description' => $plugin->getDescription(),
        '#weight' => $plugin->getWeight(),
      ];

      $form['collectors']['order'][$plugin_id]['#attributes']['class'][] = 'draggable';
      $form['collectors']['order'][$plugin_id]['#weight'] = $plugin->getWeight();
      $form['collectors']['order'][$plugin_id]['collection'] = [
        '#markup' => $plugin->getLabel(),
      ];
      $form['collectors']['order'][$plugin_id]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $plugin->getLabel()]),
        '#title_display' => 'invisible',
        '#delta' => 50,
        '#default_value' => $plugin->getWeight(),
        '#parents' => ['collectors', $plugin_id, 'weight'],
        '#attributes' => ['class' => ['check-order-weight']],
      ];
      // Retrieve the settings form of the plugin.
      $settings_form = [
        '#parents' => ['collectors', $plugin_id, 'settings'],
        '#tree' => TRUE,
      ];
      $settings_form = $plugin->settingsForm($settings_form, $form_state);
      if (!empty($settings_form)) {
        $form['collectors']['settings'][$plugin_id] = [
          '#type' => 'details',
          '#title' => $plugin->getLabel(),
          '#open' => TRUE,
          '#weight' => $plugin->getWeight(),
          '#parents' => ['collectors', $plugin_id, 'settings'],
          '#group' => 'collectors_settings',
        ];
        $form['collectors']['settings'][$plugin_id] += $settings_form;
      }
    }

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save configuration'),
        '#button_type' => 'primary',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('prometheus_exporter.settings');
    $collectors = [];
    foreach ($form_state->getValue('collectors') as $plugin_id => $collector) {
      $plugin = $this->plugins->get($plugin_id);
      $plugin->setConfiguration($collector);
      $collectors[$plugin_id] = $plugin->getConfiguration();
    }
    $config->set('collectors', $collectors)->save();
  }

}
