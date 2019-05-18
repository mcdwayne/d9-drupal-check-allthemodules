<?php

namespace Drupal\fillpdf\Plugin\FillPdfActionPlugin;

use Drupal\Core\Url;
use Drupal\fillpdf\OutputHandler;
use Drupal\fillpdf\Plugin\FillPdfActionPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Action plugin saving a generated PDF file to the filesystem.
 *
 * @package Drupal\fillpdf\Plugin\FillPdfActionPlugin
 *
 * @FillPdfActionPlugin(
 *   id = "save",
 *   label = @Translation("Save PDF to file")
 * )
 */
class FillPdfSaveAction extends FillPdfActionPluginBase {

  /**
   * The FillPdf output handler.
   *
   * @var \Drupal\fillpdf\OutputHandler
   */
  protected $outputHandler;

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param \Drupal\fillpdf\OutputHandler $output_handler
   *   The FillPdf output handler.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(OutputHandler $output_handler, array $configuration, $plugin_id, $plugin_definition) {
    $this->outputHandler = $output_handler;

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('fillpdf.output_handler'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Executes this plugin.
   *
   * Saves the PDF file to the filesystem and redirects to the front page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects user to the front page.
   */
  public function execute() {
    $this->savePdf();

    // @todo: Fix based on value of post_save_redirect, once I add that
    $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
    return $response;
  }

  /**
   * Saves merged PDF data to the filesystem.
   *
   * @return \Drupal\file\FileInterface|false
   *   The saved file entity, or FALSE on error.
   *
   * @see \Drupal\fillpdf\OutputHandlerInterface::savePdfToFile()
   */
  protected function savePdf() {
    // @todo: Error handling?
    return $this->outputHandler->savePdfToFile($this->configuration);
  }

}
