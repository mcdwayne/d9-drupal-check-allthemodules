<?php

namespace Drupal\visualn_dataset\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\HttpFoundation\Request;

use Drupal\visualn\Helpers\VisualNFormsHelper;

/**
 * Class VisualNDataSourceForm.
 */
class VisualNDataSourceForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $visualn_data_source = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $visualn_data_source->label(),
      '#description' => $this->t("Label for the VisualN Data Source."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $visualn_data_source->id(),
      '#machine_name' => [
        'exists' => '\Drupal\visualn_dataset\Entity\VisualNDataSource::load',
      ],
      '#disabled' => !$visualn_data_source->isNew(),
    ];

    $resource_providers_list = [];
    $visualNResourceProviderManager = \Drupal::service('plugin.manager.visualn.resource_provider');
    $definitions = $visualNResourceProviderManager->getDefinitions();
    foreach ($definitions as $definition) {
      if (!empty($definition['context'])) {
        foreach ($definition['context'] as $name => $context_definition) {
          if ($context_definition->isRequired()) {
            // exclude providers with required contexts
            continue 2;
          }
        }
      }

      $resource_providers_list[$definition['id']] = $definition['label'];
    }

    $ajax_wrapper_id =  'resource_provider-config-form-ajax';

    $default_provider = $visualn_data_source->getResourceProviderId();
    $form['resource_provider_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Resource Provider'),
      '#options' => $resource_providers_list,
      '#default_value' => $default_provider,
      '#description' => $this->t("Resource Provider for the data source."),
      '#ajax' => [
        'callback' => '::ajaxCallbackResourceProvider',
        'wrapper' => $ajax_wrapper_id,
      ],
      '#empty_value' => '',
      '#required' => TRUE,
    ];
    $form['provider_container'] = [
      '#tree' => TRUE,
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#type' => 'container',
      '#process' => [[$this, 'processProviderContainerSubform']],
    ];
    $stored_configuration = [
      'resource_provider_id' => $visualn_data_source->getResourceProviderId(),
      'resource_provider_config' => $visualn_data_source->getResourceProviderConfig(),
    ];
    $form['provider_container']['#stored_configuration'] = $stored_configuration;

    // Set empty values for entity_type and bundle contexts since resource providers with
    // required contexts are not allowed for data sources.
    $form['provider_container']['#entity_type'] = '';
    $form['provider_container']['#bundle'] = '';

    // @todo: add into documentation
    // Using data sources is supposed to be a good practice since it allows to limit the list of resource providers
    // in fetchers selects only to those that are needed but when a resource provider has required contexts
    // it can be used only directly using corresponding fetcher.

    return $form;
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
  public function save(array $form, FormStateInterface $form_state) {
    $visualn_data_source = $this->entity;
    $status = $visualn_data_source->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label VisualN Data Source.', [
          '%label' => $visualn_data_source->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label VisualN Data Source.', [
          '%label' => $visualn_data_source->label(),
        ]));
    }
    $form_state->setRedirectUrl($visualn_data_source->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // @todo: seems that there is no need in submitForm() here
  }

}
