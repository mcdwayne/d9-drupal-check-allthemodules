<?php

namespace Drupal\commerce_product_review\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the product review add/edit form.
 */
class ProductReviewForm extends ContentEntityForm {

  /**
   * The Current User object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The product review type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $productReviewTypeStorage;

  /**
   * Constructs a ProductReviewForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, AccountInterface $current_user) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);

    $this->currentUser = $current_user;
    $this->productReviewTypeStorage = $entity_manager->getStorage('commerce_product_review_type');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type */
    $entity_type = $this->entity->getEntityType();

    // Ensure that log message field is hidden for new entities.
    $log_message_field = $entity_type->getRevisionMetadataKey('revision_log_message');
    if ($log_message_field && isset($form[$log_message_field])) {
      $form[$log_message_field]['#access'] = !$this->entity->isNew();
    }

    $admin_access_fields = [
      'created',
      'uid',
      'status',
    ];
    foreach ($admin_access_fields as $field_name) {
      if (!empty($form[$field_name])) {
        $form[$field_name]['#access'] = $this->currentUser->hasPermission('administer commerce_product_review_type');
      }
    }

    // Show the missing field description for these fields.
    $show_description_fields = [
      'title',
      'published_as',
      'description',
    ];
    foreach ($show_description_fields as $field_name) {
      if (!empty($form[$field_name]['widget']['#description'])) {
        // @todo find a better, more generic way.
        if (isset($form[$field_name]['widget'][0]['value']) && is_array($form[$field_name]['widget'][0]['value'])) {
          // Copy the field description to the widget item.
          $form[$field_name]['widget'][0]['value']['#description'] = $form[$field_name]['widget']['#description'];
        }
        else {
          // Copy the field description to the widget item.
          $form[$field_name]['widget'][0]['#description'] = $form[$field_name]['widget']['#description'];
        }
      }
    }

    /** @var \Drupal\commerce_product_review\Entity\ProductReviewTypeInterface $product_review_type */
    $product_review_type = $this->productReviewTypeStorage->load($this->entity->bundle());
    if (($description_placeholder = $product_review_type->getDescriptionPlaceholder()) && !empty($form['description']['widget'][0])) {
      // Add a placeholder text to the description field.
      $form['description']['widget'][0]['#placeholder'] = $description_placeholder;
    }

    if (!empty($form['published_as'])) {
      $form['published_as']['widget'][0]['value']['#default_value'] = $this->currentUser()->getDisplayName();
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_product_review\Entity\ProductReviewInterface $product_review */
    $product_review = $this->entity;
    if (!$this->currentUser()->hasPermission('publish commerce_product_review')) {
      $product_review->setUnpublished();
      $msg = $this->t('Your product review has been queued for audit by site administrators and will be published after approval.');
    }
    else {
      $msg = $this->t('Your product review has been posted.');
    }
    $result = $product_review->save();
    drupal_set_message($msg);
    $form_state->setRedirect('entity.commerce_product.canonical', ['commerce_product' => $product_review->getProductId()]);
    return $result;
  }

}
