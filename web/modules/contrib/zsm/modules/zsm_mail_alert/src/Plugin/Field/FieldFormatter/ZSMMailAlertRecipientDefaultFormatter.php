<?php

namespace Drupal\zsm_mail_alert\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
/**
 * Field formatter "zsm_mail_alert_recipient_default".
 *
 * @FieldFormatter(
 *   id = "zsm_mail_alert_recipient_default",
 *   label = @Translation("ZSM MailAlert Recipient default"),
 *   field_types = {
 *     "zsm_mail_alert_recipient",
 *   }
 * )
 */
class ZSMMailAlertRecipientDefaultFormatter extends FormatterBase {
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
            '@format' => t('ZSM MailAlert Recipient settings'),
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
          $build['recipient'] = array(
            '#type' => 'container',
            '#attributes' => array(
              'class' => array('zsm__mail_alert_recipient'),
            ),
            'label' => array(
              '#type' => 'container',
              '#attributes' => array(
                'class' => array('field__label'),
              ),
              '#markup' => t('Recipient'),
            ),
            'value' => array(
              '#type' => 'container',
              '#attributes' => array(
                'class' => array('field__item'),
              ),
              '#plain_text' => $item->recipient,
            ),
          );
            $build['severity'] = array(
                '#type' => 'container',
                '#attributes' => array(
                    'class' => array('zsm__mail_alert_severity'),
                ),
                'label' => array(
                    '#type' => 'container',
                    '#attributes' => array(
                        'class' => array('field__label'),
                    ),
                    '#markup' => t('Severity'),
                ),
                'value' => array(
                    '#type' => 'container',
                    '#attributes' => array(
                        'class' => array('field__item'),
                    ),
                    '#plain_text' => $item->severity,
                ),
            );
            $build['severity_custom'] = array(
                '#type' => 'container',
                '#attributes' => array(
                    'class' => array('zsm__mail_alert_severity_custom'),
                ),
                'label' => array(
                    '#type' => 'container',
                    '#attributes' => array(
                        'class' => array('field__label'),
                    ),
                    '#markup' => t('Custom Severity'),
                ),
                'value' => array(
                    '#type' => 'container',
                    '#attributes' => array(
                        'class' => array('field__item'),
                    ),
                    '#plain_text' => $item->severity_custom,
                ),
            );
            $output[$delta] = $build;
        }
        return $output;
    }
}