<?php

/**
 * @file
 * Contains \Drupal\accessibility\Form\AccessibilityTestsImportForm.
 */

namespace Drupal\accessibility\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Entity\DatabaseStorageControllerNG;

/**
 * Provides a deletion confirmation form for accessibility test.
 */
class AccessibilityTestsImportForm extends FormBase {

  /**
   * The accessibility test storage controller.
   *
   * @var \Drupal\accessibility\AccessibilityTestStorageControllerInterface
   */
  protected $testImportController;

  /**
   * Constructs a new testDelete object.
   *
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'accessibility_tests_list';
  }

  private function getEnabledTests() {
    static $tests;
    if ($tests) {
      return $tests;
    }
    $tests = db_select('accessibility_test', 'a')
                ->fields('a', array('quail_name', 'name'))
                ->execute()
                ->fetchAllKeyed();
    return $tests;
  }

  public function buildForm(array $form, array &$form_state) {
  	$library_path = libraries_get_path('quail');
    $options = array();
    $form = array();
    $tests = module_invoke_all('accessibility_tests', TRUE);

    $guidelines = module_invoke_all('accessibility_guidelines', TRUE);

    $session = isset($_SESSION['accessibility_tests_filter']) ? $_SESSION['accessibility_tests_filter'] : array();
    $form['filter'] = array(
      '#type' => 'fieldset',
      '#title' => t('Filter tests'),
      '#collapsible' => TRUE,
    );

    $guideline_options = array();
    foreach($guidelines as $k => $guideline) {
      $guideline_options[$k] = $guideline['title'];
    }

    $form['filter']['guideline'] = array(
      '#type' => 'select',
      '#title' => t('Guideline'),
      '#options' => $guideline_options,
      '#default_value' => isset($session['guideline']) ? $session['guideline'] : '',
      '#empty_option' => t('-- Any --'),
    );

    $categories = array();
    foreach($tests as $test) {
      foreach($test['tags'] as $tag) {
        $categories[$tag] = $tag;
      }
    }
    ksort($categories);
    $form['filter']['category'] = array(
      '#type' => 'select',
      '#title' => t('Category'),
      '#options' => $categories,
      '#default_value' => isset($session['category']) ? $session['category'] : '',
      '#empty_option' => t('-- Any --'),
    );

    $form['filter']['severity'] = array(
      '#type' => 'select',
      '#title' => t('Severity'),
      '#options' => array(
          ACCESSIBILITY_TEST_SEVERE => t('Severe'),
          ACCESSIBILITY_TEST_MODERATE => t('Moderate'),
          ACCESSIBILITY_TEST_SUGGESTION => t('Suggestion'),
        ),
      '#default_value' => isset($session['severity']) ? $session['severity'] : '',
      '#empty_option' => t('-- Any --'),
    );

    $form['filter']['filter'] = array(
      '#type' => 'submit',
      '#value' => t('Filter'),
    );

    $form['filter']['reset'] = array(
      '#type' => 'submit',
      '#value' => t('Reset'),
    );

    $enabled_tests = $this->getEnabledTests();
    foreach ($tests as $testname => $test) {
      if (!isset($enabled_tests[$testname]) && 
         (!isset($session['category']) || !$session['category'] || in_array($session['category'], $test['tags'])) &&
         (!isset($session['guideline']) || !$session['guideline'] || in_array($testname, $guidelines[$session['guideline']]['tests'])) &&
         (!isset($session['severity']) || !$session['severity'] || $test['severity'] == $session['severity'])) {
        $options[$testname] = array('test' => $test['title'],
                                    'severity' => t(ucfirst($test['severity'])),
                                    'categories' => implode(', ', $test['tags']),
                                    );
      }
    }
    
    $header = array('test' => t('Test'),
                    'categories' => t('Categories'),
                    'severity' => t('Severity'),
                    );

    $form['tests'] = array(
      '#type' => 'tableselect',
      '#title' => t('Available accessibility tests'),
      '#options' => $options,
      '#header' => $header,
      '#default_value' => $enabled_tests,
    );
    
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Import tests'),
    );
      
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    if($form_state['triggering_element']['#value'] == t('Filter')) {
    $_SESSION['accessibility_tests_filter'] = array(
      'guideline' => $form_state['values']['guideline'],
      'category' => $form_state['values']['category'],
      'severity' => $form_state['values']['severity'],
    );
    return;
    }
    if($form_state['triggering_element']['#value'] == t('Reset')) {
      $_SESSION['accessibility_tests_filter'] = array();
      return;
    }
    $tests = module_invoke_all('accessibility_tests', TRUE);
    $enabled_tests = $this->getEnabledTests();
    $batch = array(
      'operations' => array(),
      'finished' => 'accessibility_tests_list_done',
      'title' => t('Importing tests'),
      'init_message' => t('Starting to import tests.'),
      'progress_message' => t('Imported @current out of @total.'),
      'error_message' => t('An error occurred while importing tests.'),
      'file' => drupal_get_path('module', 'accessibility') . '/accessibility.admin.inc',
    );
    foreach ($form_state['values']['tests'] as $test => $enabled) {
      if ($enabled && !isset($enabled_tests[$test])) {
        $batch['operations'][] = array('_accessibility_create_test_from_quail', array($test, $tests[$test]));
      }
    }

    batch_set($batch);
  }

}