<?php

/**
 * @file
 * Contains \Drupal\edit_ui\Ajax\AddBlockCommand.
 */

namespace Drupal\edit_ui\Ajax;

use Drupal\Core\Ajax\CommandInterface;
use Drupal\Core\Ajax\CommandWithAttachedAssetsTrait;
use Drupal\block\Entity\Block;
use Drupal\Component\Utility\Html;

/**
 * Provides an AJAX command for showing the messages.
 */
class AddBlockCommand implements CommandInterface {

  use CommandWithAttachedAssetsTrait;

  /**
   * The block entity.
   *
   * @var Drupal\block\Entity\Block
   */
  protected $block;

  /**
   * The renderable content.
   *
   * @var array
   */
  protected $content = array();

  /**
   * Constructs an InsertCommand object.
   */
  public function __construct(Block $block) {
    $this->block = $block;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    $entity_manager = \Drupal::entityTypeManager();

    if ($this->block->access('view')) {
      $this->content = $entity_manager->getViewBuilder($this->block->getEntityTypeId())->view($this->block);
    }
    else {
      $this->content = '';
    }

    $plugin_definition = $this->block->getPlugin()->getPluginDefinition();
    return array(
      'command'   => 'editUiAddNewBlock',
      'id'        => $this->block->getOriginalId(),
      'plugin_id' => $this->block->getPluginId(),
      'region'    => $this->block->getRegion(),
      'weight'    => $this->block->getWeight(),
      'label'     => $this->block->label(),
      'status'    => $this->block->status(),
      'html_id'   => Html::getId('block-' . $this->block->getOriginalId()),
      'provider'  => $plugin_definition['provider'],
      'content'   => $this->getRenderedContent(),
    );
  }

}
