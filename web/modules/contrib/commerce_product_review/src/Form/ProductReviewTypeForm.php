<?php

namespace Drupal\commerce_product_review\Form;

use Drupal\commerce\EntityHelper;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the product review type add/edit form.
 */
class ProductReviewTypeForm extends BundleEntityFormBase {

  /**
   * The product type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $productTypeStorage;

  /**
   * Creates a new ProductReviewTypeForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->productTypeStorage = $entity_type_manager->getStorage('commerce_product_type');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\commerce_product_review\Entity\ProductReviewTypeInterface $product_review_type */
    $product_review_type = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $product_review_type->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $product_review_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_product_review\Entity\ProductReviewType::load',
      ],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $product_review_type->getDescription(),
    ];

    $form['descriptionPlaceholder'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description placeholder'),
      '#description' => $this->t('Text shown to the user as placeholder text, when writing a product review.'),
      '#default_value' => $product_review_type->getDescriptionPlaceholder(),
    ];

    $form['emails'] = [
      '#type' => 'details',
      '#title' => $this->t('Emails'),
      '#weight' => 5,
      '#open' => TRUE,
      '#collapsible' => TRUE,
      '#tree' => FALSE,
    ];
    $form['emails']['notificationEmail'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Send a notification email to the following address(es):'),
      '#default_value' => ($product_review_type->isNew()) ? '' : $product_review_type->getNotificationEmail(),
      '#states' => [
        'visible' => [
          ':input[name="sendReceipt"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $product_types = $this->productTypeStorage->loadMultiple();
    $form['productTypes'] = [
      '#type' => 'select',
      '#title' => $this->t('Product types'),
      '#multiple' => TRUE,
      '#default_value' => $product_review_type->getProductTypeIds(),
      '#options' => EntityHelper::extractLabels($product_types),
      '#required' => TRUE,
    ];

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    drupal_set_message($this->t('The product review type %label has been successfully saved.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_product_review_type.collection');
  }

}
