<?php

namespace Drupal\tagadelic\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to display a tag cloud.
 *
 * @Block(
 *   id = "tagadelic_block",
 *   admin_label = @Translation("Tagadelic tag cloud"),
 *   category = @Translation("Taxonomy")
 * )
 */
class TagadelicBlock extends BlockBase implements ContainerFactoryPluginInterface {
  
  /**
   * Drupal\tagadelic\TagadelicCloud definition.
   */
  protected $tags;

  /**
   * Creates a TagadelicBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $tags
   *   The tags from the cloud service for this block..
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $tags) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tags = $tags;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tagadelic.tagadelic_taxonomy')->getTags()
    );
  }

  /**
  * {@inheritdoc}
  */
  public function build() {
    $config = $this->configuration;
    $tags = array_slice($this->tags, 0, $config['num_tags_block']);

    return array(
      '#theme' => 'tagadelic_taxonomy_cloud',
      '#tags' => $tags,
      '#attached' => array(
        'library' =>  array(
          'tagadelic/base'
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'num_tags_block' => 5,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->configuration;
    $options = range(0, 50);

    $form['num_tags_block'] = array(
      '#type' => 'select',
      '#title' => $this->t('Number of tags to display'),
      '#default_value' => $config['num_tags_block'],
      '#options' => $options,
      '#description' => $this->t('This will be the number of tags displayed in the block.'),
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['num_tags_block'] = $form_state->getValue('num_tags_block');
  }
}
?>
