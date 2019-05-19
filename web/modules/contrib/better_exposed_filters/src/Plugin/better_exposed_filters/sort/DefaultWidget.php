<?php

namespace Drupal\better_exposed_filters\Plugin\better_exposed_filters\sort;

use Drupal\better_exposed_filters\Plugin\BetterExposedFiltersSortWidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Default widget implementation.
 *
 * @BetterExposedFiltersSortWidget(
 *   id = "default",
 *   label = @Translation("Default"),
 * )
 */
class DefaultWidget extends BetterExposedFiltersSortWidgetBase {

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
    if ($field == 'sort_bef_combine') {
      $form['sort_bef_combine']['#type'] = 'select';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFormAlter(array &$form, FormStateInterface $form_state) {
    return [];
  }

}
