<?php

namespace Drupal\zsm_system_load\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
/**
 * Field formatter "zsm_system_load_threshold_default".
 *
 * @FieldFormatter(
 *   id = "zsm_system_load_threshold_default",
 *   label = @Translation("ZSM System Load Threshold default"),
 *   field_types = {
 *     "zsm_system_load_threshold",
 *   }
 * )
 */
class ZSMSystemLoadThresholdDefaultFormatter extends FormatterBase {
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
            '@format' => t('ZSM System Load Threshold settings'),
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
            $build['type'] = array(
                '#type' => 'container',
                '#attributes' => array(
                    'class' => array('zsm__system_load_type'),
                ),
                'label' => array(
                    '#type' => 'container',
                    '#attributes' => array(
                        'class' => array('field__label'),
                    ),
                    '#markup' => t('Type'),
                ),
                'value' => array(
                    '#type' => 'container',
                    '#attributes' => array(
                        'class' => array('field__item'),
                    ),
                    // We use #plain_text instead of #markup to prevent XSS.
                    '#plain_text' => $item->type,
                ),
            );
            $build['amount'] = array(
                '#type' => 'container',
                '#attributes' => array(
                    'class' => array('zsm__system_load_amount'),
                ),
                'label' => array(
                    '#type' => 'container',
                    '#attributes' => array(
                        'class' => array('field__label'),
                    ),
                    '#markup' => t('Amount'),
                ),
                'value' => array(
                    '#type' => 'container',
                    '#attributes' => array(
                        'class' => array('field__item'),
                    ),
                    '#plain_text' => $item->amount,
                ),
            );
            $build['severity'] = array(
                '#type' => 'container',
                '#attributes' => array(
                    'class' => array('zsm__system_load_severity'),
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
                    'class' => array('zsm__system_load_severity_custom'),
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
                    '#plain_text' => $item->severity,
                ),
            );
            $output[$delta] = $build;
        }
        return $output;
    }
}