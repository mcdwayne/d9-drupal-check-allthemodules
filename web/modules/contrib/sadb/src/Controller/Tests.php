<?php

namespace Drupal\sadb\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\sadb\Database\DatabaseSA;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for page example routes.
 */
class Tests extends ControllerBase {

  /**
   * Constructs a page with descriptive content.
   *
   * Our router maps this method to the path 'examples/page-example'.
   */
  public function description() {
    // Make our links. First the simple page.
    $page_example_simple_link = Link::createFromRoute($this->t('simple page'), 'page_example_simple')
      ->toString();
    // Now the arguments page.
    $arguments_url = Url::fromRoute('page_example_arguments', array(
      'first' => '23',
      'second' => '56'
    ));
    $page_example_arguments_link = Link::fromTextAndUrl($this->t('arguments page'), $arguments_url)
      ->toString();

    // Assemble the markup.
    $build = array(
      '#markup' => $this->t('<p>The Page example module provides two pages, "simple" and "arguments".</p><p>The @simple_link just returns a renderable array for display.</p><p>The @arguments_link takes two arguments and displays them, as in @arguments_url</p>',
        array(
          '@simple_link' => $page_example_simple_link,
          '@arguments_link' => $page_example_arguments_link,
          '@arguments_url' => $arguments_url->toString(),
        )
      ),
    );

    return $build;
  }

  /**
   * Constructs a simple page.
   *
   * The router _controller callback, maps the path
   * 'examples/page-example/simple' to this method.
   *
   * _controller callbacks return a renderable array for the content area of the
   * page. The theme system will later render and surround the content with the
   * appropriate blocks, navigation, and styling.
   */
  public function tests() {
    $html = "";
    $html .= "";
    $html .= "Time : " . date("Y-m-d H:i:s");
    $html .= '<p>' . '</p>';

    //minumum for Sqlite
    $settings = array(
      'database' => 'public://.ht.test.sqlite',
      'driver' => 'sqlite',
    );

    //minumum for Mysql
    $settings = array(
      'database' => 'my_other_db',
      'username' => 'root',
      'driver' => 'mysql',
    );


    //Other mysql Example
    $settings = array(
      'database' => 'my_other_db',
      'username' => 'root',
      'password' => 'thepassword',
      'prefix' => 'mytabs_',
      'host' => '127.0.0.1',
      'port' => '33066',
      'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
      'driver' => 'mysql',
    );


    //For thests
    $settings = array(
      'database' => 'drupal8',
      'username' => 'root',
      'port' => '33067',
      'driver' => 'mysql',
    );

    //$settings['database'] = 'public://test_sqlite_db';
    //$settings['driver'] = 'mysql';


    //dpm(file_put_contents($settings['database'],'test'));

    try {
      $con = (new DatabaseSA($settings))->getConnection();
      dpm($con);
    }
    catch (\Exception $e) {
      dpm($e);
    }


    return array(
      '#markup' => $html,
      '#cache' => ['disabled' => TRUE]
    );
  }

}
