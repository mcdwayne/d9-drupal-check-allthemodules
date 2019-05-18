<?php

namespace Drupal\mailing_list\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\mailing_list\SubscriptionInterface;
use Drupal\mailing_list\MailingListManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\mailing_list\MailingListInterface;

/**
 * Returns responses for Mailing list routes.
 */
class MailingListController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The mailing list manager.
   *
   * @var \Drupal\mailing_list\MailingListManagerInterface
   */
  protected $mailingListManager;

  /**
   * Construct a MailingListController instance.
   *
   * @param \Drupal\mailing_list\MailingListManagerInterface $mailing_list_manager
   *   The mailing list manager.
   */
  public function __construct(MailingListManagerInterface $mailing_list_manager) {
    $this->mailingListManager = $mailing_list_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('mailing_list.manager')
    );
  }

  /**
   * Displays add subscription links for available mailing lists.
   *
   * Redirects to mailing_list/subscription/add/[mailing_list] if only one
   * mailing_list is available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the mailing lists that current user can
   *   add subscriptions. RedirectResponse to the subscription add page for the
   *   mailing list if only one if present.
   */
  public function subscribePage() {
    $entityTypeManager = $this->entityTypeManager();

    $build = [
      '#theme' => 'subscription_add_list',
      '#cache' => [
        'tags' => $entityTypeManager->getDefinition('mailing_list')->getListCacheTags(),
      ],
    ];

    $content = [];

    // Only use mailing lists the user has access to.
    foreach ($entityTypeManager->getStorage('mailing_list')->loadMultiple() as $bundle) {
      $access = $entityTypeManager->getAccessControlHandler('mailing_list_subscription')->createAccess($bundle->id(), NULL, [], TRUE);
      if ($access->isAllowed()) {
        $content[$bundle->id()] = $bundle;
      }
    }

    // Bypass the mailing lists listing if only one mailing list is available.
    if (count($content) == 1) {
      $bundle = array_shift($content);
      return $this->redirect('mailing_list.subscribe', ['mailing_list' => $bundle->id()]);
    }

    $build['#content'] = $content;

    return $build;
  }

  /**
   * Returns a subscription form.
   *
   * @param \Drupal\mailing_list\MailingListInterface $mailing_list
   *   The subscription's mailing list.
   *
   * @return array
   *   The subscription add form.
   */
  public function subscribeForm(MailingListInterface $mailing_list) {
    $subscription = $this->entityTypeManager()->getStorage('mailing_list_subscription')->create(['mailing_list' => $mailing_list->id()]);
    return $this->entityFormBuilder()->getForm($subscription);
  }

  /**
   * Process access link to a subscription.
   *
   * @param int $sid
   *   The subscription ID what want to be accessed.
   * @param string $hash
   *   Access hash.
   * @param string $rel
   *   Destination entity link. Defaults to canonical.
   */
  public function accessSubscription($sid, $hash, $rel = 'canonical') {
    $subscription_storage = $this->entityTypeManager()->getStorage('mailing_list_subscription');
    /** @var \Drupal\mailing_list\SubscriptionInterface $subscription */
    if (!$subscription = $subscription_storage->load($sid)) {
      throw new NotFoundHttpException();
    }

    $url = $subscription->toUrl($rel);

    // Go to subscription if access already granted.
    if (!$go = $url->access()) {
      // Go if valid hash received and owner or cross access allowed.
      if ($subscription->getAccessHash() == $hash
        && ($subscription->getList()->isCrossAccessAllowed() || $subscription->getOwnerId() == $this->currentUser()->id())) {
        // Grant session access to the current user.
        $this->mailingListManager->grantSessionAccess($subscription);
        $go = TRUE;
      }
    }

    // Go.
    if ($go) {
      // The access link is the only way that anonymous users have to manage
      // all their subscriptions. We will grant session access to any
      // additional anonymous subscription with the same email.
      if ($this->currentUser()->isAnonymous()) {
        foreach ($subscription_storage->loadMultiple($subscription_storage->getQuery()
          ->condition('uid', 0)
          ->condition('email', $subscription->getEmail())
          ->condition('status', SubscriptionInterface::ACTIVE)
          ->execute()) as $additional_subscription) {
          if (!$additional_subscription->access('view')) {
            $this->mailingListManager->grantSessionAccess($additional_subscription);
          }
        }
      }

      // Redirect to subscription.
      return $this->redirect($url->getRouteName(), $url->getRouteParameters());
    }
    else {
      throw new AccessDeniedHttpException();
    }
  }

}
