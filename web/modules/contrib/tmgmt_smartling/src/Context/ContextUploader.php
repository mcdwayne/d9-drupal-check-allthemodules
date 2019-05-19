<?php

namespace Drupal\tmgmt_smartling\Context;

use Drupal;
use Drupal\tmgmt_smartling\Exceptions\EmptyContextParameterException;
use Drupal\tmgmt_smartling\Smartling\SmartlingApiWrapper;
use Exception;
use Psr\Log\LoggerInterface;
use Drupal\tmgmt_smartling\Exceptions\SmartlingBaseException;
use Smartling\Context\Params\MatchContextParameters;
use Smartling\Context\Params\UploadContextParameters;
use Smartling\Context\Params\UploadResourceParameters;
use Smartling\Exceptions\SmartlingApiException;

class ContextUploader {

  /**
   * @var TranslationJobToUrl
   */
  protected $urlConverter;

  /**
   * @var ContextCurrentUserAuth
   */
  protected $authenticator;

  /**
   * @var HtmlAssetInliner
   */
  protected $assetInliner;

  /**
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * @var SmartlingApiWrapper
   */
  protected $apiWrapper;

  public function __construct(
    SmartlingApiWrapper $api_wrapper,
    TranslationJobToUrl $url_converter,
    ContextUserAuth $auth,
    HtmlAssetInliner $html_asset_inliner,
    LoggerInterface $logger
  ) {
    $this->apiWrapper = $api_wrapper;
    $this->urlConverter = $url_converter;
    $this->authenticator = $auth;
    $this->assetInliner = $html_asset_inliner;
    $this->logger = $logger;
  }

  public function jobItemToUrl($job_item) {
    return $this->urlConverter->convert($job_item);
  }

  /**
   * @param $url
   * @param array $settings
   * @param bool $debug
   *
   * @return mixed|string|void
   * @throws \Drupal\tmgmt_smartling\Exceptions\EmptyContextParameterException
   * @throws Exception
   */
  public function getContextualizedPage($url, array $settings, $debug = FALSE) {
    if (empty($url)) {
      throw new EmptyContextParameterException('Context url must be a non-empty string.');
    }

    $username = $settings['contextUsername'];

    if (empty($username)) {
      $username = $this->authenticator->getCurrentAccount()->getAccountName();
    }

    $cookies = $this->authenticator->getCookies($username, $settings['context_silent_user_switching']);
    $html = $this->assetInliner->getCompletePage($url, $cookies, TRUE, FALSE, $settings, $debug);

    $html = str_replace('<p></p>', "\n", $html);

    if (empty($html)) {
      throw new Exception("Got empty context for $url url.");
    }

    return $html;
  }

  /**
   * @param string $url
   * @param string $filename
   * @param array $proj_settings
   * @return bool
   * @throws \Drupal\tmgmt_smartling\Exceptions\EmptyContextParameterException
   */
  public function upload($url, $filename = '', $proj_settings = []) {
    $response = [];
    $api_wrapper = $this->getApiWrapper($proj_settings);

    if (empty($url)) {
      $api_wrapper->createFirebaseRecord("tmgmt_smartling", "notifications", 10, [
        "message" => t('Context upload failed: context url is empty.'),
        "type" => "error",
      ]);

      throw new EmptyContextParameterException('Context url must be a non-empty field.');
    }

    $smartling_context_directory = $proj_settings['scheme'] . '://tmgmt_smartling_context';
    $smartling_context_file = $smartling_context_directory . '/' . str_replace('.', '_', $filename) . '.html';
    $error_message = t(
      'Error while uploading context for file @filename. See logs for more info.',
      ['@filename' => $filename]
    )->render();

    // Upload context body.
    try {
      $html = $this->getContextualizedPage($url, $proj_settings);

      // Save context file.
      if (file_prepare_directory($smartling_context_directory, FILE_CREATE_DIRECTORY) &&
          ($file = file_save_data($html, $smartling_context_file, FILE_EXISTS_REPLACE))
      ) {
        $response = $this->uploadContextBody($url, $file, $proj_settings, $filename);
        $this->uploadContextMissingResources($smartling_context_directory, $proj_settings);

        if (!empty($response)) {
          $this->logger->info('Context upload for file @filename completed successfully.', ['@filename' => $filename]);
          $api_wrapper->createFirebaseRecord("tmgmt_smartling", "notifications", 10, [
            "message" => t(
              'Context upload for file @filename completed successfully.',
              ['@filename' => $filename]
            )->render(),
            "type" => "status",
          ]);
        }
        else {
          $api_wrapper->createFirebaseRecord("tmgmt_smartling", "notifications", 10, [
            "message" => $error_message,
            "type" => "error",
          ]);
        }
      }
      else {
        $this->logger->error("Can't save context file: @path", [
          '@path' => $smartling_context_file,
        ]);

        $api_wrapper->createFirebaseRecord("tmgmt_smartling", "notifications", 10, [
          "message" => $error_message,
          "type" => "error",
        ]);
      }
    } catch (Exception $e) {
      $this->logger->error($e->getMessage());
      $api_wrapper->createFirebaseRecord("tmgmt_smartling", "notifications", 10, [
        "message" => $error_message,
        "type" => "error",
      ]);
    }

    return $response;
  }

  /**
   * @param $proj_settings
   *
   * @return mixed
   */
  protected function getApiWrapper($proj_settings) {
    $this->apiWrapper->setSettings($proj_settings);

    return $this->apiWrapper;
  }

  /**
   * @param $url
   * @param $file
   * @param $proj_settings
   * @param null $content_filename
   * @return array
   */
  protected function uploadContextBody($url, $file, $proj_settings, $content_filename = NULL) {
    try {
      $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager')->getViaUri($file->getFileUri());
      $api = $this->getApiWrapper($proj_settings)->getApi('context');

      $match_params = new MatchContextParameters();
      $match_params->setContentFileUri($content_filename);
      $match_params->setOverrideContextOlderThanDays(0);

      $upload_params = new UploadContextParameters();
      $upload_params->setContent($stream_wrapper_manager->realpath());
      $upload_params->setName($url);

      $upload_params_with_matching = new UploadContextParameters();
      $upload_params_with_matching->setContent($stream_wrapper_manager->realpath());
      $upload_params_with_matching->setName($url);
      $upload_params_with_matching->setMatchParams($match_params);

      $api->uploadAndMatchContextSync($upload_params_with_matching);
      $response = $api->uploadAndMatchContext($upload_params);
    } catch (Exception $e) {
      $response = [];
      watchdog_exception('tmgmt_smartling', $e);
    }

    return $response;
  }

  /**
   * @param $smartling_context_directory
   * @param $proj_settings
   */
  protected function uploadContextMissingResources($smartling_context_directory, $proj_settings) {
    // Cache for resources which we can't upload. Do not try to re-upload them
    // for 1 hour. After 1 hour cache will be reset and we will try again.
    $cache_name = 'smartling_context_resources_cache';
    $time_to_live = 60 * 60;
    $two_days = 2 * 24 * 60 * 60;
    $cache = \Drupal::cache()->get($cache_name);
    $cached_data = empty($cache) ? [] : $cache->data;
    $update_cache = FALSE;
    $smartling_context_resources_directory = $smartling_context_directory . '/resources';

    // Do nothing if directory for resources isn't accessible.
    if (!file_prepare_directory($smartling_context_resources_directory, FILE_CREATE_DIRECTORY)) {
      $this->logger->error("Context resources directory @dir doesn't exist or is not writable. Missing resources were not uploaded. Context might look incomplete.", [
        '@dir' => $smartling_context_directory,
      ]);

      return;
    }

    try {
      $api = $this->getApiWrapper($proj_settings)->getApi('context');
      $time_out = PHP_SAPI == 'cli' ? 300 : 30;
      $start_time = time();

      do {
        $delta = time() - $start_time;

        if ($delta > $time_out) {
          throw new SmartlingApiException(vsprintf('Not all context resources are uploaded after %s seconds.', [$delta]));
        }

        $all_missing_resources = $api->getAllMissingResources();

        // Method getAllMissingResources can return not all missing resources
        // in case it took to much time. Log this information.
        if (!$all_missing_resources['all']) {
          $this->logger->warning('Not all missing context resources are received. Context might look incomplete.');
        }

        $fresh_resources = [];

        foreach ($all_missing_resources['items'] as $item) {
          if (!in_array($item['resourceId'], $cached_data)) {
            $fresh_resources[] = $item;
          }
        }

        // Walk through missing resources and try to upload them.
        foreach ($fresh_resources as $item) {
          if ((time() - strtotime($item['created'])) >= $two_days) {
            $update_cache = TRUE;
            $cached_data[] = $item['resourceId'];

            continue;
          }

          // If current resource isn't in the cache and it's accessible then
          // it means we can try to upload it.
          if (!in_array($item['resourceId'], $cached_data) && $this->assetInliner->remote_file_exists($item['url'], $proj_settings)) {
            $smartling_context_resource_file = $smartling_context_resources_directory . '/' . $item['resourceId'];
            $smartling_context_resource_file_content = $this->assetInliner->getUrlContents($item['url'],
              0,
              'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_8; en-US) AppleWebKit/534.10 (KHTML, like Gecko) Chrome/8.0.552.215 Safari/534.10',
              $proj_settings
            );

            // Ensure that resources directory is accessible, resource
            // downloaded properly and only then upload it. ContextAPI will not
            // be able to fopen() resource which is behind basic auth. So
            // download it first (with a help of curl), save it to smartling's
            // directory and then upload.
            if ($file = file_save_data($smartling_context_resource_file_content, $smartling_context_resource_file, FILE_EXISTS_REPLACE)) {
              $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager')->getViaUri($file->getFileUri());
              $params = new UploadResourceParameters();
              $params->setFile($stream_wrapper_manager->realpath());
              $is_resource_uploaded = $api->uploadResource($item['resourceId'], $params);

              // Resource isn't uploaded for some reason. Log this info and set
              // resource id into the cache. We will not try to upload this
              // resource for the next hour.
              if (!$is_resource_uploaded) {
                $update_cache = TRUE;
                $cached_data[] = $item['resourceId'];

                $this->logger->warning("Can't upload context resource file with id = @id and url = @url. Context might look incomplete.", [
                  '@id' => $item['resourceId'],
                  '@url' => $item['url'],
                ]);
              }
            }
            // We can't save context resource file. Log this info.
            else {
              $this->logger->error("Can't save context resource file: @path", [
                '@path' => $smartling_context_resource_file,
              ]);
            }
          }
          else {
            // Current resource isn't accessible (or already in the cache).
            // If first case then add inaccessible resource into the cache.
            if (!in_array($item['resourceId'], $cached_data)) {
              $update_cache = TRUE;
              $cached_data[] = $item['resourceId'];
            }
          }
        }

        // Set failed resources into the cache for the next hour.
        if ($update_cache) {
          \Drupal::cache()->set($cache_name, $cached_data, time() + $time_to_live);
        }
      } while (!empty($fresh_resources));
    }
    catch (Exception $e) {
      watchdog_exception('tmgmt_smartling', $e);
    }
  }

  /**
   * @param $filename
   * @return bool
   */
  public function isReadyAcceptContext($filename, $proj_settings) {
    try {
      $api = $this->getApiWrapper($proj_settings)->getApi('file');
      $res = $api->getStatusAllLocales($filename);

      if (!$res) {
        $this->logger->warning('File "@filename" is not ready to accept context. Most likely it is being processed by Smartling right now.',
          ['@filename' => $filename]);
      }

      return $res;
    }
    catch (Exception $e) {
      $this->logger->warning($e->getMessage());
      return FALSE;
    }
  }
}
