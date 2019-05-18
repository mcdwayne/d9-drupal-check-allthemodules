<?php

/**
 * @file
 * Contains \Drupal\calc\Plugin\Block\CalcBlock
 */

namespace Drupal\calc\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\calc\Form\CalcBlockForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;


/**
 * Provides a Calc block.
 *
 * @Block(
 *  id = "calc_block",
 *  admin_label = @Translation("Calc Block")
 * )
 */
//implements ContainerFactoryPluginInterface
class CalcBlock extends BlockBase implements ContainerFactoryPluginInterface{
 

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('request'));
  }

  /**
   * Constructs a SearchBlock object.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->request = $request;
  }

  /**
   * Overrides \Drupal\block\BlockBase::access().
   */
  public function access() {
    return user_access('search content');
  }

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    return drupal_get_form(new CalcBlockForm(), $this->request);
  }

}