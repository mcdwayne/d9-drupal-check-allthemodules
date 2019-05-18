<?php

namespace Drupal\flexible_daterange\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;
use Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeDefaultWidget;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'flexible_daterange_default' widget.
 *
 * @FieldWidget(
 *   id = "flexible_daterange_default",
 *   label = @Translation("Date and time range"),
 *   field_types = {
 *     "flexible_daterange"
 *   }
 * )
 */
class FlexibleDateRangeDefaultWidget extends DateRangeDefaultWidget implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'allow_hide_time' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['allow_hide_time'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check this to allow users to see the "Hide Time" option for this content type.'),
      '#default_value' => $this->getSetting('allow_hide_time'),
      '#weight' => -1,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $display_label = $this->getSetting('allow_hide_time');
    $summary[] = t('Allow Hide time: @display_label', ['@display_label' => ($display_label ? $this->t('Yes') : $this->t('No'))]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    if ($this->getFieldSetting('datetime_type') == DateRangeItem::DATETIME_TYPE_DATETIME
    && $this->getSetting('allow_hide_time')) {
      $value = isset($items[$delta]->hide_time) ? $items[$delta]->hide_time : '';

      $element['hide_time'] = [
        '#title' => $this->t('Hide time'),
        '#type' => 'checkbox',
        '#default_value' => $value,
      ];
    }

    return $element;
  }

}
