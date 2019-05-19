<?php

namespace Drupal\testswarm\Controller;

use Drupal\testswarm\TestswarmStorageController;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class TestswarmController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Constructs a CommentController object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $httpKernel
   *   HTTP kernel to handle requests.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The CSRF token manager service.
   */
  public function __construct(HttpKernelInterface $httpKernel) {
    $this->httpKernel = $httpKernel;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_kernel')
    );
  }

  /**
   * Overview of all tests.
   */
  public function testswarm_tests(Request $request, $githash = '', $theme = '', $karma = '', $module = '') {
    $config = $this->config('testswarm.settings');

    $pagetitle = array();
    $filter_failures = FALSE;
    $testswarm_remotes = $this->testswarm_remote_urls();

    // Set page title for githash filter.
    if (!empty($githash) && $githash != 'ALL') {
      if ($githash === 'LATEST') {
        $githash = $config->get('testswarm_githash');
      }
      $pagetitle[] = $this->testswarm_short_githash($githash);
    }

    // Set page title for theme filter.
    if (!empty($theme) && $theme != 'ALL') {
      $pagetitle[] = check_plain($theme);
    }

    // Set page title for karma filter.
    if (!empty($karma) && $karma != 'ALL') {
      $pagetitle[] = check_plain($karma);
    }

    // Set page title for only failures.
    if (isset($_GET['filter-failures']) && !empty($_GET['filter-failures'])) {
      $pagetitle[] = 'only failures';
      $filter_failures = TRUE;
    }

    if (!empty($pagetitle)) {
      drupal_set_title('Overview of all tests - ' . implode(', ', $pagetitle));
    }
    else {
      drupal_set_title('Overview of all tests');
    }

    // @todo this should be implemented as a real StorageController through
    // annotation
    $result = TestswarmStorageController::getAllTests($githash, $theme, $karma, $filter_failures);

    foreach ($result as $row) {
      $data[$row->caller][] = array(
        'githash' => $row->githash,
        'theme' => $row->theme,
        'num_runs' => $row->num_runs,
        'total' => $row->total,
        'failed' => $row->failed,
        'runtime' => $row->runtime,
        'first_run' => $row->first_run,
        'last_run' => $row->last_run,
        'sitename' => $row->sitename,
        'version' => $row->version,
      );
    }

    $output = '';
    $output .= '<p>' . l(t('Run all tests'), 'testswarm-run-all-tests');
    if (!empty($testswarm_remotes)) {
      foreach ($testswarm_remotes as $remote) {
        $output .= ' | ' . l(t('Run all tests on @remote', array('@remote' => $remote->sitename)), $remote->url . '/testswarm-run-all-tests', array('attributes' => array('target' => '_blank', 'class' => array('testswarm-remote'))));
      }
    }
    $output .= (user_access('administer testswarm tests') ? ' | ' . l(t('Clear all test details'), 'testswarm-tests/clear/all', array('query' => array('destination' => 'testswarm-tests'))) : '');
    $output .= '</p>';
    $githash = $config->get('testswarm_githash');
    $link = empty($githash) ? l(\Drupal::VERSION, 'http://drupal.org/project/drupal') : l($this->testswarm_short_githash(), 'http://drupalcode.org/project/drupal.git/commit/' . $githash);
    $output .= '<p>Current/Latest drupal version: ' . $link;
    $output .= ' | ' . l(t('Show all tests'), 'testswarm-tests');
    $output .= '</p>';

    if (!$filter_failures) {
      $output .= l(t('Show only failures'), current_path(), array('query' => array('filter-failures' => 'yes')));
    }

    $current_githash = empty($githash) ? 'ALL' : $githash;
    $current_theme = empty($theme) ? 'ALL' : $theme;
    $current_karma = empty($karma) ? 'ALL' : $karma;

    // Filter on theme
    $testswarm_themes = $this->testswarm_themes_to_test();
    $output .= '<p>Filter by theme: ' . l(t('All themes'), 'testswarm-tests/' . $current_githash);
    foreach ($testswarm_themes as $testswarm_theme) {
      $output .= ' | ' . l($testswarm_theme, 'testswarm-tests/' . $current_githash . '/' . $testswarm_theme);
    }
    $output .= '</p>';

    // Filter on karma
    $karmas = $this->testswarm_get_karmas();
    $output .= '<p>Filter by karma: ' . l(t('All karmas'), 'testswarm-tests/' . $current_githash . '/' . $current_theme);
    foreach ($karmas as $karma => $points) {
      $output .= ' | ' . l(t('@karma (@points)', array('@karma' => $karma, '@points' => $points)), 'testswarm-tests/' . $current_githash . '/' . $current_theme . '/' . $karma);
    }
    $output .= '</p>';

    $header = array(
      'status',
      'githash',
      'theme',
      '# runs',
      '# tests',
      '% failed tests',
      'time taken (ms)',
      'first run',
      'last run',
      'site',
      'drupal version',
    );

    $tests = testswarm_defined_tests($module, TRUE);
    foreach ($tests as $caller => $test) {
      $output .= '<h2>' . $test['module'] . '::' . $caller . '</h2>';
      if (isset($test['description'])) {
        $output .= '<p><em>' . $test['description'] . '</em></p>';
      }
      if ($test['enabled']) {
        $output .= '<p>'
          . (empty($githash) ? l(t('Details'), 'testswarm-tests/detail/' . $caller) : l(t('Details'), 'testswarm-tests/detail/' . $caller . '/' . $githash));

        foreach ($testswarm_themes as $testswarm_theme) {
          $output .= ($this->testswarm_user_can_run_test($test) ? ' | ' . l(t('Test now in @theme', array('@theme' => $testswarm_theme)), 'testswarm-run-a-test/' . $caller, array('query' => array('testswarm-theme' => $testswarm_theme, 'testswarm-destination' => 'testswarm-tests'))) : '');
        }

        foreach ($testswarm_themes as $testswarm_theme) {
          $output .= ($this->testswarm_user_can_run_test($test) ? ' | ' . l(t('Test manually in @theme', array('@theme' => $testswarm_theme)), 'testswarm-run-a-test/' . $caller, array('query' => array('testswarm-theme' => $testswarm_theme, 'testswarm-destination' => 'testswarm-tests', 'debug' => 'on'))) : '');
        }

        $output .= '</p>';
      }
      if (!empty($testswarm_remotes)) {
        $output .= '<p>';
        $first = TRUE;
        foreach ($testswarm_remotes as $remote) {
          foreach ($testswarm_themes as $testswarm_theme) {
            if (!$first) {
              $output .= ' | ';
            }
            $first = FALSE;
            $output .= ($this->testswarm_user_can_run_test($test) ? l(t('Test now in @theme on @site', array('@theme' => $testswarm_theme, '@site' => $remote->sitename)), $remote->url . '/testswarm-run-a-test/' . $caller, array('query' => array('testswarm-theme' => $testswarm_theme, 'testswarm-destination' => 'testswarm-tests'), 'attributes' => array('target' => '_blank', 'class' => array('testswarm-remote')))) : '');
          }

          foreach ($testswarm_themes as $testswarm_theme) {
            $output .= ($this->testswarm_user_can_run_test($test) ? ' | ' . l(t('Test manually in @theme on @site', array('@theme' => $testswarm_theme, '@site' => $remote->sitename)), $remote->url . '/testswarm-run-a-test/' . $caller, array('query' => array('testswarm-theme' => $testswarm_theme, 'testswarm-destination' => 'testswarm-tests', 'debug' => 'on'), 'attributes' => array('target' => '_blank', 'class' => array('testswarm-remote')))) : '');
          }
        }
        $output .= '</p>';
      }

      if (user_access('administer testswarm tests')) {
        $output .= '<p>';
        $output .=  l(t('Clear test details'), 'testswarm-tests/clear/' . $caller, array('query' => array('destination' => 'testswarm-tests')));
        $output .= '</p>';
      }
      $rows = array();
      if (isset($data[$caller])) {
        foreach ($data[$caller] as $rowdata) {
          $image = ($rowdata['failed'] < 1 ? 'message-24-ok' : 'message-24-error');
          $rows[] = array(
            'data' => array(
              theme('image', array('uri' => 'core/misc/'. $image .'.png', 'alt'  => ($rowdata['failed'] < 1 ? t('passed') : t('failed')))),
              l($this->testswarm_short_githash($rowdata['githash']), 'testswarm-tests/' . $rowdata['githash']),
              $rowdata['theme'],
              $rowdata['num_runs'],
              $rowdata['total'],
              round($rowdata['failed'], 2) . '%',
              $rowdata['runtime'],
              format_date($rowdata['first_run'], 'short'),
              format_date($rowdata['last_run'], 'short'),
              $rowdata['sitename'],
              $rowdata['version']
            ),
            'class' => ($rowdata['failed'] == 0 ? array('testswarm-passed') : array('testswarm-failed')),
          );
        }
        $output .= theme('table', array(
          'header' => $header,
          'rows' => $rows,
        ));
      }
    }

    drupal_add_css(drupal_get_path('module', 'testswarm') . '/testswarm.css');
    return $output;
  }

  /**
   * Detailed information of a test.
   */
  public function testswarm_test_details($caller, $githash = '') {
    $output = '';
    $tests = testswarm_defined_tests();
    if (!empty($tests)) {
      $test = $tests[$caller];
      if ($test) {

        $conditions = array('ti.caller' => check_plain($caller));
        $pagetitle = array();

        if (!empty($githash) && $githash != 'ALL') {
          $conditions['ti.githash'] = $githash == '<empty>' ? '' : check_plain($githash);
          $pagetitle[] = $githash == '<empty>' ? t('Empty githash') : $this->testswarm_short_githash($githash);
        }

        // Only show failures
        $filter_failures = FALSE;
        if (isset($_GET['filter-failures']) && !empty($_GET['filter-failures'])) {
          $pagetitle[] = 'only failures';
          $conditions['qt.failed'] = array('value' => 0, 'op' => '<>');
          $filter_failures = TRUE;
        }

        if (!empty($pagetitle)) {
          drupal_set_title(t('TestSwarm tests details - @pagetitle', array('@pagetitle' => implode(', ', $pagetitle))));
        }
        else {
          drupal_set_title(t('TestSwarm tests details'));
        }
        $q = db_select('testswarm_test', 'qt')->fields('qt');
        $q->join('testswarm_info', 'ti', 'qt.info_id = ti.id');
        $q->fields('ti');
        foreach ($conditions as $field => $condition) {
          if (!is_array($condition)) {
            $q->condition($field, $condition);
          }
          else {
            $q->condition($field, $condition['value'], $condition['op']);
          }
        }
        $q->orderBy('qt.timestamp', 'DESC');
        $q->range(0, 50);
        $result = $q->execute()->fetchAll();

        $output .= '<h2>' . $test['module'] . '::' . $caller . '</h2>';
        if (!$filter_failures) {
          $output .= l(t('Show only failures'), current_path(), array('query' => array('filter-failures' => 'yes'))) . ' | ' . l(t('Show all tests'), 'testswarm-tests');;
        }

        $output .= '<p>';
        $testswarm_themes = $this->testswarm_themes_to_test();
        foreach ($testswarm_themes as $testswarm_theme) {
          $output .= ($this->testswarm_user_can_run_test($test) ? l(t('Test now in @theme', array('@theme' => $testswarm_theme)), 'testswarm-run-a-test/' . $caller, array('query' => array('testswarm-theme' => $testswarm_theme, 'testswarm-destination' => current_path()))) . ' | ' : '');
        }

        foreach ($testswarm_themes as $testswarm_theme) {
          $output .= ($this->testswarm_user_can_run_test($test) ? l(t('Test manually in @theme', array('@theme' => $testswarm_theme)), 'testswarm-run-a-test/' . $caller, array('query' => array('testswarm-theme' => $testswarm_theme, 'testswarm-destination' => current_path(), 'debug' => 'on'))) . ' | ' : '');
        }

        $output .= (user_access('administer testswarm tests') ? l(t('Clear test details'), 'testswarm-tests/clear/' . $caller, array('query' => array('destination' => 'testswarm-tests/detail/' . $caller))) : '')
          . '</p>';

        $header = array(
          'status',
          'githash',
          'karma',
          'theme',
          'browser',
          'ua',
          '# tests',
          '# failed',
          'time taken (ms)',
          'timestamp',
          'details'
        );

        $rows = array();
        foreach ($result as $rowdata) {
          $browser = @get_browser($rowdata->useragent);
          $image = ($rowdata->failed < 1 ? 'message-24-ok' : 'message-24-error');
          $rows[] = array(
            'data' => array(
                theme('image', array('uri' => 'core/misc/'. $image .'.png', 'alt'  => ($rowdata->failed != 1 ? t('passed') : t('failed')))),
                !empty($rowdata->githash) ? $this->testswarm_short_githash($rowdata->githash) : t('No githash'),
                $rowdata->karma,
                $rowdata->theme,
                (isset($browser->browser) ? $browser->browser . ' (' . $browser->parent . ')' : ''),
                check_plain($rowdata->useragent),
                $rowdata->total,
                $rowdata->failed,
                $rowdata->runtime,
                format_date($rowdata->timestamp, 'short'),
                l(t('Details'), 'testswarm-tests/detail/' . $caller . '/hash/' . $rowdata->githash),
              ),
              'class' => ($rowdata->failed == 0 ? array('testswarm-passed') : array('testswarm-failed')),
          );
        }

        $output .= theme('table', array(
          'header' => $header,
          'rows' => $rows,
        ));

        drupal_add_css(drupal_get_path('module', 'testswarm') . '/testswarm.css');
      }
    }
    return $output;
  }

  /**
   * Detailed information of one git hash.
   */
  public function testswarm_test_details_hash($caller, $githash) {
    $config = $this->config('testswarm.settings');
    $output = '';
    $tests = testswarm_defined_tests();
    if (!empty($tests)) {
      $test = $tests[$caller];
      if ($test) {
        $conditions = array('ti.caller' => check_plain($caller));
        $pagetitle = array();

        if (!empty($githash) && $githash != 'ALL') {
          $conditions['ti.githash'] = $githash == '<empty>' ? '' : check_plain($githash);
          $pagetitle[] = $githash == '<empty>' ? t('Empty githash') : $this->testswarm_short_githash($githash);
        }

        // Only show failures
        $filter_failures = FALSE;
        if (isset($_GET['filter-failures']) && !empty($_GET['filter-failures'])) {
          $pagetitle[] = 'only failures';
          $conditions['qtrd.result'] = array('value' => 0, 'op' => '=');
          $filter_failures = TRUE;
        }

        if (!empty($pagetitle)) {
          drupal_set_title(t('TestSwarm tests details - @pagetitle', array('@pagetitle' => implode(', ', $pagetitle))));
        }
        else {
          drupal_set_title(t('TestSwarm tests details'));
        }
        $q = db_select('testswarm_test', 'qt')->fields('qt', array('theme', 'useragent'));
        $q->join('testswarm_info', 'ti', 'qt.info_id = ti.id');
        $q->fields('ti', array('caller'));
        $q->join('testswarm_test_run', 'qtr', 'qtr.qt_id = qt.id');
        $q->fields('qtr');
        $q->join('testswarm_test_run_detail', 'qtrd', 'qtr.id = qtrd.tri');
        $q->fields('qtrd');
        foreach ($conditions as $field => $condition) {
          if (!is_array($condition)) {
            $q->condition($field, $condition);
          }
          else {
            $q->condition($field, $condition['value'], $condition['op']);
          }
        }
        $q->orderBy('qtr.id', 'DESC');
        $q->range(0, 50);
        $result = $q->execute()->fetchAll();

        $output .= '<h2>' . $test['module'] . '::' . $caller . '</h2>';
        if (!$filter_failures) {
          $output .= l(t('Show only failures'), current_path(), array('query' => array('filter-failures' => 'yes')));
        }
        $githash = $config->get('testswarm_githash');
        $link = empty($githash) ? l(\Drupal::VERSION, 'http://drupal.org/project/drupal') : l($this->testswarm_short_githash(), 'http://drupalcode.org/project/drupal.git/commit/' . $githash);
        $output .= '<p>'
          . ($this->testswarm_user_can_run_test($test) ? l(t('Test now'), 'testswarm-run-a-test/' . $caller, array('query' => array('testswarm-destination' => 'testswarm-tests/detail/' . $caller))) : '')
          . ($this->testswarm_user_can_run_test($test) ? ' | ' . l(t('Test manually'), 'testswarm-run-a-test/' . $caller, array('query' => array('debug' => 'on'))) : '')
          . (user_access('administer testswarm tests') ? ' | ' . l(t('Clear test details'), 'testswarm-tests/clear/' . $caller, array('query' => array('destination' => 'testswarm-tests/detail/' . $caller))) : '')
          . '</p>'
          . '<p>Drupal version: ' . $link . ' | ' . l(t('Show all tests'), 'testswarm-tests') . '</p>';


        // Group by module/test
        $data = array();
        foreach ($result as $rowdata) {
          $data[$rowdata->module][$rowdata->name][] = $rowdata;
        }

        $output .= '<h2>' . $test['module'] . '::' . $caller . '</h2>';
        $header = array(
          'status',
          'theme',
          'browser',
          'ua',
          'test',
          'result',
          'message',
          'actual',
          'expected',
          'details',
        );

        foreach ($data as $module => $moduledata) {
          $output .= '<h3>' . $module . '</h3>';
          foreach ($moduledata as $testname => $testdata) {
            $output .= '<h4>' . $testname . '</h4>';
            $rows = array();
            foreach ($testdata as $rowdata) {
              $browser = @get_browser($rowdata->useragent);
              $image = ($rowdata->result == 1 ? 'message-24-ok' : 'message-24-error');
              $rows[] = array(
                'data' => array(
                  theme('image', array('uri' => 'core/misc/'. $image .'.png', 'alt'  => ($rowdata->result == 1 ? t('passed') : t('failed')))),
                  $rowdata->theme,
                  isset($browser->browser) ? $browser->browser . ' (' . $browser->parent . ')' : '',
                  check_plain($rowdata->useragent),
                  $testname,
                  $rowdata->result,
                  check_plain($rowdata->message),
                  check_plain($rowdata->actual),
                  check_plain($rowdata->expected),
                  l(t('Details'), 'testswarm-tests/detail/' . $caller . '/tests/' . $rowdata->tri),
                ),
                'class' => ($rowdata->result == 1 ? array('testswarm-passed') : array('testswarm-failed')),
              );
            }
            $output .= theme('table', array(
              'header' => $header,
              'rows' => $rows,
            ));
          }
        }

        drupal_add_css(drupal_get_path('module', 'testswarm') . '/testswarm.css');
      }
    }
    return $output;
  }

  /**
   * Detailed information of one test run.
   */
  public function testswarm_test_details_tests($caller, $id) {
    $output = '';
    $tests = testswarm_defined_tests();
    if (!empty($tests)) {
      $test = $tests[$caller];
      if ($test) {
        $conditions = array('qtrd.tri' => check_plain($id));
        $pagetitle = array();

        // Only show failures
        $filter_failures = FALSE;
        if (isset($_GET['filter-failures']) && !empty($_GET['filter-failures'])) {
          $pagetitle[] = 'only failures';
          $conditions['qtrd.result'] = array('value' => 1, 'op' => '<>');
          $filter_failures = TRUE;
        }

        if (!empty($pagetitle)) {
          drupal_set_title(t('TestSwarm tests details - @pagetitle', array('@pagetitle' => implode(', ', $pagetitle))));
        }
        else {
          drupal_set_title(t('TestSwarm tests details'));
        }
        $q = db_select('testswarm_test', 'qt')->fields('qt', array('theme', 'useragent', 'karma'));
        $q->join('testswarm_test_run', 'qtr', 'qt.id = qtr.qt_id');
        $q->fields('qtr');
        $q->join('testswarm_test_run_detail', 'qtrd', 'qtr.id = qtrd.tri');
        $q->fields('qtrd');
        foreach ($conditions as $field => $condition) {
          if (!is_array($condition)) {
            $q->condition($field, $condition);
          }
          else {
            $q->condition($field, $condition['value'], $condition['op']);
          }
        }
        $q->orderBy('qtr.id', 'DESC');
        $q->range(0, 50);
        $result = $q->execute()->fetchAll();

        // Group by module/test
        $data = array();
        $browserinfo = '';

        foreach ($result as $rowdata) {
          $data[$rowdata->module][$rowdata->name][] = $rowdata;
          if (empty($browserinfo)) {
            $browser = @get_browser($rowdata->useragent);
            $browserinfo = $rowdata->theme . '::' . (isset($browser->browser) ? $browser->browser . ' - ' . $browser->parent : '') . ' (' . $rowdata->useragent . ') by ' . $rowdata->karma;
            $output .= '<p>' . $browserinfo . '</p>';
          }
        }

        $output .= '<h2>' . $test['module'] . '::' . $caller . '</h2>';
        if (!$filter_failures) {
          $output .= l(t('Show only failures'), current_path(), array('query' => array('filter-failures' => 'yes')));
        }

        $header = array(
          'status',
          'test',
          'result',
          'message',
          'actual',
          'expected',
        );

        foreach ($data as $module => $moduledata) {
          $output .= '<h3>' . $module . '</h3>';
          foreach ($moduledata as $testname => $testdata) {
            $output .= '<h4>' . $testname . '</h4>';
            $rows = array();
            foreach ($testdata as $rowdata) {
              $image = ($rowdata->result == 1 ? 'message-24-ok' : 'message-24-error');
              $rows[] = array(
                'data' => array(
                  theme('image', array('uri' => 'core/misc/'. $image .'.png', 'alt'  => ($rowdata->result == 1 ? t('passed') : t('failed')))),
                  $testname,
                  $rowdata->result,
                  check_plain($rowdata->message),
                  check_plain($rowdata->actual),
                  check_plain($rowdata->expected),
                ),
                'class' => ($rowdata->result == 1 ? array('testswarm-passed') : array('testswarm-failed')),
              );
            }
            $output .= theme('table', array(
              'header' => $header,
              'rows' => $rows,
            ));
          }
        }

        drupal_add_css(drupal_get_path('module', 'testswarm') . '/testswarm.css');
      }
    }
    return $output;
  }

  /*
   * Set karma form
   */

  public function testswarm_set_karma_form($form, &$form_state) {
    $form = array();

    $form['karma'] = array(
      '#type' => 'textfield',
      '#title' => t('Karma name'),
      '#required' => TRUE,
      '#default_value' => isset($_COOKIE['karma']) ? $_COOKIE['karma'] : '',
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save karma name')
    );

    return $form;
  }

  /**
   * Process reinstall menu form submissions.
   */
  public function testswarm_set_karma_form_submit($form, &$form_state) {
    setrawcookie('karma', check_plain($form_state['values']['karma']), REQUEST_TIME + 31536000, '/');
    drupal_set_message(t('Karma cookie set.'));
  }

  /**
   * Page callback. Overview of browserstack workers.
   */
  public function testswarm_browserstack_status($form, $form_state) {
    $form['run_tests'] = array(
      '#type' => 'submit',
      '#value' => t('Run Browser Tests'),
      '#submit' => array('testswarm_run_browserstack_tests'),
    );
    $form['delete_workers'] = array(
      '#type' => 'submit',
      '#value' => t('Delete Workers'),
      '#submit' => array('testswarm_delete_browserstack_workers'),
    );

    $workers = $this->testswarm_get_workers();
    $header = array(t('ID'), t('Browser'), t('Browser Version'), t('Status'));
    $rows = array();
    if ($workers) {
      foreach ($workers->data as $worker) {
        $row = array(
          'data' => array(
            $worker['id'],
            $worker['browser']['name'],
            $worker['browser']['version'],
            $worker['status'],
          ),
          'class' => array($worker['status']),
        );
        $rows[] = $row;
      }
    }
    $form['workers'] = array(
      '#type' => 'item',
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No workers were retreived.'),
    );

    return $form;
  }

  public function testswarm_framed_form($form, &$form_state, $caller, $theme) {
    $form = array();
    $test = '';
    $tests = testswarm_defined_tests();
    if (array_key_exists($caller, $tests)) {
      $test = $tests[$caller];
    }

    if ($test) {
      drupal_add_library('testswarm', $test['module'] . '.' . $test['caller']);
      $karma = '';
      if (isset($_COOKIE['karma']) && !empty($_COOKIE['karma'])) {
        $karma = $_COOKIE['karma'];
      }

      $settings = array(
        'caller' => $caller,
        'theme' => check_plain($theme),
        'karma' => $karma,
        'token' => drupal_get_token($caller, TRUE),
        'debug' => 'on', // No auto redirect on iframed tests,
      ) + $this->testswarm_caller_info($caller);
      drupal_add_js(array('testswarm' => $settings), 'setting');

      $form['qunit'] = array(
        '#type' => 'markup',
        '#markup' =>  '<h1 id="qunit-header">Drupal TestSwarm</h1>
          <h2 id="qunit-banner"></h2>
          <div id="qunit-testrunner-toolbar"></div>
          <h2 id="qunit-userAgent"></h2>
          <ol id="qunit-tests"></ol>
          <div id="xtestswarm-fixture"></div>',
      );

      // @TODO: Posts are passing ?testswarm-theme, but it doesn't have any effect, defaulting the default theme.
      $form['iframe'] = array(
        '#type' => 'markup',
        '#markup' => '<iframe name="testswarmframe" width="100%" height="400" src="/' . $test['path'] . '?testswarm-theme=' . $theme . '"></iframe>',
      );
    }

    return $form;
  }

  /**
   * Run all test the current user can access.
   */
  public function testswarm_run_all_tests($module = '') {
    $tests = $this->testswarm_defined_tests_access($module);
    $enabled_themes = $this->testswarm_themes_to_test();

    $tests_to_run = array();
    foreach ($enabled_themes as $enabled_theme) {
      $tests_to_run[$enabled_theme] = $tests;
    }

    $_SESSION['testswarm_tests'] = $tests_to_run;
    return $this->testswarm_run_a_test('', FALSE);
  }

  /**
   * Run a specific test or use the first one in the session.
   */
  public function testswarm_run_a_test($caller = '', $allowredirect = TRUE) {
    $tests = $this->testswarm_defined_tests_access();
    if (!empty($tests)) {
      $theme_to_test = '';
      if (empty($caller) && isset($_SESSION['testswarm_tests']) && !empty($_SESSION['testswarm_tests'])) {
        $themes_to_test = array_keys($_SESSION['testswarm_tests']);
        $theme_to_test = array_shift($themes_to_test);
        $tests = $_SESSION['testswarm_tests'];
        $test = array_shift($tests[$theme_to_test]);
        if (empty($tests[$theme_to_test])) {
          unset($tests[$theme_to_test]);
        }
        $_SESSION['testswarm_tests'] = $tests;
      }
      else {
        $test = $tests[$caller];
      }

      if ($test) {
        $query = $test['query'];
        if (!is_array($query)) {
          $query = (array)$query;
        }
        $query += array('testswarm-test' => $test['caller']);
        if (isset($_GET['debug'])) {
          $query += array('debug' => $_GET['debug']);
        }
        if (!empty($theme_to_test)) {
          $query += array('testswarm-theme' => $theme_to_test);
        }
        elseif (isset($_GET['testswarm-theme'])) {
          $query += array('testswarm-theme' => $_GET['testswarm-theme']);
        }
        if ($allowredirect) {
          if (isset($_GET['testswarm-destination'])) {
            $query += array('testswarm-destination' => $_GET['testswarm-destination']);
          }
        }
        return new RedirectResponse(url($test['path'], array('absolute' => TRUE, 'query' => $query)));
      }
    }
  }

  /**
   * Process the results of the test.
   */
  public function testswarm_test_done() {
    if (isset($_REQUEST['caller']) && isset($_REQUEST['token'])) {
      if (drupal_valid_token($_REQUEST['token'], $_REQUEST['caller'], TRUE)) {
        $config = \Drupal::config('testswarm.settings');
        global $base_url;
        $user = \Drupal::request()->attributes->get('_account');
        $testswarm_test = array(
          'caller' => $_REQUEST['caller'],
          'githash' => $config->get('testswarm_githash'),
          'theme' => $_REQUEST['theme'],
          'karma' => drupal_substr($_REQUEST['karma'], 0, 50),
          'useragent' => $_SERVER['HTTP_USER_AGENT'],
          'total' => $_REQUEST['info']['total'],
          'passed' => $_REQUEST['info']['passed'],
          'failed' => $_REQUEST['info']['failed'],
          'runtime' => $_REQUEST['info']['runtime'],
          'uid' => $user->id(),
          'timestamp' => REQUEST_TIME,
          'module' => $_REQUEST['module'],
          'description' => $_REQUEST['description'],
          'version' => \Drupal::VERSION,
          'sitename' => \Drupal::config('system.site')->get('name'),
          'url' => $base_url,
        );
        drupal_alter('testswarm_test', $testswarm_test);
        $tests = isset($_REQUEST['tests']) ? $_REQUEST['tests'] : array();
        $logs = isset($_REQUEST['log']) ? $_REQUEST['log'] : array();
        $this->testswarm_test_save($testswarm_test, $tests, $logs);
        if ($config->get('testswarm_save_results_remote') && module_exists('xmlrpc')) {
          $result = xmlrpc(
            $config->get('testswarm_save_results_remote_url'),
            array(
              'testswarm.test.save' => array(
                $testswarm_test,
                $tests,
                $logs,
                REQUEST_TIME,
                testswarm_xmlrpc_get_hash(),
              ),
            )
          );
          if (!$result) {
            $error = xmlrpc_error();
            if ($error) {
              watchdog('testswarm_xmlrpc', $error->code . ': ' . check_plain($error->message));
            }
            else {
              watchdog('testswarm_xmlrpc', t('Something went wrong saving the result to the remote server'));
            }
          }
        }
      }
    }
  }

  private function testswarm_short_githash($githash = '') {
    if (empty($githash)) {
      $config = $this->config('testswarm.settings');
      $githash = $config->get('testswarm_githash');
    }
    return drupal_substr($githash, 0, 7);
  }

  /**
   * Save tests after they have run.
   * @see testswarm_test_done()
   */
  public function testswarm_test_save($testswarm_test, $tests, $logs) {
    $testswarm_test['version'] = explode('.', $testswarm_test['version']);
    $testswarm_test['version'] = reset($testswarm_test['version']);
    $info_id = $this->testswarm_test_ensure_info($testswarm_test);
    $test_fields = array('info_id' => $info_id);
    foreach (array('theme', 'useragent', 'total', 'passed', 'failed', 'runtime', 'uid', 'karma', 'timestamp') as $key) {
      $test_fields[$key] = $testswarm_test[$key];
    }
    $qt_id = db_insert('testswarm_test')
      ->fields($test_fields)
      ->execute();
    foreach ($tests as $test) {
      $test_run_id = db_insert('testswarm_test_run')
        ->fields(array(
          'qt_id' => $qt_id,
          'module' => $test['module'],
          'name' => $test['name'],
          'total' => $test['total'],
          'passed' => $test['passed'],
          'failed' => $test['failed'],
        ))
        ->execute();
      if (isset($logs[$test['module']][$test['name']])) {
        foreach ($logs[$test['module']][$test['name']] as $testdetail) {
          db_insert('testswarm_test_run_detail')
            ->fields(array(
              'tri' => $test_run_id,
              'result' => ($testdetail['result'] == 'true' ? 1 : 0),
              'message' => $testdetail['message'],
              'actual' => isset($testdetail['actual']) ? $testdetail['actual'] : NULL,
              'expected' => isset($testdetail['expected']) ? $testdetail['expected'] : NULL,
            ))
            ->execute();
        }
      }
      elseif (isset($logs['default'][$test['name']])) {
        foreach ($logs['default'][$test['name']] as $testdetail) {
          db_insert('testswarm_test_run_detail')
            ->fields(array(
              'tri' => $test_run_id,
              'result' => ($testdetail['result'] == 'true' ? 1 : 0),
              'message' => $testdetail['message'],
              'actual' => isset($testdetail['actual']) ? $testdetail['actual'] : NULL,
              'expected' => isset($testdetail['expected']) ? $testdetail['expected'] : NULL,
            ))
            ->execute();
        }
      }
    }
  }

  /**
   * Get a list of all defined tests the current user can do.
   */
  public function testswarm_defined_tests_access($module = '') {
    $tests = testswarm_defined_tests($module);
    $allowdtests = array();

    foreach ($tests as $caller => &$test) {
      if ($this->testswarm_user_can_run_test($test)) {
        $allowdtests[$caller] = $test;
      }
    }

    return $allowdtests;
  }

  /**
   * Get a list of all defined tests the current user can do grouped by module.
   */
  public function testswarm_defined_modules($module = '') {
    $tests = testswarm_defined_tests($module);
    $allowdmodules = array();

    foreach ($tests as $caller => &$test) {
      $allowdmodules[$test['module']][] = $test;
    }
    return $allowdmodules;
  }

  /**
   * Get a list of all defined tests the current user can do grouped by module.
   */
  public function testswarm_defined_modules_access($module = '') {
    $tests = $this->testswarm_defined_modules($module);
    $allowdmodules = array();

    foreach ($tests as $caller => &$test) {
      if ($this->testswarm_user_can_run_test($test)) {
        $allowdmodules[$test['module']][] = $test;
      }
    }
    return $allowdmodules;
  }

  /**
   * Overview of the current user tests.
   * Also used for automated testing.
   * @TODO: Split in at least 2 functions
   */
  public function testswarm_browser_tests() {
    $config = $this->config('testswarm.settings');

    // Auto refresh page each 60 seconds
    // @TODO: make it a setting
    $element = array(
      '#tag' => 'meta',
      '#attributes' => array(
        'http-equiv' => 'refresh',
        'content' => '300',
      ),
    );
    drupal_add_html_head($element, 'refresh');

    $TESTSWARM_AUTO_MODE = TRUE;

    if (isset($_SESSION['testswarm_tests']) && !empty($_SESSION['testswarm_tests'])) {
      return $this->testswarm_run_a_test();
    }

    $output = '';
    $tests = testswarm_defined_tests();
    $allowedtests = $this->testswarm_defined_tests_access();
    $enabled_themes = $this->testswarm_themes_to_test();

    $tests_to_run = array();
    foreach ($enabled_themes as $enabled_theme) {
      $tests_to_run[$enabled_theme] = $allowedtests;
    }

    $q = db_select('testswarm_test', 'tt')->fields('tt', array('theme', 'total'));
    $q->addExpression('COUNT(tt.id)', 'num_runs');
    $q->addExpression('AVG(tt.failed)', 'failed');
    $q->addExpression('AVG(tt.runtime)', 'runtime');
    $q->addExpression('MIN(tt.timestamp)', 'first_run');
    $q->addExpression('MAX(tt.timestamp)', 'last_run');
    $q->join('testswarm_info', 'ti', 'tt.info_id = ti.id');
    $q->fields('ti', array('caller'));
    $q->condition('tt.useragent', check_plain($_SERVER['HTTP_USER_AGENT']));
    $q->condition('ti.githash', $config->get('testswarm_githash'));
    $q->groupBy('ti.caller');
    $q->groupBy('tt.theme');
    $q->orderBy('ti.caller');
    $result = $q->execute()->fetchAll();

    $browser = get_browser();
    $output .= property_exists($browser, 'parent') ? '<h2>' . $browser->browser . ' (' . $browser->parent . ')' . '</h2>' : '<h2>' . $browser->browser . '</h2>';
    $output .= '<p>' . check_plain($_SERVER['HTTP_USER_AGENT']) . '</p>';
    $githash = $config->get('testswarm_githash');
    $link = empty($githash) ? l(\Drupal::VERSION, 'http://drupal.org/project/drupal') : l($this->testswarm_short_githash(), 'http://drupalcode.org/project/drupal.git/commit/' . $githash);
    $output .= '<p>Current drupal version: ' . $link . '</p>';
    $output .= '<p>' . l('Run all tests', 'testswarm-run-all-tests') . '</p>';

    $header = array(
      'status',
      'test',
      'theme',
      '# runs',
      '# tests',
      '# failed',
      'time taken',
      'first run',
      'last run',
    );
    $rows = array();
    foreach ($result as $rowdata) {
      $image = ($rowdata->failed < 1 ? 'message-24-ok' : 'message-24-error');
      $rows[] = array(
        'data' => array(
          theme('image', array('uri' => 'core/misc/'. $image .'.png', 'alt'  => ($rowdata->failed < 1 ? t('passed') : t('failed')))),
          $tests[$rowdata->caller]['module'] . '::' . $rowdata->caller
            . ' | ' . l('Details', 'testswarm-tests/detail/' . $rowdata->caller)
            . ($this->testswarm_user_can_run_test($tests[$rowdata->caller]) ? ' | ' . l(t('Test now'), 'testswarm-run-a-test/' . $rowdata->caller, array('query' => array('testswarm-destination' => 'testswarm-tests/detail/' . $rowdata->caller))) : '')
            . ($this->testswarm_user_can_run_test($tests[$rowdata->caller]) ? ' | ' . l(t('Test manually'), 'testswarm-run-a-test/' . $rowdata->caller, array('query' => array('testswarm-destination' => 'testswarm-tests/detail/' . $rowdata->caller, 'debug' => 'on'))) : '')
            . (user_access('administer testswarm tests') ? ' | ' . l(t('Clear test details'), 'testswarm-tests/clear/' . $rowdata->caller, array('query' => array('destination' => 'testswarm-tests/detail/' . $rowdata->caller))) : ''),
          $rowdata->theme,
          $rowdata->num_runs,
          $rowdata->total,
          $rowdata->failed,
          $rowdata->runtime,
          format_date($rowdata->first_run, 'short'),
          format_date($rowdata->last_run, 'short'),
        ),
        'class' => ($rowdata->failed == 0 ? array('testswarm-passed') : array('testswarm-failed')),
      );
      if (isset($tests_to_run[$rowdata->theme]) && array_key_exists($rowdata->caller, $tests_to_run[$rowdata->theme])) {
        unset($tests_to_run[$rowdata->theme][$rowdata->caller]);
        if (empty($tests_to_run[$rowdata->theme])) {
          unset($tests_to_run[$rowdata->theme]);
        }
      }
    }

    if (!empty($tests_to_run)) {
      if ($TESTSWARM_AUTO_MODE) {
        $themes = array_keys($tests_to_run);
        $theme = array_shift($themes);
        $test_to_run = array_shift($tests_to_run);
        $test = array_shift($test_to_run);
        $query = $test['query'];
        if (!is_array($query)) {
          $query = (array)$query;
        }
        $query += array(
          'testswarm-test' => $test['caller'],
          'testswarm-theme' => $theme,
        );
        return new RedirectResponse(url($test['path'], array('absolute' => TRUE, 'query' => $query)));
      }
      else {
        foreach ($tests_to_run as $theme => $tests) {
          foreach ($tests as $test) {
            $row = array(
              'data' => array(
                $test['caller'] . ($this->testswarm_user_can_run_test($test) ? ' ' . l(t('Test now'), 'testswarm-run-a-test/' . $test['caller'], array('query' => array('testswarm-theme' => $theme))) : ''),
                0,
                0,
                0,
                0,
                '-',
                '-',
              ),
              'class' => array('testswarm-untested'),
            );
            array_unshift($rows, $row);
          }
        }
      }
    }

    $output .= theme('table', array(
      'header' => $header,
      'rows' => $rows,
    ));


    drupal_add_css(drupal_get_path('module', 'testswarm') . '/testswarm.css');
    return $output;

  }

  /**
   * Check permissions to see if the current user can run this test.
   */
  public function testswarm_user_can_run_test($test = array()) {
    foreach ($test['permissions'] as $permission) {
      if (!user_access($permission)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  public function testswarm_themes_to_test() {
    $themes = list_themes();
    $enabled_themes = array();

    foreach ($themes as $key => $theme) {
      if ($theme->status == 1) {
        $enabled_themes[] = $key;
      }
    }
    return $enabled_themes;
  }

  /**
   * Implements hook_custom_theme().
   */
  public function testswarm_custom_theme() {
    if (isset($_GET['testswarm-theme'])) {
      return $_GET['testswarm-theme'];
    }
  }

  /**
   * Set karma and redirect.
   */
  public function testswarm_set_karma($karma) {
    setrawcookie('karma', check_plain($karma), REQUEST_TIME + 31536000, '/');
    return new RedirectResponse(url('testswarm-run-all-tests', array('absolute' => TRUE)));
  }

  /**
   * Get all karma minus 'Jane Doe'.
   */
  public function testswarm_get_karmas() {
    $karmas = array();

    $q = db_select('testswarm_test', 'tt')->fields('tt', array('karma'));
    $q->addExpression('COUNT(karma)', 'count');
    $q->condition('karma', '', '<>');
    $q->condition('karma', 'jane doe', '<>');
    $q->groupBy('karma');
    $q->orderBy('karma');
    $result = $q->execute()->fetchAll();

    foreach ($result as $row) {
      $karmas[$row->karma] = $row->count;
    }

    return $karmas;
  }

  public function testswarm_run_browserstack_tests() {
    $url = $this->testswarm_browserstack_url();
    $still_running = drupal_http_request($url . "workers");
    $still_running->data = drupal_json_decode($still_running->data);
    if ($still_running->code != 200) {
      watchdog('TestSwarm Browserstack', 'Error retreiving running tests from browserstack: ' . check_plain($still_running->status_message));
      return;
    }
    elseif (!empty($still_running->data)) {
      watchdog('TestSwarm Browserstack', 'Browserstack is still running tests.');
      return;
    }
    $response = drupal_http_request($url . "browsers");
    if ($response->code != 200) {
      watchdog('TestSwarm Browserstack', 'Error retreiving browsers from browserstack: ' . check_plain($response->status_message));
      return;
    }
    $browsers = drupal_json_decode($response->data);
    $browser_url = url('testswarm-set-karma/browserstack', array('absolute' => TRUE));
    foreach ($browsers as $browser) {
      $data = "";
      $data .= "browser={$browser['browser']}&version={$browser['version']}&url={$browser_url}&timeout=300";
      $response = drupal_http_request($url . "worker", array('method' => 'POST', 'data' => $data));
      if ($response->code != 200) {
        watchdog('TestSwarm Browserstack', 'Error starting test for browser (' . check_plain($browser['browser'] . ' ' . $browser['version']) . '): ' . check_plain($response->status_message));
      }
    }
  }

  public function testswarm_delete_browserstack_workers() {
    $workers = $this->testswarm_get_workers();
    $url = $this->testswarm_browserstack_url();
    if ($workers) {
      foreach ($workers->data as $worker) {
        $delete = drupal_http_request($url . "worker/" . $worker['id'], array('method' => 'DELETE'));
        $delete->data = drupal_json_decode($delete->data);
      }
    }
    else {
      drupal_set_message(t('No workers were deleted'), 'warning');
    }
  }

  public function testswarm_get_workers() {
    $url = $this->testswarm_browserstack_url();
    $workers = drupal_http_request($url . "workers");
    if ($workers->code != 200) {
      $message = t('An error occured retreiving workers from browserstack: !message', array('!message' => $workers->status_message));
      watchdog('TestSwarm Browserstack', $message);
      drupal_set_message(check_plain($message), 'error');
      return FALSE;
    }
    $workers->data = drupal_json_decode($workers->data);
    return $workers;
  }

  public function testswarm_browserstack_url() {
    $config = $this->config('testswarm.settings');
    $username = $config->get('testswarm_browserstack_username');
    $password = $config->get('testswarm_browserstack_password');
    $url = $config->get('testswarm_browserstack_api_url');
    $url = substr($url, -1) == '/' ? $url : $url . '/';
    $url = str_replace(array('http://', 'https'), "", $url);
    $url = "http://{$username}:{$password}@{$url}";
    return $url;
  }

  public function testswarm_caller_info($caller) {
    $tests = testswarm_defined_tests();
    return isset($tests[$caller]) ? $tests[$caller] : array();
  }

  public function testswarm_test_ensure_info($testswarm_test) {
    $info_id = db_select('testswarm_info', 'ti')
      ->fields('ti', array('id'))
      ->condition('caller', $testswarm_test['caller'])
      ->condition('module', $testswarm_test['module'])
      ->condition('version', $testswarm_test['version'])
      ->condition('url', $testswarm_test['url'])
      ->condition('sitename', $testswarm_test['sitename'])
      ->execute()->fetchField();
    if (!$info_id) {
      $info_fields = array();
      foreach (array('caller', 'module', 'description', 'githash', 'sitename', 'url', 'version', 'timestamp') as $key) {
        $info_fields[$key] = $testswarm_test[$key];
      }
      $info_id = db_insert('testswarm_info')->fields($info_fields)->execute();
    }
    return $info_id;
  }

  private function testswarm_remote_urls() {
    global $base_url;
    return db_select('testswarm_info', 'ti')
        ->fields('ti', array('url', 'sitename'))
        ->condition('sitename', $this->config('system.site')->get('name'), '<>')
        ->condition('url', $base_url, '<>')
        ->groupBy('sitename')
        ->groupBy('url')
        ->execute()
        ->fetchAll();
  }
}

