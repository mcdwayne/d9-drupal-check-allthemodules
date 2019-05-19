<?php

namespace Drupal\zsm_mail_alert\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
/**
 * Field formatter "zsm_mail_alert_message_component_default".
 *
 * @FieldFormatter(
 *   id = "zsm_mail_alert_message_component_default",
 *   label = @Translation("ZSM MailAlert Message Component default"),
 *   field_types = {
 *     "zsm_mail_alert_message_component",
 *   }
 * )
 */
class ZSMMailAlertMessageComponentDefaultFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings();
  }
  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = t('Section-list display: @format', array(
      '@format' => t('ZSM MailAlert Message Component settings'),
    ));
    return $summary;
  }
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $output = array();
    foreach ($items as $delta => $item) {
      $build = array();
      $build['component_type'] = array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array('zsm__mail_alert_component_type'),
        ),
        'label' => array(
          '#type' => 'container',
          '#attributes' => array(
            'class' => array('field__label'),
          ),
          '#markup' => t('Component Type'),
        ),
        'value' => array(
          '#type' => 'container',
          '#attributes' => array(
            'class' => array('field__item'),
          ),
          '#plain_text' => $item->component_type,
        ),
      );
      $build['component_variable_data'] = array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array('zsm__mail_alert_component_variable_data'),
        ),
        'label' => array(
          '#type' => 'container',
          '#attributes' => array(
            'class' => array('field__label'),
          ),
          '#markup' => t('Data from the Alert'),
        ),
        'value' => array(
          '#type' => 'container',
          '#attributes' => array(
            'class' => array('field__item'),
          ),
          '#plain_text' => $item->component_variable_data,
        ),
      );
      $build['component_fixed_data'] = array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array('zsm__mail_alert_component_fixed_data'),
        ),
        'label' => array(
          '#type' => 'container',
          '#attributes' => array(
            'class' => array('field__label'),
          ),
          '#markup' => t('Fixed Value'),
        ),
        'value' => array(
          '#type' => 'container',
          '#attributes' => array(
            'class' => array('field__item'),
          ),
          '#plain_text' => $item->component_fixed_data,
        ),
      );
      $output[$delta] = $build;
    }
    return $output;
  }
}