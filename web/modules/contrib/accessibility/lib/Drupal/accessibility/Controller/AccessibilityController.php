<?php

/**
 * @file
 * Contains \Drupal\accessibility\Controller\AccessibilityController.
 */

namespace Drupal\accessibility\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\accessibility\VocabularyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides route responses for accessibility.module.
 */
class AccessibilityController extends ControllerBase {

  /**
   * Returns a rendered edit form to create a new term associated to the given vocabulary.
   *
   * @param \Drupal\accessibility\VocabularyInterface $accessibility_vocabulary
   *   The vocabulary this term will be added to.
   *
   * @return array
   *   The accessibility term add form.
   */
  public function addForm() {
    return $this->entityManager()->getForm();
  }

  public function accessibilityTestsExisting() {
    module_load_include('inc', 'accessibility', 'accessibility.admin');
    $build = array('filter_form' => drupal_get_form('accessibility_admin_filter_form'));

    $header = array(t('Name'), t('Severity'), t('Status'), t('Edit'), t('Delete'));

    $rows = array();

    $query = db_select('accessibility_test', 't')
             ->fields('t', array('test_id', 'name', 'quail_name', 'severity', 'status'))
             ->orderBy('t.name');

    $filter = (isset($_SESSION['accessibility_admin_filter'])) ? $_SESSION['accessibility_admin_filter'] : array();
    if (isset($filter['severity'])) {
      $in = array();
      foreach ($filter['severity'] as $severity) {
        if ($severity) {
          $in[] = $severity;
        }
      }
      if (count($in)) {
        $query->condition('t.severity', $in, 'IN');
      }
    }
    if (isset($filter['name']) && strlen($filter['name'])) {
      $query->condition('t.name', '%' . $filter['name'] . '%', 'LIKE');
    }

    $tests = $query->execute()
                   ->fetchAll();
    foreach ($tests as $test) {
      $rows[] = array(l($test->name, 'accessibility-test/' . $test->test_id),
        t(ucfirst($test->severity)),
        (($test->status) ? t('Active') : t('Inactive')),
        l(t('edit'), 'accessibility-test/' . $test->test_id . '/edit', array('query' => array('destination' => 'admin/config/accessibility/tests'))),
        l(t('delete'), 'accessibility-test/' . $test->test_id . '/delete', array('query' => array('destination' => 'admin/config/accessibility/tests')))
      );
    }

    $build['result_table'] = array('#theme' => 'table',
                                   '#header' => $header,
                                   '#rows' => $rows,
                                   );
    if (!count($rows)) {
      $build['result_table'] = array('#markup' => t('No tests, found. !link.', array('!link' => l(t('Import tests'), 'admin/config/accessibility/tests/import'))));
    }
    return $build;
  }

  public function accessibilityTestTitle($accessibility_test) {
    $accessibility_test = accessibility_test_load($accessibility_test);
    $values = $accessibility_test->getValue();
    return $values['name'][0]['value'];
  }

  public function renderTestInJSON($accessibility_test) {
    $accessibility_test = accessibility_test_load($accessibility_test);
    $rendered = $this->entityManager()->getRenderController('accessibility_test')->view($accessibility_test, 'popup');
    $rendered['#prefix'] = '<div class="accessibility-test">';
    $rendered['#suffix'] = '</div>';
    $json = array('content' => drupal_render($rendered),
                  'title'   => check_plain($accessibility_test->name)
                  );
    return new JsonResponse($json);
  }

  public function getTestsJSON() {
    if(!$result = cache()->get('accessibility_tests_json')) {
      $existing_tests = accessibility_get_active_tests();
      $accessibility_tests = module_invoke_all('accessibility_tests', FALSE);
      $result = array();
      foreach ($existing_tests as $test_id => $test) {
        $values = $test->getValue();
        $quail_name = $values['quail_name'][0]['value'];
        $result[$quail_name] = $accessibility_tests[$quail_name];
        $result[$quail_name]['readableName'] = $values['name'][0]['value'];
        $result[$quail_name]['testId'] = $test_id;
        $result[$quail_name]['tags'] = $accessibility_tests[$quail_name]['tags'];
      }
      cache()->set('accessibility_tests_json', $result);
    }
    else {
      $result = $result->data;
    }
    if ($return) {
      return $result;
    }
    return new JsonResponse(array('guideline' => array_keys($result), 'tests' => $result));
  }
}
