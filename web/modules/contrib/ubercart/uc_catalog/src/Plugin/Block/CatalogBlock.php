<?php

namespace Drupal\uc_catalog\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\uc_catalog\TreeNode;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the product catalog block.
 *
 * @Block(
 *   id = "uc_catalog_block",
 *   admin_label = @Translation("Catalog")
 * )
 */
class CatalogBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a CatalogBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'link_title' => FALSE,
      'expanded' => FALSE,
      'product_count' => TRUE,
      'label_display' => BlockPluginInterface::BLOCK_LABEL_VISIBLE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'view catalog');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['link_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make the block title a link to the top-level catalog page.'),
      '#default_value' => $this->configuration['link_title'],
    ];
    $form['expanded'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always expand categories.'),
      '#default_value' => $this->configuration['expanded'],
    ];
    $form['product_count'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display product counts.'),
      '#default_value' => $this->configuration['product_count'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['link_title'] = $form_state->getValue('link_title');
    $this->configuration['expanded'] = $form_state->getValue('expanded');
    $this->configuration['product_count'] = $form_state->getValue('product_count');

    // @todo Remove this code when catalog block theming is fully converted.
    // Theme function should use block configuration, not uc_catalog.settings.
    $catalog_config = $this->configFactory->getEditable('uc_catalog.settings');
    $catalog_config
      ->set('expand_categories', $form_state->getValue('expanded'))
      ->set('block_nodecount', $form_state->getValue('product_count'))
      ->set('block_title_link', $form_state->getValue('link_title'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get the vocabulary tree information.
    $vid = $this->configFactory->get('uc_catalog.settings')->get('vocabulary');
    $tree = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vid);

    // Then convert it into an actual tree structure.
    $seq = 0;
    $menu_tree = new TreeNode();
    foreach ($tree as $knot) {
      $seq++;
      $knot->sequence = $seq;
      $knothole = new TreeNode($knot);
      // Begin at the root of the tree and find the proper place.
      $menu_tree->addChild($knothole);
    }

    // @todo Theme function should use block configuration, passed here,
    // not uc_catalog.settings taken from \Drupal::config().
    $build['content'] = [
      '#theme' => 'uc_catalog_block',
      '#menu_tree' => $menu_tree,
    ];

    $build['#attached']['library'][] = 'uc_catalog/uc_catalog.styles';

    return $build;
  }

}
