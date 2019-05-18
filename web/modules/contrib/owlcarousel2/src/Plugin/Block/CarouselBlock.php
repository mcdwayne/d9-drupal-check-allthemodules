<?php

namespace Drupal\owlcarousel2\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\owlcarousel2\Entity\OwlCarousel2;
use Drupal\owlcarousel2\Util;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'CarouselBlock' block.
 *
 * @Block(
 *  id = "owlcarousel2_block",
 *  admin_label = @Translation("Carousel block"),
 * )
 */
class CarouselBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The keyValueExpirable service.
   *
   * @var \Drupal\owlcarousel2\Plugin\Block\KeyValueExpirableFactoryInterface
   */
  private $keyValue;

  /**
   * CarouselBlock constructor.
   *
   * @param \Drupal\owlcarousel2\Plugin\Block\KeyValueExpirableFactoryInterface $keyValue
   *   The keyValueExpirable service.
   */
  public function __construct(KeyValueExpirableFactoryInterface $keyValue) {

    $this->keyValue = $keyValue;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('keyvalue.expirable')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $carousel = FALSE;
    if (isset($this->configuration['carousel_id'])) {
      $carousel = OwlCarousel2::load($this->configuration['carousel_id']);
    }

    $form['carousel_id'] = [
      '#type'          => 'entity_autocomplete',
      '#title'         => $this->t('Carousel'),
      '#description'   => $this->t('Select the carousel'),
      '#target_type'   => 'owlcarousel2',
      '#default_value' => $carousel,
      '#weight'        => '10',
      '#required'      => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['carousel_id'] = $form_state->getValue('carousel_id');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build    = [];
    $carousel = OwlCarousel2::load($this->configuration['carousel_id']);

    if (!$carousel instanceof OwlCarousel2) {
      return [];
    }
    $settings = $carousel->get('settings')->getValue()[0];

    try {
      $data = Util::getCarouselData($carousel->id());
    }
    catch (EntityMalformedException $e) {
      return $e;
    }
    $content    = $data['content'];
    $nav_titles = $data['navigation_titles'];
    $nav_ratio  = $data['nav_ratio'];
    $nav_height = $data['nav_height'];
    $nav_width  = $data['nav_width'];

    $carousel_navigation = isset($settings['carouselNavigation']) ? ($settings['carouselNavigation'] == 'true') : FALSE;
    // If it is a carousel navigation, include a setting navigation size and set
    // the items per slide to 1.
    if ($carousel_navigation) {
      $settings['carouselNavigationSize'] = $settings['items_per_slide'];
      $settings['items_per_slide']        = 1;
    }

    $build['#theme']                 = 'owlcarousel2_block';
    $build['#content']['#markup']    = $content;
    $build['#nav_titles']            = $nav_titles;
    $build['#carousel_navigation']   = $carousel_navigation;
    $build['#nav_ratio']             = $nav_ratio;
    $build['#nav_height']            = $nav_height;
    $build['#nav_width']             = $nav_width;
    $build['#id']                    = $carousel->id();
    $build['#attached']['library'][] = 'owlcarousel2/owlcarousel2';

    // In order to allow multiple carousels in the same page, we need to create
    // a key/value pair to pass to JS and apply each configuration to the
    // appropriated carousel.
    // For each carousel block, we will store the configuration using
    // keyvalue.expirable service.
    // The last carousel call will pass key/value pairs to JS with all
    // configuration values.
    /** @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $key_value */
    $this->keyValue->get('owlcarousel2')->set($carousel->id(), $settings);
    $keyed_settings = $this->keyValue->get('owlcarousel2')->getAll();

    $build['#attached']['drupalSettings']['owlcarousel_settings'] = $keyed_settings;

    return $build;
  }

}
