<?php

namespace Drupal\commerce_oci_checkout;

use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\commerce_oci_checkout\Controller\CommerceOciCheckoutController;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

/**
 * CommerceOciCheckoutFormAlter service.
 */
class CommerceOciCheckoutFormAlter {

  use StringTranslationTrait;

  /**
   * Product storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $productStorage;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Attr bag.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface
   */
  protected $attributeBag;

  /**
   * Current route.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRoute;

  /**
   * Constructs a CommerceOciCheckoutFormAlter object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user, AttributeBagInterface $attribute_bag, CurrentRouteMatch $current_route) {
    $this->productStorage = $entity_type_manager->getStorage('commerce_product');
    $this->currentUser = $current_user;
    $this->attributeBag = $attribute_bag;
    $this->currentRoute = $current_route;
  }

  /**
   * Alter a specific form.
   */
  public function alterCart(&$form, FormStateInterface $form_state) {
    if ($this->currentUser->hasPermission('use commerce_oci_checkout')) {
      // See if we can find the hook url in the attribute bag. If we can not,
      // then the next step will be really hard.
      if (!$url = $this->attributeBag->get(CommerceOciCheckoutController::HOOK_URL_ATTRIBUTE_NAME)) {
        return;
      }
      // Redirect to OCI.
      if ($this->currentRoute->getRouteName() != 'commerce_oci_checkout.checkout') {
        throw new NeedsRedirectException('/oci-cart');
      }
      // Remove those buttons.
      unset($form["actions"]);
    }
  }

}
