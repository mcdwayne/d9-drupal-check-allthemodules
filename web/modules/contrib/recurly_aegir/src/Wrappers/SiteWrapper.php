<?php

namespace Drupal\recurly_aegir\Wrappers;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\node\NodeInterface;
use Drupal\recurly_aegir\HostingServiceCalls\SiteQuotaHostingServiceCall;
use Drupal\recurly_aegir\HostingServiceCalls\SiteVerifyHostingServiceCall;

/**
 * Wrapper for site nodes providing additional functionality.
 */
class SiteWrapper extends Wrapper {

  /**
   * The site node.
   *
   * @var Drupal\node\NodeInterface
   */
  protected $site;

  /**
   * Instantiates a new instance of this class.
   *
   * This is a factory method that returns a new instance of this class. The
   * factory should pass any needed dependencies into the constructor of this
   * class, but not the container itself. Every call to this method must return
   * a new instance of this class; that is, it may not implement a singleton.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this instance should use.
   * @param Drupal\node\NodeInterface $site
   *   The site node.
   *
   * @see ContainerInjectionInterface::create()
   */
  public static function create(ContainerInterface $container, NodeInterface $site) {
    return new static(
      $site,
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('entity_type.manager')->getStorage('node'),
      $container->get('config.factory')->get('recurly.settings'),
      $container->get('module_handler')
    );
  }

  /**
   * Class Constructor.
   *
   * @param Drupal\node\NodeInterface $site
   *   The site node.
   * @param Symfony\Component\HttpFoundation\Request $current_request
   *   The current HTTP/S request.
   * @param Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   Node storage.
   * @param Drupal\Core\Config\ImmutableConfig $recurly_config
   *   The Recurly configuration.
   * @param Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    NodeInterface $site,
    Request $current_request = NULL,
    EntityStorageInterface $node_storage = NULL,
    ImmutableConfig $recurly_config = NULL,
    ModuleHandlerInterface $module_handler = NULL
  ) {
    parent::__construct($current_request, $node_storage, $recurly_config, $module_handler);

    if ($site->getType() != 'recurly_aegir_site') {
      throw new \Exception('A site node must be provided on construction.');
    }
    $this->site = $site;
  }

  /**
   * Fetches the site's subscription ID.
   *
   * @return string
   *   The subscription ID.
   */
  public function getSubscriptionId() {
    return $this->site->get('field_site_subscription_url')->getValue()[0]['title'];
  }

  /**
   * Fetches the subscription wrapper associated with the site.
   *
   * @return Drupal\recurly_aegir\Wrappers\SubscriptionWrapper
   *   The subscription wrapper.
   */
  public function getSubscription() {
    return SubscriptionWrapper::get($this->getSubscriptionId());
  }

  /**
   * Determines if a particular subscription is active for a site.
   *
   * @return bool
   *   TRUE if subscription is active; FALSE otherwise.
   */
  public function subscriptionIsActive() {
    if (!recurly_client_initialize()) {
      return FALSE;
    }

    return SubscriptionWrapper::get($this->getSubscriptionId())->isActive();
  }

  /**
   * Gets the configuration link for a specific site.
   *
   * @return Drupal\Core\Link
   *   The site node's configuration link.
   */
  public function getConfigurationLink() {
    $url = Url::fromRoute('entity.node.edit_form', ['node' => $this->site->id()]);
    return Link::fromTextAndUrl(t('<em>Configuration required!</em>'), $url);
  }

  /**
   * Fetches the site's link.
   *
   * @return Drupal\Core\Link
   *   A link to the site itself.
   */
  public function getLink() {
    $protocol = $this->currentRequest->getScheme();
    $subdomain = Html::escape($this->getTitle());
    $domain = $this->currentRequest->getHost();
    $hostname = "$subdomain.$domain";
    $url = Url::fromUri("$protocol://$hostname/");
    return Link::fromTextAndUrl($hostname, $url);
  }

  /**
   * Fetches the site's title.
   *
   * @return string
   *   The site's title.
   */
  public function getTitle() {
    return $this->site->getTitle();
  }

  /**
   * Saves the site node.
   */
  public function save() {
    return $this->site->save();
  }

  /**
   * Set quotas based on the subscription plan & add-ons.
   *
   * @param string $plan_code
   *   The plan code for this site's subscription.
   * @param array $addons
   *   The list of add-ons included in the subscription.
   *
   * @see https://www.drupal.org/project/quenforcer
   */
  public function setQuotas($plan_code, array $addons) {
    $quotas = $this->moduleHandler->invokeAll('recurly_aegir_quota_info', [
      $this->site,
      $plan_code,
      $addons,
    ]);

    foreach ($quotas as $quota => $limit) {
      $quota_setter = new SiteQuotaHostingServiceCall($this->site, $quota, $limit);
      $quota_setter->performActionAndLogResults();
    }

    if (!empty($quotas)) {
      $site_verifier = new SiteVerifyHostingServiceCall($this->site);
      $site_verifier->performActionAndLogResults();
    }
  }

}
