<?php

namespace Drupal\remodal\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\remodal\Ajax\OpenRemodalCommand;

/**
 * Class DevController.
 *
 * @package Drupal\remodal\Controller
 */
class DevController extends ControllerBase {

  /**
   * Dev_page.
   *
   * @return string
   *   Return a page with a remodal ajax link.
   */
  public function page() {
    $content = [];
    $content['remodal_ajax_link'] = array(
      '#type' => 'link',
      '#title' => $this->t('remodal ajax link'),
      '#url' => Url::fromRoute('remodal.dev_controller_modal'),
      '#attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'remodal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
      ],
      '#attached' => array(
        'library' => array('remodal/commands'),
      ),
    );
    return $content;
  }

  /**
   * Modal.
   *
   * @return string
   *   Return remodal content.
   */
  public function modal() {
    $content['remodal_ajax_link'] = array(
      '#type' => 'link',
      '#title' => $this->t('remodal ajax link  timestamp: @timestamp', array('@timestamp' => time())),
      '#url' => Url::fromRoute('remodal.dev_controller_modal'),
      '#attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'remodal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
      ],
      '#attached' => array(
        // @todo: Check if attaching core/drupal.ajax is enough.
        'library' => array('remodal/commands'),
      ),
    );
    $options = [];

    $response = new AjaxResponse();
    // @todo: the 'title' parameter is not being rendered currently (see js/commands.js)
    $response->addCommand(new OpenRemodalCommand($this->t('remodal dev'), $content, $options));
    return $response;
  }

}
