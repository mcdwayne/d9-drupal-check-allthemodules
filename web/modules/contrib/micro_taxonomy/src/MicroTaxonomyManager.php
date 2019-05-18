<?php

namespace Drupal\micro_taxonomy;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\VocabularyInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\micro_site\SiteUsers;
use Drupal\micro_node\MicroNodeFields;

/**
 * {@inheritdoc}
 */
class MicroTaxonomyManager implements MicroTaxonomyManagerInterface {

  use StringTranslationTrait;

  protected static $allowedFormOperations = [
    'default',
    'edit',
    'add',
    'register',
  ];

  /**
   * The HTTP_HOST value of the request.
   */
  protected $httpHost;

  /**
   * The site record returned by the lookup request.
   *
   * @var \Drupal\micro_site\Entity\SiteInterface
   */
  protected $site;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;


  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Constructs a DomainNegotiator object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack object.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Domain loader object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\micro_site\SiteNegotiatorInterface $site_negotiator
   *   The site negotiator.
   */
  public function __construct(RequestStack $requestStack, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, SiteNegotiatorInterface $site_negotiator) {
    $this->requestStack = $requestStack;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->negotiator = $site_negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public static function getCurrentSiteId() {
    /** @var \Drupal\micro_site\Entity\SiteInterface $site */
    $site = \Drupal::service('micro_site.negotiator')->getActiveSite();

    // We are not on a active site url. Try to load it from the Request.
    if (empty($site)) {
      $site = \Drupal::service('micro_site.negotiator')->loadFromRequest();
    }
    return ($site) ? [$site->id()] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSite(EntityInterface $entity) {
    if (!$entity instanceof ContentEntityInterface) {
      return NULL;
    }
    if (!$entity->hasField('site_id')) {
      return NULL;
    }
    $site = $entity->get('site_id')->referencedEntities();
    if ($site) {
      $site = reset($site);
    }
    return $site ?: NULL;
  }

  /**
   * @inheritdoc
   */
  public function isAvailableOnAllSites(EntityInterface $entity) {
    if (!$entity->hasField(MicroTaxonomyFields::TERM_SITE_ALL)) {
      return FALSE;
    }
    $value = $entity->{MicroTaxonomyFields::TERM_SITE_ALL}->value;
    return $value ? TRUE : FALSE;
  }

  /**
   * @inheritdoc
   */
  public function getUsersCanUpdate(SiteInterface $site, $return_entity = FALSE) {
    $users = [];
    $users += $site->getAdminUsersId($return_entity);
    $users += $site->getManagerUsersId($return_entity);
    $users += $site->getContributorUsersId($return_entity);
    return $users;
  }

  /**
   * {@inheritdoc}
   */
  public function userCanAccessTermOverview(AccountInterface $account, SiteInterface $site = NULL, $operation = '') {
    return $this->userCanCreateTerm($account, $site, $operation);
  }

  /**
   * {@inheritdoc}
   */
  public function userCanUpdateTerm(AccountInterface $account, SiteInterface $site = NULL, $operation = '') {
    if ($site) {
      $users_allowed = $this->getUsersCanUpdate($site);
      $context = [
        'site' => $site,
        'operation' => $operation,
      ];
      $this->moduleHandler->alter('micro_taxonomy_user_allowed', $users_allowed, $context);
      return in_array($account->id(), $users_allowed);
    }
    return $account->hasPermission('administer micro vocabularies');
  }

  /**
   * @inheritdoc
   */
  public function getUsersCanDelete(SiteInterface $site, $return_entity = FALSE) {
    $users = [];
    $users += $site->getAdminUsersId($return_entity);
    $users += $site->getManagerUsersId($return_entity);
    return $users;
  }

  /**
   * {@inheritdoc}
   */
  public function userCanDeleteTerm(AccountInterface $account, SiteInterface $site = NULL, $operation = '') {
    if ($site) {
      $users_allowed = $this->getUsersCanDelete($site);
      $context = [
        'site' => $site,
        'operation' => $operation,
      ];
      $this->moduleHandler->alter('micro_taxonomy_user_allowed', $users_allowed, $context);
      return in_array($account->id(), $users_allowed);
    }
    return $account->hasPermission('administer micro vocabularies');
  }

  /**
   * @inheritdoc
   */
  public function getUsersCanCreate(SiteInterface $site, $return_entity = FALSE) {
    $users = [];
    $users += $site->getAdminUsersId($return_entity);
    $users += $site->getManagerUsersId($return_entity);
    return $users;
  }

  /**
   * {@inheritdoc}
   */
  public function userCanCreateTerm(AccountInterface $account, SiteInterface $site = NULL, $operation = '') {
    // @TODO Dispatch an event
    if ($site) {
      $users_allowed = $this->getUsersCanCreate($site);
      $context = [
        'site' => $site,
        'operation' => $operation,
      ];
      $this->moduleHandler->alter('micro_taxonomy_user_allowed', $users_allowed, $context);
      return in_array($account->id(), $users_allowed);
    }
    return $account->hasPermission('administer micro vocabularies');
  }

  /**
   * @inheritdoc
   */
  public function checkCreateSiteVocabulary(SiteInterface $entity) {
    /** @var \Drupal\micro_site\Entity\SiteInterface $entity */
    if (!$entity->hasVocabulary()) {
      return;
    }
    $site_vocabulary = $entity->getSiteVocabulary();
    if (empty($site_vocabulary)) {
      /** @var \Drupal\system\Entity\Menu $menu */
      $vocabulary = Vocabulary::create([
        'vid' => 'site_' . $entity->id(),
        'name' => 'Vocabulary ' . $entity->label(),
        'description' => t('The vocabulary for the site @label (id: @id)', ['@label' => $entity->label(), '@id' => $entity->id()]),
        'third_party_settings' => ['micro_taxonomy' => ['site_id' => $entity->id()]],
      ])->save();

      if (empty($vocabulary)) {
        throw new \Exception('Vocabulary not created and saved for site entity id' . $entity->id() . ' (' . $entity->label() . ')');
      }
    }
  }

  /**
   * @inheritdoc
   */
  public function alterForm(&$form, FormStateInterface $form_state, $form_id) {
    if ($form_id == 'taxonomy_overview_terms') {
      $this->alterTaxonomyOverviewTerms($form,$form_state, $form_id);
    }

    if (!$form_entity = $this->getFormEntity($form_state)) {
      return FALSE;
    }

    if ($form_entity instanceof TermInterface) {
      $this->alterTaxonomyTermForm($form,$form_state, $form_id, $form_entity);
    }
    elseif ($form_entity instanceof ContentEntityInterface) {
      $this->alterContentForm($form, $form_state, $form_id, $form_entity);
    }
  }

  /**
   * @inheritdoc
   */
  public function alterContentForm(&$form, FormStateInterface $form_state, $form_id, ContentEntityInterface $entity) {
    $site = $this->getSite($entity);
    if (!$site) {
      $site = $this->negotiator->getActiveSite();
    }
    // We are on an active site, or the content is attached to a site.
    // Let's remove the entity reference taxonomy term fields which are not
    // allowed on the site type.
    if ($site instanceof SiteInterface) {
      /** @var \Drupal\micro_site\Entity\SiteTypeInterface $site_type */
      $site_type = $site->type->entity;
      $site_vocabularies = $site_type->getVocabularies();
      $fields = $entity->getFieldDefinitions();
      /** @var \Drupal\Core\Field\FieldConfigInterface $field */
      foreach ($fields as $field_name => $field) {
        if ($field instanceof FieldConfigInterface && $field->getType() == 'entity_reference') {
          $field_name = $field->getName();
          $settings = $field->getSettings();

          if (isset($settings['handler']) && $settings['handler'] == 'default:taxonomy_term') {
            $bundles = $settings['handler_settings']['target_bundles'] ?: [];
            if (empty($bundles)) {
              continue;
            }
            // bundles must be allowed by the site type. All bundles so must be
            // present in the site_vocabularies array.
            $bundles_diff = array_diff($bundles, $site_vocabularies);
            if (!empty($bundles_diff)) {
              // There is at least one bundle not allowed.
              if (isset($form[$field_name])) {
                $form[$field_name]['#access'] = FALSE;
              }
            }
          }

          // We are on an active site, or the content is attached to a site.
          // Let's rename the site vocabulary entity reference field label with
          // the vocabulary label.
          if (isset($settings['handler']) && $settings['handler'] == 'site_vocabulary:taxonomy_term') {
            $vocabulary_id = $site->getSiteVocabulary();
            $vocabulary = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->load($vocabulary_id);
            if ($vocabulary instanceof VocabularyInterface) {
              $vocabulary_label = $vocabulary->label();
              if (isset($form[$field_name]['widget']['#title'])) {
                $form[$field_name]['widget']['#title'] = $vocabulary_label;
              }
            }
            // The site doesn't have a dedicated vocabulary.
            else {
              if (isset($form[$field_name])) {
                $form[$field_name]['#access'] = FALSE;
              }
            }
          }

        }
      }
    }
    // Remove entity reference type fields with the handler
    // site_vocabulary:taxonomy_term
    else {
      $fields = $entity->getFieldDefinitions();
      /** @var \Drupal\Core\Field\FieldConfigInterface $field */
      foreach ($fields as $field_name => $field) {
        if ($field instanceof FieldConfigInterface && $field->getType() == 'entity_reference') {
          $field_name = $field->getName();
          $settings = $field->getSettings();
          if (!isset($settings['handler'])) {
            continue;
          }

          if ($settings['handler'] == 'site_vocabulary:taxonomy_term') {
            if (isset($form[$field_name])) {
              $form[$field_name]['#access'] = FALSE;
            }
          }
        }
      }
    }

  }

  /**
   * @inheritdoc
   */
  public function alterTaxonomyTermForm(&$form, FormStateInterface $form_state, $form_id, TermInterface $entity) {
    /** @var \Drupal\taxonomy\VocabularyInterface $vocabulary */
    $vocabulary = Vocabulary::load($entity->bundle());
    $vocabularies_enabled = $this->configFactory->get('micro_taxonomy.settings')->get('vocabularies');
    $account = \Drupal::currentUser();
    $active_site = $this->negotiator->getSite();
    $site_id_field = MicroTaxonomyFields::TERM_SITE;
    $site_all_field = MicroTaxonomyFields::TERM_SITE_ALL;

    $form['site'] = [
      '#type' => 'details',
      '#title' => t('Site settings'),
      '#group' => isset($form['additional_settings']) ? 'additional_settings' : 'advanced',
      '#attributes' => [
        'class' => ['term-form-options'],
      ],
      '#weight' => 40,
      '#optional' => TRUE,
    ];

    if ($active_site instanceof SiteInterface) {
      if (isset($form[$site_id_field])) {
        $form[$site_id_field]['widget'][0]['target_id']['#required'] = TRUE;
        $form[$site_id_field]['widget'][0]['target_id']['#attributes']['disabled'] = TRUE;
        $form[$site_id_field]['widget'][0]['target_id']['#attributes']['readonly'] = TRUE;
        // Should we hide always this field ?
        $form[$site_id_field]['#access'] = FALSE;
        $form[$site_id_field]['#group'] = 'site';
      }
      // A term can only be created for all sites from the master host.
      if (isset($form[$site_all_field])) {
        $form[$site_all_field]['#access'] = FALSE;
      }
    }
    else {
      if (isset($form[$site_id_field])) {
        $form[$site_id_field]['#group'] = 'site';
        $form[$site_id_field]['#access'] = ($account->hasPermission('assign term to micro site') || $account->hasPermission('administer taxonomy'));
        if (!in_array($vocabulary->id(), $vocabularies_enabled)) {
          $form[$site_id_field]['#access'] = FALSE;
        }
        $form[$site_id_field]['#states'] = [
          'disabled' => [
            ':input[name="site_all[value]"]' => ['checked' => TRUE],
          ],
        ];
      }
      if (isset($form[$site_all_field])) {
        $form[$site_all_field]['#group'] = 'site';
        $form[$site_all_field]['#access'] = ($account->hasPermission('create term available all sites') || $account->hasPermission('administer taxonomy'));
        if (!in_array($vocabulary->id(), $vocabularies_enabled)) {
          $form[$site_all_field]['#access'] = FALSE;
        }
        $form[$site_all_field]['#states'] = [
          'disabled' => [
            ':input[name="site_id[0][target_id]"]' => ['filled' => TRUE],
          ],
        ];
        $term_site = $this->getSite($entity);
        if ($term_site) {
          $form[$site_all_field]['#access'] = FALSE;
        }
      }
    }
  }

  public function alterTaxonomyOverviewTerms(&$form, FormStateInterface $form_state, $form_id) {
    if ($active_site = $this->negotiator->getActiveSite()) {
      return;
    }
    $elements = &$form['terms'];
    $children = Element::children($form['terms']);
    $elements['#header']['site'] = $this->t('Site');
    foreach ($children as $child) {
      $term = $elements[$child]['#term'];
      $on_site= $this->t('Master host');
      if ($site = $this->getSite($term)) {
        $on_site = $this->t('@sitename (id: @id)', ['@sitename' => $site->getName(), '@id' => $site->id()]);
      }
      elseif ($this->isAvailableOnAllSites($term)) {
        $on_site = $this->t('All sites');
      }
      $elements[$child]['site'] = [
        '#plain_text' => $on_site,

      ];
    }
  }

  /**
   * @inheritdoc
   */
  public function getFormEntity(FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();
    if (NULL !== $form_object
      && method_exists($form_object, 'getOperation')
      && method_exists($form_object, 'getEntity')
      && in_array($form_object->getOperation(), self::$allowedFormOperations)) {
      return $form_object->getEntity();
    }
    return FALSE;
  }

}
