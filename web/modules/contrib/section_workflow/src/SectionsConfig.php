<?php

/**
 * @file
 * Contains SectionsConfig.php.
 */

namespace Drupal\section_workflow;

use Drupal\system_test\Controller\PageCacheAcceptHeaderController;
use Drupal\taxonomy\Entity\Term;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Cache\CacheBackendInterface;
/**
 * Class SectionsConfig.
 *
 * @package Drupal\section_workflow
 */
class SectionsConfig {

  /**
   * All of the section workflow config..
   *
   * @var array
   */
  public $configAllSections = array();

  /**
   * Voabulary names for all sections.
   *
   * @var array
   */
  public $vocabOfAllSections = array();

  /**
   * Config for the given context. section_workflow.level.<key>
   *
   * @var array
   */
  public $sectionConfig = array();

  /**
   * Config for the given context at section level.
   * section_workflow.level.<key>.level.<context_key_name>
   *
   * @var array
   */
  public $contentSection = NULL;

  /**
   * Whether the current context is related to section workflow.
   *
   * @var array
   */
  public $inSection = FALSE;


  /**
   * Default mode.
   *
   * @var string
   */
  public $updateMode = self::CONTEXT_UNKNOWN;

  /**
   * Flag to indicate that we are beginning a new section.
   */
  const CONTEXT_NEW_SECTION = 1;

  /**
   * Flag to indicate that we are submitting a new section to be saved.
   */
  const CONTEXT_SUBMIT_NEW_SECTION = 2;

  /**
   * Flag to indicate that we are inserting a new node within a section.
   */
  const CONTEXT_INSERT = 3;

  /**
   * Flag to indicate that we are submitting a new node to be saved within a section.
   */
  const CONTEXT_SUBMIT = 4;

  /**
   * Flag to indicate that we are editing a node that is within a section.
   */
  const CONTEXT_EDIT = 5;

  /**
   * Flag to indicate that we are on the add to section page.
   */
  const CONTEXT_ADD_TO_SECTION = 6;

  /**
   * Flag to indicate that we are on the add to section page.
   */
  const CONTEXT_DELETE_NODE = 7;

  /**
   * .Flag to indicate that we are on an admin page within a section.
   */
  const CONTEXT_ADMIN = 7;

  /**
   * Flag to indicate that we do not yet know what the current context is.
   */
  const CONTEXT_UNKNOWN = FALSE;

  /**
   * The field name of the section workflow taxonomy, it will be used consistently
   * accross all content types that are configured to be used within this module.
   */
  const SECTION_FIELD_NAME = 'section_workflow';

  /**
   * A string to indicate that the node within the current context is of a
   * landing page section type.
   *
   */
  const SECTION_TYPE_LANDING = 'landing';

  /**
   * A string to indicate that the node within the current context is of a
   * root landing section type.
   *
   */
  const SECTION_TYPE_ROOT_LANDING = 'landing';

  /**
   * A string to indicate that the node within the current context is of a
   * Page section type.
   *
   */
  const SECTION_TYPE_PAGE = 'page';

  /**
   * A string to indicate that the node within the current context is of a
   * Listings section type.
   *
   */
  const SECTION_TYPE_LISTING = 'listing';

  /**
   * A string to indicate that the node within the current context is of a
   * Reference section type.
   *
   */
  const SECTION_TYPE_REFERENCE = 'reference';

  /**
   * A string to indicate that the node within the current context is of a
   * Reference section type.
   *
   */
  const CONFIG_PATH_ROOT = 'section_workflow_section';
  const CONFIG_PATH_LEVELS = 'level';
  const URL_ADMIN = 'admin-section-node';

  /**
   * Constructor.
   */
  public function __construct($config_key = NULL) {
    $this->InitConfig();
    $this->GetAllTopLevelVocabularies();
    if (!is_null($config_key)) {
      $this->loadSectionConfigByKey($config_key);
    }
  }


  /**
   * Load all config into an array.
   */
  public function InitConfig() {
    if ($cache = \Drupal::cache()->get('cache_section_workflow_config_all')) {
      $this->configAllSections = $cache->data;
    }
    else {
      $config_factory = \Drupal::configFactory();
      foreach ($config_factory->listAll('section_workflow.sections.') as $id => $section_config_name) {
        $key_short = substr(strrchr($section_config_name, "."), 1);
        $this->configAllSections[$key_short] = $config_factory
          ->get($section_config_name)
          ->get(self::CONFIG_PATH_ROOT);
        // Initialise Menu and Vocabulary for this section as per config.
        $this->configAllSections[$key_short]['key'] = $key_short;
        $this->configAllSections[$key_short]['key_path'] = $section_config_name;
        if (!isset($this->configAllSections[$key_short]['eids'])) {
          $this->configAllSections[$key_short]['eids'] = array();
        }
        $this->ValidateBackEnd($this->configAllSections[$key_short], $section_config_name);
      }
    }
    \Drupal::cache()->set('cache_section_workflow_config_all', $this->configAllSections, CacheBackendInterface::CACHE_PERMANENT);
  }

  /**
   * Get the vocabulary for each config and keep them in an array.
   */
  private function GetAllTopLevelVocabularies() {
    foreach ($this->configAllSections as $section_config) {
      if (isset($section_config['vocabulary'])) {
        $vocabName = $section_config['vocabulary'];
        if (!in_array($vocabName, $this->vocabOfAllSections)) {
          $this->vocabOfAllSections[] = $vocabName;
        }
        // @TODO Report error.
        else {

        }
      }
    }
  }

  /**
   * Get the landing pages for each config.
   */
  public function getLandingPages() {
    $section_root_landing_pages = array();
    foreach ($this->configAllSections as $section_config) {
      if (isset($section_config['content_type'])) {
        $eids = $section_config['eids'];
        if (count($eids) < 1) {
          $section_root_landing_pages[$section_config['key']] = array(
            'config_label' => $section_config['label'],
            'config_key' => $section_config['key'],
            'content_type' => $section_config['content_type'],
            'context_description' => $section_config['description'],
          );
        }
      }
    }
    return $section_root_landing_pages;
  }

  /**
   * Determine root entity id of a section based on the node that we are in
   * interaction with.
   *
   * @param $entity_id
   *   Use the entity id as a starting point to identify the correct
   *
   * @return array
   *   An array of content types with corresponding section config.
   */
  protected function loadAncestry($entity_id = 0) {
    if ($entity_id > 0) {
      $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
      $menu_link = $menu_link_manager->loadLinksByRoute('entity.node.canonical', array('node' => $entity_id));

      // Proceed if this node has a menu item.
      if (is_array($menu_link) && count($menu_link)) {
        $menu_link = reset($menu_link);

        // Load all the ancestors from the menu.
        $ancestors = $menu_link_manager->getParentIds($menu_link->getParent());
        // Get top level menu item - the last item in the array.
        // That will be the root.
        if (!is_null($ancestors)) {
          $ancestor_sliced = array_slice($ancestors, -1);
          $rootPluginId = array_pop($ancestor_sliced);
          $mlc_id = \Drupal::database()
            ->select('menu_tree', 'mt')
            ->fields('mt', ['route_param_key'])
            ->condition('route_name', 'entity.node.canonical')
            ->condition('id', $rootPluginId)
            ->execute()
            ->fetchCol();
          $this->rootEID = intval(explode("=", $mlc_id[0])[1]);
          return $this->rootEID;
        }
      }
    }
    // We could be looking at the root landing page in which case there will
    // be no ancestry so by default let's return the entity id on this assumption.
    return $entity_id;
  }

  /**
   * Firstly, Load the section config for the context.  The context is determined from
   * the URL and parent node entity (extracted from URL).
   * Determine the possible list of actions - namely which content can we add
   * from this context.
   *
   * @return array
   *   An array of content types with corresponding section config.
   */
  public function addToSection() {
    // Load parameters from URL.
    $path_args = explode('/', \Drupal::service('path.current')->getPath());
    if (isset($path_args[3]) && $path_args[3] == 'add-to-section') {
      $this->updateMode = self::CONTEXT_ADD_TO_SECTION;
      $this->parentNID = isset($path_args[2]) ? $path_args[2] : NULL;
      $this->rootEID = $this->loadAncestry($this->parentNID);
      if ($this->loadSectionConfigByRootId($this->rootEID) == TRUE) {
        $this->loadConfigContextByEID($this->sectionConfig, $this->parentNID);
        $this->inSection = TRUE;
        // Get config for all possible content that we can from our current context.
        return $this->getDescendantTypes();
      }
    }
    return FALSE;
  }

  /**
   * Find and return the content section of the config for an entity id.
   *
   * @param $config
   *   System name of config.
   * @param $content_types
   *   List of all content types with corresponding config where applicable.
   *
   * @return array $content_types
   *   An array of content types with corresponding section config.
   */
  private function getSectionContentByEid($config, $eid = NULL, $content_type = 'root') {
    if (isset($config['eids']) && in_array($eid, $config['eids'])) {
      return $config;
    }
    if (isset($config['level'])) {
      foreach ($config['level'] as $key => $section) {
        $content_types[$key]['section_type'] = $section['section_type'];
        if (isset($config['level'])) {
          $this->getSectionContentByEid($config, $eid, $key);
        }
      }
    }
    return NULL;
  }

  /**
   * Recursive function to find the corresponding part of the config that
   * matches the entity id.
   *
   * @param array $sections_context
   *   The context of the section config we are looking at.
   * @param int $entity_id
   *   The entity id to identify the correct section of the config.
   * @param string $path
   *   Identify exactly where we are within the config.
   * @param string $context_key
   *   The identifier of the section of config that we are looking at.
   *
   * @return array $sections
   *   The identified section, or an array of new sections to search through.
   */
  protected function loadConfigContextByEID($sections_context, $entity_id = NULL, $path = self::CONFIG_PATH_ROOT, $context_key = NULL) {
    if (!is_null($entity_id)) {
      // Handle root config as an exception. entity id will be set to zero if
      // we are starting a new section.
      if ($entity_id == 0) {
        $sections_context['key_path'] = $path;
        $sections_context['section_type'] = self::SECTION_TYPE_ROOT_LANDING;
        $this->contentSection = $sections_context;
        return $this->contentSection;
      }
      // We have found the correct part of the config on an eid match.
      elseif (isset($sections_context['eids']) && in_array($entity_id, $sections_context['eids'])) {
        $sections_context['key_path'] = $path;
        $sections_context['context_key'] = $context_key;
        $this->contentSection = $sections_context;
        return $this->contentSection;
      }
    }
    
    // Lets loop through each section until we match the node id to identify
    // the section config.
    if (isset($sections_context['level'])) {
      foreach ($sections_context['level'] as $contextKey => $context) {
        if (isset($context['eids'])) {
          if (in_array($entity_id, $context['eids'])) {
            $context['key_path'] = $path . '.level.' . $contextKey;
            $context['context_key'] = $contextKey;
            $this->contentSection = $context;
            return $this->contentSection;
          }
          // We did not find anything but we have sub sections to explore.
          elseif (isset($context['level'])) {
            $path .= '.level.' . $contextKey;
            $this->loadConfigContextByEID($context, $entity_id, $path, $contextKey);
          }
        }

        // Move back a section in the path at the end of an iteration. So if we
        // have completed looking through .sections.page.sections.landing. then
        // we need to set the path to the previous point of .sections.page.
        $path = substr($path, 0, strrpos($path, "."));
        $path = substr($path, 0, strrpos($path, "."));
      }
    }
  }

  /**
   * Extract the next level of config directly below by content type (identifier).
   *
   * @param array $sections_context
   *   The context of the section config we are looking at.
   * @param string $context_key_name
   *   The identifier of the section of config that we are expecting to extract.
   *
   * @return array $sections
   *   The identified section.
   */
  protected function loadNextLevelContextByKey($sections_context, $context_key_name) {
    if (isset($sections_context['level'][$context_key_name])) {
      $sections_context['level'][$context_key_name]['key_path'] = $sections_context['key_path'] . '.level.' . $context_key_name;
      $this->contentSection = $sections_context['level'][$context_key_name];
    }
    else {
      $this->contentSection = NULL;
    }
    return $this->contentSection;
  }

  /**
   * Return a list of content types with corresponding section config that can
   * be added below the current context.
   *
   * @return array $descendant_types
   *   The descendants from the current context.
   */
  public function getDescendantTypes() {
    $descendant_types = array();
    if (isset($this->contentSection['level'])) {
      foreach ($this->contentSection['level'] as $section_context_key_name => $level_config) {
        // The "max" config constrains how many of the given content type we can
        // create at a given context, ensure that we do not provide the option
        // to create outside the limits of the constraint.
        $max = isset($level_config['max']) ? $level_config['max'] : FALSE;
        $eids = isset($level_config['eids']) ? $level_config['eids'] : NULL;
        if ($max == FALSE || $max > count($eids)) {
          $descendant_types[$section_context_key_name] = array(
            'context_key_name' => $section_context_key_name,
            'context_key_description' => $level_config['description'],
            'content_type' => $level_config['content_type'],
            'section_type' => $level_config['section_type'],
          );
        }
      }
    }
    return $descendant_types;
  }

  /**
   * Assign a section in config with an Entity ID.
   *
   * @param $entity_id
   *   The entity id that is going to be saved to the section config.
   */
  protected function saveEIDToConfig($entity_id = NULL) {
    if (($this->updateMode == self::CONTEXT_SUBMIT
      || $this->updateMode == self::CONTEXT_SUBMIT_NEW_SECTION)) {
      $config_factory = \Drupal::configFactory();
      $settings = $config_factory->getEditable('section_workflow.sections.' . $this->sectionConfig['key']);
      // We save the entity ids corresponding into the corresponding config.
      if ($this->contentSection['section_type'] !== self::SECTION_TYPE_LISTING) {
        $entity_ids = $settings->get($this->contentSection['key_path'] . '.eids');
        $entity_ids[] = intval($entity_id);
        $settings->set($this->contentSection['key_path'] . '.eids', $entity_ids);
      }
      $settings->save();
      $this->InitConfig();
    }
  }

  /**
   * Add a term to the vocabulary associated with config for the given context.
   *
   * @param $title
   *   The title of the new taxonomy term
   * @param $parent_id
   *   The parent, if there is an hierarchy of landing pages that this is to be
   * a part of.
   *
   * @return int $term_id
   *   The id of the saved term.
   */
  protected function AddSectionTerm($title, $parent_id = NULL) {
    $term = Term::create([
      'parent' => $parent_id,
      'name' => $title,
      'vid' => $this->sectionConfig['vocabulary'],
    ]);
    $term->save();
    $term_id = $term->id();
    return $term_id;
  }

  /**
   * Load section config by config key.  If we find it then return a positive
   * boolean.
   *
   * @param $config_key
   *   The title of the new taxonomy term
   * @return bool $term_id
   *   The id of the saved term.
   */
  public function loadSectionConfigByKey($config_key = '') {
    if (isset($this->configAllSections[$config_key])) {
      $this->sectionConfig = $this->configAllSections[$config_key];
      $this->contentSection = $this->sectionConfig;
      $this->inSection = TRUE;
    }
    else {
      $this->inSection = FALSE;
    }
    return $this->inSection;
  }

  /**
   * Identify a corresponding config for the given root entity id.
   *
   * @param int $root_id
   *   A root entity id as we expect to be saved at the head of each section
   * config.
   * @return bool
   */
  protected function loadSectionConfigByRootId($root_id = NULL) {
    if (!is_null($root_id)) {
      foreach ($this->configAllSections as $section_config) {
        // Identify the right section config by Root Entity ID.
        // Load the config for it.
        if (in_array($root_id, $section_config['eids'])) {
          $this->sectionConfig = $section_config;
          $this->loadSectionConfigByKey($this->sectionConfig['key']);
          $this->inSection = TRUE;
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Identify a corresponding config for the given root entity id.
   *
   * @param str $vid
   *   Vocabulary ID.
   * @param int $tid
   *   We can find the vid from a tid in case we don't have a vid.
   * config.
   *
   * @return bool
   */
  protected function loadSectionConfigByVid($vid = NULL, $tid = NULL) {
    if (is_null($vid) && !is_null($tid)) {
      $vid = taxonomy_term_load($tid)->getVocabularyId();
    }

    if (!is_null($vid)) {
      foreach ($this->configAllSections as $section_config) {
        // Identify the right section config by the vid.
        // Load the config for it.
        if ($vid == $section_config['vocabulary']) {
          $this->sectionConfig = $section_config;
          $this->loadSectionConfigByKey($this->sectionConfig['key']);
          $this->inSection = TRUE;
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Ensure that back-end architecture is ready to be used by this module.
   *
   * @param $config
   *   Section config.
   * @param $section_config_name
   *   System name of config.
   */
  private function ValidateBackEnd($config = NULL, $section_config_name = NULL) {
    $failed = array();
    // Menu
    $create = FALSE;
    if (isset($config['auto_create']['menu']) && $config['auto_create']['menu'] == TRUE) $create = TRUE;
    if ($this->ValidateMenu($section_config_name, $config['menu'], $create) == FALSE) {
      $failed['menu'] = TRUE;
    }

    // Vocabulary
    $create = FALSE;
    if (isset($config['auto_create']['vocabulary']) && $config['auto_create']['vocabulary'] == TRUE) $create = TRUE;
    if ($this->ValidateVocabulary($config['vocabulary'], $create) == FALSE) {
      $failed['vocabulary'] = TRUE;
    }

    // Content types
    $content_types = $this->GetContentTypesFromConfig($config, $content_types);
    if (count($content_types) > 0) {
      foreach ($content_types as $key_content_type => $content_type) {
        $entity_type = \Drupal::entityTypeManager()
          ->getStorage('node_type')
          ->load($key_content_type);
        if (!is_null($entity_type)) {
          // Enable menu as per config for content type.
          $this->EnableContentTypeMenu($key_content_type, 'node', $content_type['section_type'], $config['menu']);

          // Section workflow field.
          $create = FALSE;
          if (isset($config['auto_create']['vocabulary_field']) && $config['auto_create']['vocabulary_field'] == TRUE) {
            $create = TRUE;
          }
          if ($this->ValidateSectionWorkflowField($key_content_type, 'node', $content_type['section_type'], $config['vocabulary'], $create)) {
            $failed['vocabulary_field'] = TRUE;
          }

        }
        else {
          $failed['content_type'] = TRUE;
        }
      }
    }
  }


  /**
   * Get all the content types from a config.
   *
   * @param $config
   *   System name of config.
   * @param $content_types
   *   List of all content types with corresponding config where applicable.
   * @param $section_type
   *   If it is populated it means we must restrict by it.
   *
   * @return array $content_types
   *   An array of content types with corresponding section config.
   */
  protected function GetContentTypesFromConfig($config = NULL, &$content_types = array(), $section_type = NULL) {
    if (isset($config['level'])) {
      foreach ($config['level'] as $key => $section) {
        // If section type parameter has been set then restrict by it.
        if (!is_null($section_type)) {
          if ($section['section_type'] == $section_type) {
            $content_types[$section['content_type']]['section_type'][] = $section['section_type'];
          }
        }
        // There are no restrictions, so just load up the array.
        else {
          $content_types[$section['content_type']]['section_type'][] = $section['section_type'];
        }
        if (isset($config['level'])) {
          $this->GetContentTypesFromConfig($section, $content_types, $section_type);
        }
      }
    }

    // Uniquely store which section types the content types are associated with.
    if (!is_null($content_types)) {
      foreach ($content_types as $key => $content_type) {
        $content_types[$key]['section_type'] = array_unique($content_types[$key]['section_type']);
      }
    }
    return $content_types;
  }

  /**
   * Create menu if it does not exist.
   *
   * @param $key
   *   System name of config.
   * @param $menu_id
   *   Machine name of menu.
   * @param $create
   *   Should we create the menu if it does not exist.
   *
   * @return boolean
   *   If the menu exist return TRUE.
   */
  private function ValidateMenu($key = NULL, $menu_id = NULL, $create = TRUE) {
    $valid = FALSE;
    if (!is_null($menu_id)) {
      $menus = \Drupal::entityTypeManager()
        ->getStorage('menu')->loadMultiple();
      if (!isset($menus[$menu_id])) {
        if ($create == TRUE) {
          \Drupal::entityTypeManager()
            ->getStorage('menu')
            ->create([
              'id' => $menu_id,
              'label' => ucfirst(str_replace('-', ' ', $menu_id)),
              'description' => 'Vocabulary created by section_workflow module for config: ' . $key,
            ])
            ->save();
          $valid = TRUE;
        }
      }
      else {
        $valid = TRUE;
      }
    }
    return $valid;
  }

  /**
   * Create Vocabulary if it does not exist.
   *
   * @param $vid
   *   Machine name of vocabulary.
   * @param $create
   *   Should we create the menu if it does not exist.
   *
   * @return boolean
   *   If the vocabulary exist return TRUE.
   */
  private function ValidateVocabulary($vid = NULL, $create = TRUE) {
    $valid = FALSE;
    $vocabularies = \Drupal\taxonomy\Entity\Vocabulary::loadMultiple();
    if (!isset($vocabularies[$vid])) {
      if ($create == TRUE) {
        $vocabulary = \Drupal\taxonomy\Entity\Vocabulary::create(array(
          'vid' => $vid,
          'machine_name' => $vid,
          'name' => ucfirst(str_replace('_', ' ', $vid)),
        ));
        $vocabulary->save();
        $valid = TRUE;
      }
    }
    else {
      $valid = TRUE;
    }
    return $valid;
  }

  /**
   * Create the section workflow base field
   *
   * @return $field_storage
   *   Field storage definition.
   */
  public function CreateSectionWorkflowField() {
    // Create the section workflow field if it does not exist.
    if (!$field_storage = FieldStorageConfig::loadByName('node', self::SECTION_FIELD_NAME)) {
      // Create a field for the section workflow taxonomy.
      $field_storage = FieldStorageConfig::create(array(
        'field_name' => str_replace('-', '_', self::SECTION_FIELD_NAME),
        'description' => $this->sectionConfig['vocabulary'],
        'entity_type' => 'node',
        'translatable' => FALSE,
        'settings' => array(
          'target_type' => 'taxonomy_term',
        ),
        'type' => 'entity_reference',
      ));
      $field_storage->save();
    }
    return $field_storage;
  }

  /**
   * Update content type to ensure that it has the menu enabled corresponding to
   * this config.
   *
   * @param $entity_type
   *   The entity type.
   * @param $bundle
   *   The bundle.
   * @param $section_type
   *   As defined in the config, determining how a content type should be handled.
   * @param $menu
   *   Name of menu as per config.
   */
  public function EnableContentTypeMenu($entity_type, $bundle = 'node', $section_types = array(), $menu = NULL) {
    // Ensure that the menu is available in the content type, except if it is of
    // a listing or node reference section type since we don't need a menu for that.
      if (count(array_intersect($section_types, array(self::SECTION_TYPE_LISTING, self::SECTION_TYPE_REFERENCE))) < 1) {
      $content_type = \Drupal::entityTypeManager()->getStorage('node_type')->load($entity_type);
      $available_menus = $content_type->getThirdPartySetting('menu_ui', 'available_menus');
      if (!in_array($menu, $available_menus)) {
        $available_menus[] = $menu;
        $content_type->setThirdPartySetting('menu_ui', 'available_menus', $available_menus);
        $content_type->save();
      }
    }
  }

  /**
   * Create the section workflow (taxonomy reference) field if it does not
   * already exist.
   * Ensure that the vocabulary relating to this section config is configured in
   * the field.
   * Display section workflow field for content types where there config is set
   * as a "listing" section type.
   *
   * @param $entity_type
   *   The entity type.
   * @param $bundle
   *   The bundle.
   * @param $section_type
   *   As defined in the config, determining how a content type should be handled.
   * @param $vid
   *   Machine name of vocabulary.
   * @param $create
   *   Should we create the field if it does not exist.
   *
   * @return boolean
   *   If the field exists return TRUE.
   */
  public function ValidateSectionWorkflowField($entity_type, $bundle = 'node', $section_type = NULL, $vid = NULL, $create = TRUE) {
    $valid = FALSE;
    $field_manager = \Drupal::service('entity_field.manager');
    $fields = $field_manager->getFieldDefinitions('node', $entity_type);
    if (!isset($fields[self::SECTION_FIELD_NAME])) {
      if ($create == TRUE) {
        // Create base field if it does not exist first.
        if (!$field_storage = FieldStorageConfig::loadByName($bundle, self::SECTION_FIELD_NAME)) {
          $field_storage = $this->CreateSectionWorkflowField();
        }

        // Ideally we would only like the cardinality to be unlimited for listings
        // section types but since we are using one field (as there might be
        // some future and structural benefit for this) across all the content
        // types for section workflow then we will set it to unlimited for all
        // of them but limit to just one in form_alter for the other section types.
        $cardinality = $field_storage->getCardinality();
        if ($cardinality !== FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
          $field_storage->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
          $field_storage->save();
        }

        // Add section workflow taxonomy field for the given bundle
        $field = FieldConfig::create(array(
          'field_storage' => $field_storage,
          'entity_type' => $bundle,
          'bundle' => $entity_type,
          'settings' => array(
            'handler' => 'default',
            'handler_settings' => array(
              // Restrict selection of terms to a single vocabulary.
              'target_bundles' => array(
                $vid => $vid,
              ),
            ),
          ),
        ));

        $field->save();
        $valid = TRUE;
      }
    }
    else {
      $valid = TRUE;
    }

    // The vocabulary that is referenced by the section workflow field might not
    // be listed yet.  This is likely to happen when a content type is being used
    // across more than one section (config).
    $field = FieldConfig::loadByName('node', $entity_type, self::SECTION_FIELD_NAME);
    $handler_settings = $field->getSetting('handler_settings');
    // Check if the vocabulary for this config is not already listed as an
    // available vocabulary for the section workflow field.
    if (!in_array($vid, $handler_settings['target_bundles'])) {
      // Assign vocabulary to the field.
      $handler_settings['target_bundles'][$vid] = $vid;
      // Set and save
      $field->setSetting('handler_settings', $handler_settings);
      $field->save();
    }

    // If a content type is of a "listing" section type then the the Section
    // workflow Taxonomy field needs to be visible on content types (when being edited upon)
    // because they are expected to be tagged more than once (by editors).
    if ($section_type == self::SECTION_TYPE_LISTING) {
      $entity_get_form_display = entity_get_form_display('node', $entity_type, 'default')
        ->setComponent(self::SECTION_FIELD_NAME, array(
          'label' => 'Section workflow',
          'type' => 'entity_reference_autocomplete_tags',
        ));
      $entity_get_form_display->save();
    }
    return $valid;
  }
}