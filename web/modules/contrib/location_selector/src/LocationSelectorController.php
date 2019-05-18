<?php

namespace Drupal\location_selector;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Quick Edit module routes.
 */
class LocationSelectorController extends ControllerBase {

  /**
   * The GeoNames service.
   *
   * @var \Drupal\location_selector\GeoNamesService
   */
  protected $geonamesService;

  /**
   * Constructs a new QuickEditController.
   *
   * @param \Drupal\location_selector\GeoNamesService $geonames_service
   *   The GeoNames service.
   */
  public function __construct(GeoNamesService $geonames_service) {
    $this->geonamesService = $geonames_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('location_selector.geonames')
    );
  }

  /**
   * Returns the children or infos from the requestet id's.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function getGeoNames(Request $request) {
    $ids = $request->request->get('selected');
    // Error handling.
    if (!isset($ids)) {
      throw new NotFoundHttpException();
    }
    $infos = NULL;
    // Because this method is also calling from ajax request.
    // @see /modules/custom/location_selector/src/LocationSelectorController.php
    $geonames_service = \Drupal::service('location_selector.geonames');
    $result_array = $geonames_service->getGeoNamesAndIds($ids);
    if (!empty($result_array)) {
      $infos = $result_array;
    }
    return new JsonResponse($infos);
  }

  /**
   * Returns the values.
   *
   * This function is used for later validation of the
   * form widget. This should prevent editing of the
   * textarea.
   *
   * @see \Drupal\location_selector\Plugin\Field\FieldWidget::validateElement
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function validateGeoNames(Request $request) {
    $result = $request->request->get('validate');
    // Error handling.
    if (!isset($result)) {
      throw new NotFoundHttpException();
    }
    // Save the value to the session.
    $session = \Drupal::request()->getSession();
    $session->start();
    $session->set($result['ids']['id'], $result['values']);
    $session->set($result['ids']['id_ajax'], 1);
    return new JsonResponse($result);
  }

}
