<?php

namespace Drupal\mapycz\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\Field;

/**
 * Field handler for MapyCZ field.
 *
 * @ingroup views_field_handlers
 *
 * @todo Rename the extended class https://www.drupal.org/node/2408667
 *
 * @ViewsField("mapycz_field")
 */
class MapyCzField extends Field {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Remote the click sort field selector.
    unset($form['click_sort_column']);
  }

}
