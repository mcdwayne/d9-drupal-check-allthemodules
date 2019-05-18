<?php

/**
 * @file
 * Contains \Drupal\nasa\Plugin\Block\NasaApodBlock.
 *
 * This block uses the ContainerFactoryPluginInterface to handle dependency injection,
 * (using the service container) so the service is loaded outside of the build() method.
 *
 * ContainerFactoryPluginInterface only has one method you need to implement, which is
 * called create(). Create passes info along to the constructor that was loaded by the
 * service container. In this case I also loaded our block information in create().
 */

namespace Drupal\nasa\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\nasa\Apod;

/**
 * Provides a 'nasaApodBlock' block.
 *
 * @Block(
 *  id = "nasa_apod_block",
 *  admin_label = @Translation("NASA APOD"),
 * )
 */
class nasaApodBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\nasa\NasaApod definition.
   */
  protected $apod_info;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @var string $apod_info
   *   The information from the NASA APOD service for this block.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $apod_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->apod_info = $apod_info;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $element['#title'] = 'Astronomic Picture of the Day';
    $element['#image'] = $decoded->url;
    $element['#explanation'] = $decoded->explanation;
    $element['#apod_title'] = $decoded->title;

    $build = array(
      '#title' => 'Astronomic Picture of the Day',
      '#image' => $this->apod_info->url,
      '#explanation' => $this->apod_info->explanation,
      '#apod_title' => $this->apod_info->apod_title,
      '#theme' => 'nasa_apod_block'
    );
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      //this is not the only way to write this code. You may want to save the Service here instead of the string.
      $container->get('nasa.apod')->getApod()
    );
  }

}
