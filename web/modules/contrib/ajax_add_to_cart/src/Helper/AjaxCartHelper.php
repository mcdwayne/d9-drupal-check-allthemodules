<?php

namespace Drupal\ajax_add_to_cart\Helper;

use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\ajax_add_to_cart\Ajax\ReloadCommand;
use Drupal\block\Entity\Block;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Class AjaxCartHelper.
 *
 * @package Drupal\modules\ajax_add_to_cart
 */
class AjaxCartHelper {

  /**
   * Keep class object.
   *
   * @var object
   */
  public static $helper = NULL;

  /**
   * Protected cartBlock variable.
   *
   * @var cartBlock
   */
  protected $cartBlock;

  /**
   * Protected container variable.
   *
   * @var container
   */
  protected $container;

  /**
   * Protected configFactory variable.
   *
   * @var configFactory
   */
  protected $configFactory;

  /**
   * Private constructor to avoid instantiation.
   */
  private function __construct() {
    $this->container = \Drupal::getContainer();
    $this->cartBlock = $this->getCartBlock($this->container);
    $this->configFactory = $this->container->get('config.factory');
  }

  /**
   * Get class instance using this function.
   *
   * @return DomainRouteMetaHelper
   *   return Object.
   */
  public static function getInstance() {
    if (!self::$helper) {
      self::$helper = new AjaxCartHelper();
    }
    return self::$helper;
  }

  /**
   * Ajax add to cart Form.
   *
   * @param string $form_id
   *   Form id.
   * @param array $form
   *   Form array.
   *
   * @return string
   *   Return Form array.
   */
  public function ajaxAddToCartAjaxForm($form_id, &$form) {
    $messages = [
      $form_id => t('Adding to cart ...'),
    ];
    $form['#prefix'] = '<div id="modal_ajax_form_' . $form_id . '">';
    $form['#suffix'] = '</div>';
    $form['status_messages_' . $form_id] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];
    $form['form_id'] = [
      '#type' => 'hidden',
      '#value' => $form_id,
    ];
    // // Add ajax callback to the form.
    $form['actions']['submit']['#attributes']['class'][] = 'use-ajax';
    $form['actions']['submit']['#ajax'] = [
      'callback' => 'ajax_add_to_cart_ajax_validate',
      'disable-refocus' => TRUE,
      'event' => 'click',
      'progress' => [
        'type' => 'throbber',
        'message' => $messages[$form_id],
      ],
    ];
    // Add ajax dialoge library to open the form in popup.
    // Adding own library to add extra functionality.
    $form['#attached']['library'][] = 'ajax_add_to_cart/ajax_add_to_cart.commands';
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['drupalSettings']['ajax_add_to_cart']['ajax_add_to_cart']['time'] = $this->configFactory->get('ajax_add_to_cart.ajaxconfig')->get('time_ajax_modal');
    return $form;
  }

  /**
   * Ajax add to cart response.
   *
   * @param string $form_id
   *   Form id.
   * @param object $response
   *   Response object to store information.
   *
   * @return object
   *   Return response object.
   */
  public function ajaxAddToCartAjaxResponse($form_id, $response) {
    // Adding modal window.
    $options = [
      'width' => $this->configFactory->get('ajax_add_to_cart.ajaxconfig')->get('ajax_modal_width'),
      'height' => $this->configFactory->get('ajax_add_to_cart.ajaxconfig')->get('ajax_modal_height'),
    ];
    $title = t('Successfully Added');
    if ($_SESSION['_symfony_flashes']) {
      $message = $_SESSION['_symfony_flashes']['status'][0]->__toString();
    }
    if (!empty($this->cartBlock)) {
      $response->addCommand(new OpenModalDialogCommand($title, $message, $options));
    }
    else {
      $customblock = $this->container->get('plugin.manager.block')->createInstance('commerce_cart', []);
      $render = $customblock->build();
      $response->addCommand(new OpenModalDialogCommand($title, $render, $options));
    }
    $response->addCommand(new ReplaceCommand('.block-commerce-cart', $this->cartBlock));
    $response->addCommand(new ReloadCommand());
    unset($_SESSION['_symfony_flashes']);
    return $response;
  }

  /**
   * Get cart block.
   *
   * @param object $container
   *   Container object.
   *
   * @return object
   *   Return render object.
   */
  private function getCartBlock($container = NULL) {
    $blockId = $this->getCartBlockId();
    if($blockId != FALSE) {
      $block = Block::load($blockId);
      $render = $container->get('entity.manager')
        ->getViewBuilder('block')
        ->view($block);
    }
    return isset($render) ? $render : NULL;
  }

  /**
   * Gets the machine name (id) of a commerce cart block
   * visible on the current page. Returns only the first cart found
   *
   * @param none
   *
   * @return mixed or FALSE
   *   Return id of the first commerce cart block found on current page.
   * 	Returns FALSE if no commerce cart block is visible.
   */
  private function getCartBlockId() {
    $blockRepo = \Drupal::service('block.repository');
    //Returns an array of regions each with an array of blocks
    $regions = $blockRepo->getVisibleBlocksPerRegion();
    //Iterate all visible blocks and regions
    foreach($regions as $region) {
      foreach($region as $block) {
        $idPlugin = $block->get('plugin');
        //check if this is a commerce cart block
        if($idPlugin == 'commerce_cart') {
          $cartBlockId = $block->get('id');
          return($cartBlockId);
        }
      }
    }
    return FALSE;
  }

}
