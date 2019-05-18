<?php

namespace Drupal\scheduling\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'options_select' widget.
 *
 * @FieldWidget(
 *   id = "scheduling_mode",
 *   label = @Translation("Scheduling mode"),
 *   field_types = {
 *     "scheduling_mode"
 *   },
 *   multiple_values = TRUE
 * )
 */
class SchedulingModeWidget extends OptionsSelectWidget {

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return isset($values[0]) ? $values[0] : $values;
  }

}
