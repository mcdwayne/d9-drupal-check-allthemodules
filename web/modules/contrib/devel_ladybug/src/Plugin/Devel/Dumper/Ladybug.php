<?php

namespace Drupal\devel_ladybug\Plugin\Devel\Dumper;

use Drupal\devel\DevelDumperBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Ladybug\Dumper;

/**
 * Provides a Ladybug dumper plugin.
 *
 * @DevelDumper(
 *   id = "ladybug",
 *   label = @Translation("Ladybug"),
 *   description = @Translation("Wrapper for <a href='https://github.com/raulfraile/ladybug'>Ladybug</a> debugging tool."),
 * )
 */
class Ladybug extends DevelDumperBase implements ContainerFactoryPluginInterface {

  private $dumper;
  private $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (!$this->dumper) {
      $this->dumper = new Dumper();
      $this->dumper->setTheme('modern');
    }
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function dump($input, $name = NULL) {
    echo (string) $this->export($input);
  }

  /**
   * {@inheritdoc}
   */
  public function export($input, $name = NULL) {
    if ($this->currentUser->hasPermission('access ladybug')) {
      ob_start();
      $this->dumper->setFormat('html');
      echo $this->dumper->dump($input);
      return $this->setSafeMarkup(ob_get_clean());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function checkRequirements() {
    return class_exists('\Ladybug\Dumper') && class_exists('\phpDocumentor\Reflection\DocBlock');
  }

}
