<?php

namespace Drupal\pubg_api_examples\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\pubg_api\PubgApiMatchesInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * PUBG API Matches example controller.
 */
class MatchesController extends ControllerBase {

  /**
   * PUBG API Matches service.
   *
   * @var \Drupal\pubg_api\PubgApiMatchesInterface
   */
  protected $pubgApiPlayers;

  /**
   * PUBG API Matches example constructor.
   *
   * {@inheritdoc}
   *
   * @param \Drupal\pubg_api\PubgApiMatchesInterface $pubg_api_matches
   *   The PUBG API Matches service.
   */
  public function __construct(PubgApiMatchesInterface $pubg_api_matches) {
    $this->pubgApiMatches = $pubg_api_matches;
  }

  /**
   * Plugin dependencies injection.
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('pubg_api.matches')
    );
  }

  /**
   * PUBG API Matches Example build.
   *
   * @return array
   *   A renderable array.
   */
  public function build() {
    $build = [];

    kint($this->pubgApiMatches->getSingleMatch(
      'pc-eu',
      '92f7576c-48a5-4a5d-986e-74ddbc0a2232'
    ), 'Get a single match by id on PC EU shard');

    return $build;
  }

}
