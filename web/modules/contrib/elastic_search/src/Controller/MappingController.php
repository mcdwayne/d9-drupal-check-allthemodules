<?php

namespace Drupal\elastic_search\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\ResettableStackedRouteMatchInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\elastic_search\Entity\FieldableEntityMap;
use Drupal\elastic_search\Entity\FieldableEntityMapInterface;
use Drupal\elastic_search\Mapping\ElasticMappingDslGenerator;
use Drupal\elastic_search\Plugin\ElasticEnabledEntityInterface;
use Drupal\elastic_search\Plugin\ElasticEnabledEntityManager;
use Drupal\elastic_search\ValueObject\FEMParameters;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class MappingController.
 *
 * @package Drupal\search_api_elastic\Controller
 */
class MappingController extends ControllerBase {

  /**
   * @var ElasticEnabledEntityManager
   */
  protected $elasticPluginManager;

  /**
   * @var  ResettableStackedRouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var  ElasticMappingDslGenerator
   */
  protected $dslGenerator;

  /**
   * MappingController constructor.
   *
   * Although some of these types appear in the base class they get required
   * via accessor functions even though the variables are not protected, and
   * could be uninitialized, we choose to require them explicitly to make our
   * dependencies clear and so we can use the variables directly without the
   * function call overhead
   *
   * @param \Drupal\elastic_search\Plugin\ElasticEnabledEntityManager $elasticPluginManager
   * @param \Drupal\Core\Routing\ResettableStackedRouteMatchInterface $routeMatch
   * @param \Drupal\Core\Config\ConfigFactoryInterface                $configFactory
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface            $entityFormBuilder
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface            $entityTypeManager
   * @param \Drupal\Core\Entity\EntityManager                         $entityManager
   * @param \Drupal\elastic_search\Mapping\ElasticMappingDslGenerator $dslGenerator
   *
   * @internal param \Drupal\elastic_search\Mapping\Cartographer $cartographer
   */
  public function __construct(ElasticEnabledEntityManager $elasticPluginManager,
                              ResettableStackedRouteMatchInterface $routeMatch,
                              ConfigFactoryInterface $configFactory,
                              EntityFormBuilderInterface $entityFormBuilder,
                              EntityTypeManagerInterface $entityTypeManager,
                              EntityManager $entityManager,
                              ElasticMappingDslGenerator $dslGenerator) {
    $this->elasticPluginManager = $elasticPluginManager;
    $this->routeMatch = $routeMatch;
    $this->configFactory = $configFactory;
    $this->entityFormBuilder = $entityFormBuilder;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityManager = $entityManager;
    $this->dslGenerator = $dslGenerator;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.elastic_enabled_entity_plugin'),
                      $container->get('current_route_match'),
                      $container->get('config.factory'),
                      $container->get('entity.form_builder'),
                      $container->get('entity_type.manager'),
                      $container->get('entity.manager'),
                      $container->get('elastic_search.mapping.dsl_generator'));
  }

  /**
   * RController for properly dealing with adding a new fieldable entity map.
   *
   * Required as we need to restrict maps to entity bundle types and not allow
   * free creation. This also deals with redirection if the map for this entity
   * exists. This stops us being able to create any dupes of maps
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *    A RouteMatch object.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function entityLoad(RouteMatchInterface $route_match) {
    $entity = $this->getEntityFromRouteMatch($route_match);
    $bundle_type = $entity->id();
    $entity_type = $entity->bundle();

    //If you attach an elastic index to a node bundle the entity type is node_type, but actually you need the child when you look for bundles
    //This means you need a whole plugin system around this to allow something else to alter some values so that they can actually match the proper type
    //It would be nice to find a lower overhead way to manage this
    if ($this->elasticPluginManager->hasDefinition($entity_type)) {
      /** @var ElasticEnabledEntityInterface $entityAlter */
      $entityAlter = $this->elasticPluginManager->createInstance($entity_type);
    } else {
      $entityAlter = $this->elasticPluginManager->createInstance('generic');
    }

    $parent_type = $entityAlter->getChildType($entity_type, $bundle_type);

    $has = $this->entityTypeManager->hasDefinition($parent_type);
    $bundles = $this->entityManager->getBundleInfo($parent_type); // TODO - deprecated

    if (!$has || !array_key_exists($bundle_type, $bundles)) {
      throw new NotFoundHttpException();
    }

    $mapName = FieldableEntityMap::getMachineName($parent_type, $bundle_type);

    $config = $this->configFactory->get('elastic_search.fieldable_entity_map.' .
                                        $mapName);
    if ($config->isNew()) {
      /** @var FieldableEntityMapInterface $entity */
      $mapEntity = $this->entityTypeManager->getStorage('fieldable_entity_map')
                                           ->create();
      $mapEntity->setId(FieldableEntityMap::getMachineName($parent_type, $bundle_type));
      return $this->entityFormBuilder->getForm($mapEntity, 'add');
    }

    $mapEntity = $this->entityTypeManager->getStorage('fieldable_entity_map')->load($config->get('id'));
    return $this->entityFormBuilder->getForm($mapEntity, 'edit');

  }

  /**
   * Retrieves entity from route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object as determined from the passed-in route match.
   */
  protected function getEntityFromRouteMatch(RouteMatchInterface $route_match) {
    $parameter_name = $route_match->getRouteObject()->getOption('_elastic_entity_type_id');
    return $route_match->getParameter($parameter_name);
  }

  /**
   * View a mapping for an entity type.
   *
   * This route is normally accessed via the add/edit form page
   *
   * @param \Drupal\elastic_search\Entity\FieldableEntityMap $mapping
   *
   * @return array
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Drupal\Core\DependencyInjection\ContainerNotInitializedException
   */
  public function view(FieldableEntityMap $mapping) {
    return $this->buildMaps([$mapping->getId()]);
  }

  /**
   * DryRun.
   *
   * @param array $maps
   *
   * @return array Return a page of dry Run details
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  public function buildMaps(array $maps = []) {

    try {
      list($maps, $errors) = $this->getMaps($maps);
    } catch (\Throwable $t) {
      $errors = [];
    }

    $output['mappings'] = [
      '#type'  => 'details',
      '#title' => $this->t('Mapping DSL'),
      '#open'  => TRUE,
    ];

    $output['mappings'][] = $this->renderMapping($maps);

    $output[] = $this->renderErrors([
                                      $this->t('fieldable entity map'),
                                      $this->t('error'),
                                    ],
                                    $errors);
    if (!empty($errors)) {
      drupal_set_message('Errors were reported', 'error');
    } else {
      drupal_set_message('Map was built successfully', 'status');
    }

    return $output;
  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function deleteMaps() {
    $maps = $this->entityTypeManager->getStorage('fieldable_entity_map')->loadMultiple();
    $this->entityTypeManager->getStorage('fieldable_entity_map')->delete($maps);
    drupal_set_message('All Fielable Entity Maps were deleted. This has not deleted your indices or anything on the server');
    return $this->redirect('entity.fieldable_entity_map.collection');
  }

  /**
   * @param array $maps
   *
   * @return array
   * @throws \Drupal\elastic_search\Exception\MapNotFoundException
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  private function getMaps(array $maps = []) {
    return [
      $this->dslGenerator->generate($maps),
      $this->dslGenerator->getErrorsAsStrings(),
    ];
  }

  /**
   * @param array $indices
   *
   * @return array
   */
  private function renderMapping(array $indices): array {
    $rendered = [];

    foreach ($indices as $id => $index) {

      $data = $index['mappings'];
      $id = key($data);

      $output = [
        '#type'  => 'details',
        '#title' => $this->t($id),
      ];

      $dump = '<textarea data-editor="json" data-editor-theme="monokai">' .
              json_encode($index, JSON_PRETTY_PRINT) . '</textarea>';
      $render['#markup'] = Markup::create($dump);
      $render['#attached']['library'][] = 'elastic_search/ace_json_readonly';
      $output[] = $render;

      $rendered[] = $output;
    }
    return $rendered;
  }

  /**
   * @param array $header
   * @param array $rawRows
   *
   * @return array
   */
  private function renderErrors(array $header, array $rawRows): array {
    $build['title'] = [
      '#markup' => '<h2 class="mapping-errors">Errors</h2>',
    ];
    $build['errors'] = [
      '#type'   => 'table',
      '#header' => $header,
      '#title'  => 'title',
      '#empty'  => $this->t('There are no errors'),
    ];

    foreach ($rawRows as $id => $row) {
      $output = [];
      $output['fieldable_entity_map'] = [
        '#plain_text' => $id,
      ];
      $output['error'] = [
        '#plain_text' => $row,
      ];
      $build['errors'][] = $output;
    }

    return $build;
  }

}
