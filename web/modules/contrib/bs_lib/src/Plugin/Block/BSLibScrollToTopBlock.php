<?php

namespace Drupal\bs_lib\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a 'BSLibScrollToTopBlock' block.
 *
 * @Block(
 *  id = "bs_lib_scroll_to_top_block",
 *  admin_label = @Translation("Scroll to top"),
 * )
 */
class BSLibScrollToTopBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'css_class' => '',
      'duration' => 700,
      'easing' => 'swing',
      'offset' => 300,
      'offset_opacity' => 1200,
      'position' => 'static',
      'position_fixed_disable_element' => '',
      'text' => 'To top',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text'),
      '#description' => $this->t('Text of a scroll to top element.'),
      '#maxlength' => 255,
      '#default_value' => $config['text'],
    ];
    $form['duration'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Duration'),
      '#description' => $this->t('Scroll duration in milliseconds.'),
      '#maxlength' => 255,
      '#default_value' => $config['duration'],
    ];
    $easing_functions = ['swing' => 'swing', 'linear' => 'linear'];
    foreach(['Quad', 'Cubic', 'Quart', 'Quint', 'Expo', 'Sine', 'Circ', 'Elastic', 'Bounce'] as $easing_function) {
      foreach(['easeIn', 'easeOut', 'easeInOut'] as $easing_type) {
        $easing_functions[$easing_type . $easing_function] = $easing_type . $easing_function;
      }
    }
    $form['easing'] = [
      '#type' => 'select',
      '#title' => $this->t('Easing'),
      '#description' => $this->t('Easing function used for animation.'),
      '#maxlength' => 255,
      '#default_value' => $config['easing'],
      '#options' => $easing_functions,
    ];
    $form['css_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CSS classes'),
      '#description' => $this->t('Add CSS classes to the element.'),
      '#maxlength' => 255,
      '#default_value' => $config['css_class'],
    ];
    $position_id = Html::getUniqueId('scroll-top-top-position');
    $form['position'] = [
      '#id' => $position_id,
      '#type' => 'select',
      '#title' => $this->t('Position'),
      '#options' => [
        'static' => $this->t('Static'),
        'fixed' => $this->t('Fixed'),
      ],
      '#description' => $this->t('Position of the element on the page.'),
      '#maxlength' => 255,
      '#default_value' => $config['position'],
    ];
    $form['offset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Offset'),
      '#description' => $this->t('Browser window scroll (in pixels) after which the "back to top" link is shown.'),
      '#maxlength' => 255,
      '#default_value' => $config['offset'],
      '#states' => [
        'visible' => [
          ':input[id="' . $position_id . '"]' => ['value' => 'fixed'],
        ],
      ],
    ];
    $form['offset_opacity'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Offset opacity'),
      '#description' => $this->t('Browser window scroll (in pixels) after which the "back to top" link opacity is reduced.'),
      '#maxlength' => 255,
      '#default_value' => $config['offset_opacity'],
      '#states' => [
        'visible' => [
          ':input[id="' . $position_id . '"]' => ['value' => 'fixed'],
        ],
      ],
    ];
    $form['position_fixed_disable_element'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Disable fixed for element'),
      '#description' => $this->t('CSS selector of element for which fixed positioning will be disabled when to top element reach selected parent element vertically. Leave empty to disable this feature.'),
      '#maxlength' => 255,
      '#default_value' => $config['position_fixed_disable_element'],
      '#states' => [
        'visible' => [
          ':input[id="' . $position_id . '"]' => ['value' => 'fixed'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    foreach ($this->defaultConfiguration() as $item => $value) {
      $this->configuration[$item] = $form_state->getValue($item);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    $data = [];
    $exclude_data = ['css_class', 'position', 'text'];
    foreach ($this->defaultConfiguration() as $item => $value) {
      if (!in_array($item, $exclude_data)) {
        $data['data-' . $item] = $config[$item];
      }
    }

    $library = ['bs_lib/scroll_to_top'];
    if (!empty($config['easing']) && ($config['easing'] != 'swing' || $config['easing'] != 'linear')) {
      $library[] = 'core/jquery.ui.effects.core';
    }

    // MDN suggest that a tag should be used only by links that are going
    // somewhere. This is a reason we are using #main-content hash tag.
    // @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/a#Accessibility_recommendations
    // Using hash tag that exist on page will help with screen readers and also
    // when JS is disabled.
    return [
      '#type' => 'link',
      '#title' => ['#markup' => '<span class="label">' . $config['text'] . '</span>'],
      // #main-content is hardcoded because all core themes also use the same
      // element id.
      '#url' => Url::fromUserInput('#main-content', [
        'attributes' => [
          'class' => array_merge(['bs-lib-scroll-to-top', 'bs-lib-scroll-to-top--' . $config['position']], explode(' ', $config['css_class'])),
        ] + $data,
      ]),
      '#attached' => ['library' => $library],
    ];
  }

}
