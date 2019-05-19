<?php

namespace Drupal\zsm\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
/**
 * Field formatter "zsm_regex_pattern_default".
 *
 * @FieldFormatter(
 *   id = "zsm_regex_pattern_default",
 *   label = @Translation("ZSM Regex Pattern default"),
 *   field_types = {
 *     "zsm_regex_pattern",
 *   }
 * )
 */
class ZSMRegexPatternDefaultFormatter extends FormatterBase {
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
        $summary[] = t('Regex display: @format', array(
            '@format' => t('ZSM Regex Pattern settings'),
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
                    'class' => array('zsm__type'),
                ),
                'label' => array(
                    '#type' => 'container',
                    '#attributes' => array(
                        'class' => array('field__label'),
                    ),
                    '#markup' => t('Name'),
                ),
                'value' => array(
                    '#type' => 'container',
                    '#attributes' => array(
                        'class' => array('field__item'),
                    ),
                    '#plain_text' => $item->type,
                ),
            );
            $build['location'] = array(
                '#type' => 'container',
                '#attributes' => array(
                    'class' => array('zsm__location'),
                ),
                'label' => array(
                    '#type' => 'container',
                    '#attributes' => array(
                        'class' => array('field__label'),
                    ),
                    '#markup' => t('Items'),
                ),
                'value' => array(
                    '#type' => 'container',
                    '#attributes' => array(
                        'class' => array('field__item'),
                    ),
                    '#plain_text' => $item->location,
                ),
            );
            $build['pattern'] = array(
                '#type' => 'container',
                '#attributes' => array(
                    'class' => array('zsm__pattern'),
                ),
                'label' => array(
                    '#type' => 'container',
                    '#attributes' => array(
                        'class' => array('field__label'),
                    ),
                    '#markup' => t('Items'),
                ),
                'value' => array(
                    '#type' => 'container',
                    '#attributes' => array(
                        'class' => array('field__item'),
                    ),
                    '#plain_text' => $item->pattern,
                ),
            );
            $output[$delta] = $build;
        }
        return $output;
    }
}