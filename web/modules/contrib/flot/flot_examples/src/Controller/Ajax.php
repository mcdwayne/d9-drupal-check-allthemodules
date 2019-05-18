<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Displays a graph to demonstrate the AJAX capabilities of FLOT.
 */
class Ajax extends ControllerBase {

  /**
   * Function realtime.
   */
  public function content() {

    $options = [
      'lines' => ['show' => TRUE],
      'points' => ['show' => TRUE],
      'xaxis' => [
        'tickDecimals' => 0,
        'tickSize' => 1,
      ],
    ];

    $data = array();
    $text = [];
    $text[] = $this->t('Example of loading data dynamically with AJAX. Percentage change in GDP (source: <a href=":one">Eurostat</a>). Click the buttons below:', [':one' => 'http://epp.eurostat.ec.europa.eu/tgm/table.do?tab=table&init=1&plugin=1&language=en&pcode=tsieb020']);

    $text[] = $this->t('The data is fetched over HTTP, in this case directly from text files. Usually the URL would point to some web server handler (e.g. a PHP page or Java/.NET/Python/Ruby on Rails handler) that extracts it from a database and serializes it to JSON');

    $text[] = [
      'setp' => TRUE,
      0 => [
        '#type' => 'button',
        '#attributes' => ['class' => ['fetchSeries']],
        '#value' => $this->t('First dataset'),
      ],
      1 => $this->t('[ <a href=":one">see data</a> ]', [':one' => 'data-eu-gdp-growth/']),
      2 => [
        '#markup' => '<span></span>',
      ],
    ];
    $text[] = [
      0 => [
        '#type' => 'button',
        '#attributes' => ['class' => ['fetchSeries']],
        '#value' => $this->t('Second dataset'),
      ],
      1 => $this->t('[ <a href=":one">see data</a> ]', [':one' => 'data-japan-gdp-growth/']),
      2 => [
        '#markup' => '<span></span>',
      ],
      'setp' => TRUE,
    ];
    $text[] = [
      0 => [
        '#type' => 'button',
        '#attributes' => ['class' => ['fetchSeries']],
        '#value' => $this->t('Third dataset'),
      ],
      1 => $this->t('[ <a href=":one">see data</a> ]', [':one' => 'data-usa-gdp-growth/']),
      2 => [
        '#markup' => '<span></span>',
      ],
      'setp' => TRUE,
    ];
    $text[] = $this->t('If you combine AJAX with setTimeout, you can poll the server for new data.');
    $text[] = [
        [
          '#type' => 'button',
          '#attributes' => ['class' => ['dataUpdate']],
          '#value' => $this->t('Poll for data'),
        ],
      'setp' => TRUE,
    ];
    $output['flot'] = [
      '#type' => 'flot',
      '#theme' => 'flot_examples',
      '#data' => $data,
      '#options' => $options,
      '#text' => $text,
      '#attached' => ['library' => ['flot_examples/ajax']],
    ];

    return $output;
  }

  /**
   * Function realtime.
   */
  public function json1($id) {

    $data_set = [
      [1999, 3.0], [2000, 3.9], [2001, 2.0], [2002, 1.2], [2003, 1.3],
      [2004, 2.5], [2005, 2.0], [2006, 3.1], [2007, 2.9], [2008, 0.9],
    ];
    $data_slice = array_slice($data_set, 0, $id);
    $data = array(
      'label' => 'Europe (EU27)',
      'data' => $data_slice,
    );
    return new JsonResponse($data);
  }

  /**
   * Function realtime.
   */
  public function json2($id) {

    $data_set = [
      [1999, -0.1], [2000, 2.9], [2001, 0.2], [2002, 0.3], [2003, 1.4],
      [2004, 2.7], [2005, 1.9], [2006, 2.0], [2007, 2.3], [2008, -0.7],
    ];
    $data_slice = array_slice($data_set, 0, $id);
    $data = array(
      'label' => 'Japan',
      'data' => $data_slice,
    );
    return new JsonResponse($data);
  }

  /**
   * Function realtime.
   */
  public function json3($id) {

    $data_set = [
      [1999, 4.4], [2000, 3.7], [2001, 0.8], [2002, 1.6], [2003, 2.5],
      [2004, 3.6], [2005, 2.9], [2006, 2.8], [2007, 2.0], [2008, 1.1],
    ];
    $data_slice = array_slice($data_set, 0, $id);
    $data = array(
      'label' => 'USA',
      'data' => $data_slice,
    );
    return new JsonResponse($data);
  }

}
