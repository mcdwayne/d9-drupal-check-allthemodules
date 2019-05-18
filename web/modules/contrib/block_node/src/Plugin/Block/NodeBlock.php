<?php

namespace Drupal\block_node\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a 'NodeBlock' block.
 *
 * @Block(
 *   id = "node_block",
 *   admin_label = @Translation("Node block"),
 *   category = @Translation("Content")
 * )
 */
class NodeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity view builder interface.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  private $viewBuilder;

  /**
   * The node interface.
   *
   * @var \Drupal\node\NodeInterface
   */
  private $node;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a NodeBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityDisplayRepositoryInterface $entity_display_repository, EntityTypeManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->viewBuilder = $entity_manager->getViewBuilder('node');
    // If the current node should be used attempt to load it.
    if ($configuration['current']) {
      $this->node = \Drupal::routeMatch()->getParameter('node');
    }
    else {
      $this->node = isset($configuration['nid']) ? $entity_manager->getStorage('node')->load($configuration['nid']) : [];
    }
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_display.repository'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form = parent::blockForm($form, $form_state);

    // Retrieve existing configuration for this block.
    $config = $this->getConfiguration();

    $form['current'] = [
      '#title' => t('Use current node?'),
      '#description' => 'Will display the currently displayed node in the
      specified view mode. Useful for showing certain fields from the current
      node in a block. Uncheck to pick a specific node.',
      '#type' => 'checkbox',
      '#default_value' => (isset($config['current']) ? $config['current'] : TRUE),
    ];

    // Add a form field to the existing block configuration form.
    $form['nid'] = [
      '#title' => t('Node to display'),
      '#description' => t('The node you want to display'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#selection_handler' => 'default',
      '#default_value' => ((isset($config['nid']) && !empty($config['nid'])) ? Node::load($config['nid']) : NULL),
      '#states' => [
        'invisible' => [
          ':input[name="settings[current]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    // View modes.
    $options = [];
    $view_modes = $this->entityDisplayRepository->getAllViewModes();
    if (isset($view_modes['node'])) {
      foreach ($view_modes['node'] as $view_mode => $view_mode_info) {
        $options[$view_mode] = $view_mode_info['label'];
      }
    }

    $form['view_mode'] = [
      '#title' => t('View mode'),
      '#description' => t('Select the view mode you want your node to render in.'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => (isset($config['view_mode']) && !empty($config['view_mode']) ? $config['view_mode'] : 'full'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Save our custom settings when the form is submitted.
    $this->setConfigurationValue('current', $form_state->getValue('current'));
    $this->setConfigurationValue('nid', $form_state->getValue('nid'));
    $this->setConfigurationValue('view_mode', $form_state->getValue('view_mode'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $config = $this->getConfiguration();
    if (!$this->node instanceof NodeInterface) {
      return $build;
    }
    $view_mode = (isset($config['view_mode']) && !empty($config['view_mode']) ? $config['view_mode'] : 'full');
    $build = $this->viewBuilder->view($this->node, $view_mode);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account, $return_as_object = FALSE) {
    if (empty($this->node)) {
      return AccessResult::allowed();
    }
    return $this->node->access('view', NULL, TRUE);
  }

}
