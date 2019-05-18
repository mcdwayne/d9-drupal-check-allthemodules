<?php

/**
 * @file
 * Contains \Drupal\block_page\Form\PageVariantAddBlockForm.
 */

namespace Drupal\block_page\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for adding a block plugin to a page variant.
 */
class PageVariantAddBlockForm extends PageVariantConfigureBlockFormBase {

  /**
   * The block manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $blockManager;

  /**
   * Constructs a new PageVariantFormBase.
   */
  public function __construct(PluginManagerInterface $block_manager) {
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_page_page_variant_add_block_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareBlock($plugin_id) {
    $block = $this->blockManager->createInstance($plugin_id);
    $block_id = $this->pageVariant->addBlock($block->getConfiguration());
    return $this->pageVariant->getBlock($block_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function submitText() {
    return $this->t('Add block');
  }

}
