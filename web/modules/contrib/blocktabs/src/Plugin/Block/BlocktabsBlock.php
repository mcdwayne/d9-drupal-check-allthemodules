<?php

namespace Drupal\blocktabs\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a blocktabs block.
 *
 * @Block(
 *   id = "blocktabs_block",
 *   admin_label = @Translation("blocktabs block"),
 *   category = @Translation("blocktabs"),
 *   deriver = "Drupal\blocktabs\Plugin\Derivative\BlocktabsBlock"
 * )
 */
class BlocktabsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Plugin Block Manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The menu link tree service.
   *
   * @var \Drupal\blocktabs\BlocktabsInterface
   */
  protected $blocktabs;

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * Constructs a new SystemMenuBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlockManagerInterface $block_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blockManager = $block_manager;
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
      $container->get('plugin.manager.block'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * Overrides \Drupal\Core\Block\BlockBase::blockForm().
   *
   * Adds body and description fields to the block configuration form.
   */
  public function blockForm($form, FormStateInterface $form_state) {

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $blocktabs_entity = $this->getEntity();
    $build['block'] = [
      '#theme' => 'blocktabs',
      '#blocktabs' => $blocktabs_entity,
    ];

    return $build;
  }

  /**
   * Loads the block content entity of the block.
   *
   * @return \Drupal\block_content\BlockContentInterface|null
   *   The block content entity.
   */
  protected function getEntity() {
    $id = $this->getDerivativeId();

    if (!isset($this->blocktabs)) {
      $this->blocktabs = $this->entityTypeManager->getStorage('blocktabs')->load($id);
    }
    return $this->blocktabs;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return $this->getEntity()->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->getEntity()->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->getEntity()->getCacheMaxAge();
  }

}
