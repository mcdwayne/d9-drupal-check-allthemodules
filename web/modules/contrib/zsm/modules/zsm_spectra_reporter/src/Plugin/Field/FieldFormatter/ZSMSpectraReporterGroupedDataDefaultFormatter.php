<?php

namespace Drupal\zsm_spectra_reporter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
/**
 * Field formatter "zsm_spectra_reporter_grouped_data_default".
 *
 * @FieldFormatter(
 *   id = "zsm_spectra_reporter_grouped_data_default",
 *   label = @Translation("ZSM Spectra Reporter Grouped Data default"),
 *   field_types = {
 *     "zsm_spectra_reporter_grouped_data",
 *   }
 * )
 */
class ZSMSpectraReporterGroupedDataDefaultFormatter extends FormatterBase {
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
            '@format' => t('ZSM Spectra Reporter Grouped Data settings'),
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
            $build['group'] = array(
                '#type' => 'container',
                '#attributes' => array(
                    'class' => array('zsm__spectrareporter_group'),
                ),
                'label' => array(
                    '#type' => 'container',
                    '#attributes' => array(
                        'class' => array('field__label'),
                    ),
                    '#markup' => t('Group'),
                ),
                'value' => array(
                    '#type' => 'container',
                    '#attributes' => array(
                        'class' => array('field__item'),
                    ),
                    // We use #plain_text instead of #markup to prevent XSS.
                    '#plain_text' => $item->group,
                ),
            );
            $build['keys'] = array(
                '#type' => 'container',
                '#attributes' => array(
                    'class' => array('zsm__spectrareporter_keys'),
                ),
                'label' => array(
                    '#type' => 'container',
                    '#attributes' => array(
                        'class' => array('field__label'),
                    ),
                    '#markup' => t('Keys'),
                ),
                'value' => array(
                    '#type' => 'container',
                    '#attributes' => array(
                        'class' => array('field__item'),
                    ),
                    '#plain_text' => $item->keys,
                ),
            );
            $output[$delta] = $build;
        }
        return $output;
    }
}