<?php

namespace Drupal\random_reference_formatter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Process the income data and returns a random entity.
 */
class RandomEntityController extends ControllerBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs dependencies.
   *
   * PointsConvertController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'), $container->get('renderer'));
  }

  /**
   * Get the random Entity.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response containing the content.
   */
  public function content(Request $request) {

    $candidatesGeneric = explode(',', $request->get('ids'));
    $viewMode = $request->get('view_mode');

    if (empty($candidatesGeneric)) {
      return $this->returnError();
    }

    $candidates = [];
    foreach ($candidatesGeneric as $idGeneric) {
      $id = strtok($idGeneric, '/');
      $entityType = strtok('');

      if (empty($idGeneric) || empty($entityType)) {
        continue;
      }

      $candidates[] = [
        'id' => $id,
        'entityType' => $entityType,
      ];
    }

    if (empty($viewMode)) {
      $viewMode = 'default';
    }

    $quantity = $request->get('quantity') ?: 1;

    try {

      $output = [];
      $randomEntities = array_intersect_key($candidates, $this->generateRandomNumbers(0, count($candidates) - 1, $quantity));

      // @todo: Group by Entity Type and load using view/load|Multiple()
      foreach ($randomEntities as $randomEntityData) {
        $entityStorage = $this->entityTypeManager->getStorage($randomEntityData['entityType']);
        $entity = $entityStorage->load($randomEntityData['id']);

        if (!$entity) {
          continue;
        }

        $entityViewBuilder = $this->entityTypeManager->getViewBuilder($randomEntityData['entityType']);
        $output[] = $entityViewBuilder->view($entity, $viewMode);
      }

      $randomOutput = !empty($output) ? $this->renderer->render($output) : '';

    }
    catch (\Exception$e) {
      return $this->returnError();
    }

    return new JsonResponse(['randomEntities' => $randomOutput]);
  }

  /**
   * Generate random numbers.
   *
   * @param int $min
   *   Min of random number.
   * @param int $max
   *   Max of random number.
   * @param int $quantity
   *   The number of random and unique items to generate.
   *
   * @return array
   *   An Array of random numbers.
   */
  private function generateRandomNumbers($min, $max, $quantity = 1): array {
    $randomNumbers = [];

    $j = 1;
    while ($j <= $quantity) {
      $rand = rand($min, $max);
      // Skip existing.
      if (in_array($rand, $randomNumbers)) {
        continue;
      }
      $randomNumbers[$rand] = $rand;
      $j++;
    }

    return $randomNumbers;
  }

  /**
   * Return error.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response containing error.
   */
  private function returnError() {
    return new JsonResponse(['error' => TRUE]);
  }

}
