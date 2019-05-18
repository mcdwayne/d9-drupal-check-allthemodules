<?php

/**
 * @file
 * Contains \Drupal\monster_menus\Form\VerifyIntegrityForm.
 */

namespace Drupal\monster_menus\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\DatabaseException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Renderer;
use Drupal\filter\Render\FilteredMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

class VerifyIntegrityForm extends FormBase {

  /**
   * Database Service Object.
   *
   * @var Connection
   */
  protected $database;

  /**
   * Renderer Service Object.
   *
   * @var Renderer
   */
  protected $renderer;

  /**
   * Constructs an object.
   */
  public function __construct(Connection $database, Renderer $renderer) {
    $this->database = $database;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'), $container->get('renderer'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mm_admin_verify_integrity';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    foreach (\Drupal::moduleHandler()->getModuleList() as $module) {
      module_load_install($module->getName());
    }

    $tests = \Drupal::moduleHandler()->invokeAll('mm_verify_integrity');
    if (!$tests) {
      return ['#markup' => $this->t('There are no tests to perform.')];
    }

    $i = $count_tests = $num_failed = 0;
    foreach ($tests as $heading => $list) {
      $rows = [];
      $open = FALSE;
      foreach ($list as $description => $test) {
        $count_tests++;
        $error = '';
        try {
          $result = $this->database->query("SELECT COUNT(*) FROM $test")->fetchField();
        }
        catch (DatabaseException $e) {
          $result = $e->getCode();
          $error = $e->getMessage();
        }
        if ($result !== '0') {
          $open = TRUE;
          $num_failed++;
          $result = $this->t('<strong>FAIL</strong>');
          $append = $this->renderString('SELECT * FROM ' . $this->database->prefixTables($test));
          if ($error) {
            $append .= $this->renderString($error);
          }
        }
        else {
          $result = $this->t('PASS');
          $append = '';
        }
        $rows[] = [
          FilteredMarkup::create('<div class="description">' . Html::escape($description) . '</div>' . $append),
          $result,
        ];
      }

      $form["fs$i"] = [
        '#type' => 'details',
        '#title' => $heading,
        '#open' => $open,
        'table' => [
          '#type' => 'table',
          '#header' => [
            ['data' => $this->t('Test')],
            ['data' => $this->t('Result')],
          ],
          '#rows' => $rows,
        ],
      ];
      $i++;
    }
    $msg = $num_failed ? $this->t('@failed of @total tests <strong>failed.</strong>', ['@failed' => $num_failed, '@total' => $count_tests]) : $this->t('All tests passed.');
    $form['all_passed'] = ['#markup' => $msg];
    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

  private function renderString($string) {
    $array = [
      '#type' => 'textfield',
      '#value' => Html::escape($string),
      '#size' => 80,
    ];
    return $this->renderer->render($array);
  }

}
