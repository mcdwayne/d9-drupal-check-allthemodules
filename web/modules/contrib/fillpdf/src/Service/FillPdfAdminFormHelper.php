<?php

namespace Drupal\fillpdf\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\fillpdf\FillPdfAdminFormHelperInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Various helper methods used in FillPDF administrative forms.
 */
class FillPdfAdminFormHelper implements FillPdfAdminFormHelperInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a FillPdfAdminFormHelper object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, StreamWrapperManagerInterface $stream_wrapper_manager, Connection $connection) {
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminTokenForm($token_types = 'all') {
    // Special treatment for Core's taxonomy_term and taxonomy_vocabulary.
    if (is_array($token_types)) {
      foreach ($token_types as $key => $type) {
        $token_types[$key] = strtr($type, ['taxonomy_' => '']);
      }
    }
    return [
      '#theme' => 'token_tree_link',
      '#token_types' => $token_types,
      '#global_types' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function schemeOptions(array $label_templates = []) {
    $stream_wrapper_manager = $this->streamWrapperManager;

    $options = [];
    foreach (array_keys($stream_wrapper_manager->getWrappers(StreamWrapperInterface::WRITE_VISIBLE)) as $scheme) {
      $label_template = array_key_exists($scheme, $label_templates) ? $label_templates[$scheme] : '@scheme';
      $options[$scheme] = new FormattableMarkup($label_template, [
        '@scheme' => new FormattableMarkup("<strong>@label</strong>", ['@label' => $stream_wrapper_manager->getViaScheme($scheme)->getName()]),
      ]) . ': ' . $stream_wrapper_manager->getViaScheme($scheme)->getDescription();
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormsByTemplateScheme($scheme) {
    $query = $this->connection->query("SELECT u.id AS form_id, f.uri AS file_uri
      FROM {file_usage} u INNER JOIN {file_managed} f ON u.fid = f.fid
      WHERE (type = :type) AND (uri LIKE :scheme)", [
        ':type' => 'fillpdf_form',
        ':scheme' => "{$scheme}://%",
      ]);
    return $query->fetchAllKeyed();
  }

  /**
   * {@inheritdoc}
   */
  public static function getReplacementsDescription() {
    return new TranslatableMarkup("<p>Tokens, such as those from fields, sometimes output values that need additional
  processing prior to being sent to the PDF. A common example is when a key within a field's <em>Allowed values</em>
  configuration does not match the field name or option value in the PDF that you would like to be selected but you
  do not want to change the <em>Allowed values</em> key.</p><p>This field will replace any matching values with the
  replacements you specify. Specify <strong>one replacement per line</strong> in the format
  <em>original value|replacement value</em>. For example, <em>yes|Y</em> will fill the PDF with
  <strong><em>Y</em></strong> anywhere that <strong><em>yes</em></strong> would have originally
  been used. <p>Note that omitting the <em>replacement value</em> will replace <em>original value</em>
  with a blank, essentially erasing it.</p>");
  }

  /**
   * {@inheritdoc}
   */
  public function getPdftkPath() {
    $path_to_pdftk = $this->configFactory->get('fillpdf.settings')
      ->get('pdftk_path');

    if (empty($path_to_pdftk)) {
      $path_to_pdftk = 'pdftk';
      return $path_to_pdftk;
    }
    return $path_to_pdftk;
  }

}
