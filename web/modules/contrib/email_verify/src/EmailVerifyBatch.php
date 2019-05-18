<?php

namespace Drupal\email_verify;

use Drupal\email_verify\EmailVerifyManager;

class EmailVerifyBatch {

    public function checkAll() {

    // Initialize batch (to set title).
    //$batch = array(
    //  'title' => t('Checking email address'),
    //  'operations' => array(),
    //);
    //batch_set($batch);

    // Start the batch and check the email address.
    $batch = array(
      'title' => t('Checking email address'),
      // 'operations' => array(
      //   array(
      //     array(
      //     EmailVerifyManager::class, 'checkAllBatch', []
      //   ),
      //     ),
      // ),
      'finished' => [EmailVerifyManager::class, 'finishBatch'],

    );

    $batch['operations'][] = array(array(EmailVerifyManager::class, 'checkAllBatch'), array());
    //$batch['operations'][] = array(array(get_class($this), 'processBatch'), array($this));

    batch_set($batch);
  }

  public static function checkAllBatch(&$context) {

    if (!isset($context['sandbox']['email_verify_manager'])) {
      $manager = new EmailVerifyManager();
      $context['sandbox']['email_verify_manager'] = $manager;
    }

    $manager = $context['sandbox']['config_importer'];

    $user_storage = \Drupal::entityManager()->getStorage('user');
    if (empty($context['sandbox'])) {
      // Initiate multistep processing.
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_user'] = 0;
      $context['sandbox']['max'] = \Drupal::entityQuery('user')
      ->count()
      ->execute();
    }

    // Process the next 20 nodes.
    $limit = 20;
    $uids = \Drupal::entityQuery('user')
      ->condition('uid', $context['sandbox']['current_user'], '>')
      ->sort('uid', 'ASC')
      ->range(0, $limit)
      ->execute();

    $user_storage->resetCache($uids);
    $users = User::loadMultiple($uids);
    foreach ($users as $uid => $user) {
      // To preserve database integrity, only write grants if the node
      // loads successfully.
      if (!empty($user)) {
        drupal_set_message($user->getEmail());
        $manager->checkEmail($user->getEmail());
      }
      $context['sandbox']['progress']++;
      $context['sandbox']['current_user'] = $uid;
    }

    // Multistep processing : report progress.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  public static function finishBatch($success, $results, $operations) {
    if ($success) {
      if (!empty($results['errors'])) {
        foreach ($results['errors'] as $error) {
          drupal_set_message($error, 'error');
          //\Drupal::logger('config_sync')->error($error);
        }
        drupal_set_message(\Drupal::translation()->translate('The configuration was imported with errors.'), 'warning');
      }
      else {
        drupal_set_message(\Drupal::translation()->translate('The configuration was imported successfully.'));
      }
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $message = \Drupal::translation()->translate('An error occurred while processing %error_operation with arguments: @arguments', array('%error_operation' => $error_operation[0], '@arguments' => print_r($error_operation[1], TRUE)));
      drupal_set_message($message, 'error');
    }
  }

}
