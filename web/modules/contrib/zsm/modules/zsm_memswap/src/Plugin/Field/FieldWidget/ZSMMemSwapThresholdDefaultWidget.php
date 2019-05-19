<?php

namespace Drupal\zsm_memswap\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use \Drupal\Core\Render\RendererInterface;
/**
 * Field widget "zsm_memswap_threshold_default".
 *
 * @FieldWidget(
 *   id = "zsm_memswap_threshold_default",
 *   label = @Translation("ZSM MemSwap Threshold default"),
 *   field_types = {
 *     "zsm_memswap_threshold",
 *   }
 * )
 */
class ZSMMemSwapThresholdDefaultWidget extends WidgetBase implements WidgetInterface {
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
            '#description' => t('Whther you are monitoring memory or swap usage'),
          '#type' => 'select',
          '#options' => array(
            'mem' => t('Memory'),
            'swap' => t('Swap'),
          ),
            '#default_value' => isset($item->type) ? $item->type : 'mem',
        );
        $element['amount'] = array(
            '#title' => t('Amount'),
            '#description' => t('The percentage usage to trigger the error. Enter from 0 to 100: 70% should be entered as "70."'),
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