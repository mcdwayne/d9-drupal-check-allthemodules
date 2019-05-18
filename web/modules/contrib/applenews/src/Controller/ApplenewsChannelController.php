<?php

namespace Drupal\applenews\Controller;

use Drupal\applenews\Entity\ApplenewsChannel;
use Drupal\Core\Controller\ControllerBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ApplenewsChannelController.
 *
 * @package Drupal\applenews\Controller
 */
class ApplenewsChannelController extends ControllerBase {

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * ApplenewsPreviewController constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger object.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.channel.applenews')
    );
  }

  /**
   * Refresh channel details from Apple News.
   *
   * @param \Drupal\applenews\Entity\ApplenewsChannel $applenews_channel
   *   Apple News entity.
   *
   * @return \Drupal\Core\Routing\LocalRedirectResponse
   *   Redirect response.
   */
  public function refresh(ApplenewsChannel $applenews_channel) {
    try {
      $applenews_channel->updateMetaData();
      $this->messenger()->addStatus($this->t('Refreshed the %label channel details.', ['%label' => $applenews_channel->label()]));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error while trying to refreshed the %label channel details.', ['%label' => $applenews_channel->label()]));
    }

    return new RedirectResponse($applenews_channel->urlInfo('collection')->toString());
  }

}
