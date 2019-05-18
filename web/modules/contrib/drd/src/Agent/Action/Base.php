<?php

namespace Drupal\drd\Agent\Action;

use Drupal\drd\Agent\Auth\BaseInterface as AuthBaseInterface;
use Drupal\drd\Agent\Auth\Base as AuthBase;
use Drupal\drd\Agent\Remote\Base as RemoteBase;

/**
 * Base class for Remote DRD Action Code.
 */
abstract class Base implements BaseInterface {

  private static $debugMode = FALSE;
  private static $arguments = array();

  const SEC_AUTH_ACQUIA = 'Acquia';
  const SEC_AUTH_PANTHEON = 'Pantheon';
  const SEC_AUTH_PLATFORMSH = 'PlatformSH';

  /**
   * Crypt object for this DRD request.
   *
   * @var \Drupal\drd\Crypt\BaseMethodInterface
   */
  protected static $crypt;

  /**
   * Recursivly convert request arguments to an array.
   *
   * @param mixed $items
   *   Arguments to convert.
   *
   * @return mixed
   *   Array of all the given arguments.
   */
  private static function toArray($items) {
    foreach ($items as $key => $item) {
      if (is_object($item)) {
        $items[$key] = self::toArray((array) $item);
      }
      elseif (is_array($item)) {
        $items[$key] = self::toArray($item);
      }
    }
    return $items;
  }

  /**
   * Read and decode the input from the POST request.
   *
   * @param int $version
   *   Main Drupal core version, between and including 6 to 8.
   * @param bool $debugMode
   *   Whether we operate in debug mode.
   * @param string $message
   *   Optional warning to output in watchdog.
   *
   * @return array
   *   The decoded input.
   *
   * @throws \Exception
   */
  private static function readInput($version, $debugMode, $message = NULL) {
    self::setDebugMode($debugMode);
    drd_agent_require_once(DRD_BASE . "/Action/V$version/Base.php");
    drd_agent_require_once(DRD_BASE . "/Auth/V$version/Base.php");
    RemoteBase::loadClasses($version);
    if (isset($message)) {
      /* @var \Drupal\drd\Agent\Action\BaseInterface $self */
      $self = "\\Drupal\\drd\\Agent\\Action\\V$version\\Base";
      $self::watchdog($message, array(), 4);
    }

    $raw_input = file_get_contents('php://input');
    if (empty($raw_input)) {
      throw new \Exception('Can not read input');
    }

    $input = json_decode(base64_decode($raw_input), TRUE);
    if (!is_array($input) || empty($input)) {
      throw new \Exception('Input is empty');
    }

    return $input;
  }

  /**
   * Main callback to execute an action.
   *
   * @param int $version
   *   Main Drupal core version, between and including 6 to 8.
   * @param bool $debugMode
   *   Whether we operate in debug mode.
   *
   * @return string|mixed
   *   Encrypted and base64 encoded result from the executed action.
   */
  public static function run($version, $debugMode = FALSE) {
    /* @var \Drupal\drd\Agent\Action\BaseInterface $self */
    $self = "\\Drupal\\drd\\Agent\\Action\\V$version\\Base";

    try {
      $input = self::readInput($version, $debugMode);

      if (empty($input['uuid']) || empty($input['args']) || !isset($input['iv'])) {
        throw new \Exception('Input is incomplete');
      }
      $input['args'] = base64_decode($input['args']);
      $input['iv'] = base64_decode($input['iv']);

      if (!empty($input['ott']) && !empty($input['config'])) {
        if (!$self::ott($input['ott'], $input['config'])) {
          throw new \Exception('OTT config failed');
        }
        return 'ok';
      }

      if (!empty($input['auth']) && !empty($input['authsetting'])) {
        self::authenticate($version, $input['uuid'], $input);
      }

      self::$crypt = $self::getCryptInstance($input['uuid']);
      if (!self::$crypt) {
        throw new \Exception('Encryption method not available or unauthorised');
      }
      $args = self::toArray(self::$crypt->decrypt($input['args'], $input['iv']));

      if (empty($args['auth']) || !isset($args['authsetting']) || empty($args['action'])) {
        throw new \Exception('Arguments incomplete');
      }

      if (empty($input['auth'])) {
        // Let's authenticate here if we haven't yet authenticated
        // before decryption.
        self::authenticate($version, $input['uuid'], $args);
      }

      $action = $args['action'];
      $actionModule = $args['drd_action_module'];
      if (isset($args['drd_action_plugin'])) {
        $actionFile = drupal_realpath('temporary://v' . $version . '_' . $action . '.php');
        file_put_contents($actionFile, $args['drd_action_plugin']);
        unset($args['drd_action_plugin']);
      }
      else {
        $actionFile = DRD_BASE . "/Action/V$version/$action.php";
      }
      unset($args['auth']);
      unset($args['authsetting']);
      unset($args['action']);
      unset($args['drd_action_module']);
      self::$arguments = $args;
    }
    catch (\Exception $ex) {
      $self::watchdog($ex->getMessage(), array(), 3);
      header("HTTP/1.1 502 Error");
      print 'error';
      exit;
    }

    try {
      $self::promoteUser();

      drd_agent_require_once($actionFile);
      $classname = "\\Drupal\\$actionModule\\Agent\\Action\\V$version\\$action";

      /** @var \Drupal\drd\Agent\Action\BaseInterface $actionObject */
      $actionObject = new $classname();
    }
    catch (\Exception $ex) {
      $self::watchdog('Not yet implemented: ' . $action, array(), 3);
      header("HTTP/1.1 403 Not found");
      print 'Not yet implemented';
      exit;
    }

    $result = $actionObject->execute();
    if (is_array($result)) {
      $result['messages'] = drupal_get_messages();
      return base64_encode(self::$crypt->encrypt($result));
    }
    return $result;
  }

  /**
   * Authenticate the request or throw an exception.
   *
   * @param int $version
   *   Drupal version.
   * @param string $uuid
   *   The uuid of the calling DRD instance.
   * @param array $args
   *   Array of arguments.
   *
   * @throws \Exception
   */
  private static function authenticate($version, $uuid, array $args) {
    $auth_methods = AuthBase::getMethods($version);
    if (!isset($auth_methods[$args['auth']]) || !($auth_methods[$args['auth']] instanceof AuthBaseInterface)) {
      throw new \Exception('Unrecognized authentication method');
    }

    /** @var \Drupal\drd\Agent\Auth\BaseInterface $auth */
    $auth = $auth_methods[$args['auth']];
    if (!$auth->validateUuid($uuid)) {
      throw new \Exception('DRD instance not registered');
    }
    if (!$auth->validate($args['authsetting'])) {
      throw new \Exception('Not authenticated');
    }
  }

  /**
   * Callback to authorize a DRD instance with a given secret.
   *
   * @param int $version
   *   Main Drupal core version, between and including 6 to 8.
   * @param bool $debugMode
   *   Whether we operate in debug mode.
   *
   * @return string
   *   Encrypted and base64 encoded result from the executed action.
   */
  public static function authorizeBySecret($version, $debugMode = FALSE) {
    /* @var \Drupal\drd\Agent\Action\BaseInterface $self */
    $self = "\\Drupal\\drd\\Agent\\Action\\V$version\\Base";

    try {
      $input = self::readInput($version, $debugMode, 'Authorize DRD by secret');

      if (empty($input['remoteSetupToken']) || empty($input['method']) || empty($input['secrets'])) {
        throw new \Exception('Input is incomplete');
      }

      switch ($input['method']) {
        case self::SEC_AUTH_ACQUIA:
          $required = array('username', 'password');
          $local = $self::getDbInfo();
          break;

        case self::SEC_AUTH_PANTHEON:
          $required = array('PANTHEON_SITE');
          $local = $_ENV;
          break;

        case self::SEC_AUTH_PLATFORMSH:
          $required = array('PLATFORM_PROJECT');
          $local = $_ENV;
          break;

        default:
          throw new \Exception('Unknown method.');
      }

      foreach ($required as $item) {
        if (!isset($local[$item])) {
          throw new \Exception('Unsupported method.');
        }
        if ($local[$item] != $input['secrets'][$item]) {
          throw new \Exception('Invalid secret.');
        }
      }
      $self::authorize($input['remoteSetupToken']);
    }
    catch (\Exception $ex) {
      $self::watchdog($ex->getMessage(), array(), 3);
      // Let's slow down to prevent brute force.
      sleep(10);
      header("HTTP/1.1 502 Error");
      print 'error';
      exit;
    }

    return 'ok';
  }

  /**
   * {@inheritdoc}
   */
  public static function getArguments() {
    return self::$arguments;
  }

  /**
   * {@inheritdoc}
   */
  public static function getDebugMode() {
    return self::$debugMode;
  }

  /**
   * {@inheritdoc}
   */
  public static function setDebugMode($debugMode) {
    self::$debugMode = $debugMode;
  }

}
