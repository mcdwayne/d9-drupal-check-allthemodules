<?php

/**
 * @file
 * Contains \Drupal\mefibs\Plugin\Block\MefibsExposedFilterBlock.
 */

namespace Drupal\mefibs\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\views\Plugin\Block\ViewsBlockBase;

/**
 * Provides an extra 'Views Exposed Filter' block.
 *
 * @Block(
 *   id = "mefibs_exposed_filter_block",
 *   admin_label = @Translation("Mefibs Exposed Filter Block"),
 *   category = @Translation("Views"),
 *   derivative = "Drupal\mefibs\Plugin\Derivative\MefibsExposedFilterBlock"
 * )
 */
class MefibsExposedFilterBlock extends ViewsBlockBase {

  /**
   * The internal block id for a mefibs block.
   *
   * @var string
   */
  protected $block_id;

  /**
   * Constructs a \Drupal\views\Plugin\Block\ViewsBlockBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\views\ViewExecutableFactory $executable_factory
   *   The view executable factory.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage_controller
   *   The views storage controller.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ViewExecutableFactory $executable_factory, EntityStorageControllerInterface $storage_controller, AccountInterface $user) {
    $this->pluginId = $plugin_id;
    $delta = $this->getDerivativeId();
    list($name, $this->displayID, $block_id) = explode('-', $delta, 3);
    // Load the view.
    $view = $storage_controller->load($name);
    $this->view = $executable_factory->get($view);
    $this->displaySet = $this->view->setDisplay($this->displayID);
    $this->block_id = $block_id;
    $this->user = $user;

    // Don't call parent here because we already have replicated appropriate
    // logic from ViewsBlockBase::__construct.
    BlockBase::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $display = $this->view->displayHandlers->get($this->displayID);
    $output = $display->extender['mefibs']->renderExposedForm($this->block_id);

    // Before returning the block output, convert it to a renderable array with
    // contextual links.
    // $this->addContextualLinks($output, 'exposed_filter');
    return $output;
  }
  
}
