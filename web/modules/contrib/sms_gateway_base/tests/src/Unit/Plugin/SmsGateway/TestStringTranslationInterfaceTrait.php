<?php

namespace Drupal\Tests\sms_gateway_base\Unit\Plugin\SmsGateway;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Prophecy\Argument;

/**
 * Provides a mock StringTranslationInterface for core plugin unit tests.
 */
trait TestStringTranslationInterfaceTrait {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    // Mock \Drupal::service('string_translation')::translateString() so that
    // StringTranslationTrait would work.
    $string_translation = $this->prophesize(TranslationInterface::class);
    $string_translation->translateString(Argument::type(TranslatableMarkup::class))->will(function (array $arguments) {
      /** \Drupal\Core\StringTranslation\TranslatableMarkup[] $arguments */
      return $arguments[0]->getUntranslatedString();
    });
    $container = new ContainerBuilder();
    $container->set('string_translation', $string_translation->reveal());
    \Drupal::setContainer($container);
  }

}
