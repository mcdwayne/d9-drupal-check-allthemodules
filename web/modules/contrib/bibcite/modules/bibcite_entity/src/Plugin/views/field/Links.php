<?php

namespace Drupal\bibcite_entity\Plugin\views\field;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to render links for bibcite entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("bibcite_links")
 */
class Links extends FieldPluginBase {

  /**
   * Link plugin manager.
   *
   * @var \Drupal\bibcite_entity\Plugin\BibciteLinkPluginInterface
   */
  protected $linkPluginManager;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PluginManagerInterface $link_plugin_manager, ConfigFactory $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->linkPluginManager = $link_plugin_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.bibcite_link'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['override_default'] = [
      'default' => FALSE,
    ];
    $options['links'] = [
      'default' => [
        'overrides' => [],
      ],
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['override_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override default links settings'),
      '#default_value' => $this->options['override_default'],
    ];

    // @todo This is a copy of bibcite_entity_reference_settings_links form. Find a way to reuse form code.
    $form['links'] = [
      '#type' => 'details',
      '#title' => $this->t('Links overrides'),
      '#states' => [
        'visible' => [
          ':input[name="options[override_default]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
      'overrides' => [
        '#type' => 'table',
        '#header' => [
          $this->t('Label'),
          $this->t('Enabled'),
          $this->t('Weight'),
        ],
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'bibcite-links-order-weight',
          ],
        ],
      ],
    ];

    $overrides_table = &$form['links']['overrides'];

    $links = isset($this->options['links']['overrides']) ? $this->options['links']['overrides'] : [];

    foreach ($this->linkPluginManager->getDefinitions() as $plugin_id => $definition) {
      $weight = !empty($links[$plugin_id]['weight']) ? (int) $links[$plugin_id]['weight'] : NULL;

      $overrides_table[$plugin_id]['#attributes']['class'][] = 'draggable';
      $overrides_table[$plugin_id]['#weight'] = $weight;

      $overrides_table[$plugin_id]['label'] = [
        '#plain_text' => $definition['label'],
      ];
      $overrides_table[$plugin_id]['enabled'] = [
        '#type' => 'checkbox',
        '#default_value' => isset($links[$plugin_id]['enabled']) ? $links[$plugin_id]['enabled'] : TRUE,
      ];
      $overrides_table[$plugin_id]['weight'] = [
        '#type' => 'weight',
        '#title' => t('Weight for @title', ['@title' => $definition['label']]),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#attributes' => [
          'class' => ['bibcite-links-order-weight'],
        ],
      ];
    }

    uasort($overrides_table, 'Drupal\Component\Utility\SortArray::sortByWeightProperty');
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // @todo Find a way to combine view handler code with extra field.

    $build['bibcite_links'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['bibcite-links'],
      ],
      'links' => [
        '#theme' => 'item_list',
        '#attributes' => [
          'class' => ['inline'],
        ],
        '#items' => [],
      ],
    ];

    $config = $this->configFactory->get('bibcite_entity.reference.settings');

    if ($this->options['override_default'] && !empty($this->options['links']['overrides'])) {
      $overrides = [
        'links' => $this->options['links']['overrides'],
      ];

      $config->setSettingsOverride($overrides);
    }

    $default_link_attributes = [
      'enabled' => TRUE,
      'weight' => 0,
    ];

    $links_config = $config->get('links');

    foreach ($this->linkPluginManager->getDefinitions() as $plugin_id => $definition) {
      $plugin_config = isset($links_config[$plugin_id]) ? $links_config[$plugin_id] : [];
      $plugin_config = $plugin_config + $default_link_attributes;

      if ($plugin_config['enabled']) {
        $instance = $this->linkPluginManager->createInstance($plugin_id);
        if ($link = $instance->build($values->_entity)) {
          $build['bibcite_links']['links']['#items'][] = $link + ['#weight' => $plugin_config['weight']];
        }
      }
    }

    uasort($build['bibcite_links']['links']['#items'], 'Drupal\Component\Utility\SortArray::sortByWeightProperty');

    return $build;
  }

}
