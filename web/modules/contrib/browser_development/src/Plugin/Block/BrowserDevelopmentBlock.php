<?php

namespace Drupal\browser_development\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'BrowsersDevelopmentBlock' block.
 *
 * @Block(
 *  id = "browser_development_block",
 *  admin_label = @Translation("Browser development block"),
 * )
 */
class BrowserDevelopmentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   *
   * @var type
   */
  protected $formBuilder;

  /**
   *
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $formBuilder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $formBuilder;
  }


  /**
   *
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
    $build = [];
    $build['browser_development_css_form']= $this->formBuilder->getForm('Drupal\browser_development\Form\BrowserCssForm');
    $build['browser_development_image_css_form']= $this->formBuilder->getForm('Drupal\browser_development\Form\BackgroundImageForm');
    return $build;
  }

}
