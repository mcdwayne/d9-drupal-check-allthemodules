<?php

namespace Drupal\aws_cloud\EventSubscriber;

use Drupal\aws_cloud\Service\AwsEc2ServiceInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber class for aws cloud module.
 */
class AwsCloudSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  private $messenger;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private $routeMatch;

  /**
   * The ec2 service object.
   *
   * @var \Drupal\aws_cloud\Service\AwsEc2ServiceInterface
   */
  private $awsEc2Service;

  /**
   * The cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private $cache;

  /**
   * Constructs a new AwsEc2Service object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   An entity type manager instance.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\aws_cloud\Service\AwsEc2ServiceInterface $aws_ec2_service
   *   The aws ec2 service object.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache bin.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              MessengerInterface $messenger,
                              TranslationInterface $string_translation,
                              RouteMatchInterface $route_match,
                              AwsEc2ServiceInterface $aws_ec2_service,
                              CacheBackendInterface $cache) {

    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;

    // Setup the $this->t().
    $this->stringTranslation = $string_translation;

    $this->routeMatch = $route_match;

    $this->awsEc2Service = $aws_ec2_service;
    $this->cache = $cache;
  }

  /**
   * Display a warning message about EC2-Classic support on edit pages.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The response event.
   */
  public function displayEc2ClassicMessage(FilterResponseEvent $event) {
    $route_names = [
      'entity.cloud_server_template.launch',
      'entity.cloud_server_template.add_form',
      'entity.cloud_server_template.edit_form',
      'entity.aws_cloud_instance.edit_form',
      'entity.aws_cloud_image.add_form',
      'entity.aws_cloud_image.edit_form',
      'entity.aws_cloud_network_interface.add_form',
      'entity.aws_cloud_network_interface.edit_form',
      'entity.aws_cloud_elastic_ip.add_form',
      'entity.aws_cloud_elastic_ip.edit_form',
      'entity.aws_cloud_security_group.add_form',
      'entity.aws_cloud_security_group.edit_form',
      'entity.aws_cloud_key_pair.add_form',
      'entity.aws_cloud_key_pair.edit_form',
      'entity.aws_cloud_volume.add_form',
      'entity.aws_cloud_volume.edit_form',
      'entity.aws_cloud_snapshot.add_form',
      'entity.aws_cloud_snapshot.edit_form',
    ];

    // Only care about HTML responses.
    if (stripos($event->getResponse()->headers->get('Content-Type'), 'text/html') !== FALSE) {
      if (in_array($this->routeMatch->getRouteName(), $route_names)) {
        $cloud_context = $this->routeMatch->getParameter('cloud_context');
        $platforms = $this->getSupportedPlatforms($cloud_context);
        if (count($platforms) == 2) {
          // EC2 and VPC platforms supported, throw up a message.
          $this->messenger->addWarning($this->t('Your AWS account supports EC2-Classic. Please note aws_cloud module does not support EC2-Classic.'));
        }
      }
    }
  }

  /**
   * Redirect to add form if entity is empty.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The response event.
   */
  public function redirectIfEmpty(FilterResponseEvent $event) {
    $route_names = [
      'entity.cloud_server_template.add_form',
    ];
    if (in_array($this->routeMatch->getRouteName(), $route_names)) {
      // Return if not a master request.
      if (!$event->isMasterRequest()) {
        return;
      }

      // Return if not 200.
      $response = $event->getResponse();
      if ($response->getStatusCode() != 200) {
        return;
      }

      /* @var \Drupal\cloud\Entity\CloudServerTemplateTypeInterface $cloud_server_template_type */
      $cloud_server_template_type = $this->routeMatch->getParameter('cloud_server_template_type');
      if ($cloud_server_template_type == NULL || $cloud_server_template_type->id() != 'aws_cloud') {
        return;
      }

      $cloud_context = $this->routeMatch->getParameter('cloud_context');
      if ($cloud_context == NULL) {
        return;
      }

      // Check whether key pair entity exists.
      $ids = $this->entityTypeManager
        ->getStorage('aws_cloud_key_pair')
        ->getQuery()
        ->condition('cloud_context', $cloud_context)
        ->execute();

      if (empty($ids)) {
        $this->messenger->addMessage(
          $this->t('There is no aws cloud key pair. Please create a new one.')
        );
        $response = new RedirectResponse(
          Url::fromRoute(
            'entity.aws_cloud_key_pair.add_form',
            ['cloud_context' => $cloud_context]
          )->toString()
        );
        $event->setResponse($response);
        return;
      }

      // Check whether security group entity exists.
      $ids = $this->entityTypeManager
        ->getStorage('aws_cloud_security_group')
        ->getQuery()
        ->condition('cloud_context', $cloud_context)
        ->execute();

      if (empty($ids)) {
        $this->messenger->addMessage(
          $this->t('There is no aws cloud security group. Please create a new one.')
        );
        $response = new RedirectResponse(
          Url::fromRoute(
            'entity.aws_cloud_security_group.add_form',
            ['cloud_context' => $cloud_context]
          )->toString()
        );
        $event->setResponse($response);
      }
    }
  }

  /**
   * Helper function to retrieve the supported platforms.
   *
   * @param string $cloud_context
   *   The cloud context to use for the API call.
   *
   * @return array
   *   Array of platforms.
   */
  private function getSupportedPlatforms($cloud_context) {
    $cache = $this->cache->get('ec2.supported_platforms');
    if ($cache) {
      $platforms = $cache->data;
    }
    else {
      $this->awsEc2Service->setCloudContext($cloud_context);
      $platforms = $this->awsEc2Service->getSupportedPlatforms();
      $this->cache->set('ec2.supported_platforms', $platforms);
    }
    return $platforms;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['redirectIfEmpty'];
    $events[KernelEvents::RESPONSE][] = ['displayEc2ClassicMessage'];
    return $events;
  }

}
