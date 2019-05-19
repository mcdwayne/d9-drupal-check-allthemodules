<?php

namespace Drupal\zsm_backup_date\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
/**
 * Field formatter "zsm_backup_pattern_default".
 *
 * @FieldFormatter(
 *   id = "zsm_backup_pattern_default",
 *   label = @Translation("ZSM Backup Pattern default"),
 *   field_types = {
 *     "zsm_backup_pattern",
 *   }
 * )
 */
class ZSMBackupPatternDefaultFormatter extends FormatterBase {
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
        $summary[] = t('Backup Regex display: @format', array(
            '@format' => t('ZSM Backup Pattern settings'),
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
            $build['name'] = array(
                '#type' => 'container',
                '#attributes' => array(
                    'class' => array('zsm__name'),
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
                    '#plain_text' => $item->name,
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
            $build['age'] = array(
                '#type' => 'container',
                '#attributes' => array(
                    'class' => array('zsm__age'),
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
                    '#plain_text' => $item->age,
                ),
            );
            $output[$delta] = $build;
        }
        return $output;
    }
}