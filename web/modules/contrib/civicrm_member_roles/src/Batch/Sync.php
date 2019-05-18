<?php

namespace Drupal\civicrm_member_roles\Batch;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Class Sync.
 */
class Sync {

  use StringTranslationTrait;

  /**
   * Sync constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The string translation service.
   */
  public function __construct(TranslationInterface $stringTranslation) {
    $this->stringTranslation = $stringTranslation;
  }

  /**
   * Get the batch.
   *
   * @return array
   *   A batch API array for syncing user memberships and roles.
   */
  public function getBatch() {
    $batch = [
      'title' => $this->t('Updating Users...'),
      'operations' => [],
      'init_message' => $this->t('Starting Update'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message' => $this->t('An error occurred during processing'),
      'finished' => [$this, 'finished'],
    ];

    $batch['operations'][] = [[$this, 'process'], []];

    return $batch;
  }

  /**
   * Batch API process callback.
   *
   * @param mixed $context
   *   Batch API context data.
   */
  public function process(&$context) {
    $civicrmMemberRoles = $this->getCivicrmMemberRoles();

    if (!isset($context['sandbox']['cids'])) {
      $context['sandbox']['cids'] = $civicrmMemberRoles->getSyncContactIds();
      $context['sandbox']['max'] = count($context['sandbox']['cids']);
      $context['results']['processed'] = 0;
    }

    $cid = array_shift($context['sandbox']['cids']);
    if ($account = $civicrmMemberRoles->getContactAccount($cid)) {
      $civicrmMemberRoles->syncContact($cid, $account);
    }
    $context['results']['processed']++;

    if (count($context['sandbox']['cids']) > 0) {
      $context['finished'] = 1 - (count($context['sandbox']['cids']) / $context['sandbox']['max']);
    }
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Batch API success indicator.
   * @param array $results
   *   Batch API results array.
   */
  public function finished($success, array $results) {
    if ($success) {
      $message = $this->stringTranslation->formatPlural($results['processed'], 'One user processed.', '@count users processed.');
      drupal_set_message($message);
    }
    else {
      $message = $this->t('Encountered errors while performing sync.');
      drupal_set_message($message, 'error');
    }

  }

  /**
   * Get CiviCRM member roles service.
   *
   * This is called directly from the Drupal object to avoid dealing with
   * serialization.
   *
   * @return \Drupal\civicrm_member_roles\CivicrmMemberRoles
   *   The CiviCRM member roles service.
   */
  protected function getCivicrmMemberRoles() {
    return \Drupal::service('civicrm_member_roles');
  }

  /**
   * Get the database connection.
   *
   * This is called directly from the Drupal object to avoid dealing with
   * serialization.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection.
   */
  protected function getDatabase() {
    return \Drupal::database();
  }

}
