<?php

namespace Drupal\zsm_system_load\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use \Drupal\Core\Render\RendererInterface;
/**
 * Field widget "zsm_system_load_threshold_default".
 *
 * @FieldWidget(
 *   id = "zsm_system_load_threshold_default",
 *   label = @Translation("ZSM System Load Threshold default"),
 *   field_types = {
 *     "zsm_system_load_threshold",
 *   }
 * )
 */
class ZSMSystemLoadThresholdDefaultWidget extends WidgetBase implements WidgetInterface {
    /**
     * {@inheritdoc}
     */
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
        // $item is where the current saved values are stored.
        $item =& $items[$delta];
        $element += array(
            '#type' => 'fieldset',
        );
        $element['type'] = array(
            '#title' => t('Type'),
            '#description' => t('The duration of load you are monitoring'),
          '#type' => 'select',
          '#options' => array(
            'load_1min' => t('1 minute load average'),
            'load_5min' => t('5 minute load average'),
            'load_15min' => t('15 minute load average'),
          ),
            '#default_value' => isset($item->type) ? $item->type : 'load_1min',
        );
        $element['amount'] = array(
            '#title' => t('Amount'),
            '#description' => t('The load average that will trigger the alert.'),
            '#type' => 'textfield',
            '#default_value' => isset($item->amount) ? $item->amount : '',
        );
        $element['severity'] = array(
            '#title' => t('Severity'),
            '#description' => t('The severity of the alert.'),
            '#type' => 'select',
            '#options' => array(
                'custom' => t('Custom'),
                'notice' => t('Notice'),
                'warning' => t('Warning'),
                'critical' => t('Critical'),
                'highly_critical' => t('Highly Critical'),
            ),
            '#default_value' => isset($item->severity) ? $item->severity : 'custom',
        );
        $name = $element['#field_parents'][0] . $this->fieldDefinition->getName() . '[' . $delta . '][severity]';
        $element['severity_custom'] = array(
            '#title' => t('Custom Severity'),
            '#description' => t('A custom alert severity. If selected and left blank, the system will default to "notice".'),
            '#type' => 'textfield',
            '#default_value' => isset($item->severity_custom) ? $item->severity_custom : '',
            '#states' => array(
                'visible' => array(
                    ':input[name="' . $name . '"]' => array('value' => 'custom'),
                )
            ),
        );
        return $element;
    }
}