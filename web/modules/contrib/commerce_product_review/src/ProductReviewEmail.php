<?php

namespace Drupal\commerce_product_review;

use Drupal\commerce_product_review\Entity\ProductReviewInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Default product review e-mail service implementation.
 */
class ProductReviewEmail implements ProductReviewEmailInterface {

  use StringTranslationTrait;

  /**
   * The product review type storage.
   *
   * @var \Drupal\commerce_product_review\ProductReviewTypeStorageInterface
   */
  protected $reviewTypeStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new ProductReviewEmail object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_channel_factory
   *   The logger channel factory.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, LoggerChannelFactoryInterface $logger_channel_factory, MailManagerInterface $mail_manager, RendererInterface $renderer) {
    $this->reviewTypeStorage = $entity_type_manager->getStorage('commerce_product_review_type');
    $this->languageManager = $language_manager;
    $this->logger = $logger_channel_factory->get('commerce_product_review');
    $this->mailManager = $mail_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function sendNotification(ProductReviewInterface $review) {
    /** @var \Drupal\commerce_product_review\Entity\ProductReviewTypeInterface $review_type */
    $review_type = $this->reviewTypeStorage->load($review->bundle());
    $to = $review_type->getNotificationEmail();
    if (empty($to)) {
      return [];
    }

    $stores = $review->getProduct()->getStores();
    $store = reset($stores);

    $params = [
      'headers' => [
        'Content-Type' => 'text/html; charset=UTF-8;',
        'Content-Transfer-Encoding' => '8Bit',
      ],
      'from' => $store->getEmail(),
      'subject' => $this->t('New product review for @product', ['@product' => $review->getProduct()->label()]),
      'review' => $review,
    ];

    $build = [
      '#theme' => 'commerce_product_review_notification',
      '#review' => $review,
      '#product' => $review->getProduct(),
    ];
    $params['body'] = $this->renderer->executeInRenderContext(new RenderContext(), function () use ($build) {
      return $this->renderer->render($build);
    });
    $langcode = $this->languageManager->getDefaultLanguage()->getId();

    return $this->mailManager->mail('commerce_product_review', 'notification', $to, $langcode, $params);
  }

}
