<?php

namespace Drupal\aegir_site_subscriptions\Services;

use Drupal\Component\Utility\Html;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\node\NodeInterface;
use Drupal\aegir_site_subscriptions\HostingServiceCalls\SiteQuotaHostingServiceCall;
use Drupal\aegir_site_subscriptions\HostingServiceCalls\SiteVerifyHostingServiceCall;
use Drupal\aegir_site_subscriptions\Exceptions\SiteServiceMissingSiteException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The Site service, which wraps site nodes to provide additional functionality.
 */
class Site {

  /**
   * The current HTTP/S request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The site node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $site;

  /**
   * The site quota setter service.
   *
   * @var \Drupal\aegir_site_subscriptions\HostingServiceCalls\SiteQuotaHostingServiceCall
   */
  protected $siteQuotaSetter;

  /**
   * The user messenger service.
   *
   * @var \Drupal\aegir_site_subscriptions\HostingServiceCalls\SiteVerifyHostingServiceCall
   */
  protected $siteVerifier;

  /**
   * Class constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current HTTP/S request.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\aegir_site_subscriptions\HostingServiceCalls\SiteQuotaHostingServiceCall $site_quota_setter
   *   The logging service.
   * @param \Drupal\aegir_site_subscriptions\HostingServiceCalls\SiteVerifyHostingServiceCall $site_verifier
   *   The module handler.
   */
  public function __construct(
    RequestStack $request_stack,
    ModuleHandlerInterface $module_handler,
    SiteQuotaHostingServiceCall $site_quota_setter,
    SiteVerifyHostingServiceCall $site_verifier
  ) {
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->moduleHandler = $module_handler;
    $this->siteQuotaSetter = $site_quota_setter;
    $this->siteVerifier = $site_verifier;

    $this->site = NULL;
  }

  /**
   * Associates the service with a particular site.
   *
   * It's necessary to call this method before any other non-static methods.
   *
   * @param \Drupal\node\NodeInterface $site
   *   The site node.
   *
   * @return $this
   *   The object itself, for method chaining.
   *
   * @throws \Exception
   */
  public function setSite(NodeInterface $site) {
    if ($site->getType() != 'aegir_site') {
      throw new SiteServiceMissingSiteException('Only site nodes can be provided to this setter.');
    }
    $this->site = $site;
    return $this;
  }

  /**
   * Fetches the site currently set with the service.
   *
   * @return \Drupal\node\NodeInterface
   *   The site node.
   */
  protected function getSite() {
    if (is_null($this->site)) {
      throw new SiteServiceMissingSiteException('This operation requires that the site service be set with a site.');
    }
    return $this->site;
  }

  /**
   * Fetches the site's subscription ID.
   *
   * @return string
   *   The subscription ID.
   */
  public function getSubscriptionId() {
    return $this->getSite()->get('field_site_subscription_url')->getValue()[0]['title'];
  }

  /**
   * Gets the configuration link for a specific site.
   *
   * @return \Drupal\Core\Link
   *   The site node's configuration link.
   */
  public function getConfigurationLink() {
    $url = Url::fromRoute('entity.node.edit_form', ['node' => $this->getSite()->id()]);
    return Link::fromTextAndUrl(t('<em>Configuration required!</em>'), $url);
  }

  /**
   * Fetches the site's link.
   *
   * @return \Drupal\Core\Link
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
    return $this->getSite()->getTitle();
  }

  /**
   * Saves the site node.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function save() {
    return $this->getSite()->save();
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
   *
   * @throws \Exception
   */
  public function setQuotas($plan_code, array $addons) {
    $quotas = $this->moduleHandler->invokeAll('aegir_site_subscriptions_quota_info', [
      $this->getSite(),
      $plan_code,
      $addons,
    ]);

    foreach ($quotas as $quota => $limit) {
      $this->siteQuotaSetter->setSite($this->getSite())->setQuota($quota, $limit)->performActionAndLogResults();
    }

    if (!empty($quotas)) {
      $this->siteVerifier->setSite($this->getSite())->performActionAndLogResults();
    }
  }

}
