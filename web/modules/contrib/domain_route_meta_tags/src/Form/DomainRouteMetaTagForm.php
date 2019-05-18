<?php

namespace Drupal\domain_route_meta_tags\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Language\Language;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the domain_route_meta_tags entity edit forms.
 *
 * @ingroup domain_route_meta_tags
 */
class DomainRouteMetaTagForm extends ContentEntityForm {

  // Defining cache constant.
  const META_CACHE_DURATION = 14400;
  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;
  /**
   * The domain negotiator.
   *
   * @var negotiator
   */
  protected $negotiator;
  /**
   * The current path.
   *
   * @var currentPath
   */
  private $currentPath;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * {@inheritdoc}
   *
   *   The database connection.
   */
  public function __construct($pathValidator, $entityManager, $negotiator, $currentPath, $time) {
    $this->pathvalidator = $pathValidator;
    $this->entityManager = $entityManager;
    $this->negotiator = $negotiator;
    $this->currentPath = $currentPath;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static(
        $container->get('path.validator'),
        $container->get('entity.manager'),
        $container->get('domain.negotiator'),
        $container->get('path.current'),
        $container->has('datetime.time') ? $container->get('datetime.time') : NULL
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $activeDomain = $this->negotiator->getActiveDomain();
    if ($activeDomain === NULL) {
      return $this->redirect('domain.admin');
    }
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;
    $this->cache = \Drupal::cache();

    $form['langcode'] = [
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $link = $form_state->getValue('route_link')[0]['value'];
    $canonical = $form_state->getValue('canonical')[0]['value'];
    $og_url = $form_state->getValue('og_url')[0]['value'];
    $domain = $form_state->getValue('domain')[0]['value'];
    // Validation for route.
    if (!empty($link)) {
      $error = $this->validateRoute($link, 'route_link', $domain);
      $error === TRUE ? TRUE : $form_state->setErrorByName('route_link', $error['route_link']);
    }
    // Validation for canonical url.
    if (!empty($canonical)) {
      $error = $this->validateRoute($canonical, 'canonical', $domain);
      $error === TRUE ? TRUE : $form_state->setErrorByName('canonical', $error['canonical']);
    }
    // Validation for og url.
    if (!empty($og_url)) {
      $error = $this->validateRoute($og_url, 'og_url', $domain);
      $error === TRUE ? TRUE : $form_state->setErrorByName('og_url', $error['og_url']);
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Set redirection.
    $form_state->setRedirect('entity.domain_route_meta_tags.collection');
    $entity = $this->getEntity();
    // Save the entity.
    $entity->save();
    // Load domain Value.
    $domain = $form_state->getValue('domain')[0]['value'];
    // If cachable is checked, cache the data.
    $isCachable = $form_state->getValue('is_cachable')['value'];
    if ($isCachable !== NULL) {
      $cacheKey = str_replace("/", "_", $domain . $form_state->getValue('route_link')[0]['value']);
      // Get entity Data.
      $entityData = $entity->getEntityData();
      // Set cache max time.
      $expires = time() + static::META_CACHE_DURATION;
      // Set cache.
      $this->cache->set($cacheKey, $entityData, $expires, ['domain_route_meta_tags']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateRoute($route, $field, $domain = NULL) {
    $fields = [
      'route_link' => 'Route Path',
      'canonical' => 'Canonical Url',
      'og_url' => 'OG Url',
    ];
    // Check if route starts with / .
    if (substr($route, 0, 1) !== "/") {
      return [$field => 'The Route for ' . $fields[$field] . ' should start with /'];
    }
    // Validate path.
    $result = $this->pathvalidator->getUrlIfValid($route);
    if ($result === FALSE) {
      return [$field => 'The Route for ' . $fields[$field] . ' you have entered doesn\'t exist.'];
    }
    // Get route information for current path.
    $path = $this->pathvalidator->getUrlIfValid($this->currentPath->getPath())->getRouteName();
    // Check if current route is edit form.
    // Else check if route already exists.
    if ($path == 'entity.domain_route_meta_tags.edit_form') {
      // Get current path meta id.
      $id = $this->pathvalidator->getUrlIfValid($this->currentPath->getPath())->getRouteParameters()['domain_route_meta_tags'];
      // Load meta entity.
      $currentRouteCheck = $this->routeSingleCheck($route, $field, $domain, $id);
      // Return if no error.
      if ($currentRouteCheck === TRUE) {
        return $currentRouteCheck;
      }
      // Return the result for error checking.
      return $this->errorCheck($route, $field, $domain);
    }
    else {
      // Return the result for error checking.
      return $this->errorCheck($route, $field, $domain);
    }
    // Return TRUE if everything is fine.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function routeCheck($route, $field, $domain = NULL) {
    $fields = [
      'route_link' => 'Route Path',
      'canonical' => 'Canonical Url',
      'og_url' => 'OG Url',
    ];
    // Load all entities for domain_route_meta_tags.
    $entities = $this->entityManager->getStorage('domain_route_meta_tags')->loadMultiple();
    // Loope through each entity.
    foreach ($entities as $value) {
      if (($route == $value->get($field)->value) && ($value->get('domain')->value == $domain)) {
        return [$field => 'The Route for ' . $fields[$field] . ' you have entered already exist.'];
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function routeSingleCheck($route, $field, $domain = NULL, $id = NULL) {
    // Load meta entity.
    $current_entity = $this->entityManager->getStorage('domain_route_meta_tags')->load($id);
    if (($route == $current_entity->get($field)->value) && ($current_entity->get('domain')->value == $domain)) {
      // Return true if nothing is changed.
      return TRUE;
    }
    // Return false if error.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function errorCheck($route, $field, $domain = NULL) {
    // Return the result.
    return $this->routeCheck($route, $field, $domain);
  }

}
