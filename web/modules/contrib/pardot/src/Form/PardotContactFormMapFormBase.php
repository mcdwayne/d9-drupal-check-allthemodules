<?php

namespace Drupal\pardot\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\Core\Entity\EntityFieldManager;

/**
 * Class PardotContactFormMapFormBase.
 *
 * @package Drupal\pardot\Form
 *
 * @ingroup pardot
 */
class PardotContactFormMapFormBase extends EntityForm {

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $query_factory;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFormBuilder
   */
  protected $entityFormBuilder;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Construct the PardotCampaignFormBase.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   An entity query factory for the campaign entity type.
   */
  public function __construct(QueryFactory $query_factory, EntityTypeManagerInterface $entityTypeManager, EntityFormBuilder $entityFormBuilder, EntityFieldManager $entityFieldManager) {
    $this->query_factory = $query_factory;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFormBuilder = $entityFormBuilder;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * Factory method for PardotCampaignFormBase.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   *
   * Builds the entity add/edit form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An associative array containing the campaign add/edit form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // @Todo: Set defaults from config entity $mapping.
    $mapping = $this->entity;

    // Build the form.
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $mapping->label(),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $mapping->id(),
      '#machine_name' => array(
        'exists' => array($this, 'exists'),
        'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
        'error' => 'The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".',
      ),
      '#disabled' => !$mapping->isNew(),
    );

    $form['status'] = array(
      '#title' => 'Is active',
      '#type' => 'checkbox',
      '#default_value' => $mapping->status,
    );
    $form['post_url'] = array(
      '#title' => 'Post url',
      '#type' => 'textfield',
      '#default_value' => $mapping->post_url,
      '#description' => $this->t('Visit your "Form Handlers" page in Pardot. Click on a form link and then copy the "Endpoint URL" value here.'),
    );

    // Provide contact_form config entities as options.
    $options = array();
    $contact_storage = $this->entityTypeManager->getStorage('contact_form');
    $contact_forms = $contact_storage->loadMultiple();
    // @Todo: Remove options that are already configured, set help text w/
    // those already set.
    foreach ($contact_forms as $contact_form) {
      $options[$contact_form->id()] = $contact_form->label();
    }

    // Disable caching on this form.
    $form_state->setCached(FALSE);
    $form['contact_form_id'] = array(
      '#type' => 'select',
      '#title' => $this->t('Contact Form'),
      '#options' => $options,
      '#default_value' => $mapping->contact_form_id,
      '#required' => TRUE,
      '#ajax' => array(
        'callback' => '::updateMapSettings',
        'wrapper' => 'pardot-form-mapping-wrapper',
        'effect' => 'fade',
        'progress' => array(
          'type' => 'throbber',
          'message' => t('Loading the field mapping table...'),
        ),
      ),
      '#disabled' => !$mapping->isNew(),
    );

    // Add target element for ajax callback.
    $form['container'] = array(
      '#type' => 'container',
      '#attributes' => array('id' => 'pardot-form-mapping-wrapper'),
    );

    // When editing or when rebuilding form after ajax callback, add mapping
    // table element with selected form field definitions.

    if (isset($mapping->contact_form_id)) {
      $bundle = $mapping->contact_form_id;
    }
    elseif ($form_state->getValue('contact_form_id') !== NULL) {
      $bundle = $form_state->getValue('contact_form_id');
    }
    else {
      $bundle = NULL;
    }

    if (isset($bundle)) {
      $bundle_fields = array();
      $field_definitions = $this->entityFieldManager->getFieldDefinitions('contact_message', $bundle);
      foreach ($field_definitions as $field_name => $field_definition) {
        $bundle_fields[] = array(
          'field_label' => (string) $field_definition->getLabel(),
          'field_name' => $field_definition->getName(),
          'field_type' => $field_definition->getType(),
        );
      }

      $header = array(
        'field_label' => $this->t('Field Label'),
        'field_name' => $this->t('Field Name'),
        'field_type' => $this->t('Field Type'),
        'pardot_key' => $this->t('Pardot External Field Name'),
      );

      $form['container']['mapped_fields'] = array(
        '#type' => 'table',
        '#caption' => $this->t('Add Pardot External Field Names to map contact form elements.'),
        '#header' => $header,
        '#tree' => TRUE,
      );

      // Generate tables rows.
      foreach ($bundle_fields as $field) {
        $form['container']['mapped_fields'][$field['field_name']]['field_label'] = array(
          '#plain_text' => $field['field_label'],
        );
        $form['container']['mapped_fields'][$field['field_name']]['field_name'] = array(
          '#plain_text' => $field['field_name'],
        );
        $form['container']['mapped_fields'][$field['field_name']]['field_type'] = array(
          '#plain_text' => $field['field_type'],
        );
        $form['container']['mapped_fields'][$field['field_name']]['pardot_key'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Pardot External Field Name'),
          '#title_display' => 'invisible',
          '#default_value' => isset($mapping->mapped_fields[$field['field_name']]) ? $mapping->mapped_fields[$field['field_name']] : NULL,
        );
      }
    }

    return $form;
  }

  /**
   * Checks for an existing Pardot Campaign.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   * @param FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if this Pardot Campaign already exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element, FormStateInterface $form_state) {
    // Use the query factory to build a new Pardot Campaign entity query.
    $query = $this->query_factory->get('pardot_contact_form_map');

    // Query the entity ID to see if its in use.
    $result = $query->condition('id', $element['#field_prefix'] . $entity_id)
      ->execute();

    // We don't need to return the ID, only if it exists or not.
    return (bool) $result;
  }

  /**
   * Updates the form with field mapping table.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response with updated options for the embed type.
   */
  public function updateMapSettings(array &$form, FormStateInterface $form_state) {
    return $form['container'];
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   *
   * To set the submit button text, we need to override actions().
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // Get the basic actions from the base class.
    $actions = parent::actions($form, $form_state);

    // Change the submit button text.
    $actions['submit']['#value'] = $this->t('Save');

    // Return the result.
    return $actions;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function validate(array $form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);

    // Add code here to validate your config entity's form elements.
    // Nothing to do here...yet
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $mapping = array();
    $table = $form_state->getValue('mapped_fields');
    foreach ($table as $k => $v) {
      if (!empty($v['pardot_key'])) {
        $mapping[$k] = $v['pardot_key'];
      }
    }
    $form_state->setValue('mapped_fields', $mapping);
    parent::submitForm($form, $form_state);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   *
   * Saves the entity. This is called after submit() has built the entity from
   * the form values. Do not override submit() as save() is the preferred
   * method for entity form controllers.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function save(array $form, FormStateInterface $form_state) {
    $mapping = $this->getEntity();

    // Drupal already populated the form values in the entity object. Each
    // form field was saved as a public variable in the entity class. PHP
    // allows Drupal to do this even if the method is not defined ahead of
    // time.
    $status = $mapping->save();

    // Grab the URL of the new entity. We'll use it in the message.
    $url = $mapping->urlInfo();

    // Create an edit link.
    $edit_link = $this->l($this->t('Edit'), $url);

    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity.
      drupal_set_message($this->t('Pardot Contact Form Mapping %label has been updated.', array('%label' => $mapping->label())));
      $this->logger('contact')->notice('Pardot Contact Form Mapping %label has been updated.', ['%label' => $mapping->label(), 'link' => $edit_link]);
    }
    else {
      // If we created a new entity.
      drupal_set_message($this->t('Pardot Contact Form Mapping %label has been added.', array('%label' => $mapping->label())));
      $this->logger('contact')->notice('Pardot Contact Form Mapping %label has been added.', ['%label' => $mapping->label(), 'link' => $edit_link]);
    }

    // Redirect the user back to the listing route after the save operation.
    $form_state->setRedirect('pardot.pardot_contact_form_map.list');
  }

}
