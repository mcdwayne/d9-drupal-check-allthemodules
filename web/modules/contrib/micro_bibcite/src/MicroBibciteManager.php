<?php

namespace Drupal\micro_bibcite;

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
class MicroBibciteManager implements MicroBibciteManagerInterface {

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
   * Constructs a MicroBibciteManager object.
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
  public function userCanAccessBibciteOverview(AccountInterface $account, SiteInterface $site = NULL, $operation = '') {
    return $this->userCanDoOperation($account, $site, $operation);
  }

  /**
   * {@inheritdoc}
   */
  public function userCanDoOperation(AccountInterface $account, SiteInterface $site = NULL, $operation = '') {
    if ($site) {
      switch ($operation) {
        case 'update':
          $users_allowed = $this->getUsersCanUpdate($site);
          break;
        case 'delete':
          $users_allowed = $this->getUsersCanDelete($site);
          break;
        case 'create':
          $users_allowed = $this->getUsersCanCreate($site);
          break;
        default:
          $users_allowed = $this->getUsersCanDelete($site);
          break;
      }

      $context = [
        'site' => $site,
        'operation' => $operation,
      ];
      $this->moduleHandler->alter('micro_bibcite_user_allowed', $users_allowed, $context);
      return in_array($account->id(), $users_allowed);
    }
    return $account->hasPermission('administer micro bibcite');
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
   * @inheritdoc
   */
  public function getUsersCanCreate(SiteInterface $site, $return_entity = FALSE) {
    $users = [];
    $users += $site->getAdminUsersId($return_entity);
    $users += $site->getManagerUsersId($return_entity);
    return $users;
  }

  /**
   * @inheritdoc
   */
  public function alterForm(&$form, FormStateInterface $form_state, $form_id) {
    if (!$form_entity = $this->getFormEntity($form_state)) {
      return FALSE;
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
