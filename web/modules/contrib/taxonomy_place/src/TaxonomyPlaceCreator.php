<?php

namespace Drupal\taxonomy_place;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;

/**
 * Class TaxonomyPlaceCreator.
 *
 * @package Drupal\taxonomy_place
 */
class TaxonomyPlaceCreator {

  /**
   * @var Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var $query \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The country repository.
   *
   * @var \CommerceGuys\Addressing\Country\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * The subdivision repository.
   *
   * @var \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface
   */
  protected $subdivisionRepository;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, QueryFactory $query_factory, CountryRepositoryInterface $country_repository, SubdivisionRepositoryInterface $subdivision_repository) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->queryFactory = $query_factory;
    $this->countryRepository = $country_repository;
    $this->subdivisionRepository = $subdivision_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity.query'),
      $container->get('address.country_repository'),
      $container->get('address.subdivision_repository')
    );
  }

  public function getSettings($value) {
    return $this->configFactory->get('taxonomy_place.settings')->get($value);
  }

  public function getStorage() {
    return $this->entityTypeManager->getStorage('taxonomy_term');
  }

  public function getQuery() {
    return $this->queryFactory->get('taxonomy_term');
  }

  /**
   * Create a term for a place.
   *
   * Terms are created only if they don't already exist. Terms are created
   * for each part of the place (country, province, locality), with provinces
   * nested under the country, and localities nested under the province.
   *
   * @param $entity
   *   The entity that contains a field with place information.
   * @param $field_name
   *   The address field on the entity that contains the place information.
   * @param $delta
   *   The delta of the field value to use for this term.
   */
  public function createPlaceTerm($entity, $field_name, $delta = 0) {

    // If the field doesn't exist on this entity, there is nothing to do.
    if (!$entity->get($field_name)) {
      return FALSE;
    }
    // If no vid or address_field have been set, there is nothing to do.
    $vid = $this->getSettings('vid');
    $address_field = $this->getSettings('address_field');

    if (!$vid || !$address_field) {
      return FALSE;
    }

    $place_term = NULL;
    if ($place = $entity->get($field_name)->getValue()) {

      $country_code = $place[$delta]['country_code'];
      $province_code = $place[$delta]['administrative_area'];
      $locality = $place[$delta]['locality'];

      // Create the country term, if it doesn't exist.
      $place = [$country_code, NULL, NULL];
      $country_term = $this->createTermFromValues($place);
      $place_term = $country_term;

      if (!empty($province_code) && !empty($country_term)) {
        // Create the province term, if it doesn't exist.
        $place = [$country_code, $province_code, NULL];
        $province_term = $this->createTermFromValues($place);
        $place_term = $province_term;

        if (!empty($locality) && !empty($province_term)) {
          // Create the locality term, if it doesn't exist.
          $place = [$country_code, $province_code, $locality];
          $locality_term = $this->createTermFromValues($place);
          $place_term = $locality_term;
        }
      }
    }
    return $place_term;
  }

  /**
   * Get the term that matches country, province, and locality.
   *
   * @param array $place
   *   [$country_code. $province_code, $locality]
   */
  public function getPlaceTerm(array $place) {

    $vid = $this->getSettings('vid');
    $address_field = $this->getSettings('address_field');

    // If no vid or address_field have been set, there is nothing to do.
    if (!$vid || !$address_field) {
      return FALSE;
    }

    list($country_code, $province_code, $locality) = $place;

    $query = $this->getQuery();
    $query->condition('vid', $vid);
    $query->condition($address_field . '.country_code', $country_code);

    // If this value is empty we have to check for either empty OR NULL.
    if (empty($province_code)) {
      $or = $query->orConditionGroup();
      $or->condition($address_field . '.administrative_area', NULL, 'IS NULL');
      $or->condition($address_field . '.administrative_area', '');
      $query->condition($or);
    }
    else {
      $query->condition($address_field . '.administrative_area', $province_code);
    }

    // If this value is empty we have to check for either empty OR NULL.
    if (empty($locality)) {
      $or = $query->orConditionGroup();
      $or->condition($address_field . '.locality', NULL, 'IS NULL');
      $or->condition($address_field . '.locality', '');
      $query->condition($or);
    }
    else {
      $query->condition($address_field . '.locality', $locality);
    }

    // Only needed for debugging the query produced.
    //$query->addTag('debug');

    $tids = $query->execute();

    if (count($tids) && $tid = array_shift($tids)) {
      $term = $this->getStorage()->load($tid);
      return $term;
    }
    return FALSE;
  }

  /**
   * Create a term from an array of place information.
   *
   * @param array $place
   *   [$country_code. $province_code, $locality]
   *
   * @return object $term
   */
  protected function createTermFromValues(array $place, $format = 'basic_html') {

    // If the term already exists, nothing more to do.
    if ($term = $this->getPlaceTerm($place)) {
      return $term;
    }

    list($country_code, $province_code, $locality) = $place;
    $country_name = $this->getCountryName($country_code);
    $province_name = $this->getProvinceName($country_code, $province_code);
    $vid = $this->getSettings('vid');
    $address_field = $this->getSettings('address_field');

    $values = [];
    $values['vid'] = $vid;
    $values['name'] = $this->getPlaceName($place);

    if ($description_field = $this->getSettings('description_field')) {
      $values[$description_field]['value'] = $this->getDescription($place);
      $values[$description_field]['format'] = $format;
    }
    if ($short_name_field = $this->getSettings('short_name_field')) {
      $values[$short_name_field]['value'] = $this->getShortName($place);
    }
    if ($sortable_name_field = $this->getSettings('sortable_name_field')) {
      $values[$sortable_name_field]['value'] = $this->getSortableName($place);
    }

    if (empty($province_name) && empty($locality)) {
      $values['parent'] = [];
    }
    elseif (empty($locality)) {
      if ($country_term = $this->getPlaceTerm([$country_code, NULL, NULL])) {
        $values['parent'] = [$country_term->id()];
      }
    }
    else {
      if ($province_term = $this->getPlaceTerm([$country_code, $province_code, NULL])) {
        $values['parent'] = [$province_term->id()];
      }
    }

    $values[$address_field] = [
      'country_code' => $country_code,
      'administrative_area' => $province_code,
      'locality' => $locality,
    ];
    $values['weight'] = 0;
    return $this->createTerm($values);

  }

  /**
   * Create a term from an array of field values.
   */
  public function createTerm($values) {
    $defaults = [
      'vid' => '',
      'name' => '',
    ];
    $values += $defaults;

    // The submitted values must at least have these fields.
    if (empty($values['name']) || empty($values['vid'])) {
      return FALSE;
    }

    $query = $this->getQuery();
    $query->condition('vid', $values['vid']);
    $query->condition('name', $values['name']);
    if ($tids = $query->execute()) {
      $tid = array_shift($tids);
      $term = $this->getStorage()->load($tid);
    }
    else {
      $term = $this->getStorage()->create($values);
      $term->save();
    }
    return $term;
  }

  public function getCountries() {
    return $this->countryRepository->getList();
  }

  public function getCountryName($country_code) {
    $countries = $this->getCountries();
    if (array_key_exists($country_code, $countries)) {
      return $countries[$country_code];
    }
    return '';
  }

  public function getProvinces($country_code) {
    $parents = [$country_code];
    return $this->subdivisionRepository->getList($parents);
  }

  public function getProvinceName($country_code, $province_code) {
    $provinces = $this->getProvinces($country_code);
    if (array_key_exists($province_code, $provinces)) {
      return $provinces[$province_code];
    }
    return '';
  }

  /**
   * Helper function to create a place name.
   *
   * @param array $place
   *   [$country_code. $province_code, $locality]
   * @return string
   *   The name of the place.
   */
  public function getPlaceName(array $place) {
    list($country_code, $province_code, $locality) = $place;
    $country_name = $this->getCountryName($country_code);
    $province_name = $this->getProvinceName($country_code, $province_code);

    if (empty($province_name) && empty($locality)) {
      $name = $country_name;
    }
    elseif (empty($locality)) {
      $name = $province_name;
      if ($country_name != 'United States') {
        $name .= ', ' . $country_name;
      }
    }
    else {
      $name = $locality . ', ' . $province_name;
      if ($country_name != 'United States') {
        $name .= ', ' . $country_name;
      }
    }
    return $name;
  }

  /**
   * Helper function to create a short place name.
   *
   * @param array $place
   *   [$country_code. $province_code, $locality]
   * @return string
   *   The short name of the place.
   */
  public function getShortName($place) {
    list($country_code, $province_code, $locality) = $place;
    $country_name = $this->getCountryName($country_code);
    $province_name = $this->getProvinceName($country_code, $province_code);

    if (empty($province_name) && empty($locality)) {
      $name = $country_name;
    }
    elseif (empty($locality)) {
      $name = $province_name;
    }
    else {
      $name = $locality;
    }
    return $name;
  }

  /**
   * Helper function to create a sortable place name.
   *
   * @param array $place
   *   [$country_code. $province_code, $locality]
   * @return string
   *   The sortable name of the place.
   */
  public function getSortableName($place) {
    list($country_code, $province_code, $locality) = $place;
    $country_name = $this->getCountryName($country_code);
    $province_name = $this->getProvinceName($country_code, $province_code);

    if (empty($province_name) && empty($locality)) {
      $name = $country_name;
    }
    elseif (empty($locality)) {
      $name = $country_name . '/ ' . $province_name;
    }
    else {
      $name = $country_name . '/ ' . $province_name . '/ ' . $locality;
    }
    return $name;
  }

  /**
   * Helper function to create a place description.
   *
   * @param array $place
   *   [$country_code. $province_code, $locality]
   * @return string
   *   The description of the place.
   */
  public function getDescription($place) {

    $description = $this->getPlaceName($place);

    // If the Wikipedia Client module exists, add text from Wikipedia
    // about each place.
    if (\Drupal::moduleHandler()->moduleExists('wikipedia_client')) {
      $wiki_client = \Drupal::service('wikipedia_client.client');
      if ($wiki_data = $wiki_client->getResponse($description)) {
        $description = $wiki_client->getMarkup($wiki_data);
      }
    }

    return $description;
  }

}
