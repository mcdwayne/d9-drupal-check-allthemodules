<?php

namespace Drupal\micro_path;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\micro_node\MicroNodeManagerInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\micro_path\MicroPathautoGeneratorInterface;

class MicroPathHelper {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $accountManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * A domain negotiator for looking up the current domain.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The site alias uniquifier.
   *
   * @var \Drupal\micro_path\SiteAliasUniquifierInterface
   */
  protected $siteAliasUniquifier;

  /**
   * The micro pathauto generator.
   *
   * @var \Drupal\micro_path\MicroPathautoGeneratorInterface
   */
  protected $microPathautoGenerator;

  /**
   * MicroPathHelper constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $account_manager
   *   The account manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The alias manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\micro_site\SiteNegotiatorInterface $site_negotiator
   *   The site negotiator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\micro_path\SiteAliasUniquifierInterface $site_alias_uniquifier
   *   The site alias uniquifier.
   * @param \Drupal\micro_path\MicroPathautoGeneratorInterface $micro_pathauto_generator
   *   The micro pathauto generator.
   */
  public function __construct(AccountInterface $account_manager, EntityTypeManagerInterface $entity_type_manager, AliasManagerInterface $alias_manager, ConfigFactoryInterface $config_factory, SiteNegotiatorInterface $site_negotiator, ModuleHandlerInterface $module_handler, SiteAliasUniquifierInterface $site_alias_uniquifier, MicroPathautoGeneratorInterface $micro_pathauto_generator) {
    $this->accountManager = $account_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->aliasManager = $alias_manager;
    $this->config = $config_factory->get('micro_path.settings');
    $this->negotiator = $site_negotiator;
    $this->moduleHandler = $module_handler;
    $this->siteAliasUniquifier = $site_alias_uniquifier;
    $this->microPathautoGenerator = $micro_pathauto_generator;
  }

  /**
   * The micro paths form element for the entity form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Referenced entity.
   *
   * @return array $form
   *   Return the modified form array.
   */
  public function alterEntityForm(&$form, FormStateInterface $form_state, $entity) {
    $sites = $this->entityTypeManager->getStorage('site')->loadMultiple();
    // Just exit if micro paths is not enabled for this entity.
    if (!$this->microPathsIsEnabled($entity) || !$sites) {
      return $form;
    }

    // Add our validation and submit handlers.
    $form['#validate'][] = [$this, 'validateEntityForm'];
    if (!empty($form['actions'])) {
      foreach (array_keys($form['actions']) as $action) {
        if (isset($form['actions'][$action]['#submit'])) {
          $form['actions'][$action]['#submit'][] = [$this, 'submitEntityForm'];
        }
      }
    }
    else {
      // If no actions we just tack it on to the form submit handlers.
      $form['#submit'][] = [$this, 'submitEntityForm'];
    }
  }

  /**
   * The micro paths form element for the widget.
   *
   * @param array $element
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param array $context
   *   The context array.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Referenced entity.
   *
   * @return array $element
   *   Return the modified element array.
   */
  public function alterWidgetElement(&$element, FormStateInterface $form_state, $context, $entity) {
    $site = NULL;
    if ($entity->isNew()) {
      $site = $this->negotiator->getActiveSite();
    }
    else {
      $site = $this->getMainSiteFromEntity($entity);
    }

    // Set up our variables.
    $entity_id = $entity->id();
    $langcode = $entity->language()->getId();
    $default = '';

    $path = FALSE;
    if ($entity_id && $site instanceof SiteInterface) {
      $properties = [
        'source' => '/' . $entity->toUrl()->getInternalPath(),
        'language' => $langcode,
        'site_id' => $site->id(),
      ];
      if ($micro_paths = $this->entityTypeManager->getStorage('micro_path')->loadByProperties($properties)) {
        /** @var \Drupal\micro_path\MicroPathInterface $micro_path */
        $micro_path = reset($micro_paths);
        $path = $micro_path->getAlias();
      }
    }

    if ($site instanceof SiteInterface) {
      $element['alias']['#access'] = $this->accountManager->hasPermission('edit main alias entity');
    }

    $delta = $element['#delta'];
    $element['site_alias'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site alias URL'),
      '#default_value' => $path ? $path : $default,
      '#access' => $this->accountManager->hasPermission('edit micro path entity'),
      '#weight' => 99,
      '#states' => [
        'disabled' => [
          'input[name="path['. $delta .'][pathauto]"]' => ['checked' => TRUE],
        ]
      ],
    ];
  }

  /**
   * Validation handler the site paths element on the entity form.
   *
   * This is called from a custom validation handler in micro_path.module. We
   * do that instead of just declaring this as static and using it directly as
   * a validation handler so that our dependencies are injected when the
   * service is loaded via \Drupal::service().
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public static function validateEntityForm(array &$form, FormStateInterface $form_state) {
    // Set up variables.
    $entity = $form_state->getFormObject()->getEntity();
    $micro_path_storage = \Drupal::service('entity_type.manager')->getStorage('micro_path');
    $path_values = $form_state->getValue('path');

    if (!empty($path_values[0]['pathauto'])) {
      // Skip validation if checked automatically generate alias.
      return;
    }

    $alias = isset($path_values[0]['alias']) ? $path_values[0]['alias'] : NULL;
    $site_alias = isset($path_values[0]['site_alias']) ? $path_values[0]['site_alias'] : NULL;

    // Check sites settings if they are on the form.
    $site_id = '';
    if (!empty($form['site_id'])) {
      $site_id_values = $form_state->getValue('site_id');
      if (isset($site_id_values[0]['target_id'])) {
        $site_id = $site_id_values[0]['target_id'];
      }
    }

    if (!empty($site_alias)) {
      // Trim slashes and whitespace from end of path value.
      $site_path_value = rtrim(trim($site_alias), " \\/");

      // Check that the paths start with a slash.
      if ($site_path_value && $site_path_value[0] !== '/') {
        $form_state->setError($form['path'], t('Site alias URL "%alias" needs to start with a slash.', ['%alias' => $site_alias]));
      }

      // Check for duplicates.
      if ($site_id) {
        $entity_query = $micro_path_storage->getQuery();
        $entity_query->condition('site_id', $site_id)
          ->condition('alias', $site_alias);
        if (!$entity->isNew()) {
          $entity_query->condition('source', '/' . $entity->toUrl()->getInternalPath(), '<>');
        }
        $result = $entity_query->execute();
        if ($result) {
          $form_state->setError($form['path'], t('Site alias URL %path matches an existing alias', ['%path' => $site_alias]));
        }
      }
    }
  }

  /**
   * Submit handler for the site paths element on the entity form.
   *
   * This is called from a custom submit handler in domain_path.module. We
   * do that instead of just declaring this as static and using it directly as
   * a submit handler so that our dependencies are injected when the
   * service is loaded via \Drupal::service().
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function submitEntityForm($form, FormStateInterface $form_state) {
    // Setup Variables
    $entity = $form_state->getFormObject()->getEntity();
    $entity_system_path = '/' . $entity->toUrl()->getInternalPath();
    $langcode = $entity->language()->getId();
    $sites_id = $this->getAllSiteId($form, $form_state);
    if (empty($sites_id)) {
      return;
    }
    $main_site_id = $this->getMainSiteId($form, $form_state);

    // Get the saved alias
    $default_alias = $this->aliasManager->getAliasByPath($entity_system_path);
    $properties = [
      'source' => $entity_system_path,
      'language' => $langcode,
    ];

    // If the node has no main site id (the node is published from the master
    // host to several sites) then get the first site as reference for generating
    // the site alias.
    $path_values = $form_state->getValue('path');
    $site_alias = isset($path_values[0]['site_alias']) ? $path_values[0]['site_alias'] : NULL;

    if (!$main_site_id) {
      $main_site_id = reset($sites_id);
    }

    if ($this->pathautoIsEnabled($form, $form_state)) {
      $site_alias = $this->microPathautoGenerator->createEntitySiteAlias($entity, $main_site_id);
    }

    // @TODO support taxonomy term children once available with micro_taxonomy
    // here or/and with updateEntitySiteAlias().

    $micro_path_storage = $this->entityTypeManager->getStorage('micro_path');
    foreach ($sites_id as $site_id) {
      // Get the existing micro path for this site if it exists.
      $properties['site_id'] = $site_id;
      $micro_paths = $micro_path_storage->loadByProperties($properties);
      $micro_path = $micro_paths ? reset($micro_paths) : NULL;

      // We don't want to save the alias if the site alias field is empty,
      if (!$site_alias) {
        // Delete the existing micro path.
        if ($micro_path instanceof MicroPathInterface) {
          $micro_path->delete();
        }
        continue;
      }

      // Ensure that the site alias is unique on each micro site.
      $this->siteAliasUniquifier->uniquify($site_alias, $entity_system_path, $site_id, $langcode);

      // Create or update the micro path.
      $properties_map = [
          'alias' => $site_alias,
          'site_id' => $site_id,
        ] + $properties;
      if (!$micro_path instanceof MicroPathInterface) {
        $micro_path = $micro_path_storage->create(['type' => 'micro_path']);
        foreach ($properties_map as $field => $value) {
          $micro_path->set($field, $value);
        }
        $micro_path->save();
      }
      else {
        if ($micro_path->getAlias() != $site_alias) {
          $micro_path->set('alias', $site_alias);
          $micro_path->save();
        }
      }
    }
  }

  /**
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getOtherSitesId($form, FormStateInterface $form_state) {
    $sites_id = [];
    $all_site = FALSE;
    if (!empty($form[MicroPathFields::FIELD_SITES_ALL])) {
      $site_all_values = $form_state->getValue(MicroPathFields::FIELD_SITES_ALL);
      if (isset($site_all_values['value']) && $site_all_values['value']) {
        $sites = $this->entityTypeManager->getStorage('site')->loadMultiple();
        $all_site = TRUE;
        foreach ($sites as $id => $site) {
          $sites_id[$id] = $id;
        }
      }
    }

    if (!empty($form[MicroPathFields::FIELD_SITES]) && !$all_site) {
      foreach ($form_state->getValue(MicroPathFields::FIELD_SITES) as $item) {
        if (is_array($item) && isset($item['target_id']) && !empty($item['target_id'])) {
          $sites_id[$item['target_id']] = $item['target_id'];
        }
      }
    }
    return $sites_id;
  }

  /**
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param bool $return_array
   * @return array|mixed
   */
  protected function getMainSiteId($form, FormStateInterface $form_state, $return_array = FALSE) {
    $sites_id = [];
    if (!empty($form['site_id'])) {
      $site_id_value = $form_state->getValue('site_id');
      if (!empty($site_id_value[0]['target_id'])) {
        $id = $site_id_value[0]['target_id'];
        $sites_id[$id] = $id;
      }
    }
    return $return_array ? $sites_id : reset($sites_id);
  }

  /**
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array|mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getAllSiteId($form, FormStateInterface $form_state) {
    $sites_id = $this->getMainSiteId($form, $form_state, TRUE);
    $sites_id = $sites_id + $this->getOtherSitesId($form, $form_state);
    return $sites_id;
  }

  /**
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return bool
   */
  protected function pathautoIsEnabled($form, FormStateInterface $form_state) {
    $path_values = $form_state->getValue('path');
    if (!empty($path_values[0]['pathauto'])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Helper function for deleting micro paths from an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  public function deleteEntityMicroPaths(EntityInterface $entity) {
    if ($this->microPathsIsEnabled($entity)) {
      $properties_map = [
        'source' => '/' . $entity->toUrl()->getInternalPath(),
        'language' => $entity->language()->getId(),
      ];
      $micro_paths = $this->entityTypeManager
        ->getStorage('micro_path')
        ->loadByProperties($properties_map);
      if ($micro_paths) {
        foreach ($micro_paths as $micro_path) {
          $micro_path->delete();
        }
      }
    }
  }

  /**
   * Helper function for retrieving configured entity types.
   *
   * @return array
   *   Returns array of configured entity types.
   */
  public function getConfiguredEntityTypes() {
    $enabled_entity_types = $this->config->get('entity_types');
    $enabled_entity_types = array_filter($enabled_entity_types);

    return array_keys($enabled_entity_types);
  }

  /**
   * Check if micro paths is enabled for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return boolean
   *   Return TRUE or FALSE.
   */
  public function microPathsIsEnabled(EntityInterface $entity) {
    return in_array($entity->getEntityTypeId(), $this->getConfiguredEntityTypes());
  }

  /**
   * {@inheritdoc}
   */
  public function getMainSiteFromEntity(EntityInterface $node) {
    $site = $node->get('site_id')->referencedEntities();
    if ($site) {
      $site = reset($site);
    }
    return $site;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $field_name
   * @return array
   */
  public function getSecondarySitesFromEntity(EntityInterface $entity, $field_name = MicroPathFields::FIELD_SITES) {
    $list = [];
    if (!$entity->hasField($field_name)) {
      return $list;
    }
    $values = $entity->get($field_name);
    if (!empty($values)) {
      foreach ($values as $item) {
        if ($target = $item->getValue()) {
          if ($site = $this->negotiator->loadById($target['target_id'])) {
            $list[$site->id()] = $site;
          }
        }
      }
    }
    return $list;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @return bool
   */
  public function isPublishedOnAllSites(EntityInterface $entity) {
    if (!$entity->hasField(MicroPathFields::FIELD_SITES_ALL)) {
      return FALSE;
    }
    $value = $entity->{MicroPathFields::FIELD_SITES_ALL}->value;
    return $value ? TRUE : FALSE;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $field_name
   * @return bool
   */
  public function onMultipleSites(EntityInterface $entity, $field_name = MicroPathFields::FIELD_SITES) {
    return !empty($this->getSecondarySitesFromEntity($entity, $field_name)) || $this->isPublishedOnAllSites($entity);
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $field_name
   * @return bool
   */
  public function hasMultipleCanonicalUrl(EntityInterface $entity, $field_name = MicroPathFields::FIELD_DISABLE_CANONICAL_URL) {
    if (!$entity->hasField($field_name)) {
      return FALSE;
    }
    $unique_canonical_url_disabled = ($entity->{$field_name}->value) ? TRUE : FALSE;
    return $this->onMultipleSites($entity) && $unique_canonical_url_disabled;
  }

  /**
   * Get the patterns stored on the micro site data and return an array as
   * follow: $patterns[entity_type][bundle][langcode] = pattern
   *
   * @param \Drupal\micro_site\Entity\SiteInterface $site
   *
   * @return array
   */
  public function getPatternsData(SiteInterface $site) {
    $patterns = [];
    $data = $site->getData('micro_path');
    if (empty($data)) {
      return $patterns;
    }

    foreach ($data as $entity_type => $value) {
      foreach ($value as $data_pattern) {
        $bundle = $data_pattern['bundle'];
        $langcodes = $data_pattern['langcode'] ?: ['all'];
        $pattern = $data_pattern['pattern'];
        // Build the patterns array.
        foreach ($langcodes as $langcode) {
          $patterns[$entity_type][$bundle][$langcode] = $pattern;
        }
      }
    }

    return $patterns;
  }

}
