<?php

namespace Drupal\fillpdf;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\file\FileInterface;
use Drupal\fillpdf\Entity\FillPdfForm;
use Drupal\fillpdf\Entity\FillPdfFormField;

/**
 * Class InputHelper.
 *
 * @package Drupal\fillpdf
 */
class InputHelper implements InputHelperInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configManager;

  /**
   * The FillPDF backend manager.
   *
   * @var \Drupal\fillpdf\FillPdfBackendManager
   */
  protected $backendManager;

  /**
   * Constructs an InputHelper object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\fillpdf\FillPdfBackendManager $backend_manager
   *   The FillPDF backend manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FillPdfBackendManager $backend_manager) {
    $this->configManager = $config_factory;
    $this->backendManager = $backend_manager;
  }

  /**
   * Attaches a PDF template file to a FillPdfForm.
   *
   * @param \Drupal\file\FileInterface $file
   *   The PDF template file to attach.
   * @param \Drupal\fillpdf\FillPdfFormInterface $existing_form
   *   The FillPdfForm the PDF template file should be attached to.
   *
   * @return array
   */
  public function attachPdfToForm(FileInterface $file, FillPdfFormInterface $existing_form = NULL) {
    $this->saveFileUpload($file);

    if ($existing_form) {
      $fillpdf_form = $existing_form;
      $fillpdf_form->file = $file;
    }
    else {
      $fillpdf_form = FillPdfForm::create([
        'file' => $file,
        'title' => $file->filename,
      ]);
    }

    // Save PDF configuration before parsing.
    $fillpdf_form->save();

    $config = $this->config('fillpdf.settings');
    $fillpdf_service = $config->get('backend');
    /** @var FillPdfBackendPluginInterface $backend */
    $backend = $this->backendManager->createInstance($fillpdf_service, $config->get());

    // Attempt to parse the fields in the PDF.
    $fields = $backend->parse($fillpdf_form);

    $form_fields = [];
    foreach ((array) $fields as $arr) {
      if ($arr['type']) {
        // Don't store "container" fields.
        // pdftk sometimes inserts random &#0; markers - strip these out.
        // NOTE: This may break forms that actually DO contain this pattern,
        // but 99%-of-the-time functionality is better than merge failing due
        // to improper parsing.
        $arr['name'] = str_replace('&#0;', '', $arr['name']);
        $field = FillPdfFormField::create(
          [
            'fillpdf_form' => $fillpdf_form,
            'pdf_key' => $arr['name'],
            'value' => '',
          ]
        );

        // Use the field name as key, so to consolidate duplicate fields.
        $form_fields[$arr['name']] = $field;
      }
    }

    // Save the fields that were parsed out (if any). If none were, set a
    // warning message telling the user that.
    foreach ($form_fields as $fillpdf_form_field) {
      /** @var \Drupal\fillpdf\Entity\FillPdfFormField $fillpdf_form_field */
      $fillpdf_form_field->save();
    }
    return ['form' => $fillpdf_form, 'fields' => $form_fields];
  }

  /**
   * Saves an uploaded file, marking it permanent.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file object to save.
   */
  protected function saveFileUpload(FileInterface $file) {
    // Save the file to get an fid, and then create a FillPdfForm record
    // based off that.
    $file->setPermanent();
    // Save the file so we can get an fid.
    $file->save();
  }

  /**
   * Returns an immutable configuration object for a given name.
   *
   * @param string $name
   *   The name of the configuration object.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   An immutable configuration object.
   */
  protected function config($name) {
    return $this->configManager->get($name);
  }

}
