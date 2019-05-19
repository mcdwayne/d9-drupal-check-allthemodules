<?php

namespace Drupal\webform_cart\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\webform_cart\WebformCartSessionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WebformCartController.
 */
class WebformCartController extends ControllerBase {

  protected $entityTypeManager;

  protected $webformCartSession;


  /**
   * WebformCartController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\webform_cart\WebformCartSessionInterface $webform_cart_session
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WebformCartSessionInterface $webform_cart_session) {
    $this->entityTypeManager = $entity_type_manager;
    $this->webformCartSession = $webform_cart_session;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('webform_cart.session')
    );
  }

  /**
   * Viewcart.
   *
   * @return string
   *   Return Hello string.
   */
  public function ViewCart() {
    // TODO: Review cart Modal.
    $order = $this->entityTypeManager->getStorage('node')->load(22);
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: ViewCart')
    ];
  }

  /**
   * Addtocart.
   *
   * @return string
   *   Return Hello string.
   */
  public function AddToCart() {
    $node = $this->entityTypeManager->getStorage('node')->create(array('type' => 'page', 'title' => 'About Us'));
    $node->save();
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: UpdateCart')
    ];
  }
  /**
   * Removefromcart.
   *
   * @return string
   *   Return Hello string.
   */
  public function RemoveFromCart() {
    $entity = $this->entityTypeManager->getStorage('node')->load(22);
    $entity->delete();
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: RemoveFromCart')
    ];
  }
  /**
   * Updatecart.
   *
   * @return string
   *   Return Hello string.
   */
  public function UpdateCart() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: UpdateCart')
    ];
  }
  /**
   * Updateitem.
   *
   * @return string
   *   Return Hello string.
   */
  public function UpdateItem() {
    $entity = $this->entityTypeManager->getStorage("node")->load(22);
    $entity->title->value = 'Yes we can!';
    // Update the multivalue tags field term id
    // Use $entity->field_tags->getValue() to see the field's data structure.
    $entity->field_tags[0]->target_id = 3;
    $entity->save();
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: UpdateItem')
    ];
  }

}
