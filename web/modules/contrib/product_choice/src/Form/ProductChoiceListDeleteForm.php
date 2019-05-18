<?php

namespace Drupal\product_choice\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\product_choice\ProductChoiceUsageService;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Url;

/**
 * Builds the form to delete Product choice list entities.
 */
class ProductChoiceListDeleteForm extends EntityConfirmFormBase {

  /**
   * Product Choice Usage Service Object.
   *
   * @var \Drupal\product_choice\ProductChoiceUsageService
   */
  protected $productChoiceUsageService;

  /**
   * Constructs a ProductChoicesController object.
   */
  public function __construct(ProductChoiceUsageService $productChoiceUsageService) {
    $this->productChoiceUsageService = $productChoiceUsageService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('product_choice.usage_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Deleting a list will delete all the terms in it. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.product_choice_list.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * Deletion should be blocked if list in use by any commerce product types.
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $list = $this->getEntity();

    $types = $this->productChoiceUsageService->getProductTypes($list);

    if (!empty($types)) {
      drupal_set_message(t('List cannot be deleted because it is in use by one or more product types: @types',
        [
          '@types' => implode(', ', $types),
        ]
        ),
        'error'
      );
    }
    else {
      $this->entity->delete();

      drupal_set_message(
        $this->t('Product choice list deleted: @label.',
          [
            '@label' => $this->entity->label(),
          ]
          )
      );
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
