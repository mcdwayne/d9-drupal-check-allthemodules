<?php

namespace Drupal\better_exposed_filters\Plugin\better_exposed_filters\sort;

use Drupal\better_exposed_filters\Plugin\BetterExposedFiltersSortWidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Radio Buttons sort widget implementation.
 *
 * @BetterExposedFiltersSortWidget(
 *   id = "bef",
 *   label = @Translation("Radio Buttons"),
 * )
 */
class RadioButtons extends BetterExposedFiltersSortWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state, $field) {
    if (!empty($form[$field])) {
      $form[$field]['#theme'] = 'bef_radios';
      $form[$field]['#type'] = 'radios';
      if (empty($form[$field]['#process'])) {
        $form[$field]['#process'] = [];
      }
      $form[$field]['#process'][] = ['\Drupal\Core\Render\Element\Radios', 'processRadios'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFormAlter(array &$form, FormStateInterface $form_state) {
    return [];
  }

}
