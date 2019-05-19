<?php

/**
 * @file
 * Contains \Drupal\taxonews\Plugin\Block\TaxonewsBlockBlock.
 */

namespace Drupal\taxonews\Plugin\Block;

use Drupal\Component\Annotation\Block;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonews\Taxonews;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to change your pants.
 *
 * @Block(
 *   id = "taxonews_block",
 *   admin_label = @Translation("Taxonews"),
 *   category = @Translation("Taxonomy"),
 *   deriver = "Drupal\taxonews\Plugin\Derivative\TaxonewsBlock"
 * )
 */
class TaxonewsBlockBlock extends BlockBase implements ContainerFactoryPluginInterface {

  const LIMIT = 5;
  const MODE = 'taxonews';

  /**
   * The Taxonews manager service.
   *
   * @var \Drupal\taxonews\Taxonews
   */
  protected $taxonews;

  /**
   * The block manager service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The account service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The URL generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructs a new TaxonewsBlockBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Block\BlockManagerInterface
   *   The Plugin Block Manager.
   * @param \Drupal\taxonews\Taxonews $taxonews
   *   The Taxonews service
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The Module Handler.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which view access should be checked.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator service
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Taxonews $taxonews, BlockManagerInterface $block_manager, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, AccountInterface $account, UrlGeneratorInterface $url_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->taxonews = $taxonews;
    $this->blockManager = $block_manager;
    $this->entityManager = $entity_manager;
    $this->moduleHandler = $module_handler;
    $this->account = $account;
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['taxonews'] = $this->taxonews->blockConfigure($this->getPluginId());
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    dsm($form_state->getValues(), __METHOD__);
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    dsm($form_state->getValues(), __METHOD__);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('taxonews.manager'),
      $container->get('plugin.manager.block'),
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('url_generator')
    );
  }

  /**
   * Return the list of nodes for the chosen term id.
   *
   * This method currently only works on SQL with
   * taxonomy.settings:maintain_index_table = TRUE
   *
   * TODO: use an entityQuery() to support other environments.
   *
   * @param int $tid
   */
  public function getNodesByTid($tid) {
    $node_ids = taxonomy_select_nodes($tid, FALSE, static::LIMIT);
    $nodes = entity_load_multiple('node', $node_ids);
    return $nodes;
  }

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    list($plugin, $vid, $tid) = explode(':', $this->getPluginId());
    $nodes = $this->getNodesByTid($tid);

    $ret = array(
      '#theme' => 'taxonews_list',
      '#nodes' => $nodes,
    );
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'cache' => array(
        'max-age' => Cache::PERMANENT,
      ),
    );
  }

}
