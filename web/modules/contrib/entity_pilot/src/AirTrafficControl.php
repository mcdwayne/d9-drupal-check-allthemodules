<?php

namespace Drupal\entity_pilot;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\entity_pilot\Utility\FlightStubInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Defines a class for handling flight arrivals and departures.
 */
class AirTrafficControl implements AirTrafficControlInterface {

  use StringTranslationTrait;
  use SiteUrlTrait;
  use UrlGeneratorTrait;

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The flight storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $departureStorage;

  /**
   * The arrival storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $arrivalStorage;

  /**
   * The baggage handler (entity dependency manager) service.
   *
   * @var BaggageHandlerInterface
   */
  protected $baggageHandler;

  /**
   * The Entity Pilot transport service.
   *
   * @var \Drupal\entity_pilot\TransportInterface
   */
  protected $transport;

  /**
   * Customs service.
   *
   * @var \Drupal\entity_pilot\CustomsInterface
   */
  protected $customs;

  /**
   * Constructs a new AirTrafficControl object.
   *
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param BaggageHandlerInterface $baggage_handler
   *   The baggage handler (dependency) service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator service.
   * @param \Drupal\entity_pilot\TransportInterface $transport
   *   The entity pilot transport service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   * @param \Drupal\entity_pilot\CustomsInterface $customs
   *   The customs service.
   */
  public function __construct(Serializer $serializer,
  EntityManagerInterface $entity_manager,
                              BaggageHandlerInterface $baggage_handler,
  UrlGeneratorInterface $url_generator,
                              TransportInterface $transport,
  TranslationInterface $translation,
  CustomsInterface $customs) {
    $this->serializer = $serializer;
    $this->departureStorage = $entity_manager->getStorage('ep_departure');
    $this->arrivalStorage = $entity_manager->getStorage('ep_arrival');
    $this->baggageHandler = $baggage_handler;
    $this->urlGenerator = $url_generator;
    $this->transport = $transport;
    $this->stringTranslation = $translation;
    $this->customs = $customs;
  }

  /**
   * {@inheritdoc}
   */
  public function takeoff(FlightStubInterface $stub) {
    /* @var \Drupal\entity_pilot\DepartureInterface $departure */
    $departure = $stub->setStorage($this->departureStorage)->getEntity();

    // Bundle the dependencies.
    $passenger_entities = $departure->getPassengers();
    foreach ($passenger_entities as $passenger) {
      $passenger_entities += $this->baggageHandler->calculateDependencies($passenger);
    }
    $passengers = [];
    foreach ($passenger_entities as $uuid => $passenger) {
      $passengers[$uuid] = $this->serializer->normalize($passenger, 'hal_json');
    }
    $manifest = $departure->createManifest($passengers)
      ->setFieldMapping($this->baggageHandler->generateFieldMap($passenger_entities))
      ->setSite($this->getSite());
    // We don't try/catch here because we want exceptions to bubble up to
    // Cron::processQueues.
    // @todo Consider adding an EntityPilotUnavailableException and catching
    //   that and then throwing SuspendQueueException to prevent repeated
    //   attempts when EntityPilot is down.
    if ($remote_id = $this->transport->sendFlight($manifest, $departure->getAccount()->getSecret())) {
      $departure->setRevisionLog($this->t('Flight sent to EntityPilot, remote ID: @remote_id', [
        '@remote_id' => $remote_id,
      ]))
        ->setRemoteId($remote_id)
        ->setNewRevision()
        ->setStatus(FlightInterface::STATUS_LANDED)
        ->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function land(FlightStubInterface $stub) {
    /* @var \Drupal\entity_pilot\ArrivalInterface $arrival */
    $arrival = $stub->setStorage($this->arrivalStorage)->getEntity();
    $this->customs->screen($arrival, FALSE);
    $this->customs->approve($arrival);
    $arrival->setStatus(FlightInterface::STATUS_LANDED)->save();
    $this->customs->clearCache($arrival);
  }

}
