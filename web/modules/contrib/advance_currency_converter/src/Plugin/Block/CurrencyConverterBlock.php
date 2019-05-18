<?php

namespace Drupal\advance_currency_converter\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilderInterface;

/**
 * Provides a 'article' block.
 *
 * @Block(
 *   id = "currency_converter",
 *   admin_label = @Translation("Currency Converter block"),
 *   category = @Translation("Currency converter block")
 * )
 */
class CurrencyConverterBlock extends BlockBase implements ContainerFactoryPluginInterface {
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['form'] = $this->formBuilder->getForm('Drupal\advance_currency_converter\Form\FrontPanel');
    // This will attach the library file of css and js into the block.
    $build['#attached']['library'][] = 'advance_currency_converter/currency-check';
    $build['#cache'] = ['tags' => ['advance_currency_converter:currency']];
    return $build;

  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form;
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

}
