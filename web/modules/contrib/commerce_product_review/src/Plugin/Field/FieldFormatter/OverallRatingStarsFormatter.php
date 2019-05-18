<?php

namespace Drupal\commerce_product_review\Plugin\Field\FieldFormatter;

use CommerceGuys\Intl\Formatter\NumberFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the 'commerce_product_review_overall_rating_stars' formatter.
 *
 * This field formatter shows the product's overall rating as stars, based on
 * the rateit.js library.
 *
 * @FieldFormatter(
 *   id = "commerce_product_review_overall_rating_stars",
 *   label = @Translation("Stars"),
 *   field_types = {
 *     "commerce_product_review_overall_rating"
 *   }
 * )
 */
class OverallRatingStarsFormatter extends FormatterBase implements ContainerFactoryPluginInterface, OverallRatingEmptyTextFormatterInterface {

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The number formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\NumberFormatterInterface
   */
  protected $numberFormatter;

  /**
   * The product review storage.
   *
   * @var \Drupal\commerce_product_review\ProductReviewStorageInterface
   */
  protected $productReviewStorage;

  /**
   * Constructs a new OverallRatingStarsFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current active user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \CommerceGuys\Intl\Formatter\NumberFormatterInterface $number_formatter
   *   The number formatter.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, NumberFormatterInterface $number_formatter) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->currentUser = $current_user;
    $this->numberFormatter = $number_formatter;
    $this->productReviewStorage = $entity_type_manager->getStorage('commerce_product_review');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('commerce_price.number_formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_overview_link' => TRUE,
      'show_review_form_link' => TRUE,
      'empty_text' => t('Write the first review'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['show_overview_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link to overview'),
      '#description' => $this->t("If selected, a link to the product's review overview page will be displayed."),
      '#default_value' => $this->getSetting('show_overview_link'),
    ];

    $form['show_review_form_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link to review form'),
      '#description' => $this->t('If selected, a link to the review form will be displayed, so that the visitor can write a product review.'),
      '#default_value' => $this->getSetting('show_review_form_link'),
    ];

    $form['empty_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Empty text'),
      '#description' => $this->t('Text displayed, if no published review exists for the given product.'),
      '#default_value' => $this->getSetting('empty_text'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    if ($this->getSetting('show_overview_link')) {
      $summary[] = $this->t('Display overview link.');
    }
    else {
      $summary[] = $this->t('Do not display overview link.');
    }

    if ($this->getSetting('show_review_form_link')) {
      $summary[] = $this->t('Display review form link.');
    }
    else {
      $summary[] = $this->t('Do not display review form link.');
    }

    if ($empty_text = $this->getSetting('empty_text')) {
      $summary[] = $this->t('Empty text: @empty_text', ['@empty_text' => $empty_text]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $items->getEntity();
    $existing_reviews = $this->productReviewStorage->loadByProductId($product->id(), TRUE);

    $format_options = [
      'maximum_fraction_digits' => $this->getSetting('strip_trailing_zeroes') ? 1 : 3,
    ];

    /** @var \Drupal\commerce_product_review\Plugin\Field\FieldType\OverallRatingItem $item */
    foreach ($items as $delta => $item) {
      $rating = $this->numberFormatter->format($item->score, $format_options);
      $elements[$delta] = [
        '#type' => 'container',
        'overall_rating' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => ['rateit'],
            'title' => $this->t('Overall rating: @rating', ['@rating' => $rating]),
            'data-rateit-value' => $item->score,
            'data-rateit-ispreset' => 'true',
            'data-rateit-readonly' => 'true',
          ],
        ],
      ];

      if ($this->getSetting('show_overview_link')) {
        $elements[$delta]['overview_link'] = [
          '#type' => 'link',
          '#title' => $this->formatPlural(count($existing_reviews), 'View @count review', 'View all @count reviews'),
          '#url' => Url::fromRoute('entity.commerce_product.reviews', ['commerce_product' => $product->id()]),
        ];
      }

      if ($this->getSetting('show_review_form_link')) {
        $existing_own_reviews = $this->currentUser->isAuthenticated() ? $this->productReviewStorage->loadByProductAndUser($product->id(), $this->currentUser->id()) : [];
        if (empty($existing_own_reviews)) {
          $elements[$delta]['review_form_link'] = [
            '#type' => 'link',
            '#title' => $this->t('Write your own review'),
            '#url' => Url::fromRoute('entity.commerce_product.review_form', ['commerce_product' => $product->id()]),
          ];
        }
      }
    }
    $elements['#attached']['library'] = ['commerce_product_review/rateitjs'];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmptyText() {
    return $this->getSetting('empty_text');
  }

}
