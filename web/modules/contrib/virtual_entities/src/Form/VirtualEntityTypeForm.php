<?php

namespace Drupal\virtual_entities\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class VirtualEntityTypeForm.
 *
 * @package Drupal\virtual_entities\Form
 */
class VirtualEntityTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $virtual_entity_type = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $virtual_entity_type->label(),
      '#description' => $this->t("Label for the Virtual entity type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $virtual_entity_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\virtual_entities\Entity\VirtualEntityType::load',
      ],
      '#disabled' => !$virtual_entity_type->isNew(),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $virtual_entity_type->getDescription(),
      '#description' => $this->t('Virtual entity type description.'),
    ];

    $form['additional_settings'] = [
      '#type' => 'vertical_tabs',
      '#attached' => [
        'library' => ['node/drupal.content_types'],
      ],
    ];

    // Endpoint settings.
    $form['endpoint_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Endpoint settings'),
      '#group' => 'additional_settings',
      '#open' => TRUE,
    ];

    $form['endpoint_settings']['endpoint'] = [
      '#title' => t('Endpoint'),
      '#type' => 'textfield',
      '#default_value' => $virtual_entity_type->getEndpoint(),
      '#description' => t('Virtual entity endpoint.'),
      '#required' => TRUE,
    ];

    $form['endpoint_settings']['entities_identity'] = [
      '#title' => t('Entities identity'),
      '#type' => 'textfield',
      '#default_value' => $virtual_entity_type->getEntitiesIdentity(),
      '#description' => t('Virtual entities identity.'),
    ];

    $parameters = $virtual_entity_type->getParameters();
    $list_lines = [];

    if (!empty($parameters['list'])) {
      foreach ($parameters['list'] as $parameter => $value) {
        $list_lines[] = "$parameter|$value";
      }
    }

    $form['endpoint_settings']['parameters']['list'] = [
      '#type' => 'textarea',
      '#title' => t('Entities list parameters'),
      '#description' => t('Enter the parameters to add to the endpoint URL when loading the list of entities. One per line in the format "parameter_name|parameter_value"'),
      '#default_value' => implode("\n", $list_lines),
    ];

    // Storage client settings.
    $form['storage_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Storage settings'),
      '#group' => 'additional_settings',
      '#open' => FALSE,
    ];

    // List storage clients.
    $plugin_manager = \Drupal::service('plugin.manager.virtual_entity.storage_client.plugin.processor');
    $plugins = $plugin_manager->getDefinitions();
    $storageClientOptions = [];
    foreach ($plugins as $client) {
      $storageClientOptions[$client['id']] = $client['label'];
    }
    $form['storage_settings']['client'] = [
      '#type' => 'select',
      '#title' => $this->t('Storage client'),
      '#options' => $storageClientOptions,
      '#required' => TRUE,
      '#default_value' => $virtual_entity_type->getClient(),
    ];

    // List supported formats.
    $formats = \Drupal::service('virtual_entity.storage_client.decoder')->supportedFormats();
    $form['storage_settings']['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#options' => array_combine($formats, $formats),
      '#required' => TRUE,
      '#default_value' => $virtual_entity_type->getFormat(),
    ];

    // Field mappings.
    $form['field_mappings'] = [
      '#type' => 'details',
      '#title' => $this->t('Field mappings'),
      '#group' => 'additional_settings',
      '#open' => FALSE,
    ];

    // Get entity field manager.
    $entityFieldManager = \Drupal::service('entity_field.manager');

    // Fetch fields definitions.
    if ($this->operation == 'add') {
      $fields = $entityFieldManager->getBaseFieldDefinitions('virtual_entity');
    }
    else {
      $fields = $entityFieldManager->getFieldDefinitions('virtual_entity', $virtual_entity_type->id());
    }

    // Remove the not used fields.
    unset($fields[$this->entityTypeManager->getDefinition('virtual_entity')->getKey('uuid')]);
    unset($fields[$this->entityTypeManager->getDefinition('virtual_entity')->getKey('bundle')]);

    foreach ($fields as $field) {
      $form['field_mappings'][$field->getName()] = [
        '#title' => $field->getLabel(),
        '#type' => 'textfield',
        '#default_value' => $virtual_entity_type->getFieldMapping($field->getName()),
        '#required' => isset($fields[$field->getName()]),
      ];
    }

    // Set form as tree so we can save details.
    $form['#tree'] = TRUE;

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $id = trim($form_state->getValue('id'));
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName('id', $this->t("Invalid machine-readable name. Enter a name other than %invalid.", ['%invalid' => $id]));
    }

    // Set custom settings.
    $form_state->setValue('endpoint', $form_state->getValue(['endpoint_settings', 'endpoint']));
    $form_state->setValue('entities_identity', $form_state->getValue(['endpoint_settings', 'entities_identity']));

    // Set endpoint parameters.
    $parameters_types = ['list', 'single'];
    foreach ($parameters_types as $parameters_type) {
      $parameters_string = $form_state->getValue(['endpoint_settings', 'parameters'], $parameters_type);
      // Set default array.
      $parameters[$parameters_type] = [];
      if (isset($parameters_string[$parameters_type])) {
        $list = preg_split('(\r\n|\r|\n)', $parameters_string[$parameters_type]);
        $list = array_map('trim', $list);
        $list = array_filter($list, 'strlen');
        foreach ($list as $text) {
          // Check for an explicit key.
          $matches = [];
          if (preg_match('/(.*)\|(.*)/', $text, $matches)) {
            // Trim key and value to avoid unwanted spaces issues.
            $key = trim($matches[1]);
            $value = trim($matches[2]);
          }
          // Otherwise see if we can use the value as the key.
          else {
            $key = $value = $text;
          }
          $parameters[$parameters_type][$key] = $value;
        }
        $form_state->setValue('parameters', $parameters);
      }
    }
    $form_state->unsetValue('endpoint_settings');

    // Set storage settings.
    $form_state->setValue('client', $form_state->getValue(['storage_settings', 'client']));
    $form_state->setValue('format', $form_state->getValue(['storage_settings', 'format']));
    $form_state->unsetValue('storage_settings');

    // Set field mappings.
    $form_state->setValue('field_mappings', array_filter($form_state->getValue('field_mappings')));
    $form_state->unsetValue('field_mappings');
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $virtual_entity_type = $this->entity;
    $status = $virtual_entity_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Virtual entity type.', [
          '%label' => $virtual_entity_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Virtual entity type.', [
          '%label' => $virtual_entity_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($virtual_entity_type->urlInfo('collection'));
  }

}
