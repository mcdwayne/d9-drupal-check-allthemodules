<?php

namespace Drupal\rocketship_paragraphs\Plugin\DsField;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\ds\Plugin\DsField\DsFieldBase;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin that renders a image inside a link field if exist.
 *
 * @DsField(
 *   id = "image_url_field",
 *   title = @Translation("Image and url field"),
 *   entity_type = "paragraph",
 *   provider = "rocketship_paragraphs",
 *   ui_limit = {"p_002|*", "p_007_child|*"}
 * )
 */
class ImageUrlField extends DsFieldBase {

  /**
   * Drupal\Core\Entity\EntityDisplayRepositoryInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $displayRepository;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityDisplayRepositoryInterface $display_repository) {
    $this->displayRepository = $display_repository;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'image_style' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary($settings) {
    $config = $this->getConfiguration();
    $summary = '';
    if (!empty($config['responsive_image_style'])) {
      $summary = 'Using responsive style: ' . $config['responsive_image_style'];
    }
    elseif (isset($config['image_style'])) {
      $summary = 'Using image style: ' . $config['image_style'];
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $image_styles = image_style_options(FALSE);

    $settings['image_style'] = [
      '#title' => t('Image style'),
      '#type' => 'select',
      '#default_value' => $config['image_style'],
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
    ];
    /** @var \Drupal\responsive_image\Entity\ResponsiveImageStyle[] $entities */
    $entities = ResponsiveImageStyle::loadMultiple();
    $styles = [];
    foreach ($entities as $entity) {
      $styles[$entity->id()] = $entity->label();
    }

    $settings['responsive_image_style'] = [
      '#title' => t('Responsive image style'),
      '#type' => 'select',
      '#default_value' => $config['responsive_image_style'],
      '#empty_option' => t('None (original image)'),
      '#options' => $styles,
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity = $this->entity();
    $build = [];
    $cache_tags = $entity->getCacheTags();

    if ($entity->hasField('field_p_image')) {
      $image_field = $entity->get('field_p_image')->getValue();
      if (isset($image_field[0]['target_id'])) {
        $image_file = File::load($image_field[0]['target_id']);
        $image = \Drupal::service('image.factory')
          ->get($image_file->getFileUri());
        $variables = [
          'uri' => $image_file->getFileUri(),
          'alt' => $image_field[0]['alt'],
          'title' => $image_field[0]['title'],
        ];
        if ($image) {
          $variables['width'] = $image->getWidth();
          $variables['height'] = $image->getHeight();
        }
        else {
          $variables['width'] = $variables['height'] = NULL;
        }
        $img = [
          '#theme' => 'image',
          '#uri' => $variables['uri'],
          '#height' => $variables['height'],
          '#width' => $variables['width'],
          '#alt' => $variables['alt'],
          '#title' => $variables['title'],
        ];

        // Add image cache tags.
        $cache_tags = Cache::mergeTags($cache_tags, $image_file->getCacheTags());

        // Check if a responsive image style was configured.
        if (!empty($this->configuration['responsive_image_style'])) {
          $img['#theme'] = 'responsive_image';
          $img['#responsive_image_style_id'] = $this->configuration['responsive_image_style'];
          $img['#attributes']['alt'] = $variables['alt'];
          $img['#attributes']['title'] = $variables['title'];

          // Add the image style as cache tag.
          $image_style = ResponsiveImageStyle::load($this->configuration['responsive_image_style']);
          $cache_tags = Cache::mergeTags($cache_tags, $image_style->getCacheTags());
        }
        elseif (!empty($this->configuration['image_style'])) {
          $img['#theme'] = 'image_style';
          $img['#style_name'] = $this->configuration['image_style'];

          // Add the image style as cache tag.
          $image_style = ImageStyle::load($this->configuration['image_style']);
          $cache_tags = Cache::mergeTags($cache_tags, $image_style->getCacheTags());
        }

        // Check if we need to embed the image in a link.
        if ($entity->hasField('field_p_link')) {
          $url_field = $entity->get('field_p_link')->getValue();
          if (!empty($url_field) && isset($url_field[0]['uri'])) {
            $options = (isset($url_field[0]['options'])) ? $url_field[0]['options'] : [];
            $url = Url::fromUri($url_field[0]['uri'], $options);
            $output = render($img);
            $link = Link::fromTextAndUrl($output, $url);
            $build = $link->toRenderable();
          }
          else {
            // Fall back on only image.
            $build = $img;
          }
        }
        else {
          // Fall back on only image.
          $build = $img;
        }
      }

    }

    // Add cacheable dependencies.
    $build['#cache']['tags'] = $cache_tags;

    return $build;
  }

}
