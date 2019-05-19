<?php

namespace Drupal\visualn_dataset\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\Element;
use Drupal\visualn\Helpers\VisualNFormsHelper;

/**
 * Plugin implementation of the 'visualn_resource_provider' widget.
 *
 * @FieldWidget(
 *   id = "visualn_resource_provider",
 *   label = @Translation("VisualN resource provider"),
 *   field_types = {
 *     "visualn_resource_provider"
 *   }
 * )
 */
class VisualNResourceProviderWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      //'resource_provider_id' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    // Actually default resource provider is already set at field default value configuration level.
    /*$elements['resource_provider_id'] = [
      '#type' => 'select',
      '#title' => t('Resource provider plugin id'),
      '#options' => $options,
      '#description' => t('Default resource provider plugin.'),
    ];*/

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    /*if (!empty($this->getSetting('resource_provider_id'))) {
      // @todo: get label for the resource provider plugin
      $summary[] = t('Resource provider: @resource_provider_plugin_label',
        ['@resource_provider_plugin_label' => $this->getSetting('resource_provider_id')]);
    }*/

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['#type'] = 'fieldset';
    $item = $items[$delta];
    $resource_provider_config = !empty($item->resource_provider_config) ? unserialize($item->resource_provider_config) : [];


    // Get resource providers plugins list
    $definitions = \Drupal::service('plugin.manager.visualn.resource_provider')->getDefinitions();
    $resource_providers = [];
    foreach ($definitions as $definition) {
      // Check for requried contexts, allow only entity_type and bundle contexts (also
      // exclude current_entity since it is expected to be set when getting
      // resource object based on the entity), no other contexts available are expected
      // @see \Drupal\visualn\Plugin\VisualN\DrawingFetcher\ResourceProviderSwitcherDrawingFetcher
      // @todo: review the code here
      if (!empty($definition['context'])) {
        foreach ($definition['context'] as $name => $context_definition) {
          if (!in_array($name, array('entity_type', 'bundle'))) {
            if ($context_definition->isRequired() && $name != 'current_entity') {
              continue 2;
            }
          }
        }
      }

      $resource_providers[$definition['id']] = $definition['label'];
    }

    // @todo: how to check if the form is fresh

    $field_name = $this->fieldDefinition->getName();
    $ajax_wrapper_id = $field_name . '-' . $delta . '-resource_provider-config-ajax-wrapper';

    // select resource provider plugin
    $element['resource_provider_id'] = [
      '#type' => 'select',
      '#title' => t('Resource provider plugin'),
      '#description' => t('Providers prepare a resource object based on files, urls, data generators etc.'),
      '#default_value' => $item->resource_provider_id,
      '#options' => $resource_providers,
      //'#required' => TRUE,
      '#empty_value' => '',
      '#empty_option' => t('- Select resource provider -'),
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallbackResourceProvider'],
        'wrapper' => $ajax_wrapper_id,
      ],
    ];
    $element['provider_container'] = [
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#type' => 'container',
      //'#process' => [[get_called_class(), 'processProviderContainerSubform']],
      '#process' => [[$this, 'processProviderContainerSubform']],
    ];

    $stored_configuration = [
      'resource_provider_id' => $item->resource_provider_id,
      'resource_provider_config' => $resource_provider_config,
    ];
    $element['provider_container']['#stored_configuration'] = $stored_configuration;

    // @todo: Also set keys for #entity_type and #bundle (see fetcher widget). Maybe set as context.

    // @todo: see comments in VisualNFetcherWidget::formElement()


    // @todo: Set entity type and bundle for the fetcher_plugin since it may need the list of all its fields.

    // @todo: We can't pass the current reference to the entity because it doesn't always exist,
    //    e.g. when setting default value for the field in field settings.
    // @todo: maybe pass entityType config entity
    $entity = $items->getEntity();
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    // @todo: maybe we can get this data in the #process callback directly from the $item object
    $element['provider_container']['#entity_type'] = $entity_type;
    $element['provider_container']['#bundle'] = $bundle;


    return $element;
  }

  /**
   * Return resource provider configuration form via ajax request at style change.
   * Should have a different name since ajaxCallback can be used by base class.
   */
  public static function ajaxCallbackResourceProvider(array $form, FormStateInterface $form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();
    $visualn_style_id = $form_state->getValue($form_state->getTriggeringElement()['#parents']);
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element['provider_container'];
  }


  // @todo: this is a copy-paste from ResourceProviderGenericDrawingFetcher class
  // @todo: this should be static since may not work on field settings form (see fetcher field widget for example)
  //public static function processDrawerContainerSubform(array $element, FormStateInterface $form_state, $form) {
  public function processProviderContainerSubform(array $element, FormStateInterface $form_state, $form) {
    // @todo: explicitly set #stored_configuration and other keys (#entity_type and #bundle) here
    $element = VisualNFormsHelper::doProcessProviderContainerSubform($element, $form_state, $form);
    return $element;
  }


  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // serialize resource_provider_config
    foreach ($values as &$value) {
      $resource_provider_config = !empty($value['resource_provider_config']) ? $value['resource_provider_config'] : [];
      $value['resource_provider_config'] = serialize($resource_provider_config);
    }
    return $values;
  }

}
