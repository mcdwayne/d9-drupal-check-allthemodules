<?php

namespace Drupal\authorization_code\Plugin\CodeGenerator;

use Drupal\authorization_code\CodeGeneratorInterface;
use Drupal\authorization_code\Exceptions\BrokenPluginException;
use Drupal\authorization_code\Plugin\AuthorizationCodePluginBase;

/**
 * Broken implementation of code generator plugin.
 *
 * @CodeGenerator(
 *   id = "broken",
 *   title = @Translation("Broken / Missing")
 * )
 */
class Broken extends AuthorizationCodePluginBase implements CodeGeneratorInterface {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\authorization_code\Exceptions\BrokenPluginException
   */
  public function generate(): string {
    throw new BrokenPluginException('code_generator', $this->configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function isBroken(): bool {
    return TRUE;
  }

}
