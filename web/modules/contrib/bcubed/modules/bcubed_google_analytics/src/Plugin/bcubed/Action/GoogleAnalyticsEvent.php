<?php

namespace Drupal\bcubed_google_analytics\Plugin\bcubed\Action;

use Drupal\bcubed\ActionBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides basic google analytics event integration.
 *
 * @Action(
 *   id = "google_analytics_event",
 *   label = @Translation("Google Analytics Event"),
 *   description = @Translation("Create event in google analytics"),
 *   settings = {
 *     "category" = "",
 *     "action" = "",
 *     "label" = "",
 *     "interaction" = 0,
 *     "proxy" = 1
 *   },
 *   generated_strings_dictionary = "bcubed_google_analytics"
 * )
 */
class GoogleAnalyticsEvent extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return 'bcubed_google_analytics/gaevent';
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['category'] = [
      '#type' => 'textfield',
      '#title' => 'Category',
      '#default_value' => $this->settings['category'],
      '#required' => TRUE,
    ];

    $form['action'] = [
      '#type' => 'textfield',
      '#title' => 'Action',
      '#default_value' => $this->settings['action'],
      '#required' => TRUE,
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => 'Label',
      '#default_value' => $this->settings['label'],
    ];

    $form['interaction'] = [
      '#type' => 'checkbox',
      '#title' => 'Is Interaction',
      '#default_value' => $this->settings['interaction'],
    ];

    $form['proxy'] = [
      '#type' => 'checkbox',
      '#title' => 'Use Proxy',
      '#description' => 'Send event to Google Analytics from the server, rather than the client\'s browser',
      '#default_value' => $this->settings['proxy'],
    ];

    return $form;
  }

}
