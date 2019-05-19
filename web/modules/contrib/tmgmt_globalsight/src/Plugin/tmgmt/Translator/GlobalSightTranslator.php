<?php

/**
 * @file
 * Contains \Drupal\tmgmt_globalsight\Plugin\tmgmt\Translator\GlobalSightTranslator.
 */

namespace Drupal\tmgmt_globalsight\Plugin\tmgmt\Translator;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\tmgmt\ContinuousTranslatorInterface;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\Entity\JobItem;
use Drupal\tmgmt\Entity\RemoteMapping;
use Drupal\tmgmt\Entity\Translator;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\SourcePreviewInterface;
use Drupal\tmgmt\TMGMTException;
use Drupal\tmgmt\TranslatorPluginBase;
use Drupal\tmgmt_globalsight\GlobalsightConnector;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\Translator\AvailableResult;

/**
 * GlobalSight translator plugin.
 *
 * @TranslatorPlugin(
 *   id = "globalsight",
 *   label = @Translation("GlobalSight translator"),
 *   description = @Translation("GlobalSight translator service."),
 *   ui = "Drupal\tmgmt_globalsight\GlobalSightTranslatorUi"
 * )
 */
class GlobalSightTranslator extends TranslatorPluginBase implements ContainerFactoryPluginInterface, ContinuousTranslatorInterface {

  /** @var GlobalsightConnector */
  private $connector;

  const STATUS_ARCHIVED = 0;
  const STATUS_DISPATCHED = 1;
  const STATUS_EXPORTED = 2;
  const STATUS_LOCALIZED = 3;
  const STATUS_CANCELED = 4;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static (
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTargetLanguages(TranslatorInterface $translator, $source_language) {

    // Ensure successful GlobalSight connection before continuing.
    if (!($gs = $this->getConnector($translator))) {
      return [];
    };

    $locales = $gs->getLocales();
    if (!($locales)) {
      return [];
    }

    // Forbid translations if source and target languages are not supported by GlobalSight.
    if (!in_array($source_language, $locales['source'])) {
      return [];
    }

    $target_languages = [];
    foreach ($locales['target'] as $target_language) {
      $target_languages[$target_language] = $target_language;
    }

    return $target_languages;
  }

  /**
   * {@inheritdoc}
   */
  public function requestTranslation(JobInterface $job) {
    $translator = $job->getTranslator();

    // Ensure successful GlobalSight connection before continuing.
    if (!($gs = $this->getConnector($translator))) {
      $job->rejected('Job rejected because GS connection could not be established.');

      return;
    };

    // Not all strings in the job are translatable. Find them.
    $strings = \Drupal::service('tmgmt.data')->filterTranslatable($job->getData());
    $translation_strings = [];
    foreach ($strings as $key => $string) {
      if ($string['#translate']) {
        $translation_strings[$key] = $string['#text'];
      }
    }

    // Submit the job to GlobalSight.
    $jobName = $gs->send($job->id(), $job->label(), $job->getRemoteTargetLanguage(), $translation_strings);

    if (!$jobName) {
      // Cancel the job.
      $job->rejected('Translation job was rejected due to an unrecoverable error.');

      return;
    }

    $record = [
      'tjid' => $job->id(),
      'job_name' => $jobName,
      'status' => 1
    ];
    $job->submitted('The translation job has been submitted.');
    \Drupal::database()->insert('tmgmt_globalsight')->fields($record)->execute();
  }

  public function requestJobItemsTranslation(array $job_items) {
    // ContinuousTranslatorInterface
    // TODO: Implement requestJobItemsTranslation() method.
  }

  /**
   * {@inheritdoc}
   */
  public function hasCheckoutSettings(JobInterface $job) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function abortTranslation(JobInterface $job) {
    $job_name = $this->getJobName($job);
    $translator = $job->getTranslator();

    // Ensure successful GlobalSight connection before continuing.
    if (!($gs = $this->getConnector($translator))) {
      return FALSE;
    };

    if ($status = $gs->cancel($job_name)) {
      $this->updateJobRecord($job->id(), self::STATUS_ARCHIVED);
      $job->aborted('The translation job has successfully been canceled');

      return TRUE;
    }

    return FALSE;
  }


  public function pollGlobalsight($record) {
    /** @var JobInterface $job */
    $job = Job::load($record['tjid']);
    if (!$job) {
      // @todo: Archive the record, it got detached from the TMGMT job.
      return FALSE;
    }

    $translator = $job->getTranslator();

    if (!($gs = $this->getConnector($translator))) {
      return FALSE;
    };

    $status = $gs->getStatus($record['job_name']);
    if (!$status) {
      return FALSE;
    }

    // Skip execution if GlobalSight status hasn't changed
    if ($status['status'] == $gs->code2status($record['status'])) {
      return FALSE;
    }

    // In order for a translation to be considered "ready" it need to be either EXPORTED or LOCALIZED.
    if (!in_array($status['status'], ['EXPORTED', 'LOCALIZED'])) {
      return FALSE;
    }

    $translation = $gs->receive($record['job_name']);

    $job->addTranslatedData(\Drupal::service('tmgmt.data')->unflatten($translation));

    $this->updateJobRecord($record['tjid'], self::STATUS_ARCHIVED);

    return TRUE;
  }

  public function getJobName(JobInterface $job) {
    $query = \Drupal::database()->select('tmgmt_globalsight', 'gs');
    $query->addField('gs', 'job_name');
    $query->condition("gs.tjid", $job->id(), '=');
    $result = $query->execute()->fetchCol(0);

    if (!empty($result[0])) {
      return $result[0];
    }

    return FALSE;
  }

  public function getUntranslatedJobRecords() {
    $query = \Drupal::database()->select('tmgmt_globalsight', 'gs');
    $query->fields('gs');
    $query->condition('gs.status', 0, '>');
    $results = $query->execute();

    // Loop through jobs and check for translations
    $records = [];
    while ($record = $results->fetchAssoc()) {
      $records[] = $record;
    }

    return $records;
  }

  public function getConnector(TranslatorInterface $translator) {
    if (!empty($this->connector)) {
      return $this->connector;
    }

    // @todo: Dependency injection in class constructor, please.
    /** @var GlobalsightConnector $connector */
    $connector = \Drupal::service('tmgmt_globalsight.connector');

    $uri = 'base:' . drupal_get_path('module', 'tmgmt_globalsight') . '/AmbassadorWebService.xml';
    $wsdl = Url::fromUri($uri, ['absolute' => TRUE])->toString();

    if (!$connector->init(
      $translator->getSetting('endpoint'),
      $translator->getSetting('username'),
      $translator->getSetting('password'),
      $translator->getSetting('file_profile_id'),
      $wsdl
    )) {
      // @todo: Dependency injection in class constructor, please.
      $messenger = \Drupal::messenger();
      $messenger->addMessage(
        $this->t('An error occurred and connection to GlobalSight translator could not be established'),
        'error'
      );

      return FALSE;
    }
    $this->connector = $connector;

    return $this->connector;
  }

  private function updateJobRecord($tjid, $status) {
    \Drupal::database()
      ->update('tmgmt_globalsight')
      ->condition('tjid', $tjid)
      ->fields(['status' => $status])
      ->execute();
  }
}
