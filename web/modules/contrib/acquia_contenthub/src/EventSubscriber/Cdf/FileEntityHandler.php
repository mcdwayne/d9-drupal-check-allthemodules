<?php

namespace Drupal\acquia_contenthub\EventSubscriber\Cdf;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\CreateCdfEntityEvent;
use Drupal\acquia_contenthub\Event\ParseCdfEntityEvent;
use Drupal\acquia_contenthub\Plugin\FileSchemeHandler\FileSchemeHandlerManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Manipulates file content entity CDF representation to better support files.
 */
class FileEntityHandler implements EventSubscriberInterface {

  /**
   * The file scheme handler manager.
   *
   * @var \Drupal\acquia_contenthub\Plugin\FileSchemeHandler\FileSchemeHandlerManagerInterface
   */
  protected $manager;

  /**
   * FileEntityHandler constructor.
   *
   * @param \Drupal\acquia_contenthub\Plugin\FileSchemeHandler\FileSchemeHandlerManagerInterface $manager
   *   File scheme handler manager.
   */
  public function __construct(FileSchemeHandlerManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::CREATE_CDF_OBJECT][] = ['onCreateCdf', 90];
    $events[AcquiaContentHubEvents::PARSE_CDF][] = ['onParseCdf', 110];
    return $events;
  }

  /**
   * Add attributes to file entity CDF representations.
   *
   * @param \Drupal\acquia_contenthub\Event\CreateCdfEntityEvent $event
   *   The create CDF entity event.
   */
  public function onCreateCdf(CreateCdfEntityEvent $event) {
    if ($event->getEntity()->getEntityTypeId() == 'file') {
      /** @var \Drupal\file\FileInterface $entity */
      $entity = $event->getEntity();
      $handler = $this->manager->getHandlerForFile($entity);
      $handler->addAttributes($event->getCdf($entity->uuid()), $entity);
    }
  }

  /**
   * Parse CDF attributes to import files as necessary.
   *
   * @param \Drupal\acquia_contenthub\Event\ParseCdfEntityEvent $event
   *   The Parse CDF Entity Event.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function onParseCdf(ParseCdfEntityEvent $event) {
    $cdf = $event->getCdf();
    if ($cdf->getAttribute('file_scheme')) {
      $scheme = $cdf->getAttribute('file_scheme')->getValue()['und'];
      $handler = $this->manager->createInstance($scheme);
      $handler->getFile($cdf);
    }
  }

}
