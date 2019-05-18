<?php

namespace Drupal\bcubed\Plugin\bcubed\Condition;

use Drupal\bcubed\ConditionBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides basic condition to restrict condition sets to running on every nth page.
 *
 * @Condition(
 *   id = "nth_page_view",
 *   label = @Translation("Nth Page View"),
 *   description = @Translation("Fire every nth page view"),
 *   settings = {
 *     "n" = 2,
 *   }
 * )
 */
class NthPageView extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return 'bcubed/nthpageview';
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['n'] = [
      '#type' => 'number',
      '#title' => 'Load every Nth page view',
      '#description' => 'Restrict to every Nth page view, per user. Values less than 2 will fire on every page view',
      '#default_value' => $this->settings['n'],
      '#required' => TRUE,
    ];

    return $form;
  }

}
