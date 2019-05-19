<?php

namespace Drupal\zsm_memswap\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
/**
 * Field formatter "zsm_memswap_threshold_default".
 *
 * @FieldFormatter(
 *   id = "zsm_memswap_threshold_default",
 *   label = @Translation("ZSM MemSwap Threshold default"),
 *   field_types = {
 *     "zsm_memswap_threshold",
 *   }
 * )
 */
class ZSMMemSwapThresholdDefaultFormatter extends FormatterBase {
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
            '@format' => t('ZSM MemSwap Threshold settings'),
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
                    'class' => array('zsm__memswap_type'),
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
                    'class' => array('zsm__memswap_amount'),
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
                    'class' => array('zsm__memswap_severity'),
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
                    'class' => array('zsm__memswap_severity_custom'),
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