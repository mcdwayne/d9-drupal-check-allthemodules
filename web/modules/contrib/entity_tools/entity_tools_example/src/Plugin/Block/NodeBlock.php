<?php

namespace Drupal\entity_tools_example\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\entity_tools\EntityTools;

/**
 * Provides a 'NodeBlock' block.
 *
 * Get a single node from its title then select the desired
 * view mode (defaults to teaser).
 *
 * @Block(
 *  id = "node_block",
 *  admin_label = @Translation("Node block"),
 * )
 */
class NodeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\entity_tools\EntityTools definition.
   *
   * @var \Drupal\entity_tools\EntityTools
   */
  protected $entityTools;

  /**
   * Constructs a new NodeBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        EntityTools $entity_tools
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTools = $entity_tools;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_tools')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'node' => NULL,
      'view_mode' => 'teaser',
    ] + parent::defaultConfiguration();

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $node = NULL;
    if ($this->configuration['node'] !== NULL) {
      $node = $this->entityTools->nodeLoad($this->configuration['node']);
    }
    // @todo improve view_mode field with view modes selection
    // $viewModes = $this->entityTools->getViewModes(EntityTools::ENTITY_NODE);
    $form['node'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Node'),
      '#description' => $this->t('The node to display.'),
      '#target_type' => 'node',
      '#default_value' => $node,
      '#required' => TRUE,
      '#weight' => '1',
    ];
    $form['view_mode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('View mode'),
      '#description' => $this->t('The view mode to use for the Node.'),
      '#default_value' => $this->configuration['view_mode'],
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
      '#weight' => '2',
    ];

    // @todo add optional path

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['node'] = $form_state->getValue('node');
    $this->configuration['view_mode'] = $form_state->getValue('view_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['entity_tools_example_node'] = $this->entityTools->nodeDisplay($this->configuration['node'], $this->configuration['view_mode']);
    // For debug purpose only.
    $build['#cache']['max-age'] = 0;
    return $build;
  }

}
