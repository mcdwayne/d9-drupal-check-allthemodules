<?php

namespace Drupal\strava_clubs\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\strava\Api\Strava;
use Drupal\strava_athletes\Entity\Athlete;
use Strava\API\Client;
use Strava\API\Exception;

/**
 * Provides a form for refreshing Club entities.
 *
 * @ingroup strava
 */
class ClubRefreshForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['actions']['submit']['#value'] = $this->t('Refresh club details from Strava.');

    return $form;
  }

  /**
   * @inheritdoc
   */
  public function getDescription() {
    return $this->t('This action retrieves all club details from Strava and overwrites your local changes.');
  }

  /**
   * @inheritDoc
   */
  public function getQuestion() {
    return $this->t('Do you want to refresh the club details from Strava?');
  }

  /**
   * @inheritDoc
   */
  public function getCancelUrl() {
    return Url::fromRoute('entity.club.canonical', [
      'club' => $this->getEntity()
        ->id(),
    ]);
  }

  /**
   * Refresh entity info from Strava API.
   *
   * @throws \Strava\API\Exception
   */
  public function refreshEntity() {
    /** @var \Drupal\strava_clubs\Entity\Club $entity */
    $entity = $this->getEntity();

    // Get the first club member we can get an API client for.
    $club_members = $entity->getClubMembers();
    if (!empty($club_members)) {
      $strava = new Strava();

      // Loop through all members.
      foreach ($club_members as $member) {
        // Load the complete athlete object for the member.
        $athlete = Athlete::load($member);
        /** @var \Strava\API\Client $client */
        $client = $strava->getApiClientForAthlete($athlete);
        if ($client instanceof Client) {
          // If we have a working client. Try to refresh the club details.
          $club_details = $client->getClub($entity->id());
          \Drupal::service('strava.club_manager')
            ->updateClub($club_details);

          // End the loop.
          break;
        }
      }
    }

  }

  /**
   * @inheritdoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $this->refreshEntity();
      $form_state->setRedirect(
        'entity.club.canonical',
        ['club' => $this->entity->id()]
      );
    }
    catch (Exception $e) {
      $form_state->setRedirect(
        'entity.club.refresh',
        ['club' => $this->entity->id()]
      );
      $this->messenger()->addError($e->getMessage());
    }
  }
}
