<?php

namespace Drupal\fillpdf\Plugin\FillPdfBackend;

use Drupal\file\Entity\File;
use Drupal\fillpdf\FillPdfBackendPluginInterface;
use Drupal\fillpdf\FillPdfFormInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * @Plugin(
 *   id = "fillpdf_service",
 *   label = @Translation("FillPDF Service"),
 *   description = @Translation(
 *     "No technical prerequisites. Sign up for <a href=':url'>FillPDF Service</a>.",
 *     arguments = {
 *       ":url" = "https://fillpdf.io"
 *     }
 *   ),
 *   weight = -10
 * )
 */
class FillPdfServiceFillPdfBackend implements FillPdfBackendPluginInterface {

  use StringTranslationTrait;

  /**
   * The FillPDF service endpoint.
   *
   * @var string
   */
  protected $fillPdfServiceEndpoint;

  /**
   * The plugin's configuration.
   *
   * @var array
   */
  protected $config;

  /**
   * Constructs a FillPdfServiceFillPdfBackend plugin object.
   *
   * @param array $config
   *   A configuration array containing information about the plugin instance.
   */
  public function __construct(array $config) {
    $this->config = $config;
    $this->fillPdfServiceEndpoint = "{$this->config['remote_protocol']}://{$this->config['remote_endpoint']}";
  }

  /**
   * {@inheritdoc}
   */
  public function parse(FillPdfFormInterface $fillpdf_form) {
    /** @var \Drupal\file\FileInterface $file */
    $file = File::load($fillpdf_form->file->target_id);
    $content = file_get_contents($file->getFileUri());

    $result = $this->xmlRpcRequest('parse_pdf_fields', base64_encode($content));

    if ($result->error == TRUE) {
      // @todo: Throw an exception, log a message etc.
      return [];
    } // after setting error message

    $fields = $result->data;

    return $fields;
  }

  /**
   * Make an XML-RPC request.
   *
   * @param $method
   *
   * @return \stdClass
   */
  protected function xmlRpcRequest($method /* $args */) {
    $url = $this->fillPdfServiceEndpoint;
    $args = func_get_args();

    // Fix up the array for Drupal 7 xmlrpc() function style.
    $args = [$args[0] => array_slice($args, 1)];
    $result = xmlrpc($url, $args);

    $ret = new \stdClass();

    if (isset($result['error'])) {
      \Drupal::messenger()->addError($result['error']);
      $ret->error = TRUE;
    }
    elseif ($result == FALSE || xmlrpc_error()) {
      $error = xmlrpc_error();
      $ret->error = TRUE;
      \Drupal::messenger()->addError($this->t('There was a problem contacting the FillPDF service.
      It may be down, or you may not have internet access. [ERROR @code: @message]',
        ['@code' => $error->code, '@message' => $error->message]));
    }
    else {
      $ret->data = $result['data'];
      $ret->error = FALSE;
    }
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function populateWithFieldData(FillPdfFormInterface $fillpdf_form, array $field_mapping, array $context) {
    /** @var \Drupal\file\FileInterface $original_file */
    $original_file = File::load($fillpdf_form->file->target_id);
    $original_pdf = file_get_contents($original_file->getFileUri());
    $api_key = $this->config['fillpdf_service_api_key'];

    // Anonymize image data from the fields array; we should not send the real
    // filename to FillPDF Service. We do this in the specific backend because
    // other plugin types may need the filename on the local system.
    foreach ($field_mapping['fields'] as $field_name => &$field) {
      if (!empty($field_mapping['images'][$field_name])) {
        // TODO: TEST.
        $field_path_info = pathinfo($field_mapping['images'][$field_name]['filenamehash']);
        $field = '{image}' . md5($field_path_info['filename']) . '.' . $field_path_info['extension'];
      }
    }
    unset($field);

    $result = $this->xmlRpcRequest('merge_pdf_v3', base64_encode($original_pdf), $field_mapping['fields'], $api_key, $context['flatten'], $field_mapping['images']);

    if ($result->error === FALSE && $result->data) {
      $populated_pdf = base64_decode($result->data);
      return $populated_pdf;
    }
  }

}
