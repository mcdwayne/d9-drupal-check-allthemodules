<?php

namespace Drupal\xmlrpc\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Contains controller methods for the XML-RPC module.
 */
class XmlrpcController extends ControllerBase {
  /**
   * The module name, used to load its files and definee its hook name.
   */
  const MODULE = 'xmlrpc';

  /**
   * Process an XML-RPC request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A Response object.
   */
  public function php() {
    $module_handler = $this->moduleHandler();

    // xmlrpc.server.inc assumes xmlrpc.inc is already included.
    $module_handler->loadInclude(static::MODULE, 'inc');

    // Needed to define xmlrpc_server().
    $module_handler->loadInclude(static::MODULE, 'server.inc');

    return xmlrpc_server($module_handler->invokeAll(static::MODULE));
  }

}
