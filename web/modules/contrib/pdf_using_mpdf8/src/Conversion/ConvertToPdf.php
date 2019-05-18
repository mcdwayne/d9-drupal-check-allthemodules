<?php

namespace Drupal\pdf_using_mpdf\Conversion;

use Drupal\pdf_using_mpdf\ConvertToPdfInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Utility\Token;
use Drupal\file\Entity\File;
use \Mpdf\Mpdf;

class ConvertToPdf implements ConvertToPdfInterface {

  /**
   *
   * @var \Drupal\Core\Render\RendererInterface RendererInterface
   */
  protected $renderer;

  /**
   * The Mpdf object.
   *
   * @var \Mpdf\Mpdf $mpdf
   */
  protected $mpdf;

  /**
   * Token object.
   *
   * @var \Drupal\Core\Utility\Token $token
   */
  protected $token;

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  protected $configFactory;

  /**
   * pdf_using_mpdf settings.
   *
   * @var array $settings
   */
  protected $settings;

  /**
   * ConvertToPdf constructor.
   * @param RendererInterface $renderer
   */
  public function __construct(RendererInterface $renderer, ConfigFactoryInterface $config_factory, Token $token) {
    $this->renderer = $renderer;
    $this->configFactory = $config_factory;
    $this->token = $token;
  }

  /**
   * Get configuration values of this module.
   * @return array
   */
  protected function getConfigValues() {
    return $this->configFactory
      ->getEditable('pdf_using_mpdf.settings')
      ->get('pdf_using_mpdf');
  }

  /**
   * Instantiate Mpdf object.
   */
  protected function init() {
    $mpdf_config = $this->getDefaultConfig();
    $this->mpdf = new Mpdf($mpdf_config);
  }
  
  /**
   * {@inheritdoc}
   */
  public function convert($html) {
    $this->settings = $this->getConfigValues();
    if (empty($html)) {
      drupal_get_messages('error');
      drupal_set_message(t('No content. PDF cannot be generated for this path.'), 'error');
      return;
    }
    $this->generator($html);
  }

  /**
   * Generate the PDF file using the Mpdf library.
   *
   * @param string $html
   *   contents of the template already with the node data.
   * @param string $filename
   *   name of the PDF file to be generated.
   */
  protected function generator($html) {
    $styles = $this->importStyles();

    $this->init();
    $this->setHeader();

    // Apply custom cascading styles.
    if (!empty($styles)) {
      $this->mpdf->WriteHTML($styles, 1);
    }

    $this->mpdf->WriteHTML($html, 2);
    $this->applyProperties();
    $this->setFooter();
    $this->showPdf();
  }

  /**
   * Set header for PDF file.
   */
  protected function setHeader() {
    $header = $this->settings['pdf_header'];
    if (isset($header) && $header != NULL) {
      $header = $this->token->replace($header);
      $this->mpdf->SetHTMLHeader($header);
    }
  }

  /**
   * Apply additional properties to PDF file.
   */
  protected function applyProperties() {

    // Set Watermark.
    $watermark_option = $this->settings['watermark_option'];
    $watermark_opacity = $this->settings['watermark_opacity'];
    if ($watermark_option == 0) {
      $text = $this->settings['pdf_watermark_text'];
      if (!empty($text)) {
        $this->mpdf->SetWatermarkText($text, $watermark_opacity);
        $this->mpdf->showWatermarkText = TRUE;
      }
    }
    else {
      $image_id = $this->settings['watermark_image'];
      if (isset($image_id[0])) {
        $file = File::load($image_id[0]);
        $image_path = $file->getFileUri();
        $image_path = file_create_url($image_path);
        $this->mpdf->SetWatermarkImage($image_path, $watermark_opacity);
        $this->mpdf->showWatermarkImage = TRUE;
      }
    }

    // Set Title.
    $title = $this->settings['pdf_set_title'];
    if (!empty($title)) {
      $this->mpdf->SetTitle($title);
    }

    // Set Author.
    $author = $this->settings['pdf_set_author'];
    if (!empty($author)) {
      $this->mpdf->SetAuthor($author);
    }

    // Set Subject.
    $subject = $this->settings['pdf_set_subject'];
    if (isset($subject) && $subject != NULL) {
      $this->mpdf->SetSubject($subject);
    }

    // Set Creator.
    $creator = $this->settings['pdf_set_creator'];
    if (!empty($creator)) {
      $this->mpdf->SetCreator($creator);
    }

    // Set Password.
    $password = $this->settings['pdf_password'];
    if (!empty($password)) {
      $this->mpdf->SetProtection(array('print', 'copy'), $password, $password);
    }
  }

  /**
   * Set footer for PDF file.
   */
  protected function setFooter() {
    $footer = $this->settings['pdf_footer'];
    if (isset($footer) && $footer != NULL) {
      $footer = $this->token->replace($footer);
      $this->mpdf->SetHTMLFooter($footer);
    }
  }

  /**
   * Show PDF to the user.
   */
  public function showPdf() {
    $filename = $this->settings['pdf_filename'];
    $filename = $this->token->replace($filename);

    switch($this->settings['pdf_save_option']) {
      case 0:
        $this->mpdf->Output($filename . '.pdf', \Mpdf\Output\Destination::INLINE);
        break;
      case 1:
        $this->mpdf->Output($filename . '.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        break;
      case 2:
        $folder = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
        $folder .= '/pdf_using_mpdf';
        $this->mpdf->Output($folder . '/' . $filename . '.pdf' , \Mpdf\Output\Destination::FILE);
        drupal_set_message(t('PDF File <em>@filename</em> has been saved to the server.', ['@filename' => $filename . '.pdf']));
        break;
    }
  }

  /**
   * Configuration values to instantiate Mpdf constructor.
   *
   * @return array
   */
  public function getDefaultConfig() {
    $orientation = $this->settings['orientation'] == 'L' ? '-L' : '';
    $config = [
      'tempDir' => file_directory_temp(),
      'useActiveForms' => TRUE,
      'format' => $this->settings['pdf_page_size'] . $orientation,
      'default_font_size' => $this->settings['pdf_font_size'] . 'pt',
      'default_font' => $this->settings['pdf_default_font'],
      'margin_left' => $this->settings['margin_left'],
      'margin_right' => $this->settings['margin_right'],
      'margin_top' => $this->settings['margin_top'],
      'margin_bottom' => $this->settings['margin_bottom'],
      'margin_header' => $this->settings['margin_header'],
      'margin_footer' => $this->settings['margin_footer'],
      'dpi' => $this->settings['dpi'],
      'img_dpi' => $this->settings['img_dpi'],
    ];

    return $config;
  }

  /**
   * Check if the custom stylesheet exists.
   *
   * @return bool|string
   */
  protected function importStyles() {
    $file = '';
    $path = DRUPAL_ROOT . '/' . $this->settings['pdf_css_file'];
    if (file_exists($path)) {
      $file = file_get_contents($path);
    }

    return $file;
  }

}
