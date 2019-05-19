<?php

namespace Drupal\swiper_slider\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Provides a 'SwiperSliders' block.
 *
 * @Block(
 *  id = "swiper_sliders",
 *  admin_label = @Translation("Swiper sliders"),
 * )
 */
class SwiperSliders extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new SwiperSliders object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The plugin implementation manager.
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        EntityTypeManager $entity_type_manager
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $configuration,
        $plugin_id,
        $plugin_definition,
        $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'class' => $this->t('Is it empty ?'),
      'speed' => 500,
      'height' => NULL,
      'direction' => $this->t('horizontal'),
      'loop' => TRUE,
      'autoplay' => 500,
      'effect' => $this->t('slide'),
      'grab' => TRUE,
      'video_autoplay' => FALSE,
      'buttons' => FALSE,
      'scrollbar' => FALSE,
      'pagination' => FALSE,
      'parallax' => FALSE,
    ] + parent::defaultConfiguration();

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form['speed'] = [
      '#type' => 'number',
      '#title' => $this->t('speed'),
      '#description' => $this->t('Speed of the slide Animation in milliseconds. Eg. 500'),
      '#default_value' => $this->configuration['speed'],
      '#weight' => '1',
    ];
    $form['direction'] = [
      '#type' => 'select',
      '#title' => $this->t('Direction'),
      '#description' => $this->t('Direction of the Slide Animation.'),
      '#options' => ['horizontal' => $this->t('horizontal'), 'vertical' => $this->t('vertical')],
      '#default_value' => $this->configuration['direction'],
      '#size' => 2,
      '#weight' => '2',
    ];
    $form['loop'] = [
      '#type' => 'select',
      '#title' => $this->t('Loop'),
      '#description' => $this->t('Enable/Disable slider Loop'),
      '#options' => ['true' => $this->t('Enable'), 'false' => $this->t('Disable')],
      '#default_value' => $this->configuration['loop'],
      '#weight' => '3',
    ];
    $form['autoplay'] = [
      '#type' => 'number',
      '#title' => $this->t('Autoplay'),
      '#description' => $this->t("Pause Time between Slide\'s Autoplay Transition in milliseconds. Eg. 1000"),
      '#default_value' => $this->configuration['autoplay'],
      '#weight' => '4',
    ];
    $form['effect'] = [
      '#type' => 'select',
      '#title' => $this->t('Effect'),
      '#description' => $this->t('Slide Effect.'),
      '#options' => [
        'slide' => $this->t('slide'),
        'fade' => $this->t('fade'),
        'cube' => $this->t('cube'),
        'coverflow' => $this->t('coverflow'),
        'flip' => $this->t('flip'),
      ],
      '#default_value' => $this->configuration['effect'],
      '#size' => 5,
      '#weight' => '5',
    ];
    $form['grab'] = [
      '#type' => 'select',
      '#title' => $this->t('Grab'),
      '#description' => $this->t('Enable/Disable Grab Cursor.'),
      '#options' => ['true' => $this->t('Enable'), 'false' => $this->t('Disable')],
      '#default_value' => $this->configuration['grab'],
      '#weight' => '6',
    ];
    $form['video_autoplay'] = [
      '#type' => 'select',
      '#title' => $this->t('Video Autoplay'),
      '#description' => $this->t('Enable/Disable Video Autoplay.'),
      '#options' => ['true' => $this->t('Enable'), 'false' => $this->t('Disable')],
      '#default_value' => $this->configuration['video_autoplay'],
      '#weight' => '7',
    ];
    $form['height'] = [
      '#type' => 'number',
      '#title' => $this->t('height'),
      '#description' => $this->t('Height of the slide Animation in milliseconds. Eg. 500'),
      '#default_value' => $this->configuration['height'],
      '#weight' => '8',
    ];
    $form['pagination'] = [
      '#type' => 'select',
      '#title' => $this->t('Pagination'),
      '#description' => $this->t('Enable/Disable the pagination buttons.'),
      '#options' => ['true' => $this->t('Enable'), 'false' => $this->t('Disable')],
      '#default_value' => $this->configuration['pagination'],
      '#weight' => '9',
    ];
    $form['scrollbar'] = [
      '#type' => 'select',
      '#title' => $this->t('Scrollbar'),
      '#description' => $this->t('Enable/Disable the scrollbar.'),
      '#options' => ['true' => $this->t('Enable'), 'false' => $this->t('Disable')],
      '#default_value' => $this->configuration['scrollbar'],
      '#weight' => '10',
    ];
    $form['buttons'] = [
      '#type' => 'select',
      '#title' => $this->t('Buttons'),
      '#description' => $this->t('Enable/Disable Prev/Next buttons.'),
      '#options' => ['true' => $this->t('Enable'), 'false' => $this->t('Disable')],
      '#default_value' => $this->configuration['buttons'],
      '#weight' => '11',
    ];
    $form['parallax'] = [
        '#type' => 'select',
        '#title' => $this->t('Parallax'),
        '#description' => $this->t('Enable/Disable the parallax effect.'),
        '#options' => ['true' => $this->t('Enable'), 'false' => $this->t('Disable')],
        '#default_value' => $this->configuration['parallax'],
        '#weight' => '12',
    ];
    $form['class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class'),
      '#description' => $this->t('The css class for the slider'),
      '#default_value' => $this->configuration['class'],
      '#maxlength' => 255,
      '#size' => 255,
      '#weight' => '13',
    ];


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['class'] = $form_state->getValue('class');
    $this->configuration['height'] = $form_state->getValue('height');
    $this->configuration['speed'] = $form_state->getValue('speed');
    $this->configuration['direction'] = $form_state->getValue('direction');
    $this->configuration['loop'] = $form_state->getValue('loop');
    $this->configuration['autoplay'] = $form_state->getValue('autoplay');
    $this->configuration['effect'] = $form_state->getValue('effect');
    $this->configuration['grab'] = $form_state->getValue('grab');
    $this->configuration['video_autoplay'] = $form_state->getValue('video_autoplay');
    $this->configuration['pagination'] = $form_state->getValue('pagination');
    $this->configuration['buttons'] = $form_state->getValue('buttons');
    $this->configuration['scrollbar'] = $form_state->getValue('scrollbar');
    $this->configuration['parallax'] = $form_state->getValue('parallax');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $build = [];
    $build['#theme'] = 'swiper_sliders';
    $build['#class'] = $this->configuration['class'];
    $build['#height'] = $this->configuration['height'];
    $build['#direction'] = $this->configuration['direction'];
    $build['#speed'] = $this->configuration['speed'];
    $build['#loop'] = $this->configuration['loop'];
    $build['#autoplay'] = $this->configuration['autoplay'];
    $build['#effect'] = $this->configuration['effect'];
    $build['#grab'] = $this->configuration['grab'];
    $build['#video_autoplay'] = $this->configuration['video_autoplay'];
    $build['#pagination'] = $this->configuration['pagination'];
    $build['#buttons'] = $this->configuration['buttons'];
    $build['#scrollbar'] = $this->configuration['scrollbar'];
    $build['#parallax'] = $this->configuration['parallax'];
    $build['#sliders'] = [];
    $sliders = $this->entityTypeManager->getStorage('swiper_slider')->loadByProperties(['status' => TRUE]);

    foreach ($sliders as $slider) {
      $build['#sliders'][$slider->id()] = [
        'background' => $slider->getBackgroundUrl(),
        'content' => $slider->getRenderedcontent(),
        'class' => $slider->getClass(),
      ];
    }
    $build['#attached'] = [
      'library' => [
        'swiper_slider/swiper',
      ],
    ];

    return $build;

  }

}
