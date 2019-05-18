<?php

namespace Drupal\bynder\Controller;

use Drupal\bynder\BynderApiInterface;
use Drupal\bynder\Exception\TagSearchException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BynderTagSearchService.
 */
class BynderTagSearchService extends ControllerBase {

  /**
   * Limits the amount of tags returned in the Media browser filter.
   */
  const TAG_LIST_LIMIT = 25;

  /**
   * The Bynder API service.
   *
   * @var \Drupal\bynder\BynderApiInterface
   */
  protected $bynder;

  /**
   * The logger factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructs a BynderTagSearchService class instance.
   *
   * @param \Drupal\bynder\BynderApiInterface $bynder
   *   The Bynder API service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger factory service.
   */
  public function __construct(BynderApiInterface $bynder, LoggerChannelFactoryInterface $logger) {
    $this->bynder = $bynder;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bynder_api'),
      $container->get('logger.factory')
    );
  }

  /**
   * Route callback for tags search.
   */
  public function searchTags(Request $request) {
    $results = [];
    $keyword = $request->get('keyword');

    try {
      $results = array_map(
        function ($tag) {
          return ['id' => $tag['tag'], 'text' => $tag['tag']];
        },
        $this->bynder->getTags([
          'limit' => self::TAG_LIST_LIMIT,
          'keyword' => $keyword,
          'minCount' => 1,
        ])
      );
    }
    catch (\Exception $e) {
      (new TagSearchException($e->getMessage()))->logException()->displayMessage();
      return FALSE;
    }

    usort($results, function ($first, $second) {
      return ($first['text'] < $second['text']) ? -1 : 1;
    });

    $response['results'] = $results;
    return new JsonResponse($response);
  }

}