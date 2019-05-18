<?php

namespace Drupal\perspective\Controller;

use Drupal\perspective\AnalyzeToxicityService;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller that will interact with the service.
 */
class AnalyzeController extends ControllerBase {

  /**
   * Variable that will store the service.
   *
   * @var \Drupal\perspective\AnalyzeToxicityService
   */
  protected $analyzeToxicityService;

  /**
   * {@inheritdoc}
   */
  public function __construct(AnalyzeToxicityService $analyzeToxicityService) {
    $this->analyzeToxicityService = $analyzeToxicityService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('perspective.analyze_toxicity')
    );
  }

  /**
   * Calls the API and return the toxicity of the comment.
   */
  public function analyze($text) {
    $perspectiveApiResponse = [
      'score' => $this->analyzeToxicityService->getTextToxicity($text),
    ];

    return new JsonResponse($perspectiveApiResponse);
  }

}
