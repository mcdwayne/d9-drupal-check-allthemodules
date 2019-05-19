<?php

namespace Drupal\strava_activities\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\strava\Api\Strava;
use Strava\API\Exception;

/**
 * Provides a form for refreshing Activity entities.
 *
 * @ingroup strava
 */
class ActivityRefreshForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['actions']['submit']['#value'] = $this->t('Refresh activity details from Strava.');

    return $form;
  }

  /**
   * @inheritdoc
   */
  public function getDescription() {
    return $this->t('This action retrieves all activity details from Strava and overwrites your local changes.');
  }

  /**
   * @inheritDoc
   */
  public function getQuestion() {
    return $this->t('Do you want to refresh the activity details from Strava?');
  }

  /**
   * @inheritDoc
   */
  public function getCancelUrl() {
    return Url::fromRoute('entity.activity.canonical', [
      'activity' => $this->getEntity()
        ->id(),
    ]);
  }

  /**
   * Refresh entity info from Strava API.
   */
  public function refreshEntity() {
    /** @var \Drupal\strava_activities\Entity\Activity $entity */
    $entity = $this->getEntity();

    $strava = new Strava();
    $athlete = $entity->get('athlete')->getValue();
    if ($athlete) {
      $athlete = $athlete[0]['target_id'];
      /** @var \Strava\API\Client $client */
      $client = $strava->getApiClientForAthlete($athlete);
      if ($client) {
        try {
          $activity_details = $client->getActivity($entity->id());
          /** @var \Drupal\strava_activities\Manager\ActivityManager */
          \Drupal::service('strava.activity_manager')
            ->updateActivity($activity_details);
        }
        catch (Exception $e) {
          $this->logger('strava_activities')->error($e->getMessage());
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
        'entity.activity.canonical',
        ['activity' => $this->entity->id()]
      );
    }
    catch (Exception $e) {
      $form_state->setRedirect(
        'entity.activity.refresh',
        ['activity' => $this->entity->id()]
      );
      $this->messenger()->addError($e->getMessage());
    }
  }
}
