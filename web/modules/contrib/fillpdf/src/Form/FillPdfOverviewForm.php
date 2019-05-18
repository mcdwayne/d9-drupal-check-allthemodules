<?php

namespace Drupal\fillpdf\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\fillpdf\FillPdfBackendManager;
use Drupal\fillpdf\InputHelperInterface;
use Drupal\fillpdf\Component\Utility\FillPdf;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * FillPDF overview form.
 */
class FillPdfOverviewForm extends FormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The FillPDF backend manager.
   *
   * @var \Drupal\fillpdf\FillPdfBackendManager
   */
  protected $backendManager;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The FillPDF input helper.
   *
   * @var \Drupal\fillpdf\InputHelperInterface
   */
  protected $inputHelper;

  /**
   * Constructs a FillPdfSettingsForm object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\fillpdf\FillPdfBackendManager $backend_manager
   *   The FillPDF backend manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   * @param \Drupal\fillpdf\InputHelperInterface $input_helper
   *   The FillPDF input helper.
   */
  public function __construct(ModuleHandlerInterface $module_handler, FillPdfBackendManager $backend_manager, FileSystemInterface $file_system, InputHelperInterface $input_helper) {
    $this->moduleHandler = $module_handler;
    $this->backendManager = $backend_manager;
    $this->fileSystem = $file_system;
    $this->inputHelper = $input_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('plugin.manager.fillpdf_backend'),
      $container->get('file_system'),
      $container->get('fillpdf.input_helper')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'fillpdf_forms_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // @todo: convert to OOP
    $form['existing_forms'] = views_embed_view('fillpdf_forms', 'block_1');

    $config = $this->config('fillpdf.settings');
    // Only show PDF upload form if fillpdf is configured.
    if (!$config->get('backend')) {
      $form['message'] = [
        '#markup' => '<p>' . $this->t('Before you can upload PDF files, you must @link.', [
          '@link' => new FormattableMarkup($this->l($this->t('configure FillPDF'), Url::fromRoute('fillpdf.settings')), []),
        ]) . '</p>',
      ];
      $this->messenger()->addError($this->t('FillPDF is not configured.'));
      return $form;
    }

    // If using FillPDF Service, ensure XML-RPC module is present.
    if ($config->get('backend') === 'fillpdf_service' && !$this->moduleHandler->moduleExists('xmlrpc')) {
      $this->messenger()->addError($this->t('You must install the <a href=":xmlrpc">contributed XML-RPC module</a> in order to use FillPDF Service as your PDF-filling method.', [
        '@xmlrpc' => Url::fromUri('https://drupal.org/project/xmlrpc')->toString(),
      ]));
      return $form;
    }

    $upload_location = FillPdf::buildFileUri($this->config('fillpdf.settings')->get('template_scheme'), 'fillpdf');
    if (!file_prepare_directory($upload_location, FILE_CREATE_DIRECTORY + FILE_MODIFY_PERMISSIONS)) {
      $this->messenger()->addError($this->t('The directory %directory does not exist or is not writable. Please check permissions.', [
        '%directory' => $this->fileSystem->realpath($upload_location),
      ]));
    }
    else {
      $form['upload_pdf'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Upload PDF template'),
        '#accept' => 'application/pdf',
        '#upload_validators' => [
          'file_validate_extensions' => ['pdf'],
        ],
        '#upload_location' => $upload_location,
        '#description' => $this->t('Upload a fillable PDF file to create a new form.'),
      ];

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Create'),
        '#weight' => 15,
      ];
    }

    return $form;

  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('upload_pdf')) {
      /** @var \Drupal\file\FileInterface $file */
      $file = File::load($form_state->getValue('upload_pdf')['0']);
      $added = $this->inputHelper->attachPdfToForm($file);

      /** @var \Drupal\fillpdf\Entity\FillPdfForm $fillpdf_form */
      $fillpdf_form = $added['form'];
      $fid = $fillpdf_form->id();

      $this->logger('fillpdf')->notice('Added FillPDF form %id.', ['%id' => $fid]);
      $this->messenger()->addStatus($this->t('New FillPDF form has been created.'));

      /** @var \Drupal\fillpdf\FillPdfFormFieldInterface[] $form_fields */
      $form_fields = $added['fields'];
      if (count($form_fields) === 0) {
        $this->messenger()->addWarning($this->t('No fields detected in PDF. Are you sure it contains editable fields?'));
      }
      else {
        $this->messenger()->addStatus($this->t("You may now create mappings between the fields of the PDF form and an entity type."));
      }

      $form_state->setRedirect('entity.fillpdf_form.edit_form', ['fillpdf_form' => $fid]);
    }
  }

}
