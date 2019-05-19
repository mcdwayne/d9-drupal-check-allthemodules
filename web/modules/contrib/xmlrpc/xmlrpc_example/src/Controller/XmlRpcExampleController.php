<?php

namespace Drupal\xmlrpc_example\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\xmlrpc\XmlRpcTrait;

/**
 * Controller methods for basic documentation pages in this module.
 */
class XmlRpcExampleController extends ControllerBase {

  use XmlRpcTrait;

  /**
   * Constructs a page with info about the XML-RPC example.
   *
   * Our router maps this method to the path 'examples/xmlrpc'.
   */
  public function info() {
    // Make the XML-RPC request.
    $server = $this->getEndpoint();
    $options = ['system.listMethods' => []];
    $supported_methods = xmlrpc($server, $options);

    // Tell the user if there was an error.
    if ($supported_methods === FALSE) {
      drupal_set_message($this->t('Error return from xmlrpc(): Error: @errno, Message: @message', [
        '@errno' => xmlrpc_errno(),
        '@message' => xmlrpc_error_msg(),
      ]));
    }

    // Process the results.
    $build = [
      'basic' => [
        '#theme' => 'item_list',
        '#title' => $this->t('This XML-RPC example presents code that shows'),
        '#items' => [
          Link::createFromRoute($this->t('XML-RPC server code'), 'xmlrpc_example.server'),
          Link::createFromRoute($this->t('XML-RPC client code'), 'xmlrpc_example.client'),
          Link::createFromRoute($this->t('An example hook_xmlrpc_alter() call'), 'xmlrpc_example.alter'),
        ],
      ],
      'method_array' => [
        '#theme' => 'item_list',
        '#title' => $this->t('These methods are supported by :url', [
          ':url' => UrlHelper::stripDangerousProtocols($server),
        ]),
        '#list_type' => 'ul',
        '#items' => $supported_methods,
      ],
    ];

    return $build;
  }

}
