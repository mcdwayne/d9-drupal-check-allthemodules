<?php

/**
 * @file
 * Contains \Drupal\edit_ui_block\Element\EditUiBlockToolbar.
 */

namespace Drupal\edit_ui_block\Element;

use Drupal\block\BlockInterface;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;

/**
 * Provides a render element for the edit UI toolbar.
 *
 * Allow to dynamically add bloc type to website.
 *
 * @RenderElement("edit_ui_block_toolbar")
 */
class EditUiBlockToolbar extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#pre_render' => [
        [get_class($this), 'preRenderEditUiBlockToolbar'],
      ],
      '#attached' => [
        'library' => [
          'edit_ui_block/edit_ui_block.toolbar',
          'edit_ui_block/edit_ui_block.region',
          'edit_ui_block/edit_ui_block.block',
        ],
      ],
    ];
  }

  /**
   * Builds the Toolbar as a structured array ready for drupal_render().
   *
   * Since building the toolbar takes some time, it is done just prior to
   * rendering to ensure that it is built only if it will be displayed.
   *
   * @param array $element
   *   A structured array.
   *
   * @return array
   *   A renderable array.
   */
  public static function preRenderEditUiBlockToolbar(array $element) {
    // Toolbar element.
    $element['toolbar']['#type'] = 'container';
    $element['toolbar']['#attributes'] = [
      'id' => Html::getUniqueId('edit-ui-toolbar'),
      'class' => ['toolbar', 'edit-ui__toolbar', 'hidden'],
    ];
    $element['toolbar']['content'] = [];

    self::addDisabledRegionElement($element['toolbar']['content']);
    self::addBlockListElement($element['toolbar']['content']);
    self::addTabsElement($element['toolbar'], 'disabled_region');
    self::addSaveElement($element['toolbar']['content']);

    // Trash element.
    $element['trash'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['edit-ui__trash', 'js-edit-ui__trash'],
      ],
      'icon' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['edit-ui__trash-icon'],
        ],
      ],
    ];

    return $element;
  }

  /**
   * Add structured array for block list to toolbar.
   *
   * @param array $element
   *   A structured array.
   */
  public static function addBlockListElement(array &$element) {
    $block_manager = \Drupal::service('plugin.manager.block');
    $theme = \Drupal::theme()->getActiveTheme()->getName();
    $id = Html::getUniqueId('edit-ui-block-list');

    $element['block_list']['#type'] = 'container';
    $element['block_list']['#attributes'] = [
      'title' => t('Place blocks'),
      'id' => $id,
      'class' => ['edit-ui__block-list', 'js-edit-ui__tabs__content'],
    ];

    $element['block_list']['filter'] = [
      '#type' => 'search',
      '#title' => t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => t('Filter by block name'),
      '#attributes' => [
        'class' => ['js-edit-ui__filter'],
        'data-element' => '.edit-ui__block-list .edit-ui__block',
        'title' => t('Enter a part of the block name to filter by.'),
      ],
    ];

    $element['block_list']['list'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['edit-ui__toolbar__region'],
      ],
    ];

    // Only add blocks which work without any available context.
    $definitions = $block_manager->getDefinitionsForContexts();
    // Order by category, and then by admin label.
    $definitions = $block_manager->getSortedDefinitions($definitions);

    foreach ($definitions as $plugin_id => $plugin_definition) {
      $block_name = $plugin_definition['admin_label'];
      $element['block_list']['list'][$plugin_id] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'edit-ui__block',
            'js-edit-ui__add-block',
          ],
        ],
        'link' => [
          '#type' => 'link',
          '#title' => $plugin_definition['category'] . ' : ' . $block_name,
          '#url' => Url::fromRoute('block.admin_add', ['plugin_id' => $plugin_id, 'theme' => $theme]),
          '#attributes' => [
            'class' => [
              'edit-ui__toolbar__link',
              'js-edit-ui__add-block__link',
            ],
          ],
        ],
      ];
    }
  }

  /**
   * Add structured array for disabled region to toolbar.
   *
   * @param array $element
   *   A structured array.
   */
  public static function addDisabledRegionElement(array &$element) {
    $theme = \Drupal::theme()->getActiveTheme()->getName();
    $id = Html::getUniqueId('edit-ui-disabled-region');

    $element['disabled_region']['#type'] = 'container';
    $element['disabled_region']['#attributes'] = [
      'title' => t('Disabled blocks'),
      'id' => $id,
      'class' => ['edit-ui__disabled-region', 'js-edit-ui__tabs__content'],
    ];

    $element['disabled_region']['filter'] = [
      '#type' => 'search',
      '#title' => t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => t('Filter by block name'),
      '#attributes' => [
        'class' => ['js-edit-ui__filter'],
        'data-element' => '.edit-ui__disabled-region .edit-ui__block',
        'title' => t('Enter a part of the block name to filter by.'),
      ],
    ];

    $element['disabled_region']['list'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['js-edit-ui__region', 'edit-ui__toolbar__region'],
        'data-edit-ui-region' => BlockInterface::BLOCK_REGION_NONE,
      ],
    ];

    $element['disabled_region']['list']['edit_ui_block_region_block'] = [
      '#type' => 'container',
      '#weight' => -99999,
      '#attributes' => [
        'class' => ['edit-ui__region-block', 'js-edit-ui__region-block'],
      ],
    ];

    // Get disabled blocks for current theme.
    $blocks = \Drupal::entityTypeManager()
      ->getStorage('block')
      ->loadByProperties(array(
        'theme' => $theme,
        'region' => BlockInterface::BLOCK_REGION_NONE
      ));

    foreach ($blocks as $id => $block) {
      $element['disabled_region']['list'][$id] = [
        '#type' => 'container',
        '#weight' => $block->getWeight(),
        '#attributes' => [
          'id' => Html::getId('block-' . $id),
        ],
        'link' => [
          '#type' => 'link',
          '#title' => $block->label(),
          '#url' => Url::fromRoute('edit_ui.block.patch', ['block_id' => $id]),
          '#attributes' => [
            'class' => [
              'edit-ui__toolbar__link',
            ],
          ],
        ],
      ];
    }

    $element['disabled_region']['list']['edit_ui_block_region_placeholder'] = [
      '#type' => 'container',
      '#weight' => +99999,
      '#attributes' => [
        'class' => ['edit-ui__region-placeholder'],
      ],
    ];
  }

  /**
   * Add structured array for tabs element to toolbar.
   *
   * @param array $element
   *   A structured array.
   * @param string $active_tab
   *   The active tab.
   */
  public static function addTabsElement(array &$element, $active_tab) {
    $element['tabs'] = [
      '#theme' => 'links',
      '#links' => [],
      '#weight' => -1,
      '#attributes' => [
        'class' => ['edit-ui__tabs']
      ]
    ];

    foreach ($element['content'] as $key => $value) {
      $element['tabs']['#links'][$key] = [
        'title' => $value['#attributes']['title'],
        'url' => Url::fromUri('internal:#' . $value['#attributes']['id']),
        'attributes' => [
          'class' => ['edit-ui__tabs__link', 'js-edit-ui__tabs__link'],
        ],
      ];

      if ($key == $active_tab) {
        $element['tabs']['#links'][$key]['attributes']['class'][] = 'active';
      }
      else {
        $element['content'][$key]['#attributes']['class'][] = 'hidden';
      }
    }
  }

  /**
   * Add structured array for save button to toolbar.
   *
   * @param array $element
   *   A structured array.
   */
  public static function addSaveElement(array &$element) {
    $config = \Drupal::config('edit_ui.block');
    if ($config->get('save_button')) {
      $element['toolbar']['content']['save_button'] = [
        '#type' => 'button',
        '#value' => t('Save blocks\' layout'),
        '#attributes' => [
          'class' => ['edit-ui__toolbar__button', 'js-edit-ui__toolbar__button'],
        ],
      ];
    }
  }

}
