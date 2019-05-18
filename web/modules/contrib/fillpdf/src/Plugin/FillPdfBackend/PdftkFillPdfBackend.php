<?php

namespace Drupal\fillpdf\Plugin\FillPdfBackend;

use Drupal\Core\File\FileSystem;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;
use Drupal\fillpdf\Component\Utility\FillPdf;
use Drupal\fillpdf\FillPdfAdminFormHelperInterface;
use Drupal\fillpdf\FillPdfBackendPluginInterface;
use Drupal\fillpdf\FillPdfFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Plugin(
 *   id = "pdftk",
 *   label = @Translation("pdftk"),
 *   description = @Translation(
 *     "Locally installed pdftk. You will need a VPS or a dedicated server to install pdftk, see <a href=':url'>documentation</a>.",
 *     arguments = {
 *       ":url" = "https://www.drupal.org/docs/8/modules/fillpdf"
 *     }
 *   ),
 *   weight = -5
 * )
 */
class PdftkFillPdfBackend implements FillPdfBackendPluginInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The plugin's configuration.
   *
   * @var array
   */
  protected $configuration;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * The FillPDF admin form helper.
   *
   * @var \Drupal\fillpdf\FillPdfAdminFormHelperInterface
   */
  protected $adminFormHelper;

  /**
   * Constructs a PdftkFillPdfBackend plugin object.
   *
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The file system.
   * @param \Drupal\fillpdf\FillPdfAdminFormHelperInterface $admin_form_helper
   *   The FillPDF admin form helper.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(FileSystem $file_system, FillPdfAdminFormHelperInterface $admin_form_helper, array $configuration, $plugin_id, $plugin_definition) {
    $this->fileSystem = $file_system;
    $this->adminFormHelper = $admin_form_helper;
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('file_system'),
      $container->get('fillpdf.admin_form_helper'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function parse(FillPdfFormInterface $fillpdf_form) {
    /** @var \Drupal\file\FileInterface $file */
    $file = File::load($fillpdf_form->file->target_id);
    $filename = $file->getFileUri();

    $path_to_pdftk = $this->getPdftkPath();
    $status = FillPdf::checkPdftkPath($path_to_pdftk);
    if ($status === FALSE) {
      \Drupal::messenger()->addError($this->t('pdftk not properly installed.'));
      return [];
    }

    // Use exec() to call pdftk (because it will be easier to go line-by-line
    // parsing the output) and pass $content via stdin. Retrieve the fields with
    // dump_data_fields().
    $output = [];
    exec($path_to_pdftk . ' ' . escapeshellarg($this->fileSystem->realpath($filename)) . ' dump_data_fields', $output, $status);
    if (count($output) === 0) {
      \Drupal::messenger()->addWarning($this->t('PDF does not contain fillable fields.'));
      return [];
    }

    // Build a simple map of dump_data_fields keys to our own array keys.
    $data_fields_map = [
      'FieldType' => 'type',
      'FieldName' => 'name',
      'FieldFlags' => 'flags',
      'FieldJustification' => 'justification',
    ];

    // Build the fields array.
    $fields = [];
    $fieldindex = -1;
    foreach ($output as $lineitem) {
      if ($lineitem == '---') {
        $fieldindex++;
        continue;
      }
      // Separate the data key from the data value.
      $linedata = explode(':', $lineitem);
      if (in_array($linedata[0], array_keys($data_fields_map), NULL)) {
        $fields[$fieldindex][$data_fields_map[$linedata[0]]] = trim($linedata[1]);
      }
    }

    return $fields;
  }

  /**
   * Returns the configured path to the local pdftk installation.
   *
   * @return string
   *   The configured path to the local pdftk installation.
   */
  protected function getPdftkPath() {
    return $this->adminFormHelper->getPdftkPath();
  }

  /**
   * {@inheritdoc}
   */
  public function populateWithFieldData(FillPdfFormInterface $pdf_form, array $field_mapping, array $context) {
    /** @var \Drupal\file\FileInterface $original_file */
    $original_file = File::load($pdf_form->file->target_id);
    $filename = $original_file->getFileUri();
    $fields = $field_mapping['fields'];

    module_load_include('inc', 'fillpdf', 'xfdf');
    $xfdfname = $filename . '.xfdf';
    $xfdf = create_xfdf(basename($xfdfname), $fields);
    // Generate the file.
    $xfdffile = file_save_data($xfdf, $xfdfname, FILE_EXISTS_RENAME);

    // Now feed this to pdftk and save the result to a variable.
    $path_to_pdftk = $this->getPdftkPath();
    ob_start();
    passthru($path_to_pdftk . ' ' . escapeshellarg($this->fileSystem->realpath($filename)) . ' fill_form ' . escapeshellarg($this->fileSystem->realpath($xfdffile->getFileUri())) . ' output - ' . ($context['flatten'] ? 'flatten ' : '') . 'drop_xfa');
    $data = ob_get_clean();
    if ($data === FALSE) {
      \Drupal::messenger()->addError($this->t('pdftk not properly installed. No PDF generated.'));
    }
    $xfdffile->delete();

    if ($data) {
      return $data;
    }

    return NULL;
  }

}
