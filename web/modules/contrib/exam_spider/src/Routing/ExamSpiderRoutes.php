<?php

namespace Drupal\exam_spider\Routing;

use Symfony\Component\Routing\Route;

/**
 * Defines a route subscriber to register a url for serving image styles.
 */
class ExamSpiderRoutes {

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes() {
    $routes = [];
    $routes['exam_spider.exam_spider_dashboard'] = new Route(
      '/admin/structure/' . EXAM_SPIDER_EXAM_URL,
      [
        '_controller' => '\Drupal\exam_spider\Controller\ExamSpider::examSpiderDashboard',
        '_title' => EXAM_SPIDER_EXAM_TITLE . ' Dashboard',
      ],
      [
        '_permission' => 'exam spider dashboard',
      ]
    );
    $routes['exam_spider.exam_spider_exam_results'] = new Route(
      '/admin/structure/' . EXAM_SPIDER_EXAM_URL . '/results',
      [
        '_form' => '\Drupal\exam_spider\Form\ExamSpiderResultsForm',
        '_title' => EXAM_SPIDER_EXAM_TITLE . ' Results',
      ],
      [
        '_permission' => 'exam spider dashboard',
      ]
    );
    $routes['exam_spider.exam_spider_delete_result'] = new Route(
      'admin/structure/' . EXAM_SPIDER_EXAM_URL . '/result/{resultid}/delete',
      [
        '_form' => '\Drupal\exam_spider\Form\ExamSpiderResultsDelete',
        '_title' => 'Delete Result',
      ],
      [
        '_permission' => 'exam spider dashboard',
      ]
    );
    $routes['exam_spider.exam_spider_exam_result_mail'] = new Route(
      '/admin/structure/' . EXAM_SPIDER_EXAM_URL . '/result/{resultid}/mail',
      [
        '_controller' => '\Drupal\exam_spider\Controller\ExamSpider::examSpiderExamResultMail',
        '_title' => 'Send Score Card of Result',
      ],
      [
        '_permission' => 'exam spider dashboard',
      ]
    );
    $routes['exam_spider.exam_spider_exam_settings'] = new Route(
      '/admin/structure/' . EXAM_SPIDER_EXAM_URL . '/config',
      [
        '_form' => '\Drupal\exam_spider\Form\ExamSpiderSettingsForm',
        '_title' => EXAM_SPIDER_EXAM_TITLE . ' Settings',
      ],
      [
        '_permission' => 'exam spider dashboard',
      ]
    );
    $routes['exam_spider.exam_spider_add_exam'] = new Route(
      '/admin/structure/' . EXAM_SPIDER_EXAM_URL . '/add',
      [
        '_form' => '\Drupal\exam_spider\Form\ExamSpiderExamForm',
        '_title' => 'Create ' . EXAM_SPIDER_EXAM_TITLE,
      ],
      [
        '_permission' => 'exam spider dashboard',
      ]
    );
    $routes['exam_spider.exam_spider_edit_exam'] = new Route(
      'admin/structure/' . EXAM_SPIDER_EXAM_URL . '/{examid}/edit',
      [
        '_form' => '\Drupal\exam_spider\Form\ExamSpiderExamForm',
        '_title' => 'Edit ' . EXAM_SPIDER_EXAM_TITLE,
      ],
      [
        '_permission' => 'exam spider dashboard',
      ]
    );
    $routes['exam_spider.exam_spider_delete_exam'] = new Route(
      'admin/structure/' . EXAM_SPIDER_EXAM_URL . '/{examid}/delete',
      [
        '_form' => '\Drupal\exam_spider\Form\ExamSpiderExamDelete',
        '_title' => 'Delete ' . EXAM_SPIDER_EXAM_TITLE,
      ],
      [
        '_permission' => 'exam spider dashboard',
      ]
    );
    $routes['exam_spider.exam_spider_add_question'] = new Route(
      'admin/structure/' . EXAM_SPIDER_EXAM_URL . '/question/{examid}/add',
      [
        '_form' => '\Drupal\exam_spider\Form\ExamSpiderQuestionForm',
        '_title' => 'Add Question',
      ],
      [
        '_permission' => 'exam spider dashboard',
      ]
    );
    $routes['exam_spider.exam_spider_edit_question'] = new Route(
      'admin/structure/' . EXAM_SPIDER_EXAM_URL . '/question/{questionid}/edit',
      [
        '_form' => '\Drupal\exam_spider\Form\ExamSpiderQuestionForm',
        '_title' => 'Edit Question',
      ],
      [
        '_permission' => 'exam spider dashboard',
      ]
    );
    $routes['exam_spider.exam_spider_delete_question'] = new Route(
      'admin/structure/' . EXAM_SPIDER_EXAM_URL . '/question/{questionid}/delete',
      [
        '_form' => '\Drupal\exam_spider\Form\ExamSpiderQuestionDelete',
        '_title' => 'Delete Question',
      ],
      [
        '_permission' => 'exam spider dashboard',
      ]
    );
    $routes['exam_spider.exam_spider_start'] = new Route(
      '/' . EXAM_SPIDER_EXAM_URL . '/start',
      [
        '_controller' => '\Drupal\exam_spider\Controller\ExamSpider::examSpiderExamStart',
        '_title' => 'List of ' . EXAM_SPIDER_EXAM_TITLE,
      ],
      [
        '_permission' => 'exam spider user',
      ]
    );
    $routes['exam_spider.exam_spider_exam_continue'] = new Route(
      '/' . EXAM_SPIDER_EXAM_URL . '/{examid}/continue',
      [
        '_form' => '\Drupal\exam_spider\Form\ExamSpiderExamContinue',
        '_title' => 'Continue ' . EXAM_SPIDER_EXAM_TITLE,
      ],
      [
        '_permission' => 'exam spider user',
      ]
    );
    return $routes;
  }

}
