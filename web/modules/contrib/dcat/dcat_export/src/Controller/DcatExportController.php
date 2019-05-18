<?php

namespace Drupal\dcat_export\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\dcat_export\DcatExportService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class DcatExportController.
 *
 * @package Drupal\dcat_export\Controller
 */
class DcatExportController implements ContainerInjectionInterface {

  /**
   * Config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * DCAT export service.
   *
   * @var \Drupal\dcat_export\DcatExportService
   */
  protected $dcatExportService;

  /**
   * DcatExportController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\dcat_export\DcatExportService $dcat_export_service
   *   Database service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack, DcatExportService $dcat_export_service) {
    $this->config = $config_factory->get('dcat_export.settings');
    $this->request = $request_stack->getCurrentRequest();
    $this->dcatExportService = $dcat_export_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('request_stack'),
      $container->get('dcat_export')
    );
  }

  /**
   * Export DCAT entities as serialised data.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *
   * @throws \EasyRdf_Exception
   *   Thrown if EasyRdf fails in exporting data.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the output format is not found.
   */
  public function export() {
    $format = $this->getValidatedRequestFormat();
    $mime_type = $this->request->getMimeType($format);

    $content = $this->dcatExportService->export($format);

    $response = new Response();
    $response->headers->set('Content-Type', $mime_type);
    $response->setContent($content);

    return $response;
  }

  /**
   * Get the requested format and validate if it's enabled.
   *
   * @return string
   *   The requested format.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the output format is not found.
   */
  protected function getValidatedRequestFormat() {
    $format = $this->request->getRequestFormat();

    // When not defined, the default format is set to html earlier in the
    // bootstrap process.
    if ($format === 'html') {
      // Set the first enabled format we encounter as default.
      $format = reset($this->getEnabledFormats());
    }

    if (!$this->validateFormat($format)) {
      throw new NotFoundHttpException();
    }

    return $format;
  }

  /**
   * Check whether or not the format exists and is enabled.
   *
   * @return bool
   *   True if the format exists and is activated.
   */
  protected function validateFormat($format) {
    return in_array($format, $this->getEnabledFormats());
  }

  /**
   * Get the enabled output formats.
   *
   * @return array
   *   Array containing the enabled output formats. Always contains at least 1.
   */
  protected function getEnabledFormats() {
    return array_filter($this->config->get('formats'));
  }

}
