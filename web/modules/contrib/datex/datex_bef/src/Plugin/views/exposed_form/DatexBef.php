<?php

namespace Drupal\datex_bef\Plugin\views\exposed_form;

use Drupal\better_exposed_filters\Plugin\views\exposed_form\BetterExposedFilters;
use Drupal\Core\Form\FormStateInterface;

/**
 * Adds date localization support to Bef.
 */
class DatexBef extends BetterExposedFilters {

  public function buildOptionsForm(&$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  public function validateOptionsForm(&$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);
  }

  public function exposedFormAlter(&$form, FormStateInterface $form_state) {
    parent::exposedFormAlter($form, $form_state);
    if (datex_factory()->getCalendarName() !== 'persian') {
      return;
    }
    if (isset($form['#attached']['library'])) {
      $l = [];
      $found = FALSE;
      foreach ($form['#attached']['library'] as $lib) {
        if ($lib === 'core/jquery.ui.datepicker' || $lib === 'better_exposed_filters/datepickers') {
          $found = TRUE;
        }
        else {
          $l[] = $lib;
        }
      }
      $form['#attached']['library'] = $l;
      if ($found) {
        $form['#attached']['library'][] = 'datex_bef/picker';
      }
    }
  }

}
