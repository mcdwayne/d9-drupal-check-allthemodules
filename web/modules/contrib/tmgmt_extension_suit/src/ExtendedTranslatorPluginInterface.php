<?php

namespace Drupal\tmgmt_extension_suit;

use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\TranslatorPluginInterface;

/**
 * Interface for service plugin controllers.
 */
interface ExtendedTranslatorPluginInterface extends TranslatorPluginInterface {

  /**
   * Checks whether job is ready for download or not.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   Job object.
   *
   * @return bool
   *   TRUE if ready FALSE otherwise.
   */
  public function isReadyForDownload(JobInterface $job);

  /**
   * Downloads translation.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   Job object.
   *
   * @return bool
   *   TRUE if download process completed successfully FALSE otherwise.
   */
  public function downloadTranslation(JobInterface $job);

  /**
   * Returns file name for a given job.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   Job object.
   *
   * @return string
   *   Job file name.
   */
  public function getFileName(JobInterface $job);

  /**
   * Cancels translation.
   *
   * This method cancels not Drupal translation but translation in
   * 3rd party service instead.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   Job object.
   *
   * @return bool
   *   TRUE if canceled FALSE otherwise.
   */
  public function cancelTranslation(JobInterface $job);

  /**
   * Requests translation.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   * @param array $data
   *
   * @return mixed
   */
  public function requestTranslationExtended(JobInterface $job, array $data);

}
