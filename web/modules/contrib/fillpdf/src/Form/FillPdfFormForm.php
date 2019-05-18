<?php

namespace Drupal\fillpdf\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\fillpdf\Component\Utility\FillPdf;
use Drupal\fillpdf\FillPdfAdminFormHelperInterface;
use Drupal\fillpdf\FillPdfLinkManipulatorInterface;
use Drupal\fillpdf\InputHelperInterface;
use Drupal\fillpdf\SerializerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Form controller for the FillPDFForm edit form.
 */
class FillPdfFormForm extends ContentEntityForm {

  /**
   * The FillPdf admin form helper.
   *
   * @var \Drupal\fillpdf\FillPdfAdminFormHelperInterface
   */
  protected $adminFormHelper;

  /**
   * The FillPdf link manipulator.
   *
   * @var \Drupal\fillpdf\FillPdfLinkManipulatorInterface
   */
  protected $linkManipulator;

  /**
   * The FillPdf link manipulator.
   *
   * @var \Drupal\fillpdf\InputHelperInterface
   */
  protected $inputHelper;

  /**
   * The FillPdf serializer.
   *
   * @var \Drupal\fillpdf\SerializerInterface
   */
  protected $serializer;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a FillPdfFormForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\fillpdf\FillPdfAdminFormHelperInterface $admin_form_helper
   *   FillPdf admin form helper.
   * @param \Drupal\fillpdf\FillPdfLinkManipulatorInterface $link_manipulator
   *   FillPdf link manipulator.
   * @param \Drupal\fillpdf\InputHelperInterface $input_helper
   *   FillPdf link manipulator.
   * @param \Drupal\fillpdf\SerializerInterface $fillpdf_serializer
   *   FillPdf serializer.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   Helpers to operate on files and stream wrappers.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity repository service.
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    FillPdfAdminFormHelperInterface $admin_form_helper,
    FillPdfLinkManipulatorInterface $link_manipulator,
    InputHelperInterface $input_helper,
    SerializerInterface $fillpdf_serializer,
    FileSystemInterface $file_system,
    EntityTypeManager $entity_type_manager = NULL
  ) {
    parent::__construct(
      $entity_repository
    );
    $this->adminFormHelper = $admin_form_helper;
    $this->linkManipulator = $link_manipulator;
    $this->inputHelper = $input_helper;
    $this->serializer = $fillpdf_serializer;
    $this->fileSystem = $file_system;

    if (empty($entity_type_manager)) {
      @trigger_error('Not passing the entity_type.manager service to ContentEntityForm::__construct() is deprecated in FillPdf 8.x-4.8 and will be required before FillPdf 8.x-5.0.', E_USER_DEPRECATED);
      $entity_type_manager = $this->container()->get('entity_type.manager');
    }
    $this->setEntityTypeManager($entity_type_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('fillpdf.admin_form_helper'),
      $container->get('fillpdf.link_manipulator'),
      $container->get('fillpdf.input_helper'),
      $container->get('fillpdf.serializer'),
      $container->get('file_system'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\fillpdf\FillPdfFormInterface $entity */
    $entity = $this->entity;

    $form['title']['token_tree'] = $this->adminFormHelper->getAdminTokenForm();

    $entity_types = [];
    $entity_type_definitions = $this->entityTypeManager->getDefinitions();

    foreach ($entity_type_definitions as $machine_name => $definition) {
      $label = $definition->getLabel();
      $entity_types[$machine_name] = "$machine_name ($label)";
    }

    // @todo: Encapsulate this logic into a ::getDefaultEntityType() method on FillPdfForm
    $stored_default_entity_type = $entity->get('default_entity_type');
    $default_entity_type = count($stored_default_entity_type) ? $stored_default_entity_type->first()->value : NULL;

    $form['default_entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Default entity type'),
      '#options' => $entity_types,
      '#empty_option' => $this->t('- None -'),
      '#weight' => 12.5,
      '#default_value' => $default_entity_type,
      '#ajax' => [
        'callback' => '::ajaxUpdateIdAutocomplete',
        'event' => 'change',
        'wrapper' => 'test-entity-wrapper',
        'progress' => ['type' => 'none'],
      ],
    ];

    // On AJAX-triggered rebuild, work with the user input instead of previously
    // stored values.
    if ($form_state->isRebuilding()) {
      $default_entity_type = $form_state->getValue('default_entity_type');
      $default_entity_id = $form_state->getValue('default_entity_id');
    }
    else {
      $stored_default_entity_id = $entity->get('default_entity_id');
      $default_entity_id = count($stored_default_entity_id) ? $stored_default_entity_id->first()->value : NULL;
    }

    // If a default entity type is set, allow selecting a default entity, too.
    if ($default_entity_type) {
      $default_entity = $default_entity_id ? $this->entityTypeManager->getStorage($default_entity_type)->load($default_entity_id) : NULL;

      if (!empty($default_entity) && $default_entity instanceof EntityInterface) {
        $description = $this->l(
          $this->t('Download this PDF template populated with data from the @type %label (@id).', [
            '@type' => $default_entity_type,
            '%label' => $default_entity->label(),
            '@id' => $default_entity_id,
          ]),
          $this->linkManipulator->generateLink([
            'fid' => $this->entity->id(),
            'entity_ids' => [$default_entity_type => [$default_entity_id]],
          ])
        );
      }
      else {
        $description = $this->t('Enter the title of a %type to test populating the PDF template.', [
          '%type' => $default_entity_type,
        ]);
      }

      $form['default_entity_id'] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('Default entity'),
        '#target_type' => $default_entity_type,
        '#description' => $description,
        '#weight' => 13,
        '#default_value' => $default_entity,
        '#prefix' => '<div id="test-entity-wrapper">',
        '#suffix' => '</div>',
        '#ajax' => [
          'callback' => '::ajaxUpdateIdAutocomplete',
          'event' => 'autocompleteclose autocompletechange',
          'wrapper' => 'test-entity-wrapper',
          'progress' => ['type' => 'none'],
        ],
      ];
    }
    // No default entity type set, so just provide a wrapper for AJAX replace.
    else {
      $form['default_entity_id'] = [
        '#type' => 'hidden',
        '#weight' => 13,
        '#prefix' => '<div id="test-entity-wrapper">',
        '#suffix' => '</div>',
      ];
    }

    $fid = $entity->id();

    /** @var \Drupal\file\FileInterface $file_entity */
    $file_entity = File::load($entity->get('file')->first()->target_id);
    $pdf_info_weight = 0;
    $form['pdf_info'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('PDF form information'),
      '#weight' => $form['default_entity_type']['#weight'] + 1,
      'submitted_pdf' => [
        '#type' => 'item',
        '#title' => $this->t('Uploaded PDF'),
        '#description' => $file_entity->getFileUri(),
        '#weight' => $pdf_info_weight++,
      ],
    ];

    $upload_location = FillPdf::buildFileUri($this->config('fillpdf.settings')->get('template_scheme'), 'fillpdf');
    if (!file_prepare_directory($upload_location, FILE_CREATE_DIRECTORY + FILE_MODIFY_PERMISSIONS)) {
      $this->messenger()->addError($this->t('The directory %directory does not exist or is not writable. Please check permissions.', [
        '%directory' => $this->fileSystem->realpath($upload_location),
      ]));
    }
    else {
      $form['pdf_info']['upload_pdf'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Update PDF template'),
        '#accept' => 'application/pdf',
        '#upload_validators' => [
          'file_validate_extensions' => ['pdf'],
        ],
        '#upload_location' => $upload_location,
        '#description' => $this->t('Update the PDF file used as template by this form.'),
        '#weight' => $pdf_info_weight++,
      ];
    }

    $form['pdf_info']['sample_populate'] = [
      '#type' => 'item',
      '#title' => $this->t('Sample PDF'),
      '#description' => $this->l(
        $this->t('See which fields are which in this PDF.'),
        $this->linkManipulator->generateLink([
          'fid' => $fid,
          'sample' => TRUE,
        ])) . '<br />' .
        $this->t('If you have set a custom path on this PDF, the sample will be saved there silently.'),
      '#weight' => $pdf_info_weight++,
    ];
    $form['pdf_info']['form_id'] = [
      '#type' => 'item',
      '#title' => $this->t('Form info'),
      '#description' => $this->t("Form ID: [@fid].  Populate this form with entity IDs, such as @path<br/>", [
        '@fid' => $fid,
        '@path' => "/fillpdf?fid={$fid}&entity_type=node&entity_id=10",
      ]),
      '#weight' => $pdf_info_weight,
    ];

    $available_schemes = $form['scheme']['widget']['#options'];
    // If only one option is available, this is 'none', so there's nothing to
    // chose.
    if (count($available_schemes) == 1) {
      $form['scheme']['#type'] = 'hidden';
      $form['destination_path']['#type'] = 'hidden';
      $form['destination_redirect']['#type'] = 'hidden';
    }
    // Otherwise show the 'Storage and download' section.
    else {
      $form['storage_download'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Storage and download'),
        '#weight' => $form['pdf_info']['#weight'] + 1,
        '#open' => TRUE,
        '#attached' => [
          'library' => ['fillpdf/form'],
        ],
      ];

      $form['storage_download']['storage'] = [
        '#type' => 'container',
      ];

      // @todo: Check for empty value after Core issue is fixed.
      // See: https://www.drupal.org/project/drupal/issues/1585930
      $states_no_scheme = [
        ':input[name="scheme"]' => ['value' => '_none'],
      ];
      $form['scheme']['#group'] = 'storage';
      $form['destination_path']['#group'] = 'storage';
      $form['destination_path']['widget']['0']['value']['#field_prefix'] = 'fillpdf/';
      $form['destination_path']['#states'] = [
        'invisible' => $states_no_scheme,
      ];
      $form['destination_path']['token_tree'] = $this->adminFormHelper->getAdminTokenForm();
      $description = $this->t('If filled PDFs should be automatically saved to disk, chose a file storage');
      $description .= isset($available_schemes['public']) ? '; ' . $this->t('note that %public storage does not provide any access control.', [
        '%public' => 'public://',
      ]) : '.';
      $description .= ' ' . $this->t('Otherwise, filled PDFs are sent to the browser for download.');
      $form['storage_download']['storage']['description_scheme_none'] = [
        '#type' => 'item',
        '#description' => $description,
        '#weight' => 22,
        '#states' => [
          'visible' => $states_no_scheme,
        ],
      ];
      $form['storage_download']['storage']['description_scheme_set'] = [
        '#type' => 'item',
        '#description' => $this->t('As PDFs are saved to disk, make sure you include the <em>&download=1</em> flag to send them to the browser as well.'),
        '#weight' => 23,
        '#states' => [
          'invisible' => $states_no_scheme,
        ],
      ];

      $form['destination_redirect']['#group'] = 'storage_download';
      $form['destination_redirect']['#states'] = [
        'invisible' => $states_no_scheme,
      ];
    }

    $form['additional_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Additional settings'),
      '#weight' => $form['pdf_info']['#weight'] + 1,
    ];
    $form['replacements']['#group'] = 'additional_settings';

    // @todo: Add a button to let them attempt re-parsing if it failed.
    $form['fillpdf_fields']['fields'] = FillPdf::embedView('fillpdf_form_fields',
      'block_1',
      $entity->id());

    $form['fillpdf_fields']['#weight'] = 100;

    $form['export_fields'] = [
      '#prefix' => '<div>',
      '#markup' => $this->l($this->t('Export these field mappings'), Url::fromRoute('entity.fillpdf_form.export_form', ['fillpdf_form' => $entity->id()])),
      '#suffix' => '</div>',
      '#weight' => 100,
    ];

    $form['import_fields'] = [
      '#prefix' => '<div>',
      '#markup' => $this->l($this->t('Import a previous export into this PDF'), Url::fromRoute('entity.fillpdf_form.import_form', ['fillpdf_form' => $entity->id()])),
      '#suffix' => '</div>',
      '#weight' => 100,
    ];

    return $form;
  }

  /**
   * AJAX callback updating the 'default_entity_id' element.
   *
   * This is triggered whenever either the default entity type is changed or
   * another default entity ID is chosen. It replaces the 'default_entity_id'
   * form element. If triggered by the 'default_entity_type' element, both the
   * description and the autocomplete are reset, the latter being fed with
   * referenceable entities of the chosen entity type. Otherwise, only the
   * description is rebuilt reflecting the chosen default entity ID.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   A render array containing the replacement form element.
   */
  public function ajaxUpdateIdAutocomplete(array &$form, FormStateInterface $form_state) {
    $element = $form['default_entity_id'];
    $triggering_element = reset($form_state->getTriggeringElement()['#array_parents']);
    if ($triggering_element == 'default_entity_type') {
      unset($element['#value']);
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Remove this (imperfect) workaround once the Core issue is fixed.
   *   See https://www.drupal.org/project/fillpdf/issues/3046178.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    // Replace inherited '?destination' query parameter with current URL.
    /** @var \Drupal\Core\Url $route_info */
    $route_info = $actions['delete']['#url'];
    $route_info->setOption('query', []);
    $actions['delete']['#url'] = $route_info;

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\fillpdf\FillPdfFormInterface $entity */
    $entity = $this->getEntity();

    $message = [];
    $message[] = $this->t('FillPDF Form %link has been updated.', ['%link' => $entity->toLink()->toString()]);

    if ($form_state->getValue('upload_pdf')) {
      /** @var \Drupal\file\FileInterface $new_file */
      $new_file = File::load($form_state->getValue('upload_pdf')['0']);

      $existing_fields = $entity->getFormFields();

      // Delete existing fields.
      /** @var \Drupal\fillpdf\FillPdfFormFieldInterface $existing_field */
      foreach ($existing_fields as $existing_field) {
        $existing_field->delete();
      }

      $added = $this->inputHelper->attachPdfToForm($new_file, $entity);

      $form_fields = $added['fields'];

      $message[] = $this->t('Your previous field mappings have been transferred to the new PDF template you uploaded.');

      // Import previous form field values over new fields.
      $non_matching_fields = $this->serializer->importFormFieldsByKey($existing_fields, $form_fields);
      if (count($non_matching_fields)) {
        $message[] = $this->t("These keys couldn't be found in the new PDF:");
      }

      $this->messenger()->addStatus(implode(' ', $message));

      foreach ($non_matching_fields as $non_matching_field) {
        $this->messenger()->addWarning($non_matching_field);
      }

      $this->messenger()->addStatus($this->t('You might also want to update the <em>Filename pattern</em> field; this has not been changed.'));
    }
    else {
      $this->messenger()->addStatus(reset($message));
    }

    // Save custom form elements' values, resetting default_entity_id to NULL,
    // if not matching the default entity type.
    $default_entity_type = $form_state->getValue('default_entity_type');
    $default_entity_target_type = isset($form['default_entity_id']['#target_type']) ? $form['default_entity_id']['#target_type'] : NULL;
    $entity->set('default_entity_type', $default_entity_type)
      ->set('default_entity_id', ($default_entity_type == $default_entity_target_type) ? $form_state->getValue('default_entity_id') : NULL)
      ->save();
  }

}
