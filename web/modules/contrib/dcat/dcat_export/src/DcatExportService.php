<?php

namespace Drupal\dcat_export;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\dcat\Exception\MissingConfigurationException;
use Drupal\dcat_export\Event\AddResourceEvent;
use Drupal\dcat_export\Event\SerializeGraphEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use EasyRdf_Graph;
use EasyRdf_Resource;
use EasyRdf_Format;
use InvalidArgumentException;

/**
 * Class DcatExportService.
 *
 * @package Drupal\dcat_export
 */
class DcatExportService {

  /**
   * Config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Event dispatcher object.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * EasyRdf graph object.
   *
   * @var \EasyRdf_Graph
   */
  protected $graph;

  /**
   * DcatExportService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface
   *   Entity type manager service.
   *
   * @throws \Drupal\dcat\Exception\MissingConfigurationException
   *   When the module is not configured properly.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher) {
    $this->config = $config_factory->get('dcat_export.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->graph = new EasyRdf_Graph();

    $this->checkConfiguration();

    // Set namespaces according to the DCAT-AP standard.
    \EasyRdf_Namespace::set('adms', 'http://www.w3.org/ns/adms#');
    \EasyRdf_Namespace::set('dct', 'http://purl.org/dc/terms/');
    \EasyRdf_Namespace::delete('dcterms');
    \EasyRdf_Namespace::delete('dc');
  }

  /**
   * Export DCAT entities as serialised data.
   *
   * @param string $format
   *   The output format.
   *
   * @return string
   *   The exported dcat string.
   *
   * @throws \EasyRdf_Exception
   *   Thrown if EasyRdf fails in exporting data.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function export($format) {
    // Add Catalog information.
    $catalog = $this->addCatalogResource($this->graph);

    // Add datasets and their related resources.
    foreach ($this->addResources($this->graph, $this->loadDatasetEntities()) as $dataset_resource) {
      $this->addResourceSilently($catalog, 'dcat:dataset', $dataset_resource);
    }

    $format = $this->sanitizeFormat($format);
    $rdf_format = EasyRdf_Format::getFormat($format);

    // Allow other modules to alter the resource being added to the graph.
    $event = new SerializeGraphEvent($this->graph);
    $this->eventDispatcher->dispatch('dcat_export.graph.serialize', $event);

    return $this->graph->serialise($rdf_format);
  }

  /**
   * Add a value to a resource, only when the value is not empty.
   *
   * @param \EasyRdf_Resource $resource
   *   The resource to add the value to.
   * @param string $property
   *   The property name.
   * @param mixed $values
   *   Value as string or array.
   * @param string $lang
   *   The language code.
   *
   * @return int
   *   The number of values added.
   */
  public function addLiteral(EasyRdf_Resource $resource, $property, $values, $lang = NULL) {
    if ($values) {
      return $resource->addLiteral($property, $values, $lang);
    }

    return 0;
  }

  /**
   * Add a resource to another resource without throwing errors when empty.
   *
   * @param \EasyRdf_Resource $resource1
   *   The resource to add another resource to.
   * @param string $property
   *   The property name.
   * @param string|\EasyRdf_Resource $resource2
   *   The resource to be the value of the property.
   *
   * @return int
   *   The number of values added (1 or 0).
   */
  public function addResourceSilently(EasyRdf_Resource $resource1, $property, $resource2) {
    if ($resource2) {
      return $resource1->addResource($property, $resource2);
    }

    return 0;
  }

  /**
   * Add resources to the graph and return them as objects.
   *
   * @param \EasyRdf_Graph $graph
   *   The RDF graph.
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *   The entities of the same type to transform to RDF resources.
   * @param string|null $type
   *   Set type of resource. If not set, the type will be based on entity type.
   *
   * @return \EasyRdf_Resource[]
   *
   * @throws \InvalidArgumentException
   *   When a resource type has not supporting method.
   */
  protected function addResources(EasyRdf_Graph $graph, array $entities, $type = NULL) {
    $resources = [];

    if (!$entities) {
      return $resources;
    }

    $type = $type ?: reset($entities)->getEntityTypeId();
    $method = 'add' . ucfirst(str_replace('dcat_', '', $type)) . 'Resource';

    if (!method_exists($this, $method)) {
      throw new InvalidArgumentException('The resource type has no supporting add method.');
    }

    foreach ($entities as $entity) {
      $resource = $this->{$method}($graph, $entity);

      // Allow other modules to alter the resource.
      $event = new AddResourceEvent($resource, $entity);
      $this->eventDispatcher->dispatch('dcat_export.resource.add', $event);

      $resources[] = $resource;
    }

    return $resources;
  }

  /**
   * Add catalog information to the RDF graph.
   *
   * @param \EasyRdf_Graph $graph
   *   The graph object.
   *
   * @return \EasyRdf_Resource
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  protected function addCatalogResource(EasyRdf_Graph $graph) {
    /** @var \EasyRdf_Resource $resource */
    $resource = $graph->resource($this->config->get('catalog_uri'), ['dcat:Catalog']);
    $this->addLiteral($resource, 'dct:title', $this->config->get('catalog_title'));
    $this->addLiteral($resource, 'dct:description', $this->config->get('catalog_description'));
    $this->addLiteral($resource, 'dct:issued', new \DateTime((string) $this->config->get('catalog_issued')));
    $this->addLiteral($resource, 'dct:modified', new \DateTime($this->lastModified()));
    $this->addResourceSilently($resource, 'foaf:homepage', $this->createCustomResource(
      $graph,
      'foaf:Document',
      $this->config->get('catalog_homepage_uri')
    ));
    $this->addResourceSilently($resource, 'dct:language', $this->createCustomResource(
      $graph,
      'dct:LinguisticSystem',
      $this->config->get('catalog_language_uri')
    ));
    $this->addResourceSilently($resource, 'dct:license', $this->createCustomResource(
      $graph,
      'dct:LicenseDocument',
      $this->config->get('catalog_license_uri')
    ));
    $this->addResourceSilently($resource, 'dct:publisher', $this->createCustomResource(
      $graph,
      'foaf:Agent',
      $this->config->get('catalog_publisher_uri'),
      ['literals' => ['foaf:name' => $this->config->get('catalog_publisher_name')]]
    ));

    return $resource;
  }

  /**
   * Add dataset information to the RDF graph.
   *
   * @param \EasyRdf_Graph $graph
   *   The graph object.
   * @param \Drupal\Core\Entity\ContentEntityInterface $dataset
   *   The dataset entity.
   *
   * @return \EasyRdf_Resource
   *   The created RDF resource.
   *
   * @throws \InvalidArgumentException
   *   When a resource type is not supported.
   */
  protected function addDatasetResource(EasyRdf_Graph $graph, ContentEntityInterface $dataset) {
    /** @var \EasyRdf_Resource $resource */
    $resource = $graph->resource($this->getDatasetUrl($dataset), ['dcat:Dataset']);
    $this->addLiteral($resource, 'dct:title', $dataset->label());
    $this->addLiteral($resource, 'dct:description', $dataset->get('description')->getString());
    $this->addLiteral($resource, 'dct:identifier', $dataset->uuid());
    $this->addResourceSilently($resource,'dct:accrualPeriodicity', $dataset->get('accrual_periodicity')->getString());
    $this->addLiteral($resource, 'dct:issued', new \DateTime($dataset->get('issued')->getString()));
    $this->addLiteral($resource, 'dct:modified', new \DateTime($dataset->get('modified')->getString()));
    $this->addResourceSilently($resource, 'dcat:landingPage', $this->getDatasetUrl($dataset));

    foreach ($this->addResources($this->graph, $dataset->get('contact_point')->referencedEntities()) as $vcard_resource) {
      $this->addResourceSilently($resource, 'dcat:contactPoint', $vcard_resource);
    }
    foreach ($this->addResources($this->graph, $dataset->get('publisher')->referencedEntities()) as $agent_resource) {
      $this->addResourceSilently($resource, 'dcat:publisher', $agent_resource);
    }
    foreach ($dataset->get('keyword')->referencedEntities() as $keyword) {
      $this->addLiteral($resource, 'dcat:keyword', $keyword->label());
    }
    foreach ($this->addResources($graph, $dataset->get('theme')->referencedEntities(), 'theme') as $theme_resource) {
      $this->addResourceSilently($resource, 'dcat:theme', $theme_resource);
    }
    foreach ($this->addResources($graph, $dataset->get('distribution')->referencedEntities()) as $distribution_resource) {
      $this->addResourceSilently($resource, 'dcat:distribution', $distribution_resource);
    }

    return $resource;
  }

  /**
   * Add vcard information to the RDF graph.
   *
   * @param \EasyRdf_Graph $graph
   *   The graph object.
   * @param \Drupal\Core\Entity\ContentEntityInterface $vcard
   *   The vcard entity.
   *
   * @return \EasyRdf_Resource
   */
  protected function addVcardResource(EasyRdf_Graph $graph, ContentEntityInterface $vcard) {
    /** @var \EasyRdf_Resource $resource */
    $resource = $graph->resource($vcard->get('external_id')->getString(), ['vcard:Kind']);
    $this->addLiteral($resource, 'vcard:hasFN', $vcard->label());

    switch ($vcard->bundle()) {
      case 'individual':
        $this->addLiteral($resource, 'vcard:hasNickname', $vcard->get('nickname')->getString());

      case 'organization':
        $email = $vcard->get('email')->getString();
        $telephone = $vcard->get('telephone')->getString();
        $this->addResourceSilently($resource, 'vcard:hasEmail', $email ? 'mailto:' . $email : '');
        $this->addResourceSilently($resource, 'vcard:hasTelephone', $telephone ? 'tel:' . $telephone : '');
        $this->addResourceSilently($resource, 'vcard:hasURL', $vcard->get('external_id')->getString());
        break;

      case 'location':
        $this->addLiteral($resource, 'vcard:hasStreetAddress', $vcard->get('street_address')->getString());
        $this->addLiteral($resource, 'vcard:hasPostalCode', $vcard->get('postal_code')->getString());
        $this->addLiteral($resource, 'vcard:hasLocality', $vcard->get('locality')->getString());
        $this->addLiteral($resource, 'vcard:hasRegion', $vcard->get('region')->getString());
        $this->addLiteral($resource, 'vcard:hasCountryName', $vcard->get('county')->getString());
        break;
    }

    return $resource;
  }

  /**
   * Add agent information to the RDF graph.
   *
   * @param \EasyRdf_Graph $graph
   *   The graph object.
   * @param \Drupal\Core\Entity\ContentEntityInterface $agent
   *   The agent entity.
   *
   * @return \EasyRdf_Resource
   */
  protected function addAgentResource(EasyRdf_Graph $graph, ContentEntityInterface $agent) {
    /** @var \EasyRdf_Resource $resource */
    $resource = $graph->resource($agent->get('external_id')->getString(), ['foaf:Agent']);
    $this->addLiteral($resource, 'foaf:name', $agent->label());

    return $resource;
  }

  /**
   * Add distribution information to the RDF graph.
   *
   * @param \EasyRdf_Graph $graph
   *   The graph object.
   * @param \Drupal\Core\Entity\ContentEntityInterface $distribution
   *   The distribution entity.
   *
   * @return \EasyRdf_Resource
   */
  protected function addDistributionResource(EasyRdf_Graph $graph, ContentEntityInterface $distribution) {
    /** @var \EasyRdf_Resource $resource */
    $resource = $graph->resource($distribution->get('external_id')->getString(), ['dcat:Distribution']);
    $this->addResourceSilently($resource, 'dcat:accessURL', $distribution->get('access_url')->getString());
    $this->addResourceSilently($resource, 'dcat:downloadURL', $distribution->get('download_url')->getString());
    $this->addLiteral($resource,'dct:title', $distribution->label());
    $this->addLiteral($resource, 'dct:description', $distribution->get('description')->getString());
    $this->addLiteral($resource, 'dcat:mediaType', $distribution->get('media_type')->getString());
    $this->addLiteral($resource, 'dct:issued', new \DateTime($distribution->get('issued')->getString()));
    $this->addLiteral($resource, 'dct:license', $distribution->get('license')->getString());
    $this->addLiteral($resource, 'dct:format', $distribution->get('format')->getString());
    $this->addLiteral($resource, 'dcat:byteSize', $distribution->get('byte_size')->getString());
    $this->addResourceSilently($resource, 'adms:status', $distribution->get('dcat_status')->getString());
    $this->addLiteral($resource, 'dcat:rights', $this->createCustomResource(
      $graph,
      'dct:RightsStatement',
      $distribution->get('rights')->getString()
    ));

    return $resource;
  }

  /**
   * Add theme information to the RDF graph.
   *
   * @param \EasyRdf_Graph $graph
   *   The graph object.
   * @param \Drupal\Core\Entity\ContentEntityInterface $theme
   *   The theme entity.
   *
   * @return \EasyRdf_Resource
   */
  protected function addThemeResource(EasyRdf_Graph $graph, ContentEntityInterface $theme) {
    /** @var \EasyRdf_Resource $resource */
    $resource = $graph->resource($theme->get('external_id')->getString(), ['dcat:Theme']);
    $this->addLiteral($resource, 'dct:label', $theme->label());

    return $resource;
  }

  /**
   * Add a custom resource to the RDF graph.
   *
   * @param \EasyRdf_Graph $graph
   *   The graph object.
   * @param string $type
   *   The type of the resource.
   * @param string $uri
   *   The URI of the resource.
   * @param array $properties
   *   An array containing optional literals and resources with property and
   *   value as key => value respectively.
   *   E.g.:
   *   [
   *     'literals' => [
   *       'rdf:value' => 'foo',
   *       ...
   *     ],
   *     'resources' => [
   *       'dct:LicenseDocument' => 'https://foo.bar',
   *       ...
   *     ],
   *   ]
   *
   * @return \EasyRdf_Resource|false
   *   The RDF resource object or false if not able to create it.
   */
  protected function createCustomResource(EasyRdf_Graph $graph, $type, $uri, array $properties = []) {
    try {
      /** @var \EasyRdf_Resource $resource */
      $resource = $graph->resource($uri, [$type]);

      // Merge in defaults.
      $properties += [
        'literals' => [],
        'resources' => [],
      ];

      foreach ($properties['literals'] as $property => $value) {
        $this->addLiteral($resource, $property, $value);
      }

      foreach ($properties['resources'] as $property => $value) {
        $this->addResourceSilently($resource, $property, $value);
      }

      return $resource;
    }
    catch (InvalidArgumentException $ex) {
      return FALSE;
    }
  }

  /**
   * Get the node page of a dataset.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $dataset
   *   The dataset entity.
   *
   * @return string
   *   The node page full url.
   */
  protected function getDatasetUrl(ContentEntityInterface $dataset) {
    $url = Url::fromUri('internal:/dataset/' . $dataset->id(), ['absolute' => TRUE])->toString();
    return $url;
  }

  /**
   * Get the landing page of a dataset.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $dataset
   *   The dataset entity.
   *
   * @return string
   *   The landing page full url.
   */
  protected function getLandingPage(ContentEntityInterface $dataset) {
    $url = $dataset->get('landing_page')->getString();

    if (!$url) {
      $url = Url::fromRoute('view.dataset_landingpage.page', ['arg_0' => $dataset->id()], ['absolute' => TRUE])->toString();
    }

    return $url;
  }

  /**
   * Get the date of the latest modified dataset in ISO 8601 format.
   *
   * @return string|false
   *   The modified date of the latest changed dataset or false when no dataset
   *   found.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  protected function lastModified() {
    $storage = $this->entityTypeManager->getStorage('dcat_dataset');
    $ids = $storage
      ->getQuery()
      ->condition('status', 1)
      ->sort('changed', 'DESC')
      ->range(0, 1)
      ->execute();

    if ($ids) {
      $id = reset($ids);
      $entity = $storage->load($id);

      return date('c', $entity->get('changed'));
    }

    return FALSE;
  }

  /**
   * Load active DCAT dataset entities.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Active DCAT dataset entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  protected function loadDatasetEntities() {
    $storage = $this->entityTypeManager->getStorage('dcat_dataset');
    $query = $storage
      ->getQuery()
      ->condition('status', 1)
      ->sort('source', 'ASC');

    if ($sources = array_filter($this->config->get('sources'))) {
      $query->condition('source', $sources, 'IN');
    }

    $ids = $query->execute();

    return $storage->loadMultiple($ids);
  }

  /**
   * Set the format in right form suited for the EasyRdf library.
   *
   * @param string $format
   *   The output format.
   *
   * @return string
   *   The sanitized format.
   */
  protected function sanitizeFormat($format) {
    switch ($format) {
      case 'rdf':
        $format = 'rdfxml';
        break;

      case 'ttl':
        $format = 'turtle';
        break;

      case 'nt':
        $format = 'ntriples';
        break;
    }

    return $format;
  }

  /**
   * Check if the required export settings are all set.
   *
   * @throws \Drupal\dcat\Exception\MissingConfigurationException
   *   When configuration is missing.
   */
  protected function checkConfiguration() {
    if (empty($this->config->get('catalog_title')) ||
      empty($this->config->get('catalog_description')) ||
      empty($this->config->get('catalog_uri')) ||
      empty($this->config->get('catalog_language_uri')) ||
      empty($this->config->get('catalog_homepage_uri')) ||
      empty($this->config->get('catalog_issued')) ||
      empty($this->config->get('catalog_publisher_uri')) ||
      empty($this->config->get('catalog_publisher_name')) ||
      empty($this->config->get('catalog_license_uri'))
    ) {
      throw new MissingConfigurationException('Required configuration is missing for the dcat_export module.');
    }
  }

}
