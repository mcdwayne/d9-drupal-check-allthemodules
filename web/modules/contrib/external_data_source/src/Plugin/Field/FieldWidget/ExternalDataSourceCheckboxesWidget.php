<?php

namespace Drupal\external_data_source\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\external_data_source\Controller\ExternalDataSourceController;
use Drupal\Core\Form\OptGroup;
use Masterminds\HTML5\Parser\UTF8Utils;

/**
 * Plugin implementation of the 'external_data_source_widget' widget.
 *
 * @FieldWidget(
 *   id = "external_data_source_checkboxes_widget",
 *   label = @Translation("External Data Source Checkboxes Widget"),
 *   field_types = {
 *     "external_data_source"
 *   },
 *   multiple_values = TRUE
 * )
 */
class ExternalDataSourceCheckboxesWidget extends OptionsWidgetBase {

    /**
     * {@inheritdoc}
     */
    public static function defaultSettings() {
        return [
            'size' => 10,
            'placeholder' => '',
                ] + parent::defaultSettings();
    }

    /**
     * {@inheritdoc}
     */
    public function settingsForm(array $form, FormStateInterface $form_state) {
        $elements = [];
        $elements['size'] = [
            '#type' => 'number',
            '#title' => t('Size of textfield'),
            '#default_value' => $this->getSetting('size'),
            '#required' => TRUE,
            '#min' => 1,
        ];
        $elements['placeholder'] = [
            '#type' => 'textfield',
            '#title' => t('Placeholder'),
            '#default_value' => $this->getSetting('placeholder'),
            '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
        ];

        return $elements;
    }

    /**
     * {@inheritdoc}
     */
    public function settingsSummary() {
        $summary = [];
        //retrieve settings from field Storage
        $fieldSettings = $this->getFieldSettings();
        $summary[] = t('Text field size: @size', ['@size' => $this->getSetting('size')]);
        if (!empty($this->getSetting('placeholder'))) {
            $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $this->getSetting('placeholder')]);
        }
        if (!empty($fieldSettings['ws'])) {
            $summary[] = t('Web Service Plugin: @ws', ['@ws' => $fieldSettings['ws']]);
        }
        if (!empty($fieldSettings['count'])) {
            $summary[] = t('Number of suggestions: @count', ['@count' => $fieldSettings['count']]);
        }
        return $summary;
    }

    /**
     * {@inheritdoc}
     */
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
        $element = parent::formElement($items, $delta, $element, $form, $form_state);
        //retrieve settings from field Storage
        $fieldSettings = $this->getFieldSettings();
        //Default plugin to prevent exceptions
        $plugin = 'countries';
        //Chosen plugin in settings
        if (!empty($fieldSettings['ws'])) {
            $SettingPlugin = $fieldSettings['ws'];
            $type = \Drupal::service('plugin.manager.external_data_source');
            $plugin_definitions = $type->getDefinitions();
            $plugins = [];
            if (count($plugin_definitions)) {
                foreach ($plugin_definitions as $plugin) {
                    $plugins[$plugin['id']] = $plugin['name']->__toString()
                            . ' - ' . $plugin['description']->__toString();
                }
            }
            if (!array_key_exists($SettingPlugin, $plugins)) {
                throw new SuspiciousOperationException($this->t('The selected WS does\'nt exist'));
            }
            $pluginInstance = new $plugin_definitions[$SettingPlugin]['class']();
        }
        $handler = new ExternalDataSourceController(\Drupal::service('plugin.manager.external_data_source'));
        $options = $this->sanitizeArray($handler->optionsForSelect($pluginInstance));

        // We need to check against a flat list of options.
        $flat_options = OptGroup::flattenOptions($options);

        $selected_options = [];
        foreach ($items as $item) {
            $value = $item->{$this->column};
            // Keep the value if it actually is in the list of options (needs to be
            // checked against the flat list).
            if (isset($flat_options[$value])) {
                $selected_options[] = $value;
            }
        }
        $selected = $selected_options;

        // If required and there is one single option, preselect it.
        if ($this->required && count($options) == 1) {
            reset($options);
            $selected = [key($options)];
        }

        if ($this->multiple) {
            $element += [
                '#type' => 'checkboxes',
                '#default_value' => $selected,
                '#options' => $options,
            ];
        } else {
            $element += [
                '#type' => 'radios',
                // Radio buttons need a scalar value. Take the first default value, or
                // default to NULL so that the form element is properly recognized as
                // not having a default value.
                '#default_value' => $selected ? reset($selected) : NULL,
                '#options' => $options,
            ];
        }

        return $element;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEmptyLabel() {
        if (!$this->required && !$this->multiple) {
            return t('N/A');
        }
    }

    /**
     * Detect & convert special char to UTF8
     * @author Amine Cherif <maccherif200@gmail.com>
     * @param array $data
     * @return array
     */
    public function sanitizeArray(array $data) {
        $stringCleaner = new UTF8Utils();
        $cleanOptions = [];
        foreach ($data as $key => $value) {
            $cleanOptions[$stringCleaner::convertToUTF8($key)] = $stringCleaner::convertToUTF8($value);
        }
        return $cleanOptions;
    }

}
