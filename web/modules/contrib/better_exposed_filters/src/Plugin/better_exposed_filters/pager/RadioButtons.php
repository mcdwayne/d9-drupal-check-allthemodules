<?php

namespace Drupal\better_exposed_filters\Plugin\better_exposed_filters\pagert;

use Drupal\better_exposed_filters\Plugin\BetterExposedFiltersPagerWidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Radio Buttons pager widget implementation.
 *
 * @BetterExposedFiltersPagerWidget(
 *   id = "bef",
 *   label = @Translation("Radio Buttons"),
 * )
 */
class RadioButtons extends BetterExposedFiltersPagerWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state) {
    $form['items_per_page']['#type'] = 'radios';
    if (empty($form['items_per_page']['#process'])) {
      $form['items_per_page']['#process'] = [];
    }
    array_unshift($form['items_per_page']['#process'], ['\Drupal\Core\Render\Element\Radios', 'processRadios']);
    $form['items_per_page']['#prefix'] = '<div class="bef-sortby bef-select-as-radios">';
    $form['items_per_page']['#suffix'] = '</div>';
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFormAlter(array &$form, FormStateInterface $form_state) {
    return [];
  }

}
