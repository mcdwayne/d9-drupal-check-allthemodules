<?php

namespace Drupal\strava_athletes;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\strava\Api\Strava;
use Drupal\strava\Manager\UserManager;
use Drupal\strava_athletes\Entity\Athlete;
use Drupal\strava_athletes\Manager\AthleteManager;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AthleteUserHandler implements ContainerInjectionInterface {

  use MessengerTrait;

  /**
   * Strava client.
   *
   * @var \Drupal\strava\Api\Strava
   */
  protected $strava;

  /**
   * Strava athlete details for the authenticated user.
   *
   * @var array
   */
  public $stravaDetails;

  /**
   * The user manager service.
   *
   * @var \Drupal\strava\Manager\UserManager
   */
  protected $userManager;

  /**
   * The user entity.
   *
   * @var \Drupal\user\Entity\User
   */
  public $user;

  /**
   * The athlete manager service.
   *
   * @var \Drupal\strava_athletes\Manager\AthleteManager
   */
  protected $athleteManager;

  /**
   * The athlete entity.
   *
   * @var \Drupal\strava_athletes\Entity\Athlete
   */
  public $athlete;


  /**
   * AthleteUserHandler constructor.
   *
   * @param \Drupal\strava\Manager\UserManager $user_manager
   * @param \Drupal\strava_athletes\Manager\AthleteManager $athlete_manager
   */
  public function __construct(UserManager $user_manager, AthleteManager $athlete_manager) {
    $this->userManager = $user_manager;
    $this->athleteManager = $athlete_manager;
    $this->strava = new Strava();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('strava.user_manager'),
      $container->get('strava.athlete_manager')
    );
  }

  /**
   * @return bool
   */
  private function loadStravaDetails() {
    // Load the athlete details from Strava.
    if ($this->stravaDetails) {
      return TRUE;
    }
    elseif ($this->strava->authenticate()) {
      $token = $this->strava->getAccessToken();
      $this->setStravaDetails($token->getValues()['athlete']);
      return TRUE;
    }
    else {
      $this->messenger()
        ->addError(t('Could not authenticate Strava user.'));
      return FALSE;
    }
  }

  /**
   * @param array $athlete
   */
  public function setStravaDetails($athlete) {
    $this->stravaDetails = $athlete;
  }

  /**
   * @return \Drupal\user\Entity\User
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * @param \Drupal\user\Entity\User $user
   */
  public function setUser(User $user = NULL) {
    if (is_null($user)) {
      $user = \Drupal::currentUser();
    }
    $this->user = $user;
  }

  /**
   * @return bool
   */
  public function userExists() {
    if (!$this->stravaDetails) {
      $this->messenger()
        ->addError(t('Could not get Strava athlete details to create user entity for.'));

      return FALSE;
    }

    // Check if the user already exists.
    $this->user = $this->userManager->loadUserByProperty('mail', $this->stravaDetails['email']);

    return (bool) $this->user;
  }

  /**
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\user\Entity\User|FALSE
   */
  public function userCreate() {
    // If the user doesn't exist yet create a new user.
    $this->user = $this->userManager->registerUser($this->stravaDetails['email'], $this->stravaDetails['firstname'] . ' ' . $this->stravaDetails['lastname'], $this->stravaDetails['profile']);

    return $this->user;
  }

  /**
   * @return \Drupal\strava_athletes\Entity\Athlete
   */
  public function getAthlete() {
    return $this->athlete;
  }

  /**
   * @param \Drupal\strava_athletes\Entity\Athlete $athlete
   */
  public function setAthlete(Athlete $athlete) {
    $this->athlete = $athlete;
  }

  /**
   * @return bool
   */
  public function athleteExists() {
    if (!$this->user) {
      $this->messenger()
        ->addError(t('Could not get Drupal user to check athlete entity for.'));

      return FALSE;
    }

    // Check if the user already exists.
    $this->athlete = $this->athleteManager->loadAthleteByProperty('uid', $this->user->id());

    return (bool) $this->athlete;
  }

  /**
   * Connect User and Athlete entities.
   *
   * Create the entities if they don't exist yet.
   *
   * @return bool
   */
  public function connect() {
    // Load the Strava athlete details.
    if (!$this->loadStravaDetails()) {
      return FALSE;
    }

    if (!$this->user) {
      $this->setUser();
    }

    // Check if an athlete entity already exists for this user.
    if ($this->athleteExists()) {
      // Update athlete entity if an existing entity was found.
      $this->athlete = $this->athleteManager->updateAthlete($this->stravaDetails);

      // Login the existing user.
      if ($this->user->id() !== \Drupal::currentUser()->id()) {
        $this->userManager->loginUser($this->user);
      }
    }
    else {
      // @TODO: figure out how to create a new user when we no longer have
      //   access to the athlete's email address.
//      // If the user doesn't exist yet create a new user.
//      if ($this->userCreate()) {
//        // When the user has been created we create a new athlete entity for this user.
//        $this->athlete = $this->athleteManager->createAthlete($this->user, $this->stravaDetails);
//
//        // Login the new user.
//        $this->userManager->loginUser($this->user);
//      }
    }

    return TRUE;
  }

}
