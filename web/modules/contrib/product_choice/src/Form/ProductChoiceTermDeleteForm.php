<?php

namespace Drupal\product_choice\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\product_choice\ProductChoiceUsageService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting Product choice term entities.
 *
 * @ingroup product_choice
 */
class ProductChoiceTermDeleteForm extends ContentEntityDeleteForm {

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
  public function getCancelUrl() {
    // Terms have no global list page.
    $term = $this->getEntity();

    return Url::fromRoute('entity.product_choice_list.terms_list',
      ['product_choice_list' => $term->bundle()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->getCancelUrl();
  }

  /**
   * Deletion should be blocked if term in use by any commerce products.
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $term = $this->getEntity();

    $products = $this->productChoiceUsageService->getProducts($term);

    if (!empty($products)) {
      drupal_set_message(t('Term cannot be deleted because it is in use by one or more products'), 'error');
      $form_state->setRedirectUrl(Url::fromRoute('entity.product_choice_term.usage_list', [
        'product_choice_term' => $term->id(),
        'product_choice_list' => $term->getList(),
      ]));

    }
    else {
      parent::submitForm($form, $form_state);
    }
  }

}
