<?php

namespace Drupal\formblock\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\Entity\NodeType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;

/**
 * Provides a block for node forms.
 *
 * @Block(
 *   id = "formblock_node",
 *   admin_label = @Translation("Content form"),
 *   category = @Translation("Forms")
 * )
 *
 * Note that we set module to node so that blocks will be disabled correctly
 * when the module is disabled.
 */
class NodeFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface.
   */
  protected $entityFormBuilder;

  /**
   * Constructs a new NodeFormBlock plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entityFormBuilder
   *   The entity form builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, EntityFormBuilderInterface $entityFormBuilder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);

    $this->entityTypeManager = $entityTypeManager;
    $this->entityFormBuilder = $entityFormBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder')
    );
  }

  /**
   * Overrides \Drupal\block\BlockBase::settings().
   */
  public function defaultConfiguration() {
    return [
      'type' => NULL,
      'show_help' => FALSE,
    ];
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['formblock_node_type'] = [
      '#title' => $this->t('Node type'),
      '#description' => $this->t('Select the node type whose form will be shown in the block.'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => $this->getNodeTypes(),
      '#default_value' => $this->configuration['type'],
    ];
    $form['formblock_show_help'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show submission guidelines'),
      '#default_value' => $this->configuration['show_help'],
      '#description' => $this->t('Enable this option to show the submission guidelines in the block above the form.'),
    ];

    return $form;
  }

  /**
   * Get an array of node types.
   *
   * @return array
   *   An array of node types keyed by machine name.
   */
  protected function getNodeTypes() {
    $options = [];
    $types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($types as $type) {
      $options[$type->id()] = $type->label();
    }
    return $options;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['type'] = $form_state->getValue('formblock_node_type');
    $this->configuration['show_help'] = $form_state->getValue('formblock_show_help');
  }

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    $build = [];

    $node_type = NodeType::load($this->configuration['type']);

    if ($this->configuration['show_help']) {
      $build['help'] = ['#markup' => !empty($node_type->getHelp()) ? '<p>' . Xss::filterAdmin($node_type->getHelp()) . '</p>' : ''];
    }

    $node = $this->entityTypeManager->getStorage('node')->create([
      'type' => $node_type->id(),
    ]);

    $build['form'] = $this->entityFormBuilder->getForm($node);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('node');

    // NodeAccessControlHandler::createAccess() adds user.permissions
    // as a cache context to the returned AccessResult.
    /* @var $result \Drupal\Core\Access\AccessResult */
    $result = $access_control_handler->createAccess($this->configuration['type'], $account, [], TRUE);

    // Add the node type as a cache dependency.
    $node_type = $node_type = NodeType::load($this->configuration['type']);
    $result->addCacheTags($node_type->getCacheTags());

    return $result;
  }

}
