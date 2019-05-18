<?php

namespace Drupal\projects_stats\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\projects_stats\ProjectsStatsSlackService;

/**
 * Slack message Controller.
 */
class SlackMessageController extends ControllerBase {

  protected $slackService;

  /**
   * {@inheritdoc}
   */
  public function __construct(ProjectsStatsSlackService $slack_service) {
    $this->slackService = $slack_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('projects_stats.slack_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function send($token) {
    $config = $this->config('projects_stats.settings');
    $external_cron_url = $config->get('external_cron_url');
    $saved_token = substr($external_cron_url, strrpos($external_cron_url, '/') + 1);
    if ($saved_token == $token) {
      $this->slackService->sendMessage();
      return new Response($this->t('Slack message sent!'));
    }
    else {
      throw new AccessDeniedHttpException();
    }
  }

}
