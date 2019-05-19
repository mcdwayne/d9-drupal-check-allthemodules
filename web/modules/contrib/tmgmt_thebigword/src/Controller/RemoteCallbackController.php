<?php

namespace Drupal\tmgmt_thebigword\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\Entity\JobItem;
use Drupal\tmgmt\Entity\RemoteMapping;
use Drupal\tmgmt\Entity\Translator;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\RemoteMappingInterface;
use Drupal\tmgmt\TMGMTException;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt_thebigword\Plugin\tmgmt\Translator\ThebigwordTranslator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route controller of the remote callbacks for the tmgmt_thebigword module.
 */
class RemoteCallbackController extends ControllerBase {

  /**
   * Handles the notifications of changes in the files states.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request to handle.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response to return.
   */
  public function callback(Request $request) {
    $config = \Drupal::configFactory()->get('tmgmt_thebigword.settings');
    if ($config->get('debug')) {
      \Drupal::logger('tmgmt_thebigword')->debug('Request received %request.', ['%request' => $request]);
    }
    $project_id = $request->get('ProjectId');
    $file_id = $request->get('FileId');
    if (isset($project_id) && isset($file_id)) {
      // Get mappings between the job items and the file IDs, for the project.
      $remotes = RemoteMapping::loadByRemoteIdentifier('tmgmt_thebigword', $project_id);
      if (empty($remotes)) {
        \Drupal::logger('tmgmt_thebigword')->warning('Project %id not found.', ['%id' => $project_id]);
        return new Response(new FormattableMarkup('Project %id not found.', ['%id' => $project_id]), 404);
      }
      $remote = NULL;
      /** @var \Drupal\tmgmt\Entity\RemoteMapping $remote_candidate */
      foreach ($remotes as $remote_candidate) {
        if ($remote_candidate->getRemoteIdentifier3() == $file_id) {
          $remote = $remote_candidate;
        }
      }
      if (!$remote) {
        \Drupal::logger('tmgmt_thebigword')->warning('File %id not found.', ['%id' => $file_id]);
        return new Response(new FormattableMarkup('File %id not found.', ['%id' => $file_id]), 404);
      }

      /** @var \Drupal\tmgmt_thebigword\Plugin\tmgmt\Translator\ThebigwordTranslator $translator_plugin */
      $translator_plugin = $remote->getJob()->getTranslator()->getPlugin();
      $translator_plugin->setTranslator($remote->getJob()->getTranslator());

      $info = $translator_plugin->request('file/cmsstate/' . $file_id);
      if ($info['CmsState'] != $request->get('CmsState')) {
        \Drupal::logger('tmgmt_thebigword')->warning('The CmsState %cmsState is not the CmsState of the file %id.', ['%cmsState' => $info['CmsState']]);
        return new Response(new FormattableMarkup('The CmsState %cmsState is not the CmsState of the file %id.', ['%cmsState' => $info['CmsState']]), 400);
      }
      try {
        $translator_plugin->addFileDataToJob($remote, $request->get('CmsState'));
      }
      catch (TMGMTException $e) {
        $form_params = [
          'FileId' => $file_id,
          'CmsState' => $info['CmsState'] . '-Error',
        ];
        $translator_plugin->request('file/cmsstate', 'POST', $form_params);

        $job = $remote->getJob();
        $job_item = $remote->getJobItem();
        $restart_point = $translator_plugin->getErrorRestartPoint($info['CmsState']);
        $translator_plugin->sendFileError($restart_point, $project_id, $file_id, $job, $remote->getRemoteData('RequiredBy'), $info['CmsState'] . ':' . $e->getMessage(), TRUE);
        $job->addMessage('Error fetching the job item: @job_item.', ['@job_item' => $job_item->label()], 'error');
      }
    }
    else {
      return new Response('Bad request.', 400);
    }
    return new Response();
  }

  /**
   * Returns a no preview response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request to handle.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response to return.
   */
  public function noPreview(Request $request) {
    return new Response('No preview url available for this file.');
  }

  /**
   * Pull all remote translations.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request to handle.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response to return.
   */
  public function pullAllRemoteTranslations(Request $request) {
    $translators = Translator::loadMultiple();
    $operations = [];

    // Support either an explicit job_id or alternatively fetch all active
    // thebigword jobs.
    if ($request->query->get('job_id')) {
      $job_ids = [$request->query->get('job_id')];
    }
    else {
      // First get all translator IDs that use thebigword.
      $thebigword_translator_ids = array_keys(array_filter($translators, function (TranslatorInterface $translator) {
        $translator_plugin = $translator->getPlugin();
        return $translator_plugin instanceof ThebigwordTranslator;
      }));

      // Then fetch all active or continuous jobs using those translators.
      $job_ids = \Drupal::entityQuery('tmgmt_job')
        ->condition('translator', $thebigword_translator_ids, 'IN')
        ->condition('state', [JobInterface::STATE_ACTIVE, JobInterface::STATE_CONTINUOUS], 'IN')
        ->execute();
    }

    // Then fetch all active job items for those jobs, sort by job ID, job item
    // ID to have a predictable order and group them by job.
    $item_ids = \Drupal::entityQuery('tmgmt_job_item')
      ->condition('tjid', $job_ids, 'IN')
      ->condition('state', [JobItemInterface::STATE_ACTIVE, JobItemInterface::STATE_REVIEW], 'IN')
      ->sort('tjid', 'DESC')
      ->sort('tjiid', 'DESC')
      ->execute();

    foreach ($item_ids as $item_id) {
      $operations[] = [
        [static::class, 'pullRemoteTranslations'],
        [$item_id],
      ];
    }
    $batch = [
      'title' => t('Pulling translations'),
      'operations' => $operations,
      'finished' => 'tmgmt_thebigword_pull_translations_batch_finished',
      'init_message' => t('Completed 0 of @total translation job items.', ['@total' => count($item_ids)]),
      'progress_message' => t('Completed @current of @total translation job items.'),
    ];
    batch_set($batch);
    return batch_process(Url::fromRoute('view.tmgmt_translation_all_job_items.page_1'));
  }

  /**
   * Creates continuous job items for entity.
   *
   * Batch callback function.
   */
  public static function pullRemoteTranslations($item_id, &$context) {
    /** @var \Drupal\tmgmt\JobItemInterface $job_item */
    $job_item = JobItem::load($item_id);
    $mappings = RemoteMapping::loadByLocalData($job_item->getJobId(), $job_item->id());
    /** @var \Drupal\tmgmt\RemoteMappingInterface $mapping */
    $mapping = reset($mappings);

    $project_id = $mapping->getRemoteIdentifier2();

    /** @var \Drupal\tmgmt_thebigword\Plugin\tmgmt\Translator\ThebigwordTranslator $translator_plugin */
    $translator_plugin = $job_item->getTranslatorPlugin();
    $results = $translator_plugin->fetchTranslatedFiles($job_item->getJob(), $project_id, $mapping->getRemoteIdentifier3());
    foreach ($results as $key => $count) {
      if (!isset($context['results']['updates'][$key]))  {
        $context['results']['updates'][$key] = 0;
      }
      $context['results']['updates'][$key] += $count;
    }

    if ($job_item) {
      $job = $job_item->getJob();
      $context['message'] = t('Processed job item %item for job %label. @source_language to @target_language.', [
        '%label' => $job->label(),
        '%item' => $job_item->label(),
        '@source_language' => $job->getSourceLanguage()->getName(),
        '@target_language' => $job->getTargetLanguage()->getName(),
      ]);
    }
  }

  /**
   * Access callback for the review redirect form.
   *
   * @param \Drupal\tmgmt\JobItemInterface $tmgmt_job_item
   *   The job item.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function reviewRedirectAccess(JobItemInterface $tmgmt_job_item) {
    if ($tmgmt_job_item->hasTranslator() && $tmgmt_job_item->getTranslatorPlugin() instanceof ThebigwordTranslator) {
      /** @var \Drupal\tmgmt_thebigword\Plugin\tmgmt\Translator\ThebigwordTranslator $translator_plugin */
      $translator_plugin = $tmgmt_job_item->getTranslatorPlugin();
      if ($translator_plugin->userHasExternalReviewAccess($tmgmt_job_item, $this->currentUser())) {
        return AccessResult::allowed()
          ->addCacheableDependency($tmgmt_job_item)
          ->addCacheContexts(['user.permissions']);
      }
    }
    return AccessResult::neutral()
      ->addCacheableDependency($tmgmt_job_item)
      ->addCacheContexts(['user.permissions']);
  }

}
