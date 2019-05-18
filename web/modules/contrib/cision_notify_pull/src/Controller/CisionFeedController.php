<?php

namespace Drupal\cision_notify_pull\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\cision_notify_pull\CisionManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class CisionFeedController.
 *
 * @package Drupal\cision_notify_pull\Controller
 */
class CisionFeedController extends ControllerBase {

  /**
   * The contentconnected manager.
   *
   * @var \Drupal\cision_notify_pull\CisionManagerInterface
   */
  protected $cisionManager;

  /**
   * Forum settings config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(CisionManagerInterface $cision_manager, ConfigFactoryInterface $config_factory, LoggerInterface $logger) {
    $this->cisionManager = $cision_manager;
    $this->configFactory = $config_factory;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cision_notify_pull.cision_manager'),
      $container->get('config.factory'),
      $container->get('logger.factory')->get('cision_notify_pull')
    );
  }

  /**
   * HandleCisionPull.
   */
  public function handleCisionPull() {

    $config = $this->configFactory->get('cision_notify_pull.settings');
    $debug = $config->get('debug');

    $data = file_get_contents('php://input');

    if ($debug) {
      $this->logger->notice('Cision  @data', [
        '@data' => $data,
      ]);
    }

    if (!empty($data)) {
      $cision_xml = simplexml_load_string($data);
      $xml_mothod = $cision_xml->xpath('//methodCall/methodName');
      $methodName = $xml_mothod[0]->__toString();
      $xml_elements = $cision_xml->xpath('//params/param');
      if ($methodName == 'pushrelease.ping') {
        $this->cisionManager->processCisionFeeds($xml_elements);
        if ($debug) {
          $this->logger->notice('Pull Cision feed is called from @data',
            [
              '@data' => $data,
            ]);
        }
      }
      elseif ($methodName == "pushreleasedeleted.ping") {
        $this->cisionManager->deleteCisionFeeds($xml_elements);
        if ($debug) {
          $this->logger->notice('Delete Cision feed is called from data:@data', [
            '@data' => $data,
          ]);
        }
      }

      return [
        '#markup' => 'Data found',
      ];
    }
    else {
      return [
        '#markup' => 'No data found',
      ];
    }
  }

}
