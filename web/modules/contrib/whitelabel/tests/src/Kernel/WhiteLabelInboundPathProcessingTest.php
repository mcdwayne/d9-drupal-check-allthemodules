<?php

namespace Drupal\Tests\whitelabel\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\whitelabel\Traits\WhiteLabelCreationTrait;
use Drupal\whitelabel\PathProcessor\WhiteLabelPathProcessor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Tests if the correct style sheets are applied to a page.
 *
 * @group whitelabel
 */
class WhiteLabelInboundPathProcessingTest extends KernelTestBase {

  use WhiteLabelCreationTrait {
    createWhiteLabel as drupalCreateWhiteLabel;
  }
  use UserCreationTrait {
    createUser as drupalCreateUser;
  }

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'text',
    'options',
    'user',
    'file',
    'image',
    'whitelabel',
    'node',
  ];

  /**
   * Holds the generated white label throughout the different tests.
   *
   * @var \Drupal\whitelabel\WhiteLabelInterface
   */
  private $whiteLabel;

  /**
   * Holds the randomly generated token.
   *
   * @var string
   */
  private $token;

  const WHITE_LABEL_TOKEN = 'cool_whitelabel_token';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installConfig(['whitelabel']);
    $this->installEntitySchema('whitelabel');
    $this->installEntitySchema('user');

    $user = $this->drupalCreateUser(['serve white label pages']);
    $this->setCurrentUser($user);

    $this->token = self::WHITE_LABEL_TOKEN;
    $this->whiteLabel = $this->createWhiteLabel(['token' => $this->token]);
    $this->whiteLabelProvider = \Drupal::service('whitelabel.whitelabel_provider');

    // Set the global white label configuration.
    $this->config('whitelabel.settings')->set('domain', 'localhost')->save();
  }

  /**
   * Test for the inbound query parameter mode.
   *
   * @dataProvider provideTokensBasicTests
   */
  public function testInboundQueryParameter($token = NULL, $expect_whitelabel = TRUE) {
    $mode = WhiteLabelPathProcessor::CONFIG_QUERY_PARAMETER;

    // Ensure White Label is empty.
    $this->assertNull($this->whiteLabelProvider->getWhiteLabel());

    // Set the test specific white label configuration.
    $this->config('whitelabel.settings')->set('mode', $mode)->save();

    list ($path, $request) = $this->generateRequest($mode, $token);
    $new_path = $this->processInbound($path, $request);

    // Ensure White Label equals the expected one.
    if ($expect_whitelabel) {
      $this->assertEquals($this->whiteLabel->id(), $this->whiteLabelProvider->getWhiteLabel()->id());
    }
    else {
      $this->assertNull($this->whiteLabelProvider->getWhiteLabel());
    }
  }

  /**
   * Test for the inbound domain mode.
   *
   * @dataProvider provideTokensBasicTests
   */
  public function testInboundDomain($token = NULL, $expect_whitelabel = TRUE, $expect_404 = FALSE) {
    $mode = WhiteLabelPathProcessor::CONFIG_DOMAIN;

    // Ensure White Label is empty.
    $this->assertNull($this->whiteLabelProvider->getWhiteLabel());

    // Set the test specific white label configuration.
    $this->config('whitelabel.settings')->set('mode', $mode)->save();

    list ($path, $request) = $this->generateRequest($mode, $token);
    $new_path = $this->processInbound($path, $request, $expect_404);

    // Ensure White Label equals the expected one.
    if ($expect_whitelabel) {
      $this->assertEquals($this->whiteLabel->id(), $this->whiteLabelProvider->getWhiteLabel()->id());
    }
    else {
      $this->assertNull($this->whiteLabelProvider->getWhiteLabel());
    }
  }

  /**
   * Data provider.
   *
   * @return array
   *   An array with a token, expected white label and expected 404.
   */
  public function provideTokensBasicTests() {
    return [
      // Existing token.
      [self::WHITE_LABEL_TOKEN, TRUE, FALSE],
      // Non-existing token.
      ['someFakeToken', FALSE, TRUE],
    ];
  }

  /**
   * Test for the inbound path prefix mode.
   *
   * @dataProvider provideTokensPathPrefix
   */
  public function testInboundPathPrefix($token, $expect_whitelabel = TRUE, $expect_404 = FALSE) {
    $mode = WhiteLabelPathProcessor::CONFIG_PATH_PREFIX;

    // Ensure White Label is empty.
    $this->assertNull($this->whiteLabelProvider->getWhiteLabel());

    // Set the test specific white label configuration.
    $this->config('whitelabel.settings')->set('mode', $mode)->save();

    list ($path, $request) = $this->generateRequest($mode, $token);
    $new_path = $this->processInbound($path, $request, $expect_404);

    // Ensure White Label equals the expected one.
    if ($expect_whitelabel) {
      $this->assertEquals($this->whiteLabel->id(), $this->whiteLabelProvider->getWhiteLabel()->id());
    }
    else {
      $this->assertNull($this->whiteLabelProvider->getWhiteLabel());
    }
  }

  /**
   * Data provider.
   *
   * @return array
   *   An array with a token, expected white label and expected 404.
   */
  public function provideTokensPathPrefix() {
    return [
      // Existing token.
      [self::WHITE_LABEL_TOKEN, TRUE, FALSE],
      // Non-existing token.
      ['someFakeToken', FALSE, TRUE],
      // Tricky tokens.
      ['admin', FALSE, TRUE],
      ['node', FALSE, TRUE],
      ['user', FALSE, TRUE],
      ['system', FALSE, TRUE],
    ];
  }

  /**
   * Test to see if tokens based on system paths are correctly resolved.
   *
   * @dataProvider provideTokensTrickyTokenTests
   */
  public function testTrickyTokens($mode, $tricky_token, $tricky_path) {
    // Ensure White Label is empty.
    $this->assertNull($this->whiteLabelProvider->getWhiteLabel());

    // Set the test specific white label configuration.
    $this->config('whitelabel.settings')->set('mode', $mode)->save();

    $this->whiteLabel->setToken($tricky_token)->save();

    list ($path, $request) = $this->generateRequest($mode, $tricky_token, $tricky_path);
    $this->processInbound($path, $request);

    $this->assertEquals($this->whiteLabel->id(), $this->whiteLabelProvider->getWhiteLabel()->id(), 'Asserting token: ' . $tricky_token . ' for path: ' . $tricky_path);
  }

  /**
   * Data provider.
   *
   * @return array
   *   An array with a mode, white label token and a path.
   */
  public function provideTokensTrickyTokenTests() {
    $modes = [
      WhiteLabelPathProcessor::CONFIG_QUERY_PARAMETER,
      WhiteLabelPathProcessor::CONFIG_PATH_PREFIX,
      WhiteLabelPathProcessor::CONFIG_DOMAIN,
    ];

    $tricky_tokens = [
      'admin',
      'node',
      'system',
      'user',
    ];

    $return = [];

    foreach ($modes as $mode) {
      foreach ($tricky_tokens as $tricky_token) {
        foreach ($tricky_tokens as $tricky_path) {
          $return[] = [$mode, $tricky_token, $tricky_path];
        }
      }
    }
    return $return;
  }

  /**
   * Test to see if less privileged user gets a no WL page.
   *
   * @dataProvider provideModes
   */
  public function testViewerNoPermissions($mode) {
    // Test with unprivileged user.
    $white_label_viewer = $this->drupalCreateUser();
    $this->setCurrentUser($white_label_viewer);

    // Ensure White Label is empty.
    $this->assertNull($this->whiteLabelProvider->getWhiteLabel());

    // Set the test specific white label configuration.
    $this->config('whitelabel.settings')->set('mode', $mode)->save();

    $expect_404 = FALSE;
    if ($mode == WhiteLabelPathProcessor::CONFIG_PATH_PREFIX || $mode == WhiteLabelPathProcessor::CONFIG_DOMAIN) {
      $expect_404 = TRUE;
    }

    list ($path, $request) = $this->generateRequest($mode, $this->token);
    $this->processInbound($path, $request, $expect_404);

    // Ensure White Label is empty.
    $this->assertNull($this->whiteLabelProvider->getWhiteLabel());
  }

  /**
   * Test to see if less privileged owner serves a no WL page.
   *
   * @dataProvider provideModes
   */
  public function testOwnerNoPermissions($mode) {
    // Test with regular viewer account.
    $white_label_viewer = $this->drupalCreateUser(['view white label pages']);
    $this->setCurrentUser($white_label_viewer);

    // Change the owner of the white label to a less privileged one.
    $white_label_owner = $this->drupalCreateUser();
    $this->whiteLabel->setOwner($white_label_owner)->save();

    // Ensure White Label is empty.
    $this->assertNull($this->whiteLabelProvider->getWhiteLabel());

    // Set the test specific white label configuration.
    $this->config('whitelabel.settings')->set('mode', $mode)->save();

    $expect_404 = FALSE;
    if ($mode == WhiteLabelPathProcessor::CONFIG_PATH_PREFIX || $mode == WhiteLabelPathProcessor::CONFIG_DOMAIN) {
      $expect_404 = TRUE;
    }

    list ($path, $request) = $this->generateRequest($mode, $this->token);
    $this->processInbound($path, $request, $expect_404);

    // Ensure White Label is empty.
    $this->assertNull($this->whiteLabelProvider->getWhiteLabel());
  }

  /**
   * Data provider for tests.
   *
   * @return array
   *   Return an array with White label modes
   */
  public function provideModes() {
    return [
      [
        WhiteLabelPathProcessor::CONFIG_QUERY_PARAMETER,
      ],
      [
        WhiteLabelPathProcessor::CONFIG_PATH_PREFIX,
      ],
      [
        WhiteLabelPathProcessor::CONFIG_DOMAIN,
      ],
    ];
  }

  /**
   * Constructs an incoming request for the given mode and token.
   *
   * @param string $mode
   *   The white label mode to return the request for.
   * @param string|null $token
   *   The token to use when constructing the request, or NULL to use default.
   * @param string $base_path
   *   The base path to take into account while generating the request.
   *
   * @return array
   *   An array with the uri and the Symfony\Component\HttpFoundation\Request.
   */
  protected function generateRequest($mode, $token, $base_path = '') {
    $path = $base_path;

    switch ($mode) {
      case WhiteLabelPathProcessor::CONFIG_QUERY_PARAMETER:
        $request = Request::create($path, 'GET', ['whitelabel' => $token]);
        break;

      case WhiteLabelPathProcessor::CONFIG_PATH_PREFIX:
        $path = empty($token) ? $path : $token . '/' . $path;
        $request = Request::create($path, 'GET');
        break;

      case WhiteLabelPathProcessor::CONFIG_DOMAIN:
        $http_host = empty($token) ? 'localhost' : $token . '.localhost';
        $request = Request::create($path, 'GET', [], [], [], ['HTTP_HOST' => $http_host]);
        break;
    }

    return [$path, $request];
  }

  /**
   * Processes the inbound request.
   *
   * Under normal circumstances, this should set the white label as defined in
   * the inbound path processor. After calling this, we can assert that the
   * white label has indeed been set.
   *
   * @see \Drupal\whitelabel\PathProcessor\WhiteLabelPathProcessor
   *
   * @param string $path
   *   The path to process.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The constructed request to test with.
   * @param bool $expect_404
   *   Boolean indicating if a NotFoundHttpException is expected.
   *
   * @return null|string
   *   Returns the path provided by the path processors, or NULL on faillure.
   *
   * @throws \Exception
   */
  protected function processInbound($path, Request $request, $expect_404 = FALSE) {
    try {
      $new_path = $this->container->get('path_processor_manager')->processInbound($path, $request);
      return $new_path;
    }
    // Catch all NotFoundHttpExceptions.
    catch (NotFoundHttpException $exception) {
      // If an expected NotFoundHttpException was caught, all is well.
      // Otherwise, throw a new exception.
      if (!$expect_404) {
        throw new \Exception('NotFoundHttpException was thrown, but this exception was not expected. ' . $exception->getMessage());
      }
    }
    // Also relay all other encountered exceptions.
    catch (\Exception $exception) {
      throw new \Exception('Unexpected exception encountered: ' . $exception->getMessage());
    }

    return NULL;
  }

}
