<?php

namespace Drupal\healthz\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\healthz\HealthzCheckPluginCollection;
use Drupal\healthz\HealthzPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Admin form for Healthz settings.
 */
class HealthzAdminForm extends FormBase {

  /**
   * The plugin collection.
   *
   * @var \Drupal\healthz\HealthzCheckPluginCollection
   */
  protected $healthzChecksCollection;

  /**
   * HealthzController constructor.
   *
   * @param \Drupal\healthz\HealthzPluginManager $plugin_manager
   *   The plugin manager for healthz checks.
   */
  public function __construct(HealthzPluginManager $plugin_manager) {
    $definitions = NestedArray::mergeDeep($plugin_manager->getDefinitions(), $this->config('healthz.settings')->get('checks'));
    $this->healthzChecksCollection = new HealthzCheckPluginCollection($plugin_manager, $definitions);
    $this->healthzChecksCollection->sort();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.healthz')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'healthz.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'healthz/drupal.healthz.admin';

    // Check status.
    $form['checks']['status'] = [
      '#type' => 'item',
      '#title' => $this->t('Enabled checks'),
      '#prefix' => '<div id="checks-status-wrapper">',
      '#suffix' => '</div>',
      // This item is used as a pure wrapping container with heading. Ignore its
      // value, since 'checks' should only contain check definitions.
      // See https://www.drupal.org/node/1829202.
      '#input' => FALSE,
    ];
    // Check order (tabledrag).
    $form['checks']['order'] = [
      '#type' => 'table',
      '#attributes' => ['id' => 'check-order'],
      '#title' => $this->t('Healthz check order'),
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
    $form['check_settings'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Healthz check settings'),
    ];

    foreach ($this->healthzChecksCollection as $plugin_id => $plugin) {
      if (!$plugin->applies()) {
        continue;
      }
      $form['checks']['status'][$plugin_id] = [
        '#type' => 'checkbox',
        '#title' => $plugin->getLabel(),
        '#default_value' => $plugin->getStatus(),
        '#parents' => ['checks', $plugin_id, 'status'],
        '#description' => $plugin->getDescription(),
        '#weight' => $plugin->getWeight(),
      ];

      $form['checks']['order'][$plugin_id]['#attributes']['class'][] = 'draggable';
      $form['checks']['order'][$plugin_id]['#weight'] = $plugin->getWeight();
      $form['checks']['order'][$plugin_id]['check'] = [
        '#markup' => $plugin->getLabel(),
      ];
      $form['checks']['order'][$plugin_id]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $plugin->getLabel()]),
        '#title_display' => 'invisible',
        '#delta' => 50,
        '#default_value' => $plugin->getWeight(),
        '#parents' => ['checks', $plugin_id, 'weight'],
        '#attributes' => ['class' => ['check-order-weight']],
      ];
      // Retrieve the settings form of the plugin.
      $settings_form = [
        '#parents' => ['checks', $plugin_id, 'settings'],
        '#tree' => TRUE,
      ];
      $settings_form = $plugin->settingsForm($settings_form, $form_state);
      if (!empty($settings_form)) {
        $form['checks']['settings'][$plugin_id] = [
          '#type' => 'details',
          '#title' => $plugin->getLabel(),
          '#open' => TRUE,
          '#weight' => $plugin->getWeight(),
          '#parents' => ['checks', $plugin_id, 'settings'],
          '#group' => 'checks_settings',
        ];
        $form['checks']['settings'][$plugin_id] += $settings_form;
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
    $config = $this->configFactory->getEditable('healthz.settings');
    $checks = [];
    foreach ($form_state->getValue('checks') as $plugin_id => $check) {
      $plugin = $this->healthzChecksCollection->get($plugin_id);
      $plugin->setConfiguration($check);
      $checks[$plugin_id] = $plugin->getConfiguration();
    }
    $config->set('checks', $checks)->save();
  }

}
