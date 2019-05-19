<?php

namespace Drupal\Tests\user_account_language_negotiation\Kernel;

use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\user\Entity\User;
use Drupal\user_account_language_negotiation\Plugin\LanguageNegotiation\LanguageNegotiationUserAccountSaver;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the LanguageNegotiationUserAccountSaver class.
 *
 * @group user_account_language_negotiation
 * @coversDefaultClass \Drupal\user_account_language_negotiation\Plugin\LanguageNegotiation\LanguageNegotiationUserAccountSaver
 */
class PluginTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user_account_language_negotiation',
  ];

  /**
   * A prophecy of a Symfony request.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  private $request;

  /**
   * The class we are testing.
   *
   * @var \Drupal\user_account_language_negotiation\Plugin\LanguageNegotiation\LanguageNegotiationUserAccountSaver
   */
  private $plugin;

  /**
   * Runs before each test.
   */
  protected function setUp() {
    parent::setUp();

    $language_manager = $this->prophesize(ConfigurableLanguageManagerInterface::class);
    $languages = [
      'Swahili' => $this->buildLanguage('Swahili'),
      'Zulu_UserDef' => $this->buildLanguage('Zulu_UserDefault'),
      'Bodo_SystemD' => $this->buildLanguage('Bodo_SystemDefault'),
    ];
    $language_manager->getDefaultLanguage()
      ->willReturn($languages['Bodo_SystemD']);
    $language_manager->getLanguages(Argument::any())->willReturn($languages);
    $language_manager->getNativeLanguages()->willReturn($languages);
    \Drupal::getContainer()
      ->set('language_manager', $language_manager->reveal());

    $this->plugin = new LanguageNegotiationUserAccountSaver();
    $this->plugin->setLanguageManager($language_manager->reveal());
    $this->request = $this->prophesize(Request::class);
    $request_params = $this->prophesize(ParameterBag::class);
    $request_params->all()->willReturn([]);
    $this->request->query = $request_params->reveal();
  }

  /**
   * Helper for creating a Language prophecy.
   *
   * @param string $lang_name
   *   Full name of language to add.
   *
   * @return \Drupal\Core\Language\Language
   *   The mock object
   */
  private function buildLanguage($lang_name) {
    $language = $this->prophesize(Language::class);
    $language->getName()->willReturn($lang_name);
    $language->getId()->willReturn(substr($lang_name, 0, 12));
    return $language->reveal();
  }

  /**
   * Test that the plugin has no opinion about anonymous users.
   *
   * @covers ::getLangcode
   */
  public function testGetLangcodeWhenAnonymous() {
    $anonymous = $this->prophesize(AccountInterface::class);
    $anonymous->isAuthenticated()->willReturn(FALSE);
    $anonymous->id()->willReturn(0);

    $this->plugin->setCurrentUser($anonymous->reveal());
    \Drupal::currentUser()->setAccount($anonymous->reveal());

    $this->request->getPathInfo()->willReturn('/node/48');
    $page_lang = $this->plugin->getLangcode($this->request->reveal());

    self::assertNull($page_lang);
  }

  /**
   * Check that path without language prefix returns the user's preferred lang.
   *
   * @covers ::getLangcode
   */
  public function testGetLangcodeWhenLoggedIn() {
    $lang = $this->getLangcode('/node/48');
    self::assertSame('Zulu_UserDef', $lang);
  }

  /**
   * Check if the path prefix language gets saved into the user account.
   *
   * @covers ::getLangcode
   */
  public function testSetLangcode() {
    $path_lang = $this->getLangcode('/Swahili/node/48');
    self::assertSame('Swahili', $path_lang);

    $user = User::load(1);
    $user_lang = $user->getPreferredLangcode(FALSE);
    self::assertSame('Swahili', $user_lang);
  }

  /**
   * @covers ::processInbound
   */
  public function testProcessInbound() {
    $path = $this->plugin->processInbound(
      '/animal/mouse',
      $this->request->reveal());
    self::assertSame('/animal/mouse', $path);

    $path = $this->plugin->processInbound(
      '/Swahili/animal/mouse',
      $this->request->reveal());
    self::assertSame('/animal/mouse', $path);
  }

  /**
   * @covers ::getLanguageSwitchLinks
   */
  public function testGetLanguageSwitchLinks() {
    $url = $this->prophesize(Url::class);

    $links = $this->plugin->getLanguageSwitchLinks($this->request->reveal(), '', $url->reveal());
    self::assertEquals([
      'Swahili',
      'Zulu_UserDef',
      'Bodo_SystemD',
    ], array_keys($links));

    $link = reset($links);
    $expected_keys = [
      'url',
      'title',
      'language',
      'attributes',
      'query',
    ];
    self::assertEquals($expected_keys, array_keys($link));
  }

  /**
   * Helper to call negotiation plugin's getLangcode function.
   *
   * @param string $path
   *   URL segment to send to plugin.
   *
   * @return string
   *   language code
   */
  private function getLangcode(string $path): string {
    $this->request->getPathInfo()->willReturn($path);

    $user = $this->createUser(['preferred_langcode' => 'Zulu_UserDef']);
    $this->plugin->setCurrentUser($user);
    \Drupal::currentUser()->setAccount($user);
    return $this->plugin->getLangcode($this->request->reveal());
  }

}
