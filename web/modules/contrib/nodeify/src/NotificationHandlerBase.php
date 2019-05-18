<?php

namespace Drupal\nodeify;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

abstract class NotificationHandlerBase implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenUtility;

  /**
   * NotificationHandlerBase constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Utility\Token $token_utility
   */
  public function __construct(ConfigFactoryInterface $config_factory, Token $token_utility) {
    $this->configFactory = $config_factory;
    $this->tokenUtility = $token_utility;
  }

  /**
   * Perform placeholder replacements on text.
   *
   * @param $text
   *   A string with either token module tokens or twig tokens.
   * @param array $data
   *   An array of objects to be used for tokens or as twig context.
   *
   * @return mixed
   */
  protected function process($text, $data = []) {
    $element = [
      '#type' => 'inline_template',
      '#template' => $text,
      '#context' => $data,
    ];
    $text = \Drupal::service('renderer')->renderPlain($element);
    return $this->tokenUtility->replace($text->__toString(), $data);
  }


}
