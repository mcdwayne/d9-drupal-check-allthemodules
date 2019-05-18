<?php

namespace Drupal\commerce_product_review\Controller;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Defines the product review controller.
 */
class ProductReviewController extends ControllerBase {

  /**
   * The product review storage.
   *
   * @var \Drupal\commerce_product_review\ProductReviewStorageInterface
   */
  protected $reviewStorage;

  /**
   * The product review type storage.
   *
   * @var \Drupal\commerce_product_review\ProductReviewTypeStorageInterface
   */
  protected $reviewTypeStorage;

  /**
   * Constructs a new ProductReviewController object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct() {
    $this->reviewStorage = $this->entityTypeManager()->getStorage('commerce_product_review');
    $this->reviewTypeStorage = $this->entityTypeManager()->getStorage('commerce_product_review_type');
  }

  /**
   * Renders the review form for the given product entity.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $commerce_product
   *   The product entity.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The form structure, or a redirect response, if the current user has
   *   already reviewed the given product.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the given product is not published.
   */
  public function reviewForm(ProductInterface $commerce_product) {
    if ($this->currentUser()->isAuthenticated()) {
      $existing_reviews = $this->reviewStorage->loadByProductAndUser($commerce_product->id(), $this->currentUser()->id());
      if (!empty($existing_reviews)) {
        // Only allow a single review per product and user.
        drupal_set_message($this->t('You can only review a product once.'));
        return $this->redirect('entity.commerce_product.canonical', ['commerce_product' => $commerce_product->id()]);
      }
    }

    $review_type = $this->reviewTypeStorage->findMatchingReviewType($commerce_product);
    if (empty($review_type)) {
      throw new AccessDeniedHttpException('No review type defined for the given product.');
    }

    $review = $this->reviewStorage->create([
      'product_id' => $commerce_product->id(),
      'type' => $review_type->id(),
    ]);
    return $this->entityFormBuilder()->getForm($review, 'add');
  }

  /**
   * Renders the review page for the given product entity.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $commerce_product
   *   The product entity.
   *
   * @return array
   *   The rendered product review page.
   */
  public function reviewPage(ProductInterface $commerce_product) {
    $build = [];
    $reviews = $this->reviewStorage->loadByProductId($commerce_product->id());
    if (empty($reviews)) {
      $build['empty'] = [
        '#theme' => 'commerce_product_review_empty_page',
        '#product' => $commerce_product,
      ];
    }
    else {
      $build['reviews'] = $this->entityTypeManager()->getViewBuilder('commerce_product_review')->viewMultiple($reviews);
    }
    return $build;
  }

  /**
   * Title callback for ::reviewForm().
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $commerce_product
   *   The product entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function reviewFormTitle(ProductInterface $commerce_product) {
    return $this->t('Review product @product', ['@product' => $commerce_product->label()]);
  }

  /**
   * Title callback for ::reviewPage().
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $commerce_product
   *   The product entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function reviewPageTitle(ProductInterface $commerce_product) {
    return $this->t('Reviews for @product', ['@product' => $commerce_product->label()]);
  }

  /**
   * Access callback for reviewForm().
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $commerce_product
   *   The product to review.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function accessReviewForm(ProductInterface $commerce_product) {
    if (!$commerce_product->isPublished()) {
      return AccessResult::forbidden()->addCacheableDependency($commerce_product);
    }
    $review_type = $this->reviewTypeStorage->findMatchingReviewType($commerce_product);
    return $this->entityTypeManager()->getAccessControlHandler('commerce_product_review')->createAccess($review_type ? $review_type->id() : NULL, NULL, [], TRUE);
  }

}
