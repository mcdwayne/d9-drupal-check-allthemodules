<?php
/**
 * @file
 * Contains \Drupal\accessibility_tool\Plugin\Block\AccessibilityToolBlock.
 */

namespace Drupal\accessibility_tool\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use \Drupal\Core\Link;
use \Drupal\Core\Url;

/**
 * Provides a accessibility tool Block.
 *
 * @Block(
 *   id = "accessibility_tool_block",
 *   admin_label = @Translation("Accessibility tool block"),
 * )
 */
class AccessibilityToolBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $settings = \Drupal::config('accessibility_tool.settings')->get();
    $tool_color = '';
    if (!empty($settings['accessibility_tool']['tool_color'])) {
      $tool_color = $settings['accessibility_tool']['tool_color'];
    }

    // List tool buttons.
    $item_list = [
      'zoom-in' => Link::createFromRoute(
        $this->t('Zoom in'), '<front>', [], [
          'attributes' => [
            'style' => [
              "color: {$tool_color};"
            ],
            'class' => [
              'icomoon',
              'icon-plus',
              'zoom-in-btn',
            ],
            'title' => $this->t('Increase font size'),
          ],
        ]
      ),
      'zoom-out' => Link::createFromRoute(
        $this->t('Zoom out'), '<front>', [], [
          'attributes' => [
            'style' => [
              "color: {$tool_color};"
            ],
            'class' => [
              'icomoon',
              'icon-minus',
              'zoom-out-btn',
            ],
            'title' => $this->t('Decrease font size'),
          ],
        ]
      ),
      'refresh' => Link::createFromRoute(
        $this->t('Reset'), '<front>', [], [
          'attributes' => [
            'style' => [
              "color: {$tool_color};"
            ],
            'class' => [
              'icomoon',
              'icon-refresh',
              'refresh-btn',
            ],
            'title' => $this->t('Reset'),
          ],
        ]
      ),
      'contrast' => Link::createFromRoute(
        $this->t('Contrast'), '<front>', [], [
          'attributes' => [
            'style' => [
              "color: {$tool_color};"
            ],
            'class' => [
              'icomoon',
              'icon-contrast',
              'contrast-btn',
            ],
            'title' => $this->t('Colour contrast'),
          ],
        ]
      ),
    ];

    // Adding Help button.
    if (!empty($settings['accessibility_tool']['help_link'])) {
      $help_options = [
        'attributes' => [
          'style' => [
            "color: {$tool_color};"
          ],
          'class' => [
            'icomoon',
            'icon-question-mark',
            'help-btn',
          ],
          'target' => '_blank',
          'title' => $this->t('Help'),
        ]
      ];
      $url = Url::fromUri(
        $settings['accessibility_tool']['help_link'],
        $help_options
      );

      $item_list['help'] = [
        'link' => [
          '#title' => $this->t('Help'),
          '#type' => 'link',
          '#url' => $url,
        ],
        '#wrapper_attributes' => [
          'class' => ['help'],
        ]
      ];
    }

    // Position.
    $position_class = '';
    if (!empty($settings['accessibility_tool']['position'])) {
      $position_class = $settings['accessibility_tool']['position'];
    }

    return [
      '#theme' => 'item_list',
      '#type' => 'ul',
      '#items' => $item_list,
      '#attributes' => [
        'style' => [
          "background-color: {$tool_color};"
        ],
        'class' => [
          'accessibility-tool',
          $position_class,
        ],
      ],
      '#attached' => [
        'library' => ['accessibility_tool/accessibility_tool_js'],
        'drupalSettings' => [
          'accessibility_tool' => $settings['accessibility_tool'],
        ],
      ],
    ];
  }

}
