<?php

namespace Drupal\bcubed_interaction\Plugin\bcubed\Action;

use Drupal\bcubed\ActionBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Highlights a specific element.
 *
 * @Action(
 *   id = "highlight_element",
 *   label = @Translation("Highlight Element"),
 *   description = @Translation("Highlights a page element"),
 *   settings = {
 *     "use_passed_selector" = 1,
 *     "selector" = "",
 *     "message" = "",
 *     "wait" = 0,
 *     "dismiss_text" = "Dismiss",
 *     "dismiss_overlay_click" = 1,
 *     "second_button_text" = ""
 *   }
 * )
 */
class HighlightElement extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return 'bcubed_interaction/highlight';
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $events = $form_state->getFormObject()->getEntity()->get('events', []);
    $passed_selector_available = FALSE;
    foreach ($events as $event) {
      if ($event['id'] == 'ad_replaced') {
        $passed_selector_available = TRUE;
        break;
      }
    }

    if ($passed_selector_available) {
      $form['use_passed_selector'] = [
        '#type' => 'checkbox',
        '#title' => 'Highlight Replaced Ad',
        '#description' => 'Uncheck to specify other element to highlight',
        '#default_value' => $this->settings['use_passed_selector'],
      ];
    }

    $form['selector'] = [
      '#type' => 'textfield',
      '#title' => 'Element Selector',
      '#description' => 'DOM selector of element to highlight (eg: #elementid)',
      '#default_value' => $this->settings['selector'],
    ];

    if ($passed_selector_available) {
      $form['selector']['#states'] = [
        'visible' => [
          ':input[name="use_passed_selector"]' => ['checked' => FALSE],
        ],
        'required' => [
          ':input[name="use_passed_selector"]' => ['checked' => FALSE],
        ],
      ];
    }
    else {
      $form['selector']['#required'] = TRUE;
    }

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => 'Message',
      '#description' => 'Message to display while highlighting element',
      '#default_value' => $this->settings['message'],
      '#required' => TRUE,
    ];

    $form['dismiss_text'] = [
      '#type' => 'textfield',
      '#title' => 'Dismiss Button Text',
      '#default_value' => $this->settings['dismiss_text'],
      '#required' => TRUE,
    ];

    $form['second_button_text'] = [
      '#type' => 'textfield',
      '#title' => 'Custom Button Text',
      '#description' => 'Leave blank to disable custom second button',
      '#default_value' => $this->settings['second_button_text'],
    ];

    $form['dismiss_overlay_click'] = [
      '#type' => 'checkbox',
      '#title' => 'Dismiss On Overlay Click',
      '#default_value' => $this->settings['dismiss_overlay_click'],
    ];

    $form['wait'] = [
      '#type' => 'number',
      '#title' => 'Delay (ms)',
      '#description' => 'Time to wait in milliseconds before highlighting element. A value of 0 will execute immediately on event.',
      '#default_value' => $this->settings['wait'],
      '#required' => TRUE,
    ];

    return $form;
  }

}
