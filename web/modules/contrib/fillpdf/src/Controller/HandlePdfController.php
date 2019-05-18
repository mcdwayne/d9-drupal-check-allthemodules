<?php

namespace Drupal\fillpdf\Controller;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\fillpdf\Component\Helper\FillPdfMappingHelper;
use Drupal\fillpdf\Entity\FillPdfForm;
use Drupal\fillpdf\FillPdfBackendManager;
use Drupal\fillpdf\FillPdfContextManagerInterface;
use Drupal\fillpdf\FillPdfFormInterface;
use Drupal\fillpdf\FillPdfLinkManipulatorInterface;
use Drupal\fillpdf\Plugin\FillPdfActionPluginManager;
use Drupal\fillpdf\TokenResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Link;

/**
 * Class HandlePdfController.
 */
class HandlePdfController extends ControllerBase {

  /**
   * The FillPDF link manipulator.
   *
   * @var \Drupal\fillpdf\FillPdfLinkManipulatorInterface
   */
  protected $linkManipulator;

  /**
   * The FillPDF context manager.
   *
   * @var \Drupal\fillpdf\FillPdfContextManagerInterface
   */
  protected $contextManager;

  /**
   * The FillPDF token resolver.
   *
   * @var \Drupal\fillpdf\TokenResolverInterface
   */
  protected $tokenResolver;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The FillPDF backend manager.
   *
   * @var \Drupal\fillpdf\FillPdfBackendManager
   */
  protected $backendManager;

  /**
   * The FillPDF backend manager.
   *
   * @var \Drupal\fillpdf\Plugin\FillPdfActionPluginManager
   */
  protected $actionManager;

  /**
   * Constructs a FillPdfBackendManager object.
   *
   * @param \Drupal\fillpdf\FillPdfLinkManipulatorInterface $link_manipulator
   *   The FillPDF link manipulator.
   * @param \Drupal\fillpdf\FillPdfContextManagerInterface $context_manager
   *   The FillPDF context manager.
   * @param \Drupal\fillpdf\TokenResolverInterface $token_resolver
   *   The FillPDF token resolver.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\fillpdf\FillPdfBackendManager $backend_manager
   *   The FillPDF backend manager.
   * @param \Drupal\fillpdf\Plugin\FillPdfActionPluginManager $action_manager
   *   The FillPDF backend manager.
   */
  public function __construct(FillPdfLinkManipulatorInterface $link_manipulator, FillPdfContextManagerInterface $context_manager, TokenResolverInterface $token_resolver, RequestStack $request_stack, FillPdfBackendManager $backend_manager, FillPdfActionPluginManager $action_manager) {
    $this->linkManipulator = $link_manipulator;
    $this->contextManager = $context_manager;
    $this->tokenResolver = $token_resolver;
    $this->requestStack = $request_stack;
    $this->backendManager = $backend_manager;
    $this->actionManager = $action_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('fillpdf.link_manipulator'),
      $container->get('fillpdf.context_manager'),
      $container->get('fillpdf.token_resolver'),
      $container->get('request_stack'),
      $container->get('plugin.manager.fillpdf_backend'),
      $container->get('plugin.manager.fillpdf_action.processor')
    );
  }

  /**
   * Populates PDF template from context.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The action plugin's response object.
   *
   * @throws \InvalidArgumentException
   *   If one of the passed arguments is missing or does not pass the
   *   validation.
   */
  public function populatePdf() {
    $context = $this->linkManipulator->parseRequest($this->requestStack->getCurrentRequest());

    $config = $this->config('fillpdf.settings');
    $fillpdf_service = $config->get('backend');

    // Load the backend plugin.
    /** @var \Drupal\fillpdf\FillPdfBackendPluginInterface $backend */
    $backend = $this->backendManager->createInstance($fillpdf_service, $config->get());

    // @todo: Emit event (or call alter hook?) before populating PDF.
    // Rename fillpdf_merge_fields_alter() to fillpdf_populate_fields_alter().
    $fillpdf_form = FillPdfForm::load($context['fid']);
    $fields = $fillpdf_form->getFormFields();

    // Populate entities array based on what user passed in.
    $entities = $this->contextManager->loadEntities($context);

    $field_mapping = [
      'fields' => [],
      'images' => [],
    ];

    $mapped_fields = &$field_mapping['fields'];
    $image_data = &$field_mapping['images'];
    foreach ($fields as $field) {
      $pdf_key = $field->pdf_key->value;
      if ($context['sample']) {
        $mapped_fields[$pdf_key] = $pdf_key;
      }
      else {
        // Get image fields attached to the entity and derive their token names
        // based on the entity types we are working with at the moment.
        $fill_pattern = count($field->value) ? $field->value->value : '';
        $is_image_token = FALSE;
        foreach ($entities as $entity_type => $entities_of_that_type) {
          $lifo_entities = array_reverse($entities_of_that_type);
          /** @var \Drupal\Core\Entity\EntityInterface $entity */
          foreach ($lifo_entities as $entity) {
            if (method_exists($entity, 'getFields')) {
              /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
              /** @var string $field_name */
              /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
              foreach ($entity->getFields() as $field_name => $field_data) {
                $field_definition = $field_data->getFieldDefinition();
                if ($field_definition->getType() === 'image') {
                  if ($fill_pattern === "[{$entity_type}:{$field_name}]") {
                    // It's a match!
                    $is_image_token = TRUE;
                    if (count($entity->{$field_name})) {
                      /** @var \Drupal\file\FileInterface $image_file */
                      $image_file = File::load($entity->{$field_name}->target_id);
                      $this->processImageTokens($image_file, $mapped_fields, $pdf_key, $image_data);
                    }
                  }
                }
              }
            }
            // Also support (non-chained) Webform submission element tokens,
            // which are not fields.
            if ($entity_type === 'webform_submission') {
              // We can iterate over this submission's Webform's elements and
              // manually match for patterns. We support signature and image
              // fields.
              /** @var \Drupal\webform\Entity\Webform $webform */
              $webform = $entity->getWebform();
              $webform_field_data = array_filter($webform->getElementsInitializedFlattenedAndHasValue(), function ($value) {
                return (!empty($value) && $value['#type'] === 'webform_image_file');
              });
              $submission_values = $entity->getData();
              $data_keys = array_keys($webform_field_data);
              foreach ($data_keys as $webform_field_name) {
                if ($fill_pattern === "[webform_submission:values:{$webform_field_name}]") {
                  $webform_image_file = File::load($submission_values[$webform_field_name]);
                  if (!$webform_image_file) {
                    break;
                  }

                  $is_image_token = TRUE;
                  $this->processImageTokens($webform_image_file, $mapped_fields, $pdf_key, $image_data);
                }
              }
            }
          }
        }

        if (!$is_image_token) {
          $replaced_string = $this->tokenResolver->replace($fill_pattern, $entities);

          // Apply field transformations.
          // Replace <br /> occurrences with newlines.
          $replaced_string = preg_replace('|<br />|', '
', $replaced_string);

          $form_replacements = FillPdfMappingHelper::parseReplacements($fillpdf_form->replacements->value);
          $field_replacements = FillPdfMappingHelper::parseReplacements($field->replacements->value);

          $replaced_string = FillPdfMappingHelper::transformString($replaced_string, $form_replacements, $field_replacements);

          // Apply prefix and suffix, if applicable.
          if (isset($replaced_string) && $replaced_string) {
            if ($field->prefix->value) {
              $replaced_string = $field->prefix->value . $replaced_string;
            }
            if ($field->suffix->value) {
              $replaced_string .= $field->suffix->value;
            }
          }

          $mapped_fields[$pdf_key] = $replaced_string;
        }
      }
    }

    $title_pattern = $fillpdf_form->title->value;
    // Generate the filename of downloaded PDF from title of the PDF set in
    // admin/structure/fillpdf/%fid.
    $filename = $this->buildFilename($title_pattern, $entities);

    $populated_pdf = $backend->populateWithFieldData($fillpdf_form, $field_mapping, $context);
    if (empty($populated_pdf)) {
      $this->messenger()->addError($this->t('Merging the FillPDF Form failed.'));
      return new RedirectResponse(Url::fromRoute('<front>')->toString());
    }

    // @todo: When Rules integration ported, emit an event or whatever.
    $action_response = $this->handlePopulatedPdf($fillpdf_form, $populated_pdf, $context, $filename, $entities);

    return $action_response;
  }

  /**
   * Builds the filename of a populated PDF file.
   *
   * @param string $original
   *   The original filename without tokens being replaced.
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entities to be used for replacing tokens.
   */
  protected function buildFilename($original, array $entities) {
    // Replace tokens *before* sanitization.
    $original = $this->tokenResolver->replace($original, $entities);

    $output_name = str_replace(' ', '_', $original);
    $output_name = preg_replace('/\.pdf$/i', '', $output_name);
    $output_name = preg_replace('/[^a-zA-Z0-9_.-]+/', '', $output_name) . '.pdf';

    return $output_name;
  }

  /**
   * Figure out what to do with the PDF and do it.
   *
   * @param \Drupal\fillpdf\FillPdfFormInterface $fillpdf_form
   *   An object containing the loaded record from {fillpdf_forms}.
   * @param string $pdf_data
   *   A string containing the content of the merged PDF.
   * @param array $context
   *   The request context as returned by
   *   FillPdfLinkManipulatorInterface::parseLink().
   * @param string $filename
   *   Filename of the merged PDF.
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entities to be used for replacing tokens. These may be still
   *   needed for generating the destination path, if the file is saved.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The action plugin's response object.
   *
   * @see \Drupal\fillpdf\FillPdfLinkManipulatorInterface::parseLink()
   */
  protected function handlePopulatedPdf(FillPdfFormInterface $fillpdf_form, $pdf_data, array $context, $filename, array $entities) {
    $force_download = FALSE;
    if (!empty($context['force_download'])) {
      $force_download = TRUE;
    }

    // Determine the appropriate action for the PDF.
    $scheme = $fillpdf_form->getStorageScheme();
    $is_available = array_key_exists($scheme, \Drupal::service('stream_wrapper_manager')->getWrappers(StreamWrapperInterface::WRITE_VISIBLE));
    $is_allowed = in_array($scheme, \Drupal::config('fillpdf.settings')->get('allowed_schemes') ?: []);

    if (empty($scheme)) {
      $action_plugin_id = 'download';
    }
    elseif (!$is_available || !$is_allowed) {
      // @todo: We don't need the ID once an admin_title is #required,
      // see https://www.drupal.org/project/fillpdf/issues/3040776.
      $label = $fillpdf_form->label() . " ({$fillpdf_form->id()})";
      $this->getLogger('fillpdf')->critical('Saving a generated PDF file in unavailable storage scheme %scheme failed.', [
        '%scheme' => "$scheme://",
      ]);
      if ($this->currentUser()->hasPermission('administer pdfs')) {
        $this->messenger()->addError($this->t('File storage scheme %scheme:// is unavailable, so a PDF file generated from FillPDF form @link could not be stored.', [
          '%scheme' => $scheme,
          '@link' => Link::fromTextAndUrl($label, $fillpdf_form->toUrl())->toString(),
        ]));
      }
      // Make sure the file is only sent to the browser.
      $action_plugin_id = 'download';
    }
    else {
      $redirect = !empty($fillpdf_form->destination_redirect->value);
      $action_plugin_id = $redirect ? 'redirect' : 'save';
    }

    // @todo: Remove in FillPDF 5.x. The filename is not part of the context and
    // is separately available anyway.
    $context['filename'] = $filename;

    // @todo: Rename 'token_objects' to 'entities' in FillPDF 5.x. Webform
    // submissions are now entities, too.
    $action_configuration = [
      'form' => $fillpdf_form,
      'context' => $context,
      'token_objects' => $entities,
      'data' => $pdf_data,
      'filename' => $filename,
    ];

    /** @var \Drupal\fillpdf\Plugin\FillPdfActionPluginInterface $fillpdf_action */
    $fillpdf_action = $this->actionManager->createInstance($action_plugin_id, $action_configuration);
    $response = $fillpdf_action->execute();

    // If we are forcing a download, then manually get a Response from
    // the download action and return that. Side effects of other plugins will
    // still happen, obviously.
    if ($force_download) {
      /** @var FillPdfDownloadAction $download_action */
      $download_action = $this->actionManager
        ->createInstance('download', $action_configuration);
      $response = $download_action
        ->execute();
    }

    return $response;
  }

  /**
   * Processes image tokens.
   *
   * @param \Drupal\file\FileInterface $image_file
   *   Image file object.
   * @param string[] $mapped_fields
   *   Array of mapped fields.
   * @param string $pdf_key
   *   PDF key.
   * @param array[] $image_data
   *   Array of image data, keyed by the PDF key.
   */
  protected function processImageTokens(FileInterface $image_file, array &$mapped_fields, $pdf_key, array &$image_data) {
    $backend = $this->config('fillpdf.settings')->get('backend');

    // @todo Refactor in 8.x-5.x. Pdftk doesn't support image stamping.
    if ($backend == 'pdftk') {
      return;
    }

    $image_path = $image_file->getFileUri();
    $mapped_fields[$pdf_key] = "{image}{$image_path}";

    // @todo Refactor in 8.x-5.x. Local and LocalService backends handle image
    // files themselves. So this only remains in place for FillPdfService
    // and possible third-party backend plugins.
    if (in_array($backend, ['local_service', 'local'])) {
      return;
    }

    $image_path_info = pathinfo($image_path);
    // Store the image data to transmit to the remote service if necessary.
    $file_data = file_get_contents($image_path);
    if ($file_data) {
      $image_data[$pdf_key] = [
        'data' => base64_encode($file_data),
        'filenamehash' => md5($image_path_info['filename']) . '.' . $image_path_info['extension'],
      ];
    }
  }

}
