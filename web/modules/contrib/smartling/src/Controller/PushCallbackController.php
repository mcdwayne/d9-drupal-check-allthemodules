<?php
/**
 * @file
 * Contains \Drupal\smartling\Controller\PushCallbackController.
 */

namespace Drupal\smartling\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PushCallbackController.
 */
class PushCallbackController extends ControllerBase {

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new PushCallbackController instance.
   *
   * @param \Psr\Log\LoggerInterface
   *   The log handler.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.channel.smartling')
    );
  }

  /**
   * Access callback to validate cron key.
   *
   * @param string $cron_key
   *   The cron key from URL.
   *
   * @return bool
   *   Does user has access to the route or not.
   */
  public function access($cron_key) {
    return $cron_key == \Drupal::state()->get('system.cron_key') ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Queues task to download translation.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array Render array.
   *   Render array.
   */
  public function push(Request $request) {
    $file_uri = $request->get('fileUri');
    $locale = $request->get('locale');
    if (empty($file_uri) || empty($locale)) {
      return new Response('No data', 404);
    }

    // @todo Move to common method somewhere.
    $locale_mappings = $this->config('smartling.settings')
      ->get('account_info.language_mappings');
    foreach ($locale_mappings as $langcode => $s_locale) {
      if ($s_locale == $locale) {
        $d_locale = $langcode;
        break;
      }
    }
    if (!isset($d_locale)) {
      $this->logger->warning('Locale mapping not found: locale=@locale fileUri=@fileUri', [
        '@locale' => $locale,
        '@fileUri' => $file_uri,
      ]);
      return new Response('No locale mapping', 404);
    }

    $storage = $this->entityTypeManager()->getStorage('smartling_submission');
    $submissions = $storage->loadByProperties([
      'file_name' => $file_uri,
      'target_language' => $d_locale,
    ]);
    /** @var \Drupal\smartling\SmartlingSubmissionInterface $submission */
    $submission = reset($submissions);
    if (!$submission) {
      $this->logger->warning('Submission not found: locale=@locale fileUri=@fileUri', [
        '@locale' => $d_locale,
        '@fileUri' => $file_uri,
      ]);
      return new Response('No submission', 404);
    }

    /** @var \Drupal\smartling\SmartlingEntityHandler $handler */
    $handler = $this->entityTypeManager()
      ->getHandler($submission->get('entity_type')->value, 'smartling');
    if ($handler->downloadTranslation($submission)) {
      $this->logger->info('Push callback download success for %title (@locale  @fileUri)', [
        '%title' => $submission->label(),
        '@locale' => $d_locale,
        '@fileUri' => $file_uri,
      ]);
      return new Response('Download succeeded');
    }

    $this->logger->error('Push callback download error for %title (@locale  @fileUri)', [
      '%title' => $submission->label(),
      '@locale' => $d_locale,
      '@fileUri' => $file_uri,
    ]);
    // HTTP response Unprocessable Entity.
    return new Response('Download failed', 422);
  }
}
