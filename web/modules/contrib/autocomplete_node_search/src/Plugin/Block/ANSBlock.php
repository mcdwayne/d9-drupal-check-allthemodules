<?php

namespace Drupal\autocomplete_node_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\autocomplete_node_search\Form\AutocompleteNodeSearch;

/**
 * Provides Autocomplete Node Search Block.
 *
 * @Block(
 *   id = "ans_block",
 *   admin_label = @Translation("Autocomplete Search Node"),
 *   category = @Translation("Blocks")
 * )
 */
class ANSBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $formBuilder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = $this->formBuilder->getForm(AutocompleteNodeSearch::class);
    return $form;
  }

}
