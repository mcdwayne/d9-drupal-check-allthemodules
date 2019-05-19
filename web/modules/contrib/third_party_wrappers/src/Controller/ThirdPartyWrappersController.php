<?php

namespace Drupal\third_party_wrappers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\third_party_wrappers\ThirdPartyWrappersInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines a controller for Third Party Wrappers.
 */
class ThirdPartyWrappersController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The Third Party Wrappers service.
   *
   * @var \Drupal\third_party_wrappers\ThirdPartyWrappersInterface
   */
  protected $thirdPartyWrappers;

  /**
   * The HTTP client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('third_party_wrappers'),
      $container->get('http_client'),
      $container->get('module_handler'),
      $container->get('messenger')
    );
  }

  /**
   * The controller for delivering wrapper segments.
   *
   * @param \Drupal\third_party_wrappers\ThirdPartyWrappersInterface $third_party_wrappers
   *   The Third Party Wrappers service.
   * @param \GuzzleHttp\Client $client
   *   The HTTP client service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(ThirdPartyWrappersInterface $third_party_wrappers, Client $client, ModuleHandlerInterface $module_handler, MessengerInterface $messenger) {
    $this->thirdPartyWrappers = $third_party_wrappers;
    $this->client = $client;
    $this->moduleHandler = $module_handler;
    $this->messenger = $messenger;

  }

  /**
   * Requests and outputs a Third Party Wrappers page.
   *
   * @param string $action
   *   Three possible actions:
   *   - top: delivers everything before the delimiter, i.e., the header.
   *   - bottom: delivers everything after the delimiter, i.e., the footer.
   *   - all: delivers everything, delimiter included.
   *
   * @return mixed
   *   Either a Response object if successful, or a render array otherwise.
   */
  public function wrapperOutput($action) {
    // Build the URL to request.
    $url = 'base://';
    // Use the default page if none was given.
    if (empty($_REQUEST['url'])) {
      $url .= 'third-party-wrappers';
    }
    else {
      $url .= $_REQUEST['url'];
    }
    // Convert "base://page" into "http://example.com/page".
    $url = Url::fromUri($url, ['absolute' => TRUE])->toString();
    try {
      // HTTP GET request the page.
      $response = $this->client->get($url);
    }
    // Display any HTTP errors to the user so they know the request failed.
    catch (RequestException $e) {
      $this->messenger->addError($e->getMessage());
      return $this->getReturnStatus('500 Internal Server Error');
    }

    if (empty($response)) {
      $this->messenger->addError($this->t('Empty response.'));
      return $this->getReturnStatus('500 Internal Server Error');
    }
    $contents = $response->getBody()->getContents();




    // Copy aggregated CSS and JS files into a separate file folder.
    $this->thirdPartyWrappers->copyFiles($contents, 'js');
    $this->thirdPartyWrappers->copyFiles($contents, 'css');

    $public_paths = $this->thirdPartyWrappers->getFilePaths();
    $files_path = $public_paths['files_path'];
    $files_path_esc = $public_paths['files_path_esc'];

    // Rewrite CSS and JS file URLs to use the Third Party Wrappers storage.
    $third_party_wrappers_dir = $this->thirdPartyWrappers->getDir();
    $search = [
      '/' . $files_path_esc . '\/css\/css_/is',
      '/' . $files_path_esc . '\/js\/js_/is',
      '/src="\//i',
      '/href="\//i',
    ];
    $replace = [
      $files_path . '/' . $third_party_wrappers_dir . '/css/css_',
      $files_path . '/' . $third_party_wrappers_dir . '/js/js_',
      'src="http://' . $_SERVER['SERVER_NAME'] . '/',
      'href="http://' . $_SERVER['SERVER_NAME'] . '/',
    ];
    if ($this->moduleHandler->moduleExists('advagg')) {
      $search[] = '/' . $files_path_esc . '\/advagg_css\/css_/is';
      $search[] = '/' . $files_path_esc . '\/advagg_js\/js_/is';
      $replace[] = $files_path . '/' . $third_party_wrappers_dir . '/advagg_css/css_';
      $replace[] = $files_path . '/' . $third_party_wrappers_dir . '/advagg_js/js_';
    }
    $contents = preg_replace($search, $replace, $contents);

    // Return all of the contents, with no modification.
    if ($action == 'all') {
      return new Response($contents, 200);
    }
    // Make sure there is a string set.
    $split_on = $this->thirdPartyWrappers->getSplitOn();
    if (empty($split_on)) {
      $this->messenger->addError($this->t('Please configure a content marker string for Third Party Wrappers.'));
      return $this->getReturnStatus('500 Internal Server Error');
    }
    // Split the content by the user-defined string.
    $contents = explode($split_on, $contents);
    // Return the contents from before the string.
    if ($action == 'top') {
      return new Response($contents[0], 200);
    }
    // Return the contents from after the string.
    if ($action == 'bottom') {
      return new Response($contents[1], 200);
    }
    $this->messenger->addError($this->t('No valid action found. Valid actions are top, bottom, or all.'));

    return $this->getReturnStatus('400 Bad Request');
  }

  /**
   * Outputs the default split string.
   *
   * @return array
   *   The default split string, as a render array.
   */
  public function defaultPage() {
    return [
      '#theme' => 'third_party_wrappers',
      '#marker' => $this->thirdPartyWrappers->getSplitOn(),
    ];
  }

  /**
   * Builds a render array with an attached HTTP status code.
   *
   * @param string $status
   *   (optional) A HTTP status string to use. It should include both the error
   *   code and the description.
   *
   * @return array
   *   A render array.
   */
  protected function getReturnStatus($status = '200 OK') {
    return [
      '#attached' => [
        'http_header' => [
          ['Status', $status],
        ],
      ],
    ];
  }

}
