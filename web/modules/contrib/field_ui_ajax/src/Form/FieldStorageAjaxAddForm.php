<?php

/**
 * @file
 * Contains \Drupal\field_ui_ajax\Form\FieldStorageAjaxAddForm.
 */

namespace Drupal\field_ui_ajax\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormState;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\field_ui\Form\FieldStorageAddForm;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a form for the "field storage" add page.
 */
class FieldStorageAjaxAddForm extends FieldStorageAddForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL) {
    $form = parent::buildForm($form, $form_state, $entity_type_id, $bundle);

    $form['actions']['submit']['#ajax'] = [
      'callback' => '::addFieldAjaxFormSubmit',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $error = FALSE;
    $values = $form_state->getValues();
    $destinations = array();
    $entity_type = $this->entityManager->getDefinition($this->entityTypeId);

    // Create new field.
    if ($values['new_storage_type']) {
      $field_storage_values = [
        'field_name' => $values['field_name'],
        'entity_type' => $this->entityTypeId,
        'type' => $values['new_storage_type'],
        'translatable' => $values['translatable'],
      ];
      $field_values = [
        'field_name' => $values['field_name'],
        'entity_type' => $this->entityTypeId,
        'bundle' => $this->bundle,
        'label' => $values['label'],
        // Field translatability should be explicitly enabled by the users.
        'translatable' => FALSE,
      ];
      $widget_id = $formatter_id = NULL;

      // Check if we're dealing with a preconfigured field.
      if (strpos($field_storage_values['type'], 'field_ui:') !== FALSE) {
        list(, $field_type, $option_key) = explode(':', $field_storage_values['type'], 3);
        $field_storage_values['type'] = $field_type;

        $field_type_class = $this->fieldTypePluginManager->getDefinition($field_type)['class'];
        $field_options = $field_type_class::getPreconfiguredOptions()[$option_key];

        // Merge in preconfigured field storage options.
        if (isset($field_options['field_storage_config'])) {
          foreach (array('cardinality', 'settings') as $key) {
            if (isset($field_options['field_storage_config'][$key])) {
              $field_storage_values[$key] = $field_options['field_storage_config'][$key];
            }
          }
        }

        // Merge in preconfigured field options.
        if (isset($field_options['field_config'])) {
          foreach (array('required', 'settings') as $key) {
            if (isset($field_options['field_config'][$key])) {
              $field_values[$key] = $field_options['field_config'][$key];
            }
          }
        }

        $widget_id = isset($field_options['entity_form_display']['type']) ? $field_options['entity_form_display']['type'] : NULL;
        $formatter_id = isset($field_options['entity_view_display']['type']) ? $field_options['entity_view_display']['type'] : NULL;
      }

      // Create the field storage and field.
      try {
        $field_storage_config = $this->entityManager->getStorage('field_storage_config')->create($field_storage_values);
        $field_storage_config->save();
        $form_state->set('field_storage_config', $field_storage_config);
        $field = $this->entityManager->getStorage('field_config')->create($field_values);
        $field->save();
        $form_state->set('field_config', $field);

        $this->configureEntityFormDisplay($values['field_name'], $widget_id);
        $this->configureEntityViewDisplay($values['field_name'], $formatter_id);

        // Always show the field settings step, as the cardinality needs to be
        // configured for new fields.
        $route_parameters = array(
          'field_config' => $field->id(),
        ) + FieldUI::getRouteBundleParameter($entity_type, $this->bundle);
        $destinations[] = array('route_name' => "entity.field_config.{$this->entityTypeId}_storage_edit_form", 'route_parameters' => $route_parameters);
        $destinations[] = array('route_name' => "entity.field_config.{$this->entityTypeId}_field_edit_form", 'route_parameters' => $route_parameters);
        $destinations[] = array('route_name' => "entity.{$this->entityTypeId}.field_ui_fields", 'route_parameters' => $route_parameters);

        // Store new field information for any additional submit handlers.
        $form_state->set(['fields_added', '_add_new_field'], $values['field_name']);
      }
      catch (\Exception $e) {
        $error = TRUE;
        drupal_set_message($this->t('There was a problem creating field %label: @message', array('%label' => $values['label'], '@message' => $e->getMessage())), 'error');
      }
    }

    // Re-use existing field.
    if ($values['existing_storage_name']) {
      $field_name = $values['existing_storage_name'];

      try {
        $field = $this->entityManager->getStorage('field_config')->create(array(
          'field_name' => $field_name,
          'entity_type' => $this->entityTypeId,
          'bundle' => $this->bundle,
          'label' => $values['existing_storage_label'],
        ));
        $field->save();
        $form_state->set('field_config', $field);

        $this->configureEntityFormDisplay($field_name);
        $this->configureEntityViewDisplay($field_name);

        $route_parameters = array(
          'field_config' => $field->id(),
        ) + FieldUI::getRouteBundleParameter($entity_type, $this->bundle);
        $destinations[] = array('route_name' => "entity.field_config.{$this->entityTypeId}_field_edit_form", 'route_parameters' => $route_parameters);
        $destinations[] = array('route_name' => "entity.{$this->entityTypeId}.field_ui_fields", 'route_parameters' => $route_parameters);

        // Store new field information for any additional submit handlers.
        $form_state->set(['fields_added', '_add_existing_field'], $field_name);
      }
      catch (\Exception $e) {
        $error = TRUE;
        drupal_set_message($this->t('There was a problem creating field %label: @message', array('%label' => $values['label'], '@message' => $e->getMessage())), 'error');
      }
    }

    if ($destinations) {
      $destination = $this->getDestinationArray();
      $destinations[] = $destination['destination'];
      $next_destination = FieldUI::getNextDestination($destinations, $form_state);
      $form_state->set('next_destination', $next_destination);
      $form_state->set('destinations', $destinations);
      $form_state->setRedirectUrl($next_destination);
    }
    elseif (!$error) {
      drupal_set_message($this->t('Your settings have been saved.'));
    }
  }

  public static function addFieldAjaxFormSubmit(&$form, FormStateInterface &$form_state) {
    $response = new AjaxResponse();

    if ($form_state->hasAnyErrors()) {
      $build = [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        'messages' => [
          '#type' => 'status_messages',
        ],
        'form' => $form,
      ];
      $response->addCommand(new HtmlCommand(
        '.block-system-main-block',
        $build
      ));
      return $response;
    }

    $storage = $form_state->getStorage();
    $next_state = new FormState();
    $next_state->set('add_field_multistep', TRUE);
    $next_state->set('next_destination', $storage['next_destination']);
    $next_state->set('destinations', $storage['destinations']);
    if (isset($storage['field_storage_config'])) {
      $entityManager = \Drupal::getContainer()->get('entity.manager');
      $form_object = $entityManager->getFormObject('field_storage_config', 'edit');
      $form_object->setEntity($storage['field_storage_config']);
      $next_state->addBuildInfo('args', [$storage['field_config']->id()]);
      $next_form = \Drupal::formBuilder()->buildForm($form_object, $next_state);
    }
    else {
      $entityManager = \Drupal::getContainer()->get('entity.manager');
      $form_object = $entityManager->getFormObject('field_config', 'edit');
      $form_object->setEntity($storage['field_config']);
      $next_form = \Drupal::formBuilder()->buildForm($form_object, $next_state);
    }
    $build = [
      'messages' => ['#type' => 'status_messages'],
      'form' => $next_form,
    ];
    $response->addCommand(new HtmlCommand(
      '.block-system-main-block',
      $build
    ));
    return $response;
  }
}
