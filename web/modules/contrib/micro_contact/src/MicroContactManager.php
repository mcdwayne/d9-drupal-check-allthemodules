<?php

namespace Drupal\micro_contact;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
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
class MicroContactManager implements MicroContactManagerInterface {

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
  public function getContactFormAllowed($type = 'canonical', $return_entity = FALSE, $reset = FALSE) {
    $bool = $return_entity ? 'true' : 'false';
    $list = &drupal_static(__FUNCTION__ . '_' . $bool);

    if ($reset) {
      $list = NULL;
    }

    if (is_null($list)) {
      $list = [];
      $contact_forms = $this->entityTypeManager->getStorage('contact_form')->loadMultiple();
      /** @var \Drupal\contact\ContactFormInterface $contact_form */
      foreach ($contact_forms as $id => $contact_form) {
        if ($contact_form->getThirdPartySetting('micro_contact', $type)) {
          $list[$id] = $return_entity ? $contact_form : $id;
        }
      }
    }

    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteContactFormId(SiteInterface $site) {
    return $site->get('contact_id')->value;
  }


}
