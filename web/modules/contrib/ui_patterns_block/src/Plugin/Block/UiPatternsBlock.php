<?php

namespace Drupal\ui_patterns_block\Plugin\Block;

use Drupal\ui_patterns\UiPatternsManager;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block for rendering a UI pattern preview.
 *
 * @Block(
 *   id = "pattern_block",
 *   admin_label = @Translation("Pattern block"),
 *   category = @Translation("User interface"),
 * )
 */
class UiPatternsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * UI Patterns manager.
   *
   * @var \Drupal\ui_patterns\UiPatternsManager
   */
  protected $patternsManager;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\ui_patterns\UiPatternsManager $patterns_manager
   *   UI Patterns manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UiPatternsManager $patterns_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $patterns_manager);
    $this->configuration = $configuration;
    $this->patternsManager = $patterns_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.ui_patterns')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    if (!empty($config['pattern'])) {
      return [
        '#type' => 'pattern_preview',
        '#id' => $config['pattern'],
      ];
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['pattern'] = [
      '#type' => 'select',
      '#empty_value' => '_none',
      '#title' => $this->t('Pattern'),
      '#options' => $this->patternsManager->getPatternsOptions(),
      '#default_value' => isset($config['pattern']) ? $config['pattern'] : NULL,
      '#required' => TRUE,
      '#attributes' => ['id' => 'patterns-select'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['pattern'] = $values['pattern'];
  }

}
