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
 * Field widget "zsm_mail_alert_recipient_default".
 *
 * @FieldWidget(
 *   id = "zsm_mail_alert_recipient_default",
 *   label = @Translation("ZSM MailAlert Recipient default"),
 *   field_types = {
 *     "zsm_mail_alert_recipient",
 *   }
 * )
 */
class ZSMMailAlertRecipientDefaultWidget extends WidgetBase implements WidgetInterface {
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // $item is where the current saved values are stored.
    $item =& $items[$delta];
    $element += array(
      '#type' => 'fieldset',
    );
    $element['severity'] = array(
      '#title' => t('Severity'),
      '#description' => t('The severity of the alert.'),
      '#type' => 'select',
      '#options' => array(
        'custom' => t('Custom'),
        '__default__' => t('All Severities'),
        'notice' => t('Notice'),
        'warning' => t('Warning'),
        'critical' => t('Critical'),
        'highly_critical' => t('Highly Critical'),
      ),
      '#default_value' => isset($item->severity) ? $item->severity : '',
      '#empty_option' => ' - Select - ',
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
    $element['recipient'] = array(
      '#title' => t('Recipient'),
      '#description' => t('A comma-separated list of email addresses.'),
      '#type' => 'textarea',
      '#default_value' => isset($item->recipient) ? $item->recipient : '',
    );
    return $element;
  }
}