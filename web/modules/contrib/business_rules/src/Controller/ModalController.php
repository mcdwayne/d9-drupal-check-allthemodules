<?php

namespace Drupal\business_rules\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class HelpController.
 *
 * Provide help pop up pages.
 *
 * @package Drupal\business_rules\Controller
 */
class ModalController extends ControllerBase {

  /**
   * The BusinessRulesUtil.
   *
   * @var \Drupal\business_rules\Util\BusinessRulesUtil
   */
  protected $util;

  /**
   * {@inheritdoc}
   */
  public function __construct(ContainerInterface $container) {
    $this->util = $container->get('business_rules.util');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static ($container);
  }

  /**
   * Provide the modal page.
   *
   * @param string $collection
   *   The keyvalue collection witch contains the page content.
   * @param string $key
   *   The keyvalue key witch contains the page content.
   * @param string $title
   *   The title for the modal content.
   * @param string $method
   *   The method tho show the content: ajax|nojs.
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   The help content.
   */
  public function modal($collection, $key, $title, $method) {

    $keyvalue = $this->util->getKeyValueExpirable($collection);
    $content = $keyvalue->get($key);

    if ($method == 'ajax') {
      $content['#attached']['library'][] = 'core/drupal.dialog.ajax';

      $options = ['width' => '75%'];

      $response = new AjaxResponse();
      $response->addCommand(new OpenModalDialogCommand($title, $content, $options));

      return $response;
    }
    else {
      return $content;
    }
  }

}
