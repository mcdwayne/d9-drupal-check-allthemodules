<?php

namespace Drupal\color_schema_ui\Controller;

use Drupal\color_schema_ui\RequestContentHandler;
use Drupal\color_schema_ui\SCSSCompilerFacade;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class CompileController extends ControllerBase {

  /**
   * @var RequestContentHandler
   */
  private $requestContentHandler;

  /**
   * @var SCSSCompilerFacade
   */
  private $SCSSCompilerFacade;

  public function __construct(RequestContentHandler $requestContentHandler, SCSSCompilerFacade $SCSSCompilerFacade) {
    $this->requestContentHandler = $requestContentHandler;
    $this->SCSSCompilerFacade = $SCSSCompilerFacade;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('color_schema_ui.request_content_handler'),
      $container->get('color_schema_ui.scss_compiler_facade')
    );
  }

  public function compileSCSSToFilesystem(Request $request): Response {
    $this->SCSSCompilerFacade->compileSCSSToFilesystem($this->requestContentHandler->computeColorReplacement($request));
    $response = Response::create($content = '', $status = 200, $headers = array());

    return $response->send();
  }

  public function getCompiledSCSS(Request $request): Response {
    $compiledCSS = $this->SCSSCompilerFacade->getCompiledSCSS($this->requestContentHandler->computeColorReplacement($request));

    $response = new Response(
      $compiledCSS,
      Response::HTTP_OK,
      array('content-type' => 'text/css')
    );

    return $response->send();
  }

  public function getInitialColors(): JsonResponse {
    $initialColors = $this->SCSSCompilerFacade->getInitialColors();

    return new JsonResponse($initialColors);
  }

  public function saveColors(Request $request): void {
    $this->SCSSCompilerFacade->compileSCSSToFilesystem($request);
  }

}
