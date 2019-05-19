<?php

namespace Drupal\zsm_spectra_reporter\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Field widget "zsm_spectra_reporter_grouped_data_default".
 *
 * @FieldWidget(
 *   id = "zsm_spectra_reporter_grouped_data_default",
 *   label = @Translation("ZSM Spectra Reporter Grouped Data default"),
 *   field_types = {
 *     "zsm_spectra_reporter_grouped_data",
 *   }
 * )
 */
class ZSMSpectraReporterGroupedDataDefaultWidget extends WidgetBase implements WidgetInterface {
    /**
     * {@inheritdoc}
     */
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
        // $item is where the current saved values are stored.
        $item =& $items[$delta];
        $element += array(
            '#type' => 'fieldset',
        );
        $element['group'] = array(
            '#title' => t('Group'),
            '#description' => t('The group reporting key to be used in the data. Use "unique" to make groups items report uniquely.'),
          '#type' => 'textfield',
            '#default_value' => isset($item->group) ? $item->group : '',
        );
        $element['keys'] = array(
            '#title' => t('Keys'),
            '#description' => t('The "type" keys of reporting data that will be added to this group. Add one per line.'),
            '#type' => 'textarea',
            '#default_value' => isset($item->keys) ? $item->keys : '',
        );
        return $element;
    }
}