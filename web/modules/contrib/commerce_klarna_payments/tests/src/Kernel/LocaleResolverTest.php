<?php

namespace Drupal\Tests\commerce_klarna_payments\Kernel;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\language\Kernel\LanguageTestBase;

/**
 * Locale resolver tests.
 *
 * @group commerce_klarna_paymnents
 * @coversDefaultClass \Drupal\commerce_klarna_payments\LocaleResolver
 */
class LocaleResolverTest extends LanguageTestBase {

  public static $modules = ['commerce_klarna_payments'];
  protected $languages = [];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->languages = [
      'fi' => 'fi-fi',
      'sv' => 'sv-sv',
      'nb' => 'nb-no',
      'nn' => 'nn-no',
      'de' => 'de-de',
      'at' => 'de-at',
      // Define some extra languages to test fallback.
      'es' => 'en-us',
      'it' => 'en-us',
    ];

    foreach ($this->languages as $language => $rfc) {
      $language = ConfigurableLanguage::createFromLangcode($language);
      $language->save();
    }
  }

  /**
   * Make sure we get proper locale.
   */
  public function testDefaultResolve() {
    // We don't actually have any logic around order.
    $order = $this->getMockBuilder(OrderInterface::class)
      ->getMock();

    $found = 0;
    foreach ($this->languages as $language => $rfc) {
      // Change default language.
      \Drupal::configFactory()
        ->getEditable('system.site')
        ->set('default_langcode', $language)
        ->save();

      /** @var \Drupal\commerce_klarna_payments\LocaleResolver $resolver */
      $resolver = $this->container->get('commerce_klarna_payments.locale_resolver');
      $resolved = $resolver->resolve($order);
      $this->assertEquals($rfc, $resolved);
      $found++;
    }

    // Make sure foreach actually ran.
    $this->assertTrue($found > 0);
  }

}
