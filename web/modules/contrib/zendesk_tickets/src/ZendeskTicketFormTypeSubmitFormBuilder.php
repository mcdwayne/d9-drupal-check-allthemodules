<?php

namespace Drupal\zendesk_tickets;

use Drupal\Core\Entity\EntityHandlerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Bytes;

/**
 * Defines a class to build the Zendesk Ticket Submit Form.
 *
 * @see \Drupal\zendesk_tickets\Entity\ZendeskTicketFormType
 */
class ZendeskTicketFormTypeSubmitFormBuilder extends EntityHandlerBase implements ZendeskTicketFormTypeSubmitFormBuilderInterface {

  /**
   * The entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Information about the entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The config object for the Zendesk tickets module.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The currently logged-in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * A sanitized list of form type options.
   *
   * @var array
   */
  protected $formTypeOptions;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('config.factory'),
      $container->get('current_user')
    );
  }

  /**
   * Constructs a new  object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory object being edited.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The currently logged-in user.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, ConfigFactoryInterface $config_factory, AccountInterface $current_user) {
    $this->entityTypeId = $entity_type->id();
    $this->storage = $storage;
    $this->entityType = $entity_type;
    $this->config = $config_factory->get('zendesk_tickets.settings');
    $this->currentUser = $current_user;
  }

  /**
   * Defines the Zendesk mapping to Drupal field element type.
   */
  public static function ticketFieldBuilderMap() {
    return [
      'email' => 'Drupal\zendesk_tickets\TicketFieldBuilder\EmailBuilder',
      'subject' => 'Drupal\zendesk_tickets\TicketFieldBuilder\TextfieldBuilder',
      'description' => 'Drupal\zendesk_tickets\TicketFieldBuilder\TextareaBuilder',
      'checkbox' => 'Drupal\zendesk_tickets\TicketFieldBuilder\CheckboxBuilder',
      'date' => 'Drupal\zendesk_tickets\TicketFieldBuilder\DateBuilder',
      'decimal' => 'Drupal\zendesk_tickets\TicketFieldBuilder\NumberBuilder',
      'integer' => 'Drupal\zendesk_tickets\TicketFieldBuilder\NumberBuilder',
      'regexp' => 'Drupal\zendesk_tickets\TicketFieldBuilder\TextfieldBuilder',
      'tagger' => 'Drupal\zendesk_tickets\TicketFieldBuilder\SelectBuilder',
      'text' => 'Drupal\zendesk_tickets\TicketFieldBuilder\TextfieldBuilder',
      'textarea' => 'Drupal\zendesk_tickets\TicketFieldBuilder\TextareaBuilder',
    ];
  }

  /**
   * Determine the form element builder for the Zendesk field.
   *
   * @param object $field
   *   The Zendesk field definition.
   *
   * @return string|null
   *   The class method.
   */
  public static function ticketFieldBuilder($field) {
    if (empty($field->type)) {
      return NULL;
    }

    $map = static::ticketFieldBuilderMap();
    if (isset($map[$field->type])) {
      $builder_class = $map[$field->type];
      return new $builder_class($field);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(EntityInterface $entity = NULL) {
    $form = [];

    $elements = [];
    $have_entity = isset($entity);
    $can_submit = $have_entity && $entity->canSubmit();

    // Request type selection.
    // Show only for default form and for forms that can be submitted.
    if (!$have_entity || $can_submit) {
      $form_type_selector_element = $this->buildFormTypeSelector($entity);
      if ($form_type_selector_element) {
        $elements['ticket_form_id'] = $form_type_selector_element;
      }
    }

    // Add entity form.
    if ($have_entity) {
      // Update form selector.
      if (isset($elements['ticket_form_id'])) {
        if (!isset($elements['ticket_form_id']['#links'][$entity->id()])) {
          // Remove if viewing a form not in the options.
          // Example: Admin view a disabled form.
          unset($elements['ticket_form_id']);
        }
      }

      // Add fields.
      if (($form_object = $entity->getTicketFormObject()) && !empty($form_object->ticket_fields)) {
        $elements['anonymous_requester_email'] = [
          '#type' => 'email',
          '#title' => $this->t('Your email address'),
          '#required' => TRUE,
          '#weight' => -100,
        ];

        $max_weight = 0;
        foreach ($form_object->ticket_fields as $ticket_field_id => $ticket_field) {
          $field_element = $this->buildFieldElement($ticket_field);
          if ($field_element) {
            $elements["field_{$ticket_field_id}"] = $field_element;
            if (isset($field_element['#weight']) && $field_element['#weight'] > $max_weight) {
              $max_weight = $field_element['#weight'];
            }
          }
        }

        $upload_element = $this->buildUploadElement($entity);
        if ($upload_element) {
          $elements['attachments'] = $upload_element;
          $elements['attachments']['#weight'] = $max_weight + 100;
        }
      }
      else {
        // No fields for this form.
        $can_submit = FALSE;
      }
    }
    else {
      // Form selection page.
      if (!empty($elements['ticket_form_id'])) {
        // Show label on form selection page.
        $elements['label'] = [
          '#type' => 'html_tag',
          '#tag' => 'label',
          '#value' => $this->t('Please choose your issue below'),
          '#weight' => -99999,
        ];
      }
    }

    if ($elements) {
      $form['request'] = [
        '#type' => 'container',
        '#tree' => TRUE,
      ];
      $form['request'] += $elements;

      if ($can_submit) {
        $form['actions'] = [
          '#type' => 'actions',
          'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Submit'),
          ],
        ];
      }
    }

    return $form;
  }

  /**
   * Build the form element for a field.
   *
   * @param object $field
   *   The Zendesk field definition.
   *
   * @return array
   *   The Drupal equivalent form render array.
   */
  protected function buildFieldElement($field) {
    // Exit if this field should not be displayed.
    if (empty($field->active) || empty($field->visible_in_portal) || empty($field->editable_in_portal)) {
      return [];
    }

    $element = [];

    // Determine element builder.
    $builder = static::ticketFieldBuilder($field);
    if (!empty($builder)) {
      $element = $builder->getElement();
    }

    return $element;
  }

  /**
   * Build to form type selector element.
   *
   * @param EntityInterface|null $selected_entity
   *   The currently selected entity.
   *
   * @return array|null
   *   A form element array.
   */
  protected function buildFormTypeSelector(EntityInterface $selected_entity = NULL) {
    $element = NULL;
    $form_type_options = $this->getFormTypeOptions($selected_entity);
    if (!empty($form_type_options)) {
      $have_entity = isset($selected_entity) && $selected_entity->id();
      $base_classname = 'zendesk-tickets-form-selector';
      $classes = [$base_classname];
      $classes[] = $have_entity ? "{$base_classname}--with-selection" : "{$base_classname}--no-selection";
      $element = [
        '#type' => 'dropbutton',
        '#subtype' => 'zendesk_tickets_form_selector',
        '#links' => $form_type_options,
        '#attributes' => [
          'class' => $classes,
        ],
        '#attached' => [
          'library' => ['zendesk_tickets/zendesk-tickets-form-selector'],
        ],
      ];
    }

    return $element;
  }

  /**
   * Builds a sanitized list of form type options.
   *
   * @param EntityInterface|null $selected_entity
   *   The currently selected entity.
   *
   * @return array
   *   An array of sanitized options.
   */
  protected function getFormTypeOptions(EntityInterface $selected_entity = NULL) {
    if (!isset($this->formTypeOptions)) {
      $options = [];

      // @TODO: should this be loadMultipleOverrideFree()?
      // See ConfigEntityListBuilder::load().
      $form_types = $this->storage->loadMultiple();
      if (!empty($form_types)) {
        // Sort the entities using the entity class's sort() method.
        // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
        uasort($form_types, array($this->entityType->getClass(), 'sort'));

        // Build options with incremented weight to match sorted order.
        $weight = 0;
        foreach ($form_types as $form_id => $form_type) {
          $weight++;
          if ($form_type->access('submit') && $form_type->canSubmit()) {
            $options[$form_id] = [
              'title' => $form_type->label(),
              'weight' => $weight,
              'url' => $form_type->urlInfo(),
            ];
          }
        }
      }

      $this->formTypeOptions = $options;
    }

    $output_options = $this->formTypeOptions;

    // Adjust options for the selected entity.
    // For the dropbutton implementation, the first option is the selected.
    if (isset($selected_entity) && $selected_entity->id()) {
      // Form page.
      $output_options = $this->formTypeOptions ?: [];
      $selected_id = $selected_entity->id();
      if (isset($output_options[$selected_id])) {
        // Move the selected item to the top of the options.
        $output_options[$selected_id]['weight'] = -99999;

        // Remove the url and link since the current page should be this url.
        unset($output_options[$selected_id]['url']);

        // Wrap the title in a span tag instead of a link.
        $output_options[$selected_id]['title'] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $output_options[$selected_id]['title'],
          '#attributes' => [
            'class' => ['dropbutton-action--selected'],
          ],
        ];
      }
    }
    else {
      // All selection.
      $empty_option = [
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => ' - ',
          '#attributes' => [
            'class' => ['dropbutton-action--selected'],
          ],
        ],
        'weight' => -99999,
      ];
      $output_options[''] = $empty_option;
    }

    uasort($output_options, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $output_options;
  }

  /**
   * Provide the default upload extensions.
   *
   * By providing a default list, we prevent a potential security issue
   * that would allow files of any type to be uploaded.
   *
   * @return string
   *   A file extensions string compatible with upload_validators.
   */
  public static function defaultUploadExtensions() {
    return 'jpg jpeg gif png';
  }

  /**
   * Build the file upload form element.
   *
   * @param EntityInterface|null $selected_entity
   *   The currently selected entity.
   *
   * @return array
   *   The Drupal equivalent form render array.
   */
  protected function buildUploadElement(EntityInterface $selected_entity) {
    $element = NULL;

    if ($this->config->get('file_upload_enabled') &&
        $selected_entity->supportsFileUploads() &&
        $this->currentUser->hasPermission('upload files on zendesk ticket forms')) {
      $upload_validators = $this->getUploadValidators();
      $element = [
        '#type' => 'plupload',
        '#title' => $this->t('Attachments'),
        '#description' => $this->t('Upload or drop files here.'),
        '#autoupload' => TRUE,
        '#autosubmit' => FALSE,
        '#upload_validators' => $upload_validators,
        '#plupload_settings' => [
          'runtimes' => 'html5,html4',
          'unique_names' => TRUE,
        ],
      ];

      // Max file size client side validation.
      // Note: file extensions are added by the plupload element already.
      if (!empty($upload_validators['file_validate_size'][0])) {
        $element['#plupload_settings']['max_file_size'] = $upload_validators['file_validate_size'][0] . 'b';
      }
    }

    return $element;
  }

  /**
   * Retrieves the upload validators for a file field.
   *
   * @return array
   *   An array suitable for passing to file_save_upload() or the file field
   *   element's '#upload_validators' property.
   */
  public function getUploadValidators() {
    $validators = array();

    // Cap the upload size according to the PHP limit.
    $max_filesize = Bytes::toInt(file_upload_max_size());
    $max_filesize_config = $this->config->get('file_upload_max_size');
    if (!empty($max_filesize_config)) {
      $max_filesize = min($max_filesize, Bytes::toInt($max_filesize_config));
    }

    // There is always a file size limit due to the PHP server limit.
    $validators['file_validate_size'] = [$max_filesize];

    // Add the extension check if necessary.
    $upload_extensions = $this->config->get('file_upload_extensions');
    if (!empty($upload_extensions)) {
      $validators['file_validate_extensions'] = [$upload_extensions];
    }
    else {
      // By setting a default list, we prevent a potential security issue
      // that would allow files of any type to be uploaded.
      $validators['file_validate_extensions'] = [$this->defaultUploadExtensions()];
    }

    return $validators;
  }

}
