<?php

namespace Drupal\algolia_search_custom\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Entity\File;

use Drupal\image\Entity\ImageStyle;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;

/**
 * Provides a 'AlgoliaSearchBlock' block.
 *
 * @Block(
 *  id = "algolia_search_custom_block",
 *  admin_label = @Translation("Algolia Search block"),
 * )
 */
class AlgoliaSearchBlock extends BlockBase implements ContainerFactoryPluginInterface
{
  /**
   * @var FormBuilderInterface $formBuilder
   */
  protected $formBuilder;

  /**
   *
   * @var StateInterface $state
   */
  protected $state;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $formBuilder, StateInterface $state)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $formBuilder;
    $this->state       = $state;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $bandeau_url = null;
    $settingsName = 'algolia_search_custom_settings_';

    $settings = [
      'appId' => $this->state->get($settingsName . 'app_id'),
      'apiKey' => $this->state->get($settingsName . 'api_key'),
      'indexName' => $this->state->get($settingsName . 'index_name'),
    ];


    $form = $this->formBuilder->getForm('Drupal\algolia_search_custom\Form\AlgoliaSearchForm');
    $data = [
      '#theme'       => 'algolia_block',
      '#form'        => $form,
      '#attached'    => [
        'library'        => ['algolia_search_custom/algolia_search_custom.search_block'],
        'drupalSettings' => [
          'settings' => $settings,
        ],
      ],
    ];

    return $data;
  }


}
