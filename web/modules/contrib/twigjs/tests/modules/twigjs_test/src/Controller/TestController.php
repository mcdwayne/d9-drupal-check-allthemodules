<?php

namespace Drupal\twigjs_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class TestController.
 *
 * @package Drupal\twigjs_test\Controller
 */
class TestController extends ControllerBase {

  /**
   * Test controller.
   */
  public function testFile() {
    $template_content = file_get_contents(DRUPAL_ROOT . '/core/modules/system/templates/time.html.twig');
    return [
      'content' => [
        '#theme' => 'time',
        '#text' => 'test_time',
      ],
      '#prefix' => '<div id="twigjs-test-file-php">',
      '#suffix' => '</div><div id="twigjs-test-file-js"></div>',
      '#attached' => [
        'drupalSettings' => [
          'twigjsTest' => [
            'fileTemplate' => $template_content,
          ],
        ],
        'library' => [
          'twigjs_test/twigjs_test',
        ],
      ],
    ];
  }

  /**
   * Test controller.
   */
  public function testInline() {
    $template = '<ul>{% for user in users %}
                      <li>{{ user }}</li>
                     {% endfor %}
                 </ul>';
    return [
      '#type' => 'inline_template',
      '#prefix' => '<div id="twigjs-test-controller-wrapper">',
      '#suffix' => '</div><div id="twigjs-test-controller-wrapper-js"></div>',
      '#template' => $template,
      '#context' => [
        'users' => [
          'testUser',
        ],
      ],
      '#attached' => [
        'drupalSettings' => [
          'twigjsTest' => [
            'inlineTemplate' => $template,
          ],
        ],
        'library' => [
          'twigjs_test/twigjs_test',
        ],
      ],
    ];
  }

  /**
   * Test controller.
   */
  public function testLight() {
    $template = 'I have a variable called name and it is {{ name }} and I have a number and it is {{ number }}';
    return [
      '#type' => 'inline_template',
      '#template' => $template,
      '#context' => [
        'name' => 'testName',
        'number' => 1337,
      ],
      '#prefix' => '<div id="twigjs-test-light-wrapper">',
      '#suffix' => '</div><div id="twigjs-test-light-wrapper-js"></div>',
      '#attached' => [
        'drupalSettings' => [
          'twigjsTest' => [
            'lightTemplate' => $template,
          ],
        ],
        'library' => [
          'twigjs_test/twigjs_test',
        ],
      ],
    ];
  }

  /**
   * Test controller.
   */
  public function testSimple() {
    $template = 'This is rendered by {{ name }}';
    $loader = new \Twig_Loader_Array([
      'twigjs_test' => $template,
    ]);
    $template_variables = ['name' => 'twigjs module'];
    $twig = new \Twig_Environment($loader);
    return [
      '#markup' => $twig->render('twigjs_test', $template_variables),
      '#prefix' => '<div id="twigjs_test_php">',
      '#suffix' => '</div><div id="twigjs_test_js"></div>',
      '#attached' => [
        'library' => [
          'twigjs_test/twigjs_test',
        ],
        'drupalSettings' => [
          'twigjsTest' => [
            'testTemplate' => $template,
            'variables' => $template_variables,
          ],
        ],
      ],
    ];
  }

}
