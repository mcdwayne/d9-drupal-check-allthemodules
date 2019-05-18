<?php

namespace Drupal\pubg_api_examples\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\pubg_api\PubgApiPlayersInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * PUBG API Players example controller.
 */
class PlayersController extends ControllerBase {

  /**
   * PUBG API Players service.
   *
   * @var \Drupal\pubg_api\PubgApiPlayersInterface
   */
  protected $pubgApiPlayers;

  /**
   * PUBG API Players example constructor.
   *
   * {@inheritdoc}
   *
   * @param \Drupal\pubg_api\PubgApiPlayersInterface $pubg_api_players
   *   The PUBG API Players service.
   */
  public function __construct(PubgApiPlayersInterface $pubg_api_players) {
    $this->pubgApiPlayers = $pubg_api_players;
  }

  /**
   * Plugin dependencies injection.
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('pubg_api.players')
    );
  }

  /**
   * PUBG API Players Example build.
   *
   * @return array
   *   A renderable array.
   */
  public function build() {
    $build = [];

    kint($this->pubgApiPlayers->getPlayers(
      'pc-eu',
      '',
      'MacSim75,infini'
    ), 'Get 2 players by name on PC EU shard');

    kint($this->pubgApiPlayers->getPlayers(
      'pc-eu',
      'account.3464d685979b436a98490c7220790e34,account.41e6a168e25d41e28fd35467a29b1814'
    ), 'Get 2 players by id on PC EU shard');

    kint($this->pubgApiPlayers->getSinglePlayer(
      'pc-eu',
      'account.3464d685979b436a98490c7220790e34'
    ), 'Get single player by id');

    $build[] = [
      '#markup' => "Beware, by default PUBG API calls are limited to 10 per minute. That page make 3 calls each time you reload it which can lead to <em>&laquo;429 Too Many Requests&raquo;</em> responses.",
    ];

    return $build;
  }

}
