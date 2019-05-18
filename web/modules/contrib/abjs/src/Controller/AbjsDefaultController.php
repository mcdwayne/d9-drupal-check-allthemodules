<?php

namespace Drupal\abjs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Default controller for the abjs module.
 */
class AbjsDefaultController extends ControllerBase {

  /**
   * Turns a render array into a HTML string.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $userStorage;

  /**
   * Provides a service to handle various date related functionality.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Turns a render array into a HTML string.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new FeedTypeForm object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The services of date.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The render object.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;

    $this->userStorage = $this->entityTypeManager()->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * Lists all tests in a default table theme.
   *
   * Sorted by active tests first, then modified most recently, then
   * created most recently. For each test, link to test, and list active
   * status, conditions applied (with links), experiences with fractions
   * assigned (with links), and created and edited info.
   */
  public function abjsTestAdmin() {
    $db = Database::getConnection();
    $date_Formatter = $this->dateFormatter;

    $output = [];
    $header = [
      $this->t('ID'),
      $this->t('Name'),
      $this->t('Status'),
      $this->t('Conditions'),
      $this->t('Experiences'),
      $this->t('Created'),
      $this->t('Created By'),
      $this->t('Changed'),
      $this->t('Changed By'),
    ];
    $rows = [];
    $active_array = [$this->t('Inactive'), $this->t('Active')];
    $tests = $db->query("SELECT * FROM {abjs_test} ORDER BY active DESC, changed DESC, created DESC")->fetchAll();
    foreach ($tests as $test) {
      $test_link = [
        '#title' => $test->name,
        '#type' => 'link',
        '#url' => Url::fromRoute('abjs.test_edit_form', ['tid' => $test->tid]),
      ];

      $condition_list = [];
      $condition_output = '';
      $conditions = $db->query("SELECT tc.cid, c.name FROM {abjs_test_condition} AS tc INNER JOIN {abjs_condition} AS c ON tc.cid = c.cid WHERE tc.tid = :tid", [':tid' => $test->tid])->fetchAll();
      if (!empty($conditions)) {
        foreach ($conditions as $condition) {
          $condition_link = [
            '#title' => $condition->name . ' (c_' . $condition->cid . ')',
            '#type' => 'link',
            '#url' => Url::fromRoute('abjs.condition_edit_form', ['cid' => $condition->cid]),
          ];
          $condition_list[] = $this->renderer->render($condition_link);
        }
        $condition_output = [
          '#theme' => 'item_list',
          '#items' => $condition_list,
        ];
      }

      $experience_list = [];
      $experience_output = '';
      $experiences = $db->query("SELECT te.eid, te.fraction, e.name FROM {abjs_test_experience} AS te INNER JOIN {abjs_experience} AS e ON te.eid = e.eid WHERE te.tid = :tid", [':tid' => $test->tid])->fetchAll();
      if (!empty($experiences)) {
        foreach ($experiences as $experience) {
          $experience_link = [
            '#title' => '[' . $experience->fraction . '] ' . $experience->name . ' (e_' . $experience->eid . ')',
            '#type' => 'link',
            '#url' => Url::fromRoute('abjs.experience_edit_form', ['eid' => $experience->eid]),
          ];
          $experience_list[] = $this->renderer->render($experience_link);
        }
        $experience_output = [
          '#theme' => 'item_list',
          '#items' => $experience_list,
        ];
      }
      $user_created = $this->userStorage->load($test->created_by);
      $user_changed = $this->userStorage->load($test->changed_by);
      $rows[] = [
        't_' . $test->tid,
        $this->renderer->render($test_link),
        $active_array[$test->active],
        $this->renderer->render($condition_output),
        $this->renderer->render($experience_output),
        $date_Formatter->format($test->created),
        $user_created->toLink(),
        $date_Formatter->format($test->changed),
        $user_changed->toLink(),
      ];

    }

    $output['add'] = [
      '#title' => $this->t('Add new test'),
      '#type' => 'link',
      '#url' => Url::fromRoute('abjs.test_add_form'),
      '#attributes' => ['class' => 'button button-action button--primary button--small'],
      '#suffix' => '<br><br>',
    ];

    $output['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
    return $output;
  }

  /**
   * Lists all conditions in default table, sorted by modified date.
   *
   * For each condition, link to edit form, and list created and edited info.
   */
  public function abjsConditionAdmin() {
    $db = Database::getConnection();
    $date_Formatter = $this->dateFormatter;

    $output = [];
    $header = [
      $this->t('ID'),
      $this->t('Name'),
      $this->t('Created'),
      $this->t('Created By'),
      $this->t('Changed'),
      $this->t('Changed By'),
    ];
    $rows = [];

    $conditions = $db->query("SELECT * FROM {abjs_condition} ORDER BY changed DESC, created DESC")->fetchAll();
    foreach ($conditions as $condition) {
      $condition_link = [
        '#title' => $condition->name,
        '#type' => 'link',
        '#url' => Url::fromRoute('abjs.condition_edit_form', ['cid' => $condition->cid]),
      ];

      $user_created = $this->userStorage->load($condition->created_by);
      $user_changed = $this->userStorage->load($condition->changed_by);

      $rows[] = [
        'c_' . $condition->cid,
        $this->renderer->render($condition_link),
        $date_Formatter->format($condition->created),
        $user_created->toLink(),
        $date_Formatter->format($condition->changed),
        $user_changed->toLink(),
      ];
    }
    $output['add'] = [
      '#title' => $this->t('Add new condition'),
      '#type' => 'link',
      '#url' => Url::fromRoute('abjs.condition_add_form'),
      '#attributes' => ['class' => 'button button-action button--primary button--small'],
      '#suffix' => '<br><br>',
    ];
    $output['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
    return $output;

  }

  /**
   * Lists all experiences in default table, sorted by modified date.
   *
   * For each experience, link to edit form, and list created and edited info.
   */
  public function abjsExperienceAdmin() {
    $db = Database::getConnection();
    $date_Formatter = $this->dateFormatter;

    $output = [];
    $header = [
      $this->t('ID'),
      $this->t('Name'),
      $this->t('Created'),
      $this->t('Created By'),
      $this->t('Changed'),
      $this->t('Changed By'),
    ];
    $rows = [];

    $experiences = $db->query("SELECT * FROM {abjs_experience} ORDER BY changed DESC, created DESC")->fetchAll();
    foreach ($experiences as $experience) {
      $experience_link = [
        '#title' => $experience->name,
        '#type' => 'link',
        '#url' => Url::fromRoute('abjs.experience_edit_form', ['eid' => $experience->eid]),
      ];
      $user_created = $this->userStorage->load($experience->created_by);
      $user_changed = $this->userStorage->load($experience->changed_by);

      $rows[] = [
        'e_' . $experience->eid,
        $this->renderer->render($experience_link),
        $date_Formatter->format($experience->created),
        $user_created->toLink(),
        $date_Formatter->format($experience->changed),
        $user_changed->toLink(),
      ];
    }
    $output['add'] = [
      '#title' => $this->t('Add new experience'),
      '#type' => 'link',
      '#url' => Url::fromRoute('abjs.experience_add_form'),
      '#attributes' => ['class' => 'button button-action button--primary button--small'],
      '#suffix' => '<br><br>',
    ];
    $output['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
    return $output;

  }

}
