<?php

namespace Drupal\external_data_source\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\external_data_source\Controller\ExternalDataSourceController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;

/**
 * Plugin implementation of the 'External Data Source Select Widget' widget.
 *
 * @FieldWidget(
 *   id = "external_data_source_select_widget",
 *   label = @Translation("External Data Source Select Widget"),
 *   field_types = {
 *     "external_data_source"
 *   },
 *   multiple_values = TRUE
 * )
 */
class ExternalDataSourceSelectWidget extends WidgetBase implements ContainerFactoryPluginInterface {

    /**
     * {@inheritdoc}
     */
    public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ElementInfoManagerInterface $element_info) {
        parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
        $this->elementInfo = $element_info;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings'], $container->get('element_info'));
    }

    /**
     * {@inheritdoc}
     */
    public static function defaultSettings() {
        return [
            'size' => 1,
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
            '#title' => t('Size of Select field'),
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
        $element['value'] = $element + [
            '#type' => 'select',
            '#options' => ['' => t('None')] + $handler->optionsForSelect($pluginInstance),
            '#attributes' => ['class' => ['input-lg', 'form-control']],
            '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : '',
            '#size' => $this->getSetting('size'),
            '#placeholder' => $this->getSetting('placeholder'),
        ];
        return $element;
    }

}
