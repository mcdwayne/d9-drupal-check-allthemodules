<?php


namespace Drupal\sl_base;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Config\ConfigFactory;

class SlBaseHelper {

  protected $efq;
  protected $modalidades_config;


  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.query')
    );
  }

  /**
   * When the service is created, set a value for the example variable.
   */
  public function __construct(ConfigFactory $config_factory, QueryFactory $efq) {
    $this->efq = $efq;
  }


  function findTeamPlayers($team, $formatted = FALSE) {

    $cached_info = \Drupal::cache()->get('sb_base_team_players_' . $team);
    if (empty($cached_info)) {
      $efq = $this->efq->get('node');
      $efq->condition('type', 'sl_person')
        ->condition('status', 1, '=')
        ->condition('field_sl_teams', $team);
      $result = $efq->execute();
      $players = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($result);
      \Drupal::cache()->set('sb_base_team_players_' . $team, $players, time() + (60*60), ['node.list']);
    }
    else {
      $players = $cached_info->data;
    }

    if (!formatted) {
      return $players;
    }
    else {
      foreach ($players as $player) {
        $player_options[$player->id()] = ['label' => $player->label(), 'number' => $player->field_sl_person_number->value];
      }

      return $player_options;
    }
  }
}