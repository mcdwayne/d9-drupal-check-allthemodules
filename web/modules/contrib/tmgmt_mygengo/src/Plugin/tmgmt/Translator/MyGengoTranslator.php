<?php

/**
 * @file
 * Contains \Drupal\tmgmt_mygengo\Plugin\tmgmt\Translator\MyGengoTranslator.
 */

namespace Drupal\tmgmt_mygengo\Plugin\tmgmt\Translator;

use Drupal;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\TMGMTException;
use Drupal\tmgmt\TranslatorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\tmgmt_mygengo\GengoConnector;
use GuzzleHttp\ClientInterface;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\JobInterface;
use Drupal\Core\Url;
use Drupal\tmgmt\Entity\JobItem;
use Drupal\tmgmt\Entity\RemoteMapping;
use Drupal\tmgmt\Translator\AvailableResult;
use Drupal\Component\Utility\Html;
use Drupal\tmgmt\JobItemInterface;

/**
 * Gengo translation plugin controller.
 *
 * @TranslatorPlugin(
 *   id = "mygengo",
 *   label = @Translation("Gengo translator"),
 *   description = @Translation("A Gengo translator service."),
 *   ui = "Drupal\tmgmt_mygengo\MyGengoTranslatorUi",
 *   logo = "icons/gengo.svg",
 *   default_settings = {
 *     "show_remaining_credits_info" = 1
 *   }
 * )
 */
class MyGengoTranslator extends TranslatorPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  protected $escapeStart = '[[[';

  /**
   * {@inheritdoc}
   */
  protected $escapeEnd = ']]]';

  /**
   * If set it will be sent by job post action as a comment.
   *
   * @var string
   */
  protected $serviceComment;

  /**
   * Guzzle HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * List of supported languages by Gengo.
   *
   * @var array
   */
  protected $supportedRemoteLanguages = array();

  /**
   * Constructs a MyGengoTranslator object.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The Guzzle HTTP client.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(ClientInterface $client, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('http_client'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultRemoteLanguagesMappings() {
    return array(
      'zh-hans' => 'zh',
      'zh-hant' => 'zh-tw',
    );
  }

  /**
   * Sets comment to be sent to gengo service with job post request.
   *
   * @param string $comment
   *   The comment to be sent.
   */
  public function setServiceComment($comment) {
    $this->serviceComment = Html::escape(trim($comment));
  }

  /**
   * {@inheritdoc}
   */
  public function rejectForm($form, &$form_state) {
    $form['message'] = array(
      '#markup' => '<div class="messages warning">' .
        t('By rejecting this item you will submit a new translate job to the Gengo translate service which will result in additional costs.') . '</div>',
    );
    $form['comment'] = array(
      '#type' => 'textarea',
      '#title' => t('Rejection comment'),
      '#description' => t('Provide a brief explanation that you actually rejected previous translation and state your reasons.'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function checkAvailable(TranslatorInterface $translator) {
    if ($translator->getSetting('api_public_key') && $translator->getSetting('api_private_key')) {
      return AvailableResult::yes();
    }
    return AvailableResult::no(t('@translator is not available. Make sure it is properly <a href=:configured>configured</a>.', [
      '@translator' => $translator->label(),
      ':configured' => $translator->url()
    ]));
  }

  /**
   * {@inheritdoc}
   *
   * Here we will actually query source and get translations.
   */
  public function requestTranslation(JobInterface $job) {

    try {

      // Check if we have comment from user input and if yes, set it to be sent.
      if ($job->getSetting('comment')) {
        $this->setServiceComment($job->getSetting('comment'));
      }

      $this->sendJob($job);

      $job->submitted(t('Job has been submitted.'));
    }
    catch (TMGMTException $e) {
      watchdog_exception('tmgmt_mygengo', $e);
      $job->rejected('Job has been rejected with following error: @error',
        array('@error' => $e->getMessage()), 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedRemoteLanguages(TranslatorInterface $translator) {
    if (!empty($this->supportedRemoteLanguages)) {
      return $this->supportedRemoteLanguages;
    }

    try {
      $connector = new GengoConnector($translator, $this->client);
      foreach ($connector->getLanguages() as $gengo_language) {
        $this->supportedRemoteLanguages[$gengo_language['lc']] = $gengo_language['lc'];
      }
    }
    catch (\Exception $e) {
      watchdog_exception('tmgmt', $e);
    }

    return $this->supportedRemoteLanguages;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTargetLanguages(TranslatorInterface $translator, $source_language) {
    $results = array();

    try {
      $connector = new GengoConnector($translator, $this->client);
      $response = $connector->getLanguages($source_language);
      foreach ($response as $target) {
        $results[$target['lc']] = $target['lc'];
      }
    }
    catch (\Exception $e) {
      watchdog_exception('tmgmt', $e);
    }

    return $results;
  }

  /**
   * Will build and send a job to gengo service.
   *
   * @param \Drupal\tmgmt\Entity\Job $job
   *   Job to be submitted for translation.
   * @param bool $quote_only
   *   (Optional) Set to TRUE to only get a quote for the given job.
   *
   * @return mixed
   *   - Array of job objects returned from gengo.
   *   - Status object with order info.
   */
  public function sendJob(Job $job, $quote_only = FALSE) {
    $data = \Drupal::service('tmgmt.data')->filterTranslatable($job->getData());

    $translator = $job->getTranslator();
    $translations = array();
    $sources = array();
    $position = 0;
    $duplicates = array();

    foreach ($data as $key => $value) {
      if (isset($value['#translate']) && $value['#translate'] === FALSE) {
        continue;
      }

      if (!$quote_only) {
        // Detect duplicate source strings and add a mapping for them.
        if ($duplicate_key = array_search($value['#text'], $sources)) {
          $duplicates[$duplicate_key][] = $key;
          continue;
        }
      }

      // Keep track of source texts for easy lookup.
      $sources[$key] = $value['#text'];

      $translations[$job->id() . '][' . $key] = array(
        'type' => 'text',
        'slug' =>  \Drupal::service('tmgmt.data')->itemLabel($value, 56),
        'body_src' => $value['#text'],
        'lc_src' => $job->getRemoteSourceLanguage(),
        'lc_tgt' => $job->getRemoteTargetLanguage(),
        'tier' => $job->getSetting('quality'),
        'callback_url' => Url::fromRoute('tmgmt_mygengo.callback')->setOptions(array('absolute' => TRUE))->toString(),
        'custom_data' => $job->id() . '][' . $key,
        'position' => $position++,
        'auto_approve' => (int) $translator->getSetting('mygengo_auto_approve'),
        'use_preferred' => (int) $translator->getSetting('use_preferred'),
      );

      if (!empty($this->serviceComment)) {
        $translations[$job->id() . '][' . $key]['comment'] = $this->serviceComment;
      }
    }

    $connector = new GengoConnector($job->getTranslator(), $this->client);
    if ($quote_only) {
      return $connector->getQuote($translations);
    }
    else {
      $response = $connector->submitJob($translations);
      // If we already receive jobs, process them.
      if (!empty($response['jobs'])) {
        $this->processGengoJobsUponTranslationRequest($job, $response['jobs'], $duplicates);
      }
      elseif (isset($response['order_id'])) {
        $this->initGengoMapping($job, $response['order_id'], $translations, $duplicates);
      }
      return $response;
    }
  }

  /**
   * Receives and stores a translation returned by Gengo.
   *
   * @param \Drupal\tmgmt\Entity\Job $job
   *   Job for which to receive translations.
   * @param string $key
   *   Data keys for data item which will be updated with translation.
   * @param array $data
   *   Translated data received from gengo.
   */
  public function saveTranslation(Job $job, $key, $data) {
    if ($data['status'] == 'approved' || $data['status'] == 'reviewable') {
      // If the status is approved or reviewable, we expect a body_tgt property,
      // abort and log if it doesn't exist.
      if (!isset($data['body_tgt'])) {
        $job->addMessage('Callback called for @key and status @status without translation.', array('@key' => $data['custom_data'], '@status' => $data['status']));
        return;
      }
      $text = $this->unescapeText($data['body_tgt']);
      $job->addTranslatedData(array('#text' => $text), $key);

      // Look for duplicated strings that were saved with a mapping to this key.
      // @todo: Refactor this method to accept the remote instead of $key?
      list($tjiid, $data_item_key) = explode('][', $key, 2);
      $remotes = RemoteMapping::loadByLocalData($job->id(), $tjiid, $data_item_key);
      $remote = reset($remotes);
      if ($remote && !empty($remote->remote_data->duplicates)) {
        // If we found any mappings, also add the translation for those.
        foreach ($remote->remote_data->duplicates as $duplicate_key) {
          $job->addTranslatedData(array('#text' => $text), $duplicate_key);
        }
      }
    }
  }

  /**
   * Will process remote jobs upon translation request.
   *
   * This deals with following:
   *   - Creates mappings of local data items to remote gengo jobs.
   *   - Saves translation in case it has been already received.
   *   - Deals with duplicate translations.
   *
   * @param \Drupal\tmgmt\Entity\Job $job
   *   Local job that is going to be processed.
   * @param array $response_jobs
   *   List of gengo jobs received.
   * @param array $duplicates
   *   Array of duplicate mappings.
   */
  protected function processGengoJobsUponTranslationRequest(Job $job, $response_jobs, array $duplicates) {
    foreach ($response_jobs as $key => $response_job) {

      // Duplicate content has been submitted.
      if (isset($response_job['duplicate'])) {
        // @todo Currently handled manually, so this should never occur.
        continue;
      }

      // For machine translations the job is not wrapped in another object
      // however for human translations it is. So try to cope with this
      // gengo system variety.
      if (!empty($response_job['custom_data']) || !empty($response_job[0]['custom_data'])) {
        $response_job = reset($response_job);
      }

      // Not sure what this status means - I was unable to find its purpose in
      // the gengo documentation. But if we have this status we will receive
      // no data at all and therefore we will not be able to create mapping.
      // @todo - this is just a quick fix so that we can finish job submission
      // in such case. But not a solution as we end up with not mapped jobs
      // at gengo. This should be fixed in #2022147.
      if ($response_job['status'] == 'held') {
        continue;
      }

      // In case we receive an existing translation the array offset IS also
      // the data kay, and we ought to use it as the object custom data is not
      // updated. However this is not always the case and in some case we
      // receive numeric keys, so try to set some custom data to increase the
      // chance of matching the job.
      if (is_numeric($key)) {
        $key = $response_job['custom_data'];
      }

      // Extract job item id and data item key.
      list(, $tjiid, $data_item_key) = explode('][', $key, 3);

      $item_id_data_key = $tjiid . '][' . $data_item_key;
      $item = JobItem::load($tjiid);
      // Create the mapping.
      $item->addRemoteMapping($data_item_key, NULL, array(
        // Yes, this is not a joke, they really return string value "NULL" in
        // case of a machine translation.
        'remote_identifier_2' => $response_job['job_id'] == 'NULL' ? 0 : $response_job['job_id'],
        'word_count' => $response_job['unit_count'],
        'remote_data' => array(
          'credits' => $response_job['credits'],
          'tier' => $response_job['tier'],
          'duplicates' => isset($duplicates[$item_id_data_key]) ? $duplicates[$item_id_data_key] : array(),
        ),
      ));

      // Update the translation. This needs to be after the mapping as it
      // depends on it for the duplicates handling.
      $this->saveTranslation($job, $item_id_data_key, $response_job);
    }
  }

  /**
   * Creates placeholder records in the mapping table.
   *
   * The idea here is not to introduce additional storage to temporarily store
   * gengo order id before we get gengo job ids.
   *
   * @param \Drupal\tmgmt\Entity\Job $job
   *   Job for which to initiate mappings with remote jobs.
   * @param int $gorder_id
   *   Gengo job id.
   * @param array $translations
   *   Array of requested translations.
   * @param array $duplicates
   *   Array of duplicate mappings.
   */
  protected function initGengoMapping(Job $job, $gorder_id, array $translations, array $duplicates) {
    $items = $job->getItems();
    foreach ($translations as $key => $translation) {
      // Extract job item id and data item key.
      list(, $tjiid, $data_item_key) = explode('][', $key, 3);
      $mapping_data = array();
      $item_id_data_key = $tjiid . '][' . $data_item_key;
      if (isset($duplicates[$item_id_data_key])) {
        $mapping_data['remote_data']['duplicates'] = $duplicates[$item_id_data_key];
      }
      $items[$tjiid]->addRemoteMapping($data_item_key, $gorder_id, $mapping_data);
    }
  }

  /**
   * Maps TMGMT job data items to gengo jobs.
   *
   * @param \Drupal\tmgmt\Entity\Job $job
   *   Job that will be mapped to remote jobs.
   */
  public function fetchGengoJobs(Job $job, $save_translations = TRUE) {
    // Search for placeholder item.
    $remotes = RemoteMapping::loadByLocalData($job->id());

    $connector = new GengoConnector($job->getTranslator(), $this->client);

    // Collect unique order id's and job ids.
    $order_ids = array();
    $job_ids = array();
    $new_job_ids = array();
    foreach ($remotes as $remote) {
      if (!empty($remote->remote_identifier_1->value) && !isset($order_ids[$remote->remote_identifier_1->value])) {
        $order_ids[$remote->remote_identifier_1->value] = $remote->remote_identifier_1->value;
      }
      if (!empty($remote->remote_identifier_2->value)) {
        $job_ids[$remote->remote_identifier_2->value] = $remote->remote_identifier_2->value;
      }
    }

    // If we have orders we want to check for new job ids.
    foreach ($order_ids as $gorder_id) {
      try {
        $response = $connector->getOrder($gorder_id);
        // No jobs yet created at Gengo side. Nothing to do.
        if ($response['order']['jobs_queued'] == $response['order']['total_jobs']) {
          return;
        }

        // Collect job ids of that job. Use array_merge() as we have numerical
        // array keys.
        $order_job_ids = array();
        if (!empty($response['order']['jobs_available'])) {
          $order_job_ids = array_merge($order_job_ids, $response['order']['jobs_available']);
        }
        if (!empty($response['order']['jobs_pending'])) {
          $order_job_ids = array_merge($order_job_ids, $response['order']['jobs_pending']);
        }
        if (!empty($response['order']['jobs_reviewable'])) {
          $order_job_ids = array_merge($order_job_ids, $response['order']['jobs_reviewable']);
        }
        if (!empty($response['order']['jobs_approved'])) {
          $order_job_ids = array_merge($order_job_ids, $response['order']['jobs_approved']);
        }
        if (!empty($response['order']['jobs_revising'])) {
          $order_job_ids = array_merge($order_job_ids, $response['order']['jobs_revising']);
        }

        // Keep a record of new job ids and their mappings and add them to the
        // job id list.
        foreach (array_diff($order_job_ids, $job_ids) as $new_job_id) {
          $job_ids[$new_job_id] = $new_job_id;
          $new_job_ids[$new_job_id] = $gorder_id;
        }

      }
      catch (TMGMTException $e) {
        watchdog_exception('tmgmt_mygengo', $e);
        drupal_set_message($e->getMessage(), 'error');
        continue;
      }
    }

    // Get gengo jobs for existing gengo job ids.
    $response = $connector->getJobs($job_ids);

    // Gengo did not provide any mapping data, do nothing.
    // This only happens in the case that Gengo is unreachable.
    if (empty($response['jobs'])) {
      return;
    }

    // Update mappings and save any translations.
    foreach ($response['jobs'] as $key => $response_job) {
      // Extract job item id and data item key.
      list(, $tjiid, $data_item_key) = explode('][', $response_job['custom_data'], 3);

      // If this is a new job, look for a remote mapping based on the data item
      // key and update it.
      if (isset($new_job_ids[$response_job['job_id']])) {

        $matching_remote = NULL;
        foreach ($remotes as $remote) {
          if ($remote->data_item_key->value == $data_item_key && $remote->getJobItem()->id() == $tjiid) {
            $matching_remote = $remote;
            break;
          }
        }

        // We don't have a remote mapping yet, create one.
        if (!$matching_remote) {
          $item = JobItem::load($tjiid);
          $item->addRemoteMapping($data_item_key, $new_job_ids[$response_job['job_id']], array(
            'remote_identifier_2' => $response_job['job_id'],
            'word_count' => $response_job['unit_count'],
            'remote_data' => array(
              'credits' => $response_job['credits'],
              'tier' => $response_job['tier'],
            ),
            // @todo: Add remote_url.
          ));
        }
        // We have a mapping, update it.
        else {
          $matching_remote->remote_identifier_2->value = $response_job['job_id'];
          $matching_remote->word_count = $response_job['unit_count'];
          $matching_remote->addRemoteData('credits', $response_job['credits']);
          $matching_remote->addRemoteData('tier', $response_job['tier']);
          $matching_remote->save();
        }
      }
      if ($save_translations) {
        $this->saveTranslation($job, $tjiid . '][' . $data_item_key, $response_job);
      }
    }
  }

  /**
   * Returns account statistics.
   */
  public function getStatistics(TranslatorInterface $translator) {
    try {
      $connector = new GengoConnector($translator, $this->client);
      return $connector->getStatistics();
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function abortTranslation(JobInterface $job) {
    // Update the TMGMT jobs to Gengo jobs mapping.
    $this->fetchGengoJobs($job, FALSE);

    $count_aborted = 0;
    $remotes = RemoteMapping::loadByLocalData($job->id());
    // There is only 1 job item for each remotes.
    /** @var JobItem $job_item */
    $job_item = reset($remotes)->getJobItem();

    if (!$job_item->isAborted()) {
      // Loop on Gengo translation jobs.
      foreach ($remotes as $remote) {
        if (!empty($remote->remote_identifier_2->value)) {
          try {
            $connector = new GengoConnector($job->getTranslator(), $this->client);
            $connector->abortJob($remote->remote_identifier_2->value);
            $count_aborted++;
          } catch (TMGMTException $e) {
            // Nothing to do. The abortion count isn't incremented.
          }
        }
      }

      // Add a message to the job item.
      $count_remotes = count($remotes);
      if ($count_aborted != $count_remotes) {
        $job_item->addMessage('Aborted @count_aborted out of @count_total jobs at Gengo. Gengo does not allow aborting jobs that are already in translation.', ['@count_aborted' => $count_aborted, '@count_total' => $count_remotes], 'warning');
      }
      else {
        $job_item->addMessage('Aborted @count_aborted out of @count_total jobs at Gengo.', ['@count_aborted' => $count_aborted, '@count_total' => $count_remotes]);
      }

      // Abort the job item even if Gengo job(s) couldn't be aborted.
      $job_item->setState(JobItemInterface::STATE_ABORTED, 'Aborted by user.');
      $job_item->save();
    }

    // Abort the job in the current system.
    return parent::abortTranslation($job);
  }

}
