<?php

namespace Drupal\chatbot\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a common interface for all ChatbotPlugin objects.
 */
interface ChatbotPluginInterface extends PluginInspectionInterface {

  /**
   * Process data from GET
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function challenge();

  /**
   * Process incomming data.
   *
   * @param string $data
   *   Json encoded data delivered by the Facebook API.
   */
  public function process($data);

  /**
   * Parse incoming POST data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request to parse.
   *
   * @return
   *  parsed data in required format.
   */
  public function parsePostData(Request $request);

}
