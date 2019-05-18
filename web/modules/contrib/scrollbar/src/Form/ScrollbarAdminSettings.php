<?php

/**
 * @file
 * Contains \Drupal\scrollbar\Form\ScrollbarAdminSettings.
 */

namespace Drupal\scrollbar\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class ScrollbarAdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scrollbar_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('scrollbar.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['scrollbar.settings'];
  }
  
  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('scrollbar.settings');

    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable scrollbar'),
      '#description' => $this->t('Enable scrollbar + jscrollpane functionality on your site.'),
      '#default_value' => $config->get('enable'),
    ];
    $form['element'] = [
      '#type' => 'textfield',
      '#title' => t('Elements to get the jScrollPane function'),
      '#default_value' => \Drupal::config('scrollbar.settings')->get('element'),
      '#size' => 100,
      '#required' => TRUE,
      '#maxlength' => 800,
      '#description' => "<p>" . t('Set here the DOM elements that will get the scrollbar function.') . "</p><p>" . t('Seperate elements with a comma. Example <code>@code</code>', [
        '@code' => '.field--name-body, #mydiv'
        ]) . "</p><p><strong>" . t("Do not use a trailing comma") . "</strong></p><p>" . t("Finally, don't forget to use the proper CSS. Example <code>@code</code>", [
        '@code' => ".field--name-body {overflow:auto; \n height: 200px;}"
        ]) . "</p>",
    ];
    $form['showArrows'] = [
      '#type' => 'select',
      '#title' => t('Show arrows for scrollbar'),
      '#default_value' => \Drupal::config('scrollbar.settings')->get('showArrows'),
      '#options' => [
        'true' => t('yes'),
        'false' => t('no'),
      ],
      '#description' => t('Whether arrows should be shown on the generated scroll pane. When set to false only the scrollbar track and drag will be shown, if set to true then arrows buttons will also be shown.'),
    ];
    $form['scrollbar_advanced'] = [
      '#type' => 'details',
      '#title' => t('Advanced options'),
      '#open' => FALSE,
    ];
    $form['scrollbar_advanced']['generalOptions'] = [
      '#type' => 'details',
      '#title' => t('General options'),
      '#open' => FALSE,
    ];
    $form['scrollbar_advanced']['verticalOptions'] = [
      '#type' => 'details',
      '#title' => t('Vertical scrollbar options'),
      '#open' => FALSE,
      '#description' => "<p>" . t('The size of the drag elements is based on the proportion of the size of the content to the size of the viewport but is contrained within the minimum and maximum dimensions given') . "</p>",
    ];
    $form['scrollbar_advanced']['horizontalOptions'] = [
      '#type' => 'details',
      '#title' => t('Horizontal scrollbar options'),
      '#open' => FALSE,
      '#description' => "<p>" . t('The size of the drag elements is based on the proportion of the size of the content to the size of the viewport but is contrained within the minimum and maximum dimensions given') . "</p>",
    ];
    $form['scrollbar_advanced']['generalOptions']['arrowScrollOnHover'] = [
      '#type' => 'select',
      '#title' => t('Scroll element when mouse is over arrows'),
      '#default_value' => \Drupal::config('scrollbar.settings')->get('arrowScrollOnHover'),
      '#options' => [
        'true' => t('true'),
        'false' => t('false'),
      ],
      '#description' => t('Whether the arrow buttons should cause the scrollbar to scroll while you are hovering over them.'),
    ];
    $form['scrollbar_advanced']['generalOptions']['mouseWheelSpeed'] = [
      '#type' => 'number',
      '#title' => t('Mousewheel speed multiplier'),
      '#default_value' => \Drupal::config('scrollbar.settings')->get('mouseWheelSpeed'),
      '#max' => 99999,
      '#min' => 0,
      '#step' => 1,
      '#description' => t("A multiplier which is used to control the amount that the scrollpane scrolls each time the mouse wheel is turned."),
    ];
    $form['scrollbar_advanced']['generalOptions']['arrowButtonSpeed'] = [
      '#type' => 'number',
      '#title' => t('Arrow buttons speed multiplier'),
      '#default_value' => \Drupal::config('scrollbar.settings')->get('arrowButtonSpeed'),
      '#max' => 99999,
      '#min' => 0,
      '#step' => 1,
      '#description' => t('A multiplier which is used to control the amount that the scrollpane scrolls each time on of the arrow buttons is pressed.'),
    ];
    $form['scrollbar_advanced']['generalOptions']['arrowRepeatFreq'] = [
      '#type' => 'number',
      '#title' => t('Arrow buttons Repeat frequency, in ms'),
      '#default_value' => \Drupal::config('scrollbar.settings')->get('arrowRepeatFreq'),
      '#max' => 99999,
      '#min' => 0,
      '#step' => 1,
      '#description' => t('The number of milliseconds between each repeated scroll event when the mouse is held down over one of the arrow keys.'),
    ];
    $form['scrollbar_advanced']['horizontalOptions']['horizontalGutter'] = [
      '#type' => 'number',
      '#title' => t('Horizontal scrolling gap, in px'),
      '#default_value' => \Drupal::config('scrollbar.settings')->get('horizontalGutter'),
      '#max' => 99999,
      '#min' => 0,
      '#step' => 1,
      '#description' => t('Introduces a gap between the scrolling content and the scrollbar itself.'),
    ];
    $form['scrollbar_advanced']['verticalOptions']['verticalGutter'] = [
      '#type' => 'number',
      '#title' => t('Vertical scrolling gap, in px'),
      '#default_value' => \Drupal::config('scrollbar.settings')->get('scrollbar_verticalGutter'),
      '#max' => 99999,
      '#min' => 0,
      '#step' => 1,
      '#description' => t('Introduces a gap between the scrolling content and the scrollbar itself.'),
    ];
    $form['scrollbar_advanced']['verticalOptions']['verticalDragMinHeight'] = [
      '#type' => 'number',
      '#title' => t('Vertical Drag min height, in px'),
      '#default_value' => \Drupal::config('scrollbar.settings')->get('scrollbar_verticalDragMinHeight'),
      '#max' => 99999,
      '#min' => 0,
      '#step' => 1,
      '#description' => "<p>" . t('The smallest height that the vertical drag can have') . "</p>",
    ];
    $form['scrollbar_advanced']['verticalOptions']['verticalDragMaxHeight'] = [
      '#type' => 'number',
      '#title' => t('Vertical Drag max height, in px'),
      '#default_value' => \Drupal::config('scrollbar.settings')->get('scrollbar_verticalDragMaxHeight'),
      '#max' => 99999,
      '#min' => 0,
      '#step' => 1,
      '#description' => "<p>" . t('The maximum height that the vertical drag can have') . "</p>",
    ];
    $form['scrollbar_advanced']['verticalOptions']['verticalDragMinWidth'] = [
      '#type' => 'number',
      '#title' => t('Vertical Drag min width, in px'),
      '#default_value' => \Drupal::config('scrollbar.settings')->get('scrollbar_verticalDragMinWidth'),
      '#max' => 99999,
      '#min' => 0,
      '#step' => 1,
      '#description' => "<p>" . t('The smallest width that the vertical drag can have') . "</p>",
    ];
    $form['scrollbar_advanced']['verticalOptions']['verticalDragMaxWidth'] = [
      '#type' => 'number',
      '#title' => t('Vertical Drag max width, in px'),
      '#default_value' => \Drupal::config('scrollbar.settings')->get('scrollbar_verticalDragMaxWidth'),
      '#max' => 99999,
      '#min' => 0,
      '#step' => 1,
      '#description' => "<p>" . t('The maximum width that the vertical drag can have') . "</p>",
    ];
    $form['scrollbar_advanced']['horizontalOptions']['horizontalDragMinHeight'] = [
      '#type' => 'number',
      '#title' => t('Horizontal Drag min height, in px'),
      '#default_value' => \Drupal::config('scrollbar.settings')->get('scrollbar_horizontalDragMinHeight'),
      '#max' => 99999,
      '#min' => 0,
      '#step' => 1,
      '#description' => "<p>" . t('The smallest height that the horizontal drag can have') . "</p>",
    ];
    $form['scrollbar_advanced']['horizontalOptions']['horizontalDragMaxHeight'] = [
      '#type' => 'number',
      '#title' => t('Horizontal Drag max height, in px'),
      '#default_value' => \Drupal::config('scrollbar.settings')->get('scrollbar_horizontalDragMaxHeight'),
      '#max' => 99999,
      '#min' => 0,
      '#step' => 1,
      '#description' => "<p>" . t('The maximum height that the horizontal drag can have') . "</p>",
    ];
    $form['scrollbar_advanced']['horizontalOptions']['scrollbar_horizontalDragMinWidth'] = [
      '#type' => 'number',
      '#title' => t('Horizontal Drag min width, in px'),
      '#default_value' => \Drupal::config('scrollbar.settings')->get('scrollbar_horizontalDragMinWidth'),
      '#max' => 99999,
      '#min' => 0,
      '#step' => 1,
      '#description' => "<p>" . t('The smallest width that the horizontal drag can have') . "</p>",
    ];
    $form['scrollbar_advanced']['horizontalOptions']['scrollbar_horizontalDragMaxWidth'] = [
      '#type' => 'number',
      '#title' => t('Horizontal Drag max width, in px'),
      '#default_value' => \Drupal::config('scrollbar.settings')->get('scrollbar_horizontalDragMaxWidth'),
      '#max' => 99999,
      '#min' => 0,
      '#step' => 1,
      '#description' => "<p>" . t('The maximum width that the horizontal drag can have') . "</p>",
    ];
    $form['scrollbar_advanced']['horizontalOptions']['scrollbar_contentWidth'] = [
      '#type' => 'number',
      '#title' => t('The width of the content of the scroll pane, in px'),
      '#default_value' => \Drupal::config('scrollbar.settings')->get('scrollbar_contentWidth'),
      '#max' => 99999,
      '#min' => 0,
      '#step' => 1,
      '#description' => "<p>" . t('The width of the content of the scroll pane. The default value of undefined will allow jScrollPane to
				calculate the width of it\'s content. However, in some cases you will want to disable this (e.g. to
				prevent horizontal scrolling or where the calculation of the size of the content doesn\'t return reliable
				results)') . "</p>",
    ];
    $form['scrollbar_advanced']['verticalOptions']['scrollbar_verticalArrowPositions'] = [
      '#type' => 'select',
      '#title' => t('Show the vertical arrows relative to the vertical track'),
      '#default_value' => \Drupal::config('scrollbar.settings')->get('scrollbar_verticalArrowPositions'),
      '#options' => [
        'split' => t('split'),
        'before' => t('before'),
        'after' => t('after'),
        'os' => t('os'),
      ],
      '#description' => t('Where the vertical arrows should appear relative to the vertical track.'),
    ];
    $form['scrollbar_advanced']['horizontalOptions']['scrollbar_horizontalArrowPositions'] = [
      '#type' => 'select',
      '#title' => t('Show the horizontal arrows relative to the horizontal track'),
      '#default_value' => \Drupal::config('scrollbar.settings')->get('scrollbar_horizontalArrowPositions'),
      '#options' => [
        'split' => t('split'),
        'before' => t('before'),
        'after' => t('after'),
        'os' => t('os'),
      ],
      '#description' => t('Where the horizontal arrows should appear relative to the horizontal track.'),
    ];
    $form['scrollbar_advanced']['scrollbar_generalOptions']['scrollbar_autoReinitialise'] = [
      '#type' => 'select',
      '#title' => t('Reinitialise scrollbar'),
      '#default_value' => \Drupal::config('scrollbar.settings')->get('scrollbar_autoReinitialise'),
      '#options' => [
        'true' => t('true'),
        'false' => t('false'),
      ],
      '#description' => "<p>" . t('Whether scrollbar should automatically reinitialise itself periodically after you have initially initialised it.') . "</p>" . "<p>" . t('This can help with instances when the size of the content of the scrollpane (or the surrounding element) changes.') . "</p>" . "<p>" . t('However, it does involve an overhead of running a javascript function on a timer so it is recommended only to activate where necessary.') . "</p>",
    ];
    $form['scrollbar_advanced']['scrollbar_generalOptions']['scrollbar_autoReinitialiseDelay'] = [
      '#type' => 'number',
      '#title' => t('Reinitialise Delay in ms'),
      '#default_value' => \Drupal::config('scrollbar.settings')->get('scrollbar_autoReinitialiseDelay'),
      '#max' => 99999,
      '#min' => 0,
      '#step' => 1,
      '#description' => t('The number of milliseconds between each reinitialisation (if autoReinitialise is true).'),
    ];
    return parent::buildForm($form, $form_state);
  }
}
