<?php

namespace Drupal\zsm\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use \Drupal\Core\Render\RendererInterface;
/**
 * Field widget "zsm_alert_threshold_default".
 *
 * @FieldWidget(
 *   id = "zsm_alert_threshold_default",
 *   label = @Translation("ZSM Alert Threshold default"),
 *   field_types = {
 *     "zsm_alert_threshold",
 *   }
 * )
 */
class ZSMAlertThresholdDefaultWidget extends WidgetBase implements WidgetInterface {
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
            '#description' => t('The type of data you are monitoring. See the notes for your plugin.'),
            '#type' => 'textfield',
            '#default_value' => isset($item->type) ? $item->type : '',
        );
        $element['context'] = array(
            '#title' => t('Plugin-specific Context'),
            '#description' => t('The plugin-specific context of you are monitoring. See the notes for your plugin.'),
            '#type' => 'textfield',
            '#default_value' => isset($item->context) ? $item->context : '',
        );
        $element['amount'] = array(
            '#title' => t('Amount'),
            '#description' => t('The threshold amount. See the notes for your plugin.'),
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