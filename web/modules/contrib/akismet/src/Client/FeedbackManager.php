<?php

namespace Drupal\akismet\Client;

use Drupal\Core\Form\FormStateInterface;
use Drupal\akismet\Controller\FormController;
use Drupal\akismet\Entity\Form;
use Drupal\akismet\Storage\ResponseDataStorage;
use Drupal\akismet\Utility\Logger;

/**
 * FeedbackManager provides functionality related to reporting feedback
 * to the Akismet service.
 */
class FeedbackManager {

  /**
   * Add feedback options to an existing form, e.g., the delete form for
   * a protected entity.
   *
   * @see akismet_form_alter()
   */
  public static function addFeedbackOptions(&$form, FormStateInterface &$form_state) {
    if (!isset($form['description']['#weight'])) {
      $form['description']['#weight'] = 90;
    }
    $form['akismet'] = array(
      '#tree' => TRUE,
      '#weight' => 80,
    );
    $form['akismet']['feedback'] = array(
      '#type' => 'radios',
      '#title' => t('Report as…'),
      '#options' => array(
        'spam' => t('Spam, unsolicited advertising'),
        '' => t('Do not report'),
      ),
      '#default_value' => 'spam',
      '#description' => t('Sending feedback to <a href="@akismet-url">Akismet</a> improves the automated moderation of new submissions.', array('@akismet-url' => 'https://akismet.com')),
    );
  }

  /**
   * Submit handler for feedback options.
   */
  public static function addFeedbackOptionsSubmit(&$form, FormStateInterface &$form_state) {
    $forms = FormController::getProtectedForms();
    $akismet_form = Form::load($forms['delete'][$form_state->getFormObject()->getFormId()])->initialize();
    $entity_type = $akismet_form['entity'];
    if (!empty($entity_type)) {
      $id = $form_state->getFormObject()->getEntity()->id();
    }
    else {
      $id = $form_state->getValue($akismet_form['mapping']['post_id']);
    }

    $feedback = $form_state->getValue(array('akismet', 'feedback'));
    if (!empty($feedback)) {
      if (self::sendFeedback($entity_type, $id, $feedback)) {
        \Drupal::messenger()->addMessage(t('The content was successfully reported as inappropriate.'));
      }
    }

    // Remove Akismet session data.
    ResponseDataStorage::delete($entity_type, $id);
  }

  /**
   * Sends feedback for a Akismet session data record.
   *
   * @param $entity
   *   The entity type to send feedback for.
   * @param $id
   *   The entity id to send feedback for.
   * @param $feedback
   *   The feedback reason for reporting content.
   * @param $type
   *   The type of feedback, one of 'moderate' or 'flag'.
   * @param $source
   *   An optional single word string identifier for the user interface source.
   *   This is tracked along with the feedback to provide a more complete picture
   *   of how feedback is used and submitted on the site.
   */
  public static function sendFeedback($entity, $id, $feedback) {
    return self::sendFeedbackMultiple($entity, array($id), $feedback);
  }

  /**
   * Sends feedback for multiple Akismet session data records.
   *
   * @param $entity
   *   The entity type to send feedback for.
   * @param $ids
   *   An array of entity ids to send feedback for.
   * @param $feedback
   *   The feedback reason for reporting content.
   *
   * @return bool
   *   TRUE on successful submit, FALSE on failure.
   */
  public static function sendFeedbackMultiple($entity, array $ids, $feedback) {
    $return = TRUE;
    foreach ($ids as $id) {
      // Load the Akismet session data.
      $data = ResponseDataStorage::loadByEntity($entity, $id);
      if (empty($data)) {
        continue;
      }
      // Send feedback, if we have the original request data.
      if (!empty($data->request)) {
        $result = self::sendFeedbackToAkismet($data, $feedback);
        $return = $return && $result;
      }
      $data->moderate = 0;
      ResponseDataStorage::save($data);
    }
    return $return;
  }

  /**
   * Send feedback to Akismet.
   *
   * @param $data
   *   An Akismet data record containing at least
   *   - entity: The entity type of the data in the record.
   *   - id: The entity id.
   *   - request: The original request data sent to Akismet.
   * @param $reason
   *   The feedback to send: either 'spam' or 'ham'.
   *
   * @return int|bool
   *   On success, the text response from Akismet. On failure, the error code.
   */
  protected static function sendFeedbackToAkismet($data, $reason = 'spam') {
    $feedback = (array) $data->request;
    $feedback['guid'] = $data->guid;
    if ($reason === 'spam') {
      $result = \Drupal::service('akismet.client')->sendFeedback($feedback, 'spam');
    }
    else {
      $result = \Drupal::service('akismet.client')->sendFeedback($feedback, 'ham');
    }
    Logger::addMessage(array(
      'message' => 'Reported %feedback for @resource %id.',
      'arguments' => array(
        '%feedback' => $reason,
        '@resource' => $data->entity,
        '%id' => $data->id,
      ),
    ));

    return $result;
  }
}
