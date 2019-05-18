<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 3/10/2017
 * Time: 3:03 PM
 */

namespace Drupal\forena\Controller;


use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Route;

interface AjaxControllerInterface {

  /**
   * Initialize
   */
  public function initLayout();

  /**
   * @param \Drupal\forena\Controller\Route $route
   * @param $action
   * @param string $js_mode
   * @return mixed
   */
  public function page($action, $js_mode='nojs');

  /**
   * Route the requested action for the controller
   * @param string $action
   *   Indicates which action in the ajax controller is to be peformed.
   */
  public function route($action);

  /**
   * Return the response to the ajax request.
   * @param $action
   * @return array|RedirectResponse
   */
  public function response($action);
}