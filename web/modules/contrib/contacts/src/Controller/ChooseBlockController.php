<?php

namespace Drupal\contacts\Controller;

use Drupal\contacts\ContactsTabManagerInterface;
use Drupal\contacts\Entity\ContactTab;
use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller to choose a new block.
 *
 * @internal
 */
class ChooseBlockController implements ContainerInjectionInterface {

  use AjaxHelperTrait;

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * Contact tab manager.
   *
   * @var \Drupal\contacts\ContactsTabManagerInterface
   */
  protected $tabManager;

  /**
   * ChooseBlockController constructor.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   * @param \Drupal\contacts\ContactsTabManagerInterface $tab_manager
   *   Contact tab manager.
   */
  public function __construct(BlockManagerInterface $block_manager, ContactsTabManagerInterface $tab_manager) {
    $this->blockManager = $block_manager;
    $this->tabManager = $tab_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('contacts.tab_manager')
    );
  }

  /**
   * Provides the UI for choosing a new block.
   *
   * @param \Drupal\contacts\Entity\ContactTab $tab
   *   The tab to add the block to.
   * @param string $region
   *   The region the block is going in.
   *
   * @return array
   *   A render array.
   */
  public function build(ContactTab $tab, $region) {
    $build['#type'] = 'container';
    $build['#attributes']['class'][] = 'block-categories';

    $contexts = $this->tabManager->getContexts($tab);
    $definitions = $this->blockManager->getDefinitionsForContexts($contexts);
    foreach ($this->blockManager->getGroupedDefinitions($definitions) as $category => $blocks) {
      if (!in_array($category, ['Dashboard Blocks', 'Lists (Views)'])) {
        continue;
      }

      $build[$category]['#type'] = 'details';
      $build[$category]['#open'] = TRUE;
      $build[$category]['#title'] = $category;
      $build[$category]['links'] = [
        '#theme' => 'links',
      ];
      foreach ($blocks as $block_id => $block) {
        $link = [
          'title' => $block['admin_label'],
          'url' => Url::fromRoute('contacts.manage.off_canvas_add',
            [
              'tab' => $tab->id(),
              'region' => $region,
              'plugin_id' => $block_id,
            ]
          ),
        ];
        if ($this->isAjax()) {
          $link['attributes']['class'][] = 'use-ajax';
          $link['attributes']['data-dialog-type'][] = 'dialog';
          $link['attributes']['data-dialog-renderer'][] = 'off_canvas';
        }
        $build[$category]['links']['#links'][] = $link;
      }
    }
    return $build;
  }

}
