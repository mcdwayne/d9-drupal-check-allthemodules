<?php

namespace Drupal\menu_twig\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;

/**
 * Class MenuTwigController.
 */
class MenuTwigController extends ControllerBase {

  /**
   * The modal .
   *
   * @param string $name
   *   By default $name value is filter.
   *
   * @return AjaxResponse
   *   json
   */
  public function getTwigExtenstions($name) {
    $options = [
      'dialogClass' => 'popup-dialog-class',
      'width' => '40%',
    ];
    $twig = \Drupal::service('twig');
    $item_list = ($name == 'filter') ? $twig->getFilters() : $twig->getFunctions();
    foreach (array_keys($item_list) as $key => $item) {
      $data[] = array(
        array('data' => ++$key, 'align' => 'center'),
        array('data' => $item, 'align' => 'left', 'class' => 'command'),
        array('data' => t('<a class="menu-twig-command" href="#">Copy</a>'), 'align' => 'center'),
      );
    }
    $render = [
      '#theme' => 'table',
      '#header' => ['#ID', 'name', t('Copy command')],
      '#rows' => $data,
      '#wrapper_attributes' => [
        'class' => [
          'wrapper__links__link',
        ],
      ],
    ];
    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand(t('List of ' . $name . 's avaiable with twig extensions '), render($render), $options));
    return $response;
  }

  /**
   * The modal for Twig examples .
   *
   * @return AjaxResponse
   *   json
   */
  public function getTwigExamples() {
    $options = [
      'dialogClass' => 'popup-dialog-class',
      'width' => '70%',
    ];
    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand(
        t('Sample code and tricks to use the menu twig'),
        \Drupal::service('twig')->loadTemplate(drupal_get_path('module', 'menu_twig') . '/templates/menu-twig-examples.html.twig')->render([]),
        $options)
    );
    return $response;
  }

}
