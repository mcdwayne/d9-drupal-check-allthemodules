<?php

namespace Drupal\cloudwords;

use Drupal\cloudwords\CloudwordsProject;

class CloudwordsDrupalProject extends CloudwordsProject {

  protected $isDrupalCancelled;

  /**
   * Overrides CloudwordsProject::__construct().
   *
   * Add our custom status if the project is cancelled via Drupal.
   */
  public function __construct($params) {
    parent::__construct($params);

    if ($this->isDrupalCancelled()) {
      $status = [
        'code' => 'drupal_cancelled',
        'display' => 'Cancelled in Drupal',
      ];
      $this->status = $status;
      $this->params['status'] = $status;
    }
  }

  /**
   * Returns a list of translatable ids for this project.
   *
   * @param $string $langcode
   *   (Optional) A specific language code to filter on.
   *
   * @return array
   *   A list of ctids.
   */
  public function getCtids($lang_code = NULL) {
    $query = \Drupal::database()->select('cloudwords_content', 'cc')
      ->fields('cc', ['ctid'])
      ->condition('cc.pid', $this->getId());

    if ($lang_code) {
      $query->addJoin('INNER', 'cloudwords_translatable', 'ct', 'ct.id = cc.ctid');
      $query->condition('ct.language', $lang_code);
    }

    return $query->execute()->fetchCol();
  }

  /**
   * Returns a list of translatables
   *
   * @param string $langcode
   *   (Optional) A specific language code to filter on.
   *
   * @return array
   *   A list of CloudwordsTransltable objects.
   */
  public function getTranslatables($lang_code = NULL) {
    $ctids = $this->getCtids($lang_code);
    return cloudwords_translatable_load_multiple($ctids);
  }

  /**
   * Gets the import status for a language.
   *
   * @param CloudwordsLanguage $language
   *   A Cloudwords language object.
   */
  public function getLanguageImportStatus(CloudwordsLanguage $language) {
    $args = [
      ':pid' => $this->getId(),
      ':lang' => $language->getLanguageCode(),
    ];

    return \Drupal::database()->query("SELECT status FROM {cloudwords_project_language} WHERE pid = :pid AND language = :lang", $args)->fetchField();
  }

  /**
   * Updates the import status for a language.
   *
   * @param CloudwordsLanguage $language
   *   A Cloudwords language object.
   * @param int $code
   *   The status code for the import.
   */
  public function setLanguageImportStatus(CloudwordsLanguage $language, $code) {

    \Drupal::database()->merge('cloudwords_project_language')
      ->key([
        'pid' => $this->getId(),
        'language' => $language->getLanguageCode(),
      ])
      ->fields([
        'status' => $code,
      ])
      ->execute();
  }

  /**
   * Release content from this project.
   *
   * @param string $lang_code
   *   (Optional) An optional lang_code. If provided, only content with the
   *   specified language will be released.
   */
  public function releaseContent($lang_code = NULL) {
    foreach ($this->getTranslatables($lang_code) as $translatable) {
      if ($translatable->get('status')->value == CLOUDWORDS_QUEUE_IN_PROJECT) {
        $translatable->set('status', CLOUDWORDS_QUEUE_NOT_IN_QUEUE);
        //$translatable->status = CLOUDWORDS_QUEUE_NOT_IN_QUEUE;
        $translatable->save();
      }
    }
  }

  /**
   * Sets the status for this project.
   *
   * @param string $status
   *   A project status code.
   */
  public function setStatus($status) {
    \Drupal::database()->merge('cloudwords_project')
      ->key([
        'id' => $this->getId(),
      ])
      ->fields([
        'status' => $status,
      ])
      ->execute();
  }

  /**
   * Returns whether or not this project status is 'drupal_cancelled'.
   *
   * @return bool
   *   TRUE or FALSE.
   */
  public function isDrupalCancelled() {
    if ($this->isDrupalCancelled === NULL) {
      $this->isDrupalCancelled = \Drupal::database()->query("SELECT 1 FROM {cloudwords_project} WHERE id = :id and status = 'drupal_cancelled'", [':id' => $this->getId()])->fetchField();
    }
    return $this->isDrupalCancelled;
  }

  /**
   * Cancels a project.
   *
   * This will not cancel the project in Cloudwords!
   */
  public function cancel() {
    $this->releaseContent();
    $this->setStatus('drupal_cancelled');
  }

  public function approve(CloudwordsLanguage $language) {
    $this->setLanguageImportStatus($language, CLOUDWORDS_LANGUAGE_APPROVED);
    $this->releaseContent(cloudwords_map_cloudwords_drupal($language->getLanguageCode()));
  }

  public function isActive() {
    return !in_array($this->status['code'], cloudwords_project_closed_statuses());
  }

}
