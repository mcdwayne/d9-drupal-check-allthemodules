<?php

namespace Drupal\layout_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Image' block.
 *
 * @Block(
 *   id = "layout_blocks_image",
 *   admin_label = @Translation("Image (with optional heading and text)"),
 *   category = @Translation("Layout blocks")
 * )
 */
class ImageBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * File storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $file_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fileStorage = $file_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('file')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'image' => [],
      'heading' => '',
      'text' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Image'),
      '#default_value' => $this->configuration['image'],
      // @todo: Make configurable.
      '#upload_location' => 'public://layout_blocks',
      '#upload_validators'  => [
        'file_validate_extensions' => ['gif png jpg jpeg'],
      ],
      '#required' => TRUE,
    ];
    $form['heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Heading'),
      '#description' => $this->t('Optional heading to display on the image'),
      '#default_value' => $this->configuration['heading'],
    ];
    $text = $this->configuration['text'];
    $form['text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Text to display on the image'),
      '#default_value' => !empty($text['value']) ? $text['value'] : '',
      '#format' => !empty($text['format']) ? $text['format'] : filter_default_format(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $image_value = $form_state->getValue('image');
    /** @var \Drupal\file\FileInterface $file */
    $file = $this->fileStorage->load($image_value[0]);
    $file->setPermanent();
    $file->save();
    $this->configuration['image'] = $image_value;
    $this->configuration['text'] = $form_state->getValue('text');
    $this->configuration['heading'] = $form_state->getValue('heading');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['#cache'] = [
      'max-age' => -1,
    ];
    $image = $this->configuration['image'];
    if (empty($image)) {
      return $build;
    }
    $text = [];
    if (!empty($this->configuration['text'])) {
      $text = [
        '#type' => 'processed_text',
        '#text' => $this->configuration['text']['value'],
        '#format' => $this->configuration['text']['format'],
      ];
    }
    // Load the image.
    /** @var \Drupal\file\FileInterface $image */
    if (!$image = $this->fileStorage->load($image[0])) {
      return $build;
    }
    // @todo: Create with image style preset.
    $build['image'] = [
      '#theme' => 'layout_blocks_image_block',
      '#heading' => $this->configuration['heading'],
      '#text' => $text,
      '#image' => [
        '#theme' => 'image',
        // @todo: Support for this.
        '#alt' => '',
        '#title' => '',
        '#uri' => $image->getFileUri(),
      ],
    ];
    return $build;
  }

}
