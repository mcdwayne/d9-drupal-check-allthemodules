<?php

/**
 * @file
 * Contains \Drupal\rut_field\Plugin\field\widget\RutWidget.
 */

namespace Drupal\rut_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\WidgetBase;
use Tifon\Rut\RutUtil;

/**
 * Plugin implementation of the 'rut_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "rut_field_widget",
 *   module = "rut_field",
 *   label = @Translation("Rut Element"),
 *   field_types = {
 *     "rut_field_rut"
 *   }
 * )
 */
class RutWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'validate_js' => TRUE,
      'message_js' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $validate_js = $this->getSetting('validate_js');
    $summary[] = t('Use Javascript validator: @validate_js', array('@validate_js' => ($validate_js ? t('Yes') : 'No')));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['validate_js'] = array(
      '#type' => 'checkbox',
      '#title' => t('Javascript validator'),
      '#default_value' => $this->getSetting('validate_js'),
    );
    $element['message_js'] = array(
      '#type' => 'textfield',
      '#title' => t('Message by js'),
      '#description' => t('Define the message to display if the javascript validator is checked'),
      '#default_value' => $this->getSetting('message_js'),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    if (isset($items[$delta]->value)) {
      $default_value = $items[$delta]->value;
    }
    else {
      $rut = isset($items[$delta]->rut) ? $items[$delta]->rut : '';
      $dv = isset($items[$delta]->dv) ? $items[$delta]->dv : '';
      $default_value = RutUtil::formatterRut($rut, $dv);
    }

    $rut_element = array(
      '#type' => 'rut_field',
      '#default_value' => $default_value,
    );

    if ($this->getSetting('validate_js')) {
      $rut_element['#validate_js'] = TRUE;
      $rut_element['#message_js'] = $this->getSetting('message_js');
    }

    $element += $rut_element;

    return array('value' => $element);
  }


}
