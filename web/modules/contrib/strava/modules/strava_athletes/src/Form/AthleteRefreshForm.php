<?php

namespace Drupal\strava_athletes\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\strava\Api\Strava;
use Strava\API\Exception;

/**
 * Provides a form for refreshing Athlete entities.
 *
 * @ingroup strava
 */
class AthleteRefreshForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['actions']['submit']['#value'] = $this->t('Refresh athlete details from Strava.');

    return $form;
  }

  /**
   * @inheritdoc
   */
  public function getDescription() {
    return $this->t('This action retrieves all athlete details from Strava and overwrites your local changes.');
  }

  /**
   * @inheritDoc
   */
  public function getQuestion() {
    return $this->t('Do you want to refresh the athlete details from Strava?');
  }

  /**
   * @inheritDoc
   */
  public function getCancelUrl() {
    return Url::fromRoute('entity.athlete.canonical', [
      'athlete' => $this->getEntity()
        ->id(),
    ]);
  }

  /**
   * Refresh entity info from Strava API.
   *
   * @throws \Strava\API\Exception
   */
  public function refreshEntity() {
    /** @var \Drupal\strava_athletes\Entity\Athlete $entity */
    $entity = $this->getEntity();

    $strava = new Strava();
    /** @var \Strava\API\Client $client */
    $client = $strava->getApiClientForAthlete($entity);
    if ($client) {
      $athlete_details = $client->getAthlete($entity->id());
      \Drupal::service('strava.athlete_manager')
        ->updateAthlete($athlete_details);
    }
  }

  /**
   * @inheritdoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $this->refreshEntity();
      $form_state->setRedirect(
        'entity.athlete.canonical',
        ['athlete' => $this->entity->id()]
      );
    }
    catch (Exception $e) {
      $form_state->setRedirect(
        'entity.athlete.refresh',
        ['athlete' => $this->entity->id()]
      );
      $this->messenger()->addError($e->getMessage());
    }
  }
}
