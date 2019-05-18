<?php

namespace Drupal\yp\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Default controller for the yp module.
 */
class YpController extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  /**
   * Constructs a YpController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
    $this->logger = $this->getLogger('yp');
  }

  /**
   * Endpoint for YP CGI requests.
   */
  public function cgi(Request $request) {
    $action = isset($_REQUEST['action']) && is_string($_REQUEST['action']) ? trim($_REQUEST['action']) : '';
    if ($this->config('yp.settings')->get('debug')) {
      $this->logger->debug('YP %action: %request', [
        '%action' => $action,
        '%request' => print_r($_REQUEST, TRUE),
      ]);
    }
    if (!in_array($action, ['add', 'remove', 'touch'])) {
      throw new AccessDeniedHttpException();
    }
    $this->response = new Response();
    $this->fields['last_touch'] = REQUEST_TIME;
    $this->sid = isset($_REQUEST['sid']) && is_string($_REQUEST['sid']) ? (int) $_REQUEST['sid'] : NULL;
    $this->$action($request);
    return $this->response;
  }

  /**
   * Builds a mapping from request parameters to database columns.
   */
  public function buildFields($map) {
    $schema = drupal_get_module_schema('yp', 'yp_stream');
    foreach ($map as $key => $variables) {
      $this->fields[$key] = $schema['fields'][$key]['type'] == 'varchar' ? '' : 0;
      foreach ($variables as $variable) {
        if (isset($_REQUEST[$variable]) && is_string($_REQUEST[$variable])) {
          $this->fields[$key] = $schema['fields'][$key]['type'] == 'varchar' ? (Unicode::validateUtf8($_REQUEST[$variable]) ? trim($_REQUEST[$variable]) : '') : (int) $_REQUEST[$variable];
        }
      }
    }
  }

  /**
   * Adds a new stream.
   */
  public function add(Request $request) {
    $this->buildFields([
      'server_name' => ['sn'],
      'server_type' => ['type'],
      'genre' => ['genre'],
      'bitrate' => ['audio_bitrate', 'b', 'bitrate', 'ice-bitrate'],
      'samplerate' => ['audio_samplerate', 'samplerate', 'ice-samplerate'],
      'channels' => ['audio_channels', 'channels', 'ice-channels'],
      'listen_url' => ['listenurl'],
      'description' => ['desc'],
      'url' => ['url'],
      'cluster_password' => ['cpswd'],
    ]);
    $this->fields['listing_ip'] = $request->getClientIP();
    $this->sid = $this->database->insert('yp_stream')->fields($this->fields)->execute();
    $this->response->headers->set('SID', $this->sid);
    $this->response->headers->set('TouchFreq', 200);
    $this->response->headers->set('YPMessage', $this->sid ? 'Added' : 'Error');
    $this->response->headers->set('YPResponse', $this->sid ? 1 : 0);
  }

  /**
   * Touches (updates) a stream.
   */
  public function touch(Request $request) {
    $this->buildFields([
      'listeners' => ['listeners'],
      'max_listeners' => ['max_listeners'],
      'server_subtype' => ['stype'],
      'current_song' => ['st'],
    ]);
    $this->touched = $this->database->update('yp_stream')->fields($this->fields)->condition('sid', $this->sid)->execute();
    $this->response->headers->set('YPMessage', $this->touched ? 'Touched' : 'SID not found');
    $this->response->headers->set('YPResponse', $this->touched ? 1 : 0);
  }

  /**
   * Removes a stream.
   */
  public function remove(Request $request) {
    $this->database->delete('yp_stream')->condition('sid', $this->sid)->execute();
    $this->response->headers->set('YPMessage', 'Removed');
    $this->response->headers->set('YPResponse', 1);
  }

}
