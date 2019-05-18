<?php /**
 * @file
 * Contains \Drupal\yplog\Controller\DefaultController.
 */

namespace Drupal\yplog\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the yplog module.
 */
class DefaultController extends ControllerBase {

  public function yplog_graph_index() {
    $rows = [];
    $max_touches = 0;
    $header = [
      ['data' => t('URL'), 'field' => 'listen_url'],
      [
        'data' => t('Max listeners'),
        'field' => 'maximum',
      ],
      ['data' => t('Average listeners'), 'field' => 'average'],
      [
        'data' => t('Relative uptime'),
        'field' => 'touches',
      ],
    ];
    $query = db_select('yp_log');
    $query->addField('yp_log', 'listen_url');
    $query->addExpression('MAX(listeners)', 'maximum');
    $query->addExpression('ROUND(AVG(listeners))', 'average');
    $query->addExpression('COUNT(*)', 'touches');
    $query->condition('timestamp', REQUEST_TIME - 604800, '>');
    $query->groupBy('listen_url');
    $query = $query
      ->extend('TableSort')
      ->orderByHeader($header);
    $result = $query->execute();
    foreach ($result as $stream) {
      $link = 'yp/log/' . str_replace('http://', '', $stream->listen_url);
      // @FIXME
      // l() expects a Url object, created from a route name or external URI.
      // $rows[] = array(
      //       l($stream->listen_url, $link),
      //       $stream->maximum,
      //       $stream->average,
      //       $stream->touches,
      //     );

      $max_touches = max($max_touches, $stream->touches);
    }
    foreach ($rows as $key => $row) {
      $rows[$key][3] = round(100 * ($rows[$key][3] / $max_touches), 2) . '%';
    }
    if (empty($rows)) {
      $rows[] = [['data' => t('No stream data found.'), 'colspan' => 4]];
    }
    // @FIXME
    // theme() has been renamed to _theme() and should NEVER be called directly.
    // Calling _theme() directly can alter the expected output and potentially
    // introduce security issues (see https://www.drupal.org/node/2195739). You
    // should use renderable arrays instead.
    // 
    // 
    // @see https://www.drupal.org/node/2195739
    // return(
    //     theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array(), 'caption' => t('Listing all streams seen in the past seven days.')))
    //     . t('Uptime is relative to the stream with the highest uptime as seen by this YP server. Therefore "100%" does not necessarily indicate the stream was up 100% of the time. The log takes only periodic snapshots of directory data; therefore it may have missed the actual maximum listenership of a stream.')
    //   );

  }

  public function yplog_graph_page($host, $path = '') {
    $listen_url = 'http://' . $host . '/' . $path;
    // @FIXME
    // drupal_set_title() has been removed. There are now a few ways to set the title
    // dynamically, depending on the situation.
    // 
    // 
    // @see https://www.drupal.org/node/2067859
    // drupal_set_title($listen_url);

    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
    // drupal_set_breadcrumb(array(l(t('Home'), NULL), l(t('Stream directory'), 'yp'), l(t('Reports'), 'yp/log')));

    $directory = 'public://yplog';
    file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
    $filename = $directory . '/' . md5($listen_url) . '.png';
    // If image is stale or not found, generate new image.
    if (!file_exists($filename) || REQUEST_TIME - filemtime($filename) > 900) {
      yplog_graph($listen_url, $filename);
    }
    // @FIXME
    // theme() has been renamed to _theme() and should NEVER be called directly.
    // Calling _theme() directly can alter the expected output and potentially
    // introduce security issues (see https://www.drupal.org/node/2195739). You
    // should use renderable arrays instead.
    // 
    // 
    // @see https://www.drupal.org/node/2195739
    // return theme('yplog_graph', array('listen_url' => $listen_url, 'filename' => $filename));

  }

}
