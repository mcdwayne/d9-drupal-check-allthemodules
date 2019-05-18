<?php

namespace Drupal\ads_system\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormAjaxResponseBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class AdsRenderController.
 *
 * @package Drupal\ads_system\Controller
 */
class AdsRenderController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityManager definition.
   *
   * @var Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Drupal\Core\Entity\EntityManager definition.
   *
   * @var Drupal\Core\Entity\EntityManager
   */
  protected $viewBuilder;


  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var Drupal\Core\Entity\Query\QueryFactorygetViewBuilderad
   */
  protected $entityQuery;

  /**
   * Drupal\Core\Form\FormAjaxResponseBuilder definition.
   *
   * @var Drupal\Core\Form\FormAjaxResponseBuilder
   */
  protected $formAjaxResponseBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityManager $entityManager, QueryFactory $entityQuery, FormAjaxResponseBuilder $formAjaxResponseBuilder) {
    $this->entity_manager = $entityManager;
    $this->viewBuilder = $entityManager->getViewBuilder('ad');
    $this->entity_query = $entityQuery;
    $this->form_ajax_response_builder = $formAjaxResponseBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity.query'),
      $container->get('form_ajax_response_builder')
    );
  }

  /**
   * Getads.
   *
   * @return string
   *   Return Hello string.
   */
  public function getAds(Request $request) {
    $ad_types = $request->request->get('adTypes');
    if (!isset($ad_types)) {
      throw new BadRequestHttpException($this->t('No Ad Types specified.'));
    }

    // Get Ad config.
    $config = \Drupal::config('ads_system.settings');

    $ad_sizes = explode("\r\n", $config->get('ad_sizes'));
    $ad_breakpoints = explode("\r\n", $config->get('ad_breakpoints'));

    $rendered = [];

    foreach ($ad_types as $type) {
      $bundle = explode("-", $type);
      $rendered[$type] = $this->entity_query->get('ad')
        ->condition("type", $bundle[1])
        ->execute();

      foreach ($rendered[$type] as $adId) {
        $ad = $this->entity_manager->getStorage('ad')->load($adId);

        $sizeInfo = explode("|", $ad_sizes[$ad->get('size')->value]);
        $breakpointsMinInfo = explode("|", $ad_breakpoints[$ad->get('breakpoint_min')->value]);
        $breakpointsMaxInfo = explode("|", $ad_breakpoints[$ad->get('breakpoint_max')->value]);

        $rendered[$type][$adId] = [
          "name" => $ad->get('name')->value,
          "size" => [
            'name' => $sizeInfo[0],
            'w' => $sizeInfo[1],
            'h' => $sizeInfo[2],
          ],
          "breakpoint_min" => [
            'name' => $breakpointsMinInfo[0],
            'size' => $breakpointsMinInfo[1],
          ],
          "breakpoint_max" => [
            'name' => $breakpointsMaxInfo[0],
            'size' => $breakpointsMaxInfo[1],
          ],
          "render" => render($this->viewBuilder->view($ad)),
        ];
      }

    }

    return new JsonResponse($rendered);
  }

}
