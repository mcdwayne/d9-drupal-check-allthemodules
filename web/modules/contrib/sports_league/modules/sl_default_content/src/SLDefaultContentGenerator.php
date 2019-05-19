<?php

namespace Drupal\sl_default_content;

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

class SLDefaultContentGenerator {

  protected $final_categories;

  // PHP array containing categories
  protected $categories = [
    'footbal' => [
      'title' => 'Football',
      'subcategories' => [
        'england' => [
          'title' => 'England',
          'subcategories' => [
            'england_league' => ['title' => 'England league'],
            'england_cup' => ['title' => 'England Cup']
          ]
        ],
        'spain' => [
          'title' => 'Spain',
          'subcategories' => [
            'spain_league' => ['title' => 'Spain league'],
            'spain_cup' => ['title' => 'Spain cup']
          ]
        ],
        'italy' => [
          'title' => 'Italy',
          'subcategories' => [
            'italy_league' => ['title' => 'Italy league'],
            'italy_cup' => ['title' => 'Italy cup']
          ]
        ],
        'europe' => [
          'title' => 'Europe',
          'subcategories' => [
            'european_league' => ['title' => 'European league'],
            'south_cup' => ['title' => 'South cup']
          ]
        ]
      ]
    ]
  ];

  //PHP array containing forenames.
  protected $first_names = array(
    'Christopher',
    'Ryan',
    'Ethan',
    'John',
    'Zoey',
    'Sarah',
    'Michelle',
    'Samantha',
    'Joao',
    'Francesco',
    'Juan',
    'Aitor',
    'Pedro',
    'Jordi',
    'Ruslan'
  );

  //PHP array containing surnames.
  protected $last_names = array(
    'Walker',
    'Thompson',
    'Anderson',
    'Johnson',
    'Tremblay',
    'Peltier',
    'Cunningham',
    'Simpson',
    'Mercado',
    'Sellers',
    'Garcia',
    'Navarro',
    'Silva',
    'Gomez'
  );

  // PHP array containing surnames.
  protected $teams = array(
    'madrid' => ['title' => 'Madrid', 'country' => 'ES', 'category' => 'spain'],
    'barcelona' => ['title' => 'Barcelona', 'country' => 'ES', 'category' => 'spain'],
    'manchester' => ['title' => 'Manchester', 'country' => 'GB', 'category' => 'england'],
    'london' => ['title' => 'London', 'country' => 'GB', 'category' => 'england'],
    'munich' => ['title' => 'Oxford', 'country' => 'DE', 'category' => 'england'],
    'milano' => ['title' => 'Milano', 'country' => 'IT', 'category' => 'italy'],
    'torino' => ['title' => 'Torino', 'country' => 'IT', 'category' => 'italy'],
    'roma' => ['title' => 'Roma', 'country' => 'IT', 'category' => 'italy'],
  );

  // PHP array containing competitions
  protected $competitions = array(
    'england_league' => ['title' => 'England league', 'category' => 'england'],
    'super_league' => ['title' => 'Super league', 'category' => 'spain'],
    'european_league' => ['title' => 'European league', 'category' => 'europe'],
    'south_cup' => ['title' => 'South Cup', 'category' => 'south_cup']
  );

  // PHP array containing countries
  protected $countries = array(
    'PT',
    'ES',
    'GB',
    'DE',
    'FR'
  );

  // PHP array containing countries
  protected $stadiums = array(
    'wood_venue' => ['title' => 'Wood Venue'],
    'metal_stadium' => ['title' => 'Metal stadium'],
    'hall_city' => ['title' => 'Hall city'],
    'woodstock_stadium' => ['title' => 'Woodstock stadium'],
    'la_palmera_stadium' => ['title' => 'La Palmera stadium']
  );

  // PHP array containing countries
  protected $positions = [
    'keeper' => ['title' => 'Keeper'],
    'defense' => ['title' => 'Defense'],
    'midfield' => ['title' => 'Midfield'],
    'forward' => ['title' => 'Forward']
  ];

  // Array containing
  protected $seasons = [
    '2016/2017',
    '2017/2018'
  ];

  /**
   * Used to generate names for athletes, coaches, referees
   * @return string
   */
  private function generateName() {
    $random_first = rand(0, count($this->first_names) -1);
    $random_last = rand(0, count($this->last_names) -1);
    return $this->first_names[$random_first] . ' ' . $this->last_names[$random_last];
  }

  /**
   * Private function to generate taxonomy term
   * @param $title
   * @param $type
   * @param $params
   * @return \Drupal\Core\Entity\EntityInterface|static
   */
  private function createTerm($title, $type, $params) {
    $term = Term::create(['vid' => $type]);
    $term->set('name', $title);

    foreach ($params as $key => $value) {
      $term->set($key, $value);
    }

    $term->enforceIsNew();
    $term->save();
    return $term;
  }

  /**
   * Private auxiliary function to generate nodes
   * @param $title
   * @param $type
   * @param $params
   * @return \Drupal\Core\Entity\EntityInterface|static
   */
  private function createNode($title, $type, $params) {
    $node = Node::create(['type' => $type]);
    $node->set('title', $title);

    foreach ($params as $key => $value) {
      $node->set($key, $value);
    }

    $node->enforceIsNew();
    $node->save();

    return $node;
  }

  /**
   * Generate statiums and referees
   */
  protected function generateOthers() {
    $args = [];
    foreach ($this->stadiums as $stadium_id => $stadium) {
      $term = $this->createTerm($stadium['title'], 'sl_venues', $args);
      $this->stadiums[$stadium_id]['id'] = $term->id();
    }

    $this->referees = [];
    for ($i = 0; $i < 10; $i++) {
      $term_name = $this->generateName();
      $term = $this->createTerm($term_name, 'sl_referees', $args);
      $this->referees[$term->id()]['id'] = $term->id();
      $this->referees[$term->id()]['name'] = $term->label();
    }

  }

  /**
   * Generate categories in an hierarchy
   */
  protected function generateCategories($categories = NULL, $parent_id = NULL) {

    if (!isset($categories)) {
      $categories = $this->categories;
    }

    foreach ($categories as $cat_id => $category) {
      $args['parent'] = $parent_id;
      $term = $this->createTerm($category['title'], 'sl_categories', $args);
      $this->final_categories[$cat_id] = $term->id();

      if (!empty($category['subcategories'])) {
        $subcategories =& $category['subcategories'];
        $this->generateCategories($subcategories, $term->id());
      }
    }
  }

  /**
   * Find category id
   * @param $category_id
   * @return mixed
   */
  private function findCategory($category_id) {
   return $this->final_categories[$category_id];
  }

  /*
   * Generate dates
   */
  private function generateDate($year_start = 1982, $year_end = 1998) {
    $year = rand ($year_start, $year_end);
    $month = rand (1, 12);
    $day = rand(1, 28);
    return mktime(0, 0, 0, $month, $day, $year);
  }

  /**
   * Generate random number except the one already generated
   * @param $min
   * @param $max
   * @param array $except
   * @return int
   */
  private function generateRandomExcept($min, $max, $except = []) {
    $number = rand($min, $max);
    if (!in_array($number, $except)) {
      return $number;
    }
    else {
      return $this->generateRandomExcept($min, $max, $except);
    }
  }

  /**
   * Generate players
   */
  protected function generatePlayers() {

    foreach ($this->teams as $team_id => $team) {
      for ($i = 0; $i < 23; $i++) {
        $name = $this->generateName();
        $args['field_sl_teams'] =  $team['id'];
        $args['field_sl_person_number'] = rand(1,23);
        $rand_country = rand(0, count($this->countries) - 1);
        $args['field_sl_country'] = $this->countries[$rand_country];
        $rand_position = rand(0, count($this->positions) - 1);
        $args['field_sl_person_position'] = $this->positions[$rand_position]['id'];
        $args['field_sl_detailed_position'] = $this->positions[$rand_position]['title'];
        $args['field_sl_person_date_of_birth'] = $this->generateDate(1982, 1988);
        $args['field_sl_stats_disabled'] = FALSE;
        $this->createNode($name, 'sl_person', $args);
      }
    }
  }

  /**
   * Generate clubs
   */
  protected function generateClubs() {
    $args = [];
    foreach ($this->teams as $team_id => $team) {
      $args['field_sl_country'] = $team['country'];
      $args['field_sl_archived'] = 0;
      $node = $this->createNode($team['title'], 'sl_club', $args);
      $this->teams[$team_id]['id'] = $node->id();
    }
  }

  /**
   * Generate positions
   */
  protected function generatePositions() {
    $args = [];
    foreach ($this->positions as  $position_id => $position) {
      $term = $this->createTerm($position['title'], 'sl_positions', $args);
      $this->positions[$position_id]['id'] = $term->id();
    }
  }

  /**
   * Generate teams
   */
  protected function generateTeams() {
    $args = [];
    foreach ($this->teams as $team_id => $team) {
      $args['field_sl_categories'] = $this->findCategory($team['category']);
      $node = $this->createNode($team['title'], 'sl_team', $args);
      $this->teams[$team_id]['id'] = $node->id();
    }
  }

  /**
   * Generate competitions
   */
  protected function generateCompetitions() {
    $args = [];
    foreach ($this->competitions as $competition_id => $competition) {
      $args['field_sl_categories'] = $this->findCategory($competition['category']);
      $node = $this->createNode($competition['title'], 'sl_competition', $args);
      $this->competitions[$competition_id]['id'] = $node->id();
    }
  }

  /**
   * Generate competition instances
   */
  protected function generateCompetitionsInstances() {
    $args = [];
    foreach ($this->seasons as $season) {
      foreach ($this->competitions as $competition_id => $competition) {
        $args['field_sl_archived'] = FALSE;
        $args['field_sl_categories'] = $this->findCategory($competition['category']);
        $args['field_sl_competition'] = $competition['id'];
        $title = $competition['title'] . ' ' . $season;
        $node = $this->createNode($title, 'sl_competition_edition', $args);
        $this->competitions[$competition_id]['id'] = $node->id();
      }
    }
  }

  /**
   * Generate matches
   */
  protected function generateMatches() {

    for ($i=0; $i < 30; $i++) {

      // we need to seelect a random team
      $team_1_id = $this->generateRandomExcept(0, count($this->teams) -1 );
      $team_2_id = $this->generateRandomExcept(0, count($this->teams) - 1, [$team_1_id]);

      $teams_ids = array_keys($this->teams);


      $team_1 = $this->teams[$teams_ids[$team_1_id]];
      $team_2 = $this->teams[$teams_ids[$team_2_id]];

      // how many matches we have with more than 5 goals
      $goals_1 = rand(0, 5);
      $goals_2 = rand(0, 5);

      $args = [];
      $args['field_sl_match_team_home'] = $team_1['id'];
      $args['field_sl_match_team_away'] = $team_2['id'];

      $args['field_sl_match_score_home'] = $goals_1;
      $args['field_sl_match_score_away'] = $goals_2;

      $args['field_sl_teams'] = [$team_1, $team_2];

      $args['field_sl_match_status'] = 'played';

      $referee_id = rand(0, count($this->referees) -1);
      $stadium_id = rand(0, count($this->stadiums) -1);
      $competition_id = rand(0, count($this->competitions) -1);

      $referees_keys = array_keys($this->referees);
      $stadium_keys = array_keys($this->stadiums);

      // random referee and stadium
      $args['field_sl_referee'] = $this->referees[$referees_keys[$referee_id]]['id'];
      $args['field_sl_venue'] = $this->stadiums[$stadium_keys[$stadium_id]]['id'];

      // $args['field_sl_categories'] = $this->findCategory($competition['category']);
      $args['field_sl_competition'] = $this->competitions[$competition_id];
      $title = $team_1['title'] . ' ' . $goals_1 . ' x ' . $goals_2 . ' ' . $team_2['title'];
      $args['field_sl_administrative_title'] = $title;
      $args['field_sl_match_date'] = $this->generateDate(1982, 1988);

      $node = $this->createNode($title, 'sl_match', $args);

      // time to generate rosters and substitutes
      $home_rosters = $this->generateRosters($team_1['id'], 11, 0, 90, $node->id());
      $home_coach = $this->generateRosters($team_1['id'], 1, 0, 90, $node->id());
      $away_rosters = $this->generateRosters($team_2['id'], 11, 0, 90, $node->id());
      $away_coach = $this->generateRosters($team_1['id'], 1, 0, 90, $node->id());

      $node->set('field_sl_match_home_inirosters', $home_rosters);
      $node->set('field_sl_match_away_inirosters', $away_rosters);
      $node->set('field_sl_match_home_coach', $home_coach);
      $node->set('field_sl_match_away_coach', $away_coach);

      $home_subs = $this->generateRosters($team_1['id'], 7, 0, 90, $node->id());
      $away_subs = $this->generateRosters($team_2['id'], 7, 0, 90, $node->id());

      $home_moments = $this->generateMoments($team_1['id'], $goals_1, 3, $node->id());
      $away_moments = $this->generateMoments($team_1['id'], $goals_2, 3, $node->id());

      $node->set('field_sl_match_home_inisubs', $home_subs);
      $node->set('field_sl_match_away_inisubs', $away_subs);

      // time to generate moments
      $node->set('field_sl_match_home_moments', $home_moments);
      $node->set('field_sl_match_away_moments', $away_moments);

      $node->save();
    }
  }

  /**
   * Auxiliary function to generate the rosters().
   * @param $team
   * @param $number
   * @param $in
   * @param $out
   * @param $match_id
   * @return int|null|string
   */
  protected function generateRosters($team, $number, $in, $out, $match_id) {
    $slBaseHelper = \Drupal::service('sl_base.helper');
    $players = $slBaseHelper->findTeamPlayers($team);

    if (empty($players)) {
      return [];
    }

    $players_picked = $rosters_ids = [];
    for ($i = 0; $i < $number; $i++) {

      $players_id = $this->generateRandomExcept(0, count($players) -1 , $players_picked);
      $players_picked[] = $players_id;
      $players_entity_ids = array_keys($players);

      $data = array(
        'type' => 'sl_rosters',
        'field_sl_roster_in' => $in,
        'field_sl_roster_out' => $out,
        'field_sl_match' => $match_id,
        'field_sl_roster_player' => $players_entity_ids[$players_id],
        'field_sl_roster_num' => $players[$players_entity_ids[$players_id]]['number']
      );

      $entity = \Drupal::entityTypeManager()
        ->getStorage('sl_rosters')
        ->create($data);

      $entity->save();
      $rosters_ids[] = $entity->id();
    }

    return $rosters_ids;
  }

  /**
   * Generate moments
   * @param $team
   * @param $goals_num
   * @param $subs_num
   * @param $match_id
   * @return array
   */
  protected function generateMoments($team, $goals_num, $subs_num, $match_id) {
    $slBaseHelper = \Drupal::service('sl_base.helper');
    $players = $slBaseHelper->findTeamPlayers($team);

    if (empty($players)) {
      return [];
    }

    $players_picked = $moments_ids = [];

    for ($i = 0; $i < $goals_num; $i++) {

      $players_id = $this->generateRandomExcept(0, count($players) - 1, $players_picked);
      $players_picked[] = $players_id;
      $players_entity_ids = array_keys($players);

      $data = array(
        'type' => 'sl_match_moments_goal',
        'field_sl_match_moments_time' => rand(0, 90),
        'field_sl_match' => $match_id,
        'field_sl_match_moments_player' => $players_entity_ids[$players_id]
      );

      $entity = \Drupal::entityTypeManager()
        ->getStorage('sl_match_moments')
        ->create($data);

      $entity->save();
      $moments_ids[] = $entity->id();
    }

    for ($i = 0; $i < $subs_num; $i++) {

      $players_id = $this->generateRandomExcept(0, count($players) -1 , $players_picked);
      $players_picked[] = $players_id;
      $players_entity_ids = array_keys($players);

      $data = array(
        'type' => 'sl_match_moments_substitution',
        'field_sl_match_moments_time' => rand(0, 90),
        'field_sl_match' => $match_id,
        'field_sl_match_moments_player' => $players_entity_ids[$players_id],
        'field_sl_match_moments_player_in' => $players_entity_ids[$players_id]
      );

      $entity = \Drupal::entityTypeManager()
        ->getStorage('sl_match_moments')
        ->create($data);

      $entity->save();
      $moments_ids[] = $entity->id();
    }

    return $moments_ids;
  }

  /**
   * Main generator function used to generate all the needed content
   */
  public function mainGenerator() {
    $this->generateCategories();
    $this->generateOthers();
    $this->generateClubs();
    $this->generateTeams();
    $this->generateCompetitions();
    $this->generateCompetitionsInstances();
    $this->generatePositions();
    $this->generatePlayers();
    $this->generateMatches();
  }

}