<?php

/**
 * @file
 * Contains \Drupal\powertagging\Form\PowerTaggingRefreshExtractionModelForm.
 */

namespace Drupal\powertagging\Form;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\powertagging\Entity\PowerTaggingConfig;
use Drupal\semantic_connector\SemanticConnector;

/**
 * Start the taxonomy term updating for a PowerTagging configuration.
 */
class PowerTaggingRefreshExtractionModelForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'powertagging_refresh_extraction_model_form';
  }


  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    /** @var PowerTaggingConfig $entity */
    $entity = \Drupal::routeMatch()->getParameter('powertagging_config');

    // Get the project label.
    $connection_config = $entity->getConnection()->getConfig();
    $project_label = t('Project label not found');
    if (isset($connection_config['projects'])) {
      foreach ($connection_config['projects'] as $project) {
        if ($project['id'] == $entity->getProjectId()) {
          $project_label = $project['title'];
          break;
        }
      }
    }

    return $this->t('Are you sure you want to refresh the extraction model of PoolParty project "%projectname"?', array('%projectname' => $project_label));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This process makes the PoolParty extraction use the latest version of the thesaurus.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelURL() {
    return Url::fromUserInput(\Drupal::request()->getRequestUri());
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Refresh the extraction model');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var PowerTaggingConfig $entity */
    $entity = \Drupal::routeMatch()->getParameter('powertagging_config');

    /** @var \Drupal\semantic_connector\Api\SemanticConnectorPPTApi $ppt_api */
    $ppt_api = $entity->getConnection()
      ->getApi('PPT');

    // Get the project label.
    $connection_config = $entity->getConnection()->getConfig();
    $project_label = t('Project label not found');
    if (isset($connection_config['projects'])) {
      foreach ($connection_config['projects'] as $project) {
        if ($project['id'] == $entity->getProjectId()) {
          $project_label = $project['title'];
          break;
        }
      }
    }

    $result = $ppt_api->refreshExtractionModel($entity->getProjectId());
    if ($result['success']) {
      drupal_set_message(t('Successfully refreshed the extraction model for project "%projectname".', array('%projectname' => $project_label)));

      // If there are any global notifications and they could be caused by a missing
      // sync, refresh the notifications.
      $notifications = \Drupal::config('semantic_connector.settings')->get('global_notifications');
      if (!empty($notifications)) {
        $notification_config = SemanticConnector::getGlobalNotificationConfig();
        if (isset($notification_config['actions']['powertagging_refresh_extraction_model']) && $notification_config['actions']['powertagging_refresh_extraction_model']) {
          SemanticConnector::checkGlobalNotifications(TRUE);
        }
      }
    }
    else {
      drupal_set_message(t('An error occurred while refreshing the extraction model for project "%projectname".', array('%projectname' => $project_label)) . ((isset($result['message']) && !empty($result['message'])) ? ' message: ' . $result['message'] : ''), 'error');
    }

    $form_state->setRedirect('entity.powertagging.edit_config_form', array('powertagging' => $entity->id()));
  }
}