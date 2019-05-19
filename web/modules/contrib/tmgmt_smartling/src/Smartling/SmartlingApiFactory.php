<?php

/**
 * @file
 * SmartlingApiFactory.php.
 */

namespace Drupal\tmgmt_smartling\Smartling;

use Exception;
use Smartling\AuditLog\AuditLogApi;
use Smartling\BaseApiAbstract;
use Smartling\AuthApi\AuthTokenProvider;
use Smartling\Batch\BatchApi;
use Smartling\Context\ContextApi;
use Smartling\Exceptions\SmartlingApiException;
use Smartling\File\FileApi;
use Smartling\Jobs\JobsApi;
use Smartling\ProgressTracker\ProgressTrackerApi;
use Smartling\Project\ProjectApi;
use Smartling\TranslationRequests\TranslationRequestsApi;

/**
 * Class SmartlingApiFactory
 * @package Drupal\tmgmt_smartling\Smartling
 */
class SmartlingApiFactory {

  /**
   * Returns API object as a service.
   *
   * @param array $settings
   * @param string $api_type
   *
   * @return \Smartling\BaseApiAbstract
   * @throws \Exception
   * @throws \Smartling\Exceptions\SmartlingApiException
   */
  public static function create(array $settings, $api_type) {
    require_once __DIR__ . '/../../vendor/autoload.php';

    if (empty($settings['user_id']) || empty($settings['project_id']) || empty($settings['token_secret'])) {
      throw new SmartlingApiException('The "User Id", "Token Secret", or "Project Id" are not correct.');
    }

    ConnectorInfo::setUpCurrentClientInfo();

    $auth_provider = AuthTokenProvider::create($settings['user_id'], $settings['token_secret']);
    $logger = \Drupal::logger('smartling_api');
    $api = NULL;

    switch ($api_type) {
      case 'file':
        $api = FileApi::create($auth_provider, $settings['project_id'], $logger);

        break;

      case 'project':
        $api = ProjectApi::create($auth_provider, $settings['project_id'], $logger);

        break;

      case 'jobs':
        $api = JobsApi::create($auth_provider, $settings['project_id'], $logger);

        break;

      case 'batch':
        $api = BatchApi::create($auth_provider, $settings['project_id'], $logger);

        break;

      case 'context':
        $api = ContextApi::create($auth_provider, $settings['project_id'], $logger);

        break;

      case 'progress':
        $api = ProgressTrackerApi::create($auth_provider, $settings['project_id'], $logger);

        break;

      case 'translation_request':
        $api = TranslationRequestsApi::create($auth_provider, $settings['project_id'], $logger);

        break;

      case 'audit':
        $api = AuditLogApi::create($auth_provider, $settings['project_id'], $logger);

        break;

      default:
        throw new Exception('Unsupported API has been requested: ' . $api_type);
    }

    return $api;
  }

}
