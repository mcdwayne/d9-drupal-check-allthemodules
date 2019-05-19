<?php

namespace Drupal\coc_forms_auto_export\Element;

use Drupal\webform\Entity\Webform as WebformEntity;
use Drupal\webform\Element\WebformExcludedBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\TableSelect;

/**
 * Provides a webform element for auto export excluded columns (submission field and elements).
 *
 * @FormElement("auto_export_excluded_columns")
 */
class AutoExportExcludedColumns extends WebformExcludedBase {

    /**
     * Processes a webform elements webform element.
     */
    public static function processWebformExcluded(&$element, FormStateInterface $form_state, &$complete_form) {
        $options = static::getWebformExcludedOptions($element);

        $element['#tree'] = TRUE;

        // Add validate callback.
        $element += ['#element_validate' => []];
        array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformIncluded']);

        // Get selected columns as the default value.
        if(!empty($options['checkbox'])){
            $columns_keys = array_keys($options['checkbox']);
            $columns_default_value = array_combine($columns_keys, $columns_keys);
        }else{
            $columns_default_value = [];
        }

        // Display columns in sortable table select element.
        $element['columns_table'] = [
            '#type' => 'webform_tableselect_sort',
            '#header' => static::getWebformExcludedHeader(),
            '#options' => $options['options'],
            '#default_value' => $columns_default_value,
        ];

        return $element;
    }

    /**
     * #element_validate callback for #type 'table'.
     *
     * @param array $element
     *   An associative array containing the properties and children of the
     *   table element.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     * @param array $complete_form
     *   The complete form structure.
     */
    public static function validateWebformIncluded(array &$element, FormStateInterface $form_state, &$complete_form) {
        // Skip this validation if download button triggered or
        // save settings button without a tick to update auto CSV export settings checkbox
        $triggering_element = $form_state->getTriggeringElement();
        if (empty($triggering_element['#submit']) || $form_state->getValue(update_auto_export_configs) == 0) {
            return;
        }
        if ($element['#multiple']) {
            if (!is_array($element['#value']) || !count(array_filter($element['#value']))) {
                $form_state
                    ->setError($element, t('No items selected.'));
            }
        }
        elseif (!isset($element['#value']) || $element['#value'] === '') {
            $form_state
                ->setError($element, t('No item selected.'));
        }

        $value = array_filter($element['columns_table']['#value']);

        // Unset columns_table and set the element's value to excluded.
        $form_state->setValueForElement($element['columns_table'], NULL);
        $element['#value'] = $value;
        $form_state->setValueForElement($element, $value);
    }

    /**
     * {@inheritdoc}
     */
    public static function getWebformExcludedHeader() {
        return [
            'title' => t('Title'),
            'name' => t('Name'),
            'type' => t('Date type/Element type'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getWebformExcludedOptions(array $element) {
        /** @var \Drupal\webform\WebformInterface $webform */
        $webform = WebformEntity::load($element['#webform_id']);
        $options = [];

        $elements = $webform->getElementsInitializedFlattenedAndHasValue('view');
        // Replace tokens which can be used in an element's #title.
        /** @var \Drupal\webform\WebformTokenManagerInterface $token_manager */
        $token_manager = \Drupal::service('webform.token_manager');
        $elements = $token_manager->replace($elements, $webform);

        if(!empty($element['#default_value'])){
            $included_elements = $element['#default_value'];
            $excluded_elements = array_diff_key($elements, $included_elements);
            $excluded_elements_keys = array_keys($excluded_elements);
            $elements_keys = array_merge(array_keys($included_elements), $excluded_elements_keys);

            foreach ($elements_keys as $key) {
                if(!in_array($key, $excluded_elements_keys)) {
                    $options['checkbox'][$key] = $key;
                }
                    $options['options'][$key] = [
                        'title' => $elements[$key]['#admin_title'] ?: $elements[$key]['#title'] ?: $key,
                        'name' => $key,
                        'type' => isset($elements[$key]['#type']) ? $elements[$key]['#type'] : '',
                    ];
            }
        }else{
            foreach ($elements as $key => $element) {
                $options['options'][$key] = [
                    'title' => $element['#admin_title'] ?:$element['#title'] ?: $key,
                    'name' => $key,
                    'type' => isset($element['#type']) ? $element['#type'] : '',
                ];
            }
        }

        return $options;
    }

}
