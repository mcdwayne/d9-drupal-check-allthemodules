<?php

namespace Drupal\zsm_mail_alert\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use \Drupal\Core\Render\RendererInterface;
/**
 * Field widget "zsm_mail_alert_message_component_default".
 *
 * @FieldWidget(
 *   id = "zsm_mail_alert_message_component_default",
 *   label = @Translation("ZSM MailAlert Message Component default"),
 *   field_types = {
 *     "zsm_mail_alert_message_component",
 *   }
 * )
 */
class ZSMMailAlertMessageComponentDefaultWidget extends WidgetBase implements WidgetInterface {
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // $item is where the current saved values are stored.
    $item =& $items[$delta];
    $element += array(
      '#type' => 'fieldset',
    );
    $element['component_type'] = array(
      '#title' => t('Component Data Type'),
      '#description' => t('Whether to add data from the ZSM Alert, or a fixed message.'),
      '#type' => 'select',
      '#options' => array(
        'variable' => t('Data from the Alert'),
        'fixed' => t('Fixed Value'),
      ),
      '#default_value' => isset($item->component_type) ? $item->component_type : '',
    );
    $name = $element['#field_parents'][0] . $this->fieldDefinition->getName() . '[' . $delta . '][component_type]';
    $element['component_fixed_data'] = array(
      '#title' => t('Fixed Value'),
      '#description' => t('This will be inserted as written'),
      '#type' => 'textfield',
      '#default_value' => isset($item->component_fixed_data) ? $item->component_fixed_data : '',
      '#states' => array(
        'visible' => array(
          ':input[name="' . $name . '"]' => array('value' => 'fixed'),
        )
      ),
    );
    $element['component_variable_data'] = array(
      '#title' => t('Data from the Alert'),
      '#description' => t('This data will be computed from the alert data.'),
      '#type' => 'select',
      '#options' => array(
        'severity' => t('Alert severity'),
        'type' => t('Alert type'),
        'threshold_value' => t('Threshold value, which when exceeded triggered the alert'),
        'data' => t('Alert data, usualy the actual value that exceeded the alert value'),
        'reporter' => t('Name of the ZSM plugin that generated the report.'),
      ),
      '#default_value' => isset($item->component_variable_data) ? $item->component_variable_data : '',
      '#empty_option' => t(' - Select - '),
      '#states' => array(
        'visible' => array(
          ':input[name="' . $name . '"]' => array('value' => 'variable'),
        )
      ),
    );
    return $element;
  }
}