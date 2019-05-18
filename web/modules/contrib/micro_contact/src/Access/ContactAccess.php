<?php

namespace Drupal\micro_contact\Access;

use Drupal\contact\ContactFormInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\micro_contact\MicroContactManagerInterface;
use Symfony\Component\Routing\Route;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Manage access on contact form from a site entity.
 */
class ContactAccess implements AccessInterface{

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The system theme config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The micro contact manager service.
   *
   * @var \Drupal\micro_contact\MicroContactManagerInterface
   */
  protected $microContactManager;

  /**
   * Constructs a NodeAccess object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\micro_site\SiteNegotiatorInterface $site_negotiator
   *   The site negotiator.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\micro_contact\MicroContactManagerInterface $micro_contact_manager
   *   The micro contact manager service.
   */
  function __construct(EntityTypeManagerInterface $entity_type_manager, SiteNegotiatorInterface $site_negotiator, ConfigFactoryInterface $config_factory, MicroContactManagerInterface $micro_contact_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->negotiator = $site_negotiator;
    $this->configFactory = $config_factory;
    $this->microContactManager = $micro_contact_manager;
  }

  /**
   * Checks access to the entity operation on the given route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\contact\ContactFormInterface $contact_form
   *   The contact form on which check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account, ContactFormInterface $contact_form = NULL) {
    static $active_site;

    if (!isset($active_site)) {
      $active_site = $this->negotiator->getActiveSite();
    }

    // Check to see that we have a valid active site.
    // Without one, we are on the master host site, and we let it manage
    // access to contact form.
    if (empty($active_site)) {
      return AccessResult::allowed();
    }

    // Use the default form if no form has been passed to the route.
    if (empty($contact_form)) {
      $config = $this->configFactory->get('contact.settings');
      $contact_form = $this->entityTypeManager
        ->getStorage('contact_form')
        ->load($config->get('default_form'));
      // If there are no forms, do not display the form.
      if (empty($contact_form)) {
        // Can I return here a NotFoundHttpException ?
        return AccessResult::forbidden()
          ->addCacheableDependency($config);
      }
    }

    if ($active_site instanceof SiteInterface) {
      $site_contact_form_id = $this->microContactManager->getSiteContactFormId($active_site);
      if ($contact_form->id() == $site_contact_form_id) {
        return AccessResult::allowed()
          ->addCacheableDependency($active_site)
          ->addCacheableDependency($contact_form);
      }
      else {
        return AccessResult::forbidden()
          ->addCacheableDependency($active_site)
          ->addCacheableDependency($contact_form);
      }

    }

  }

}
