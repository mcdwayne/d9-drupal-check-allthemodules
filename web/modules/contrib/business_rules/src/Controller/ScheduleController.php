<?php

namespace Drupal\business_rules\Controller;

use Drupal\business_rules\Entity\Action;
use Drupal\business_rules\Entity\Schedule;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\Util\BusinessRulesUtil;
use Drupal\business_rules\VariablesSet;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ScheduleController.
 *
 *  Returns responses for Schedule routes.
 */
class ScheduleController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * A dateFormatter object.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  private $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public function __construct(DateFormatterInterface $dateFormatter) {

    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $dateFormatter = $container->get('date.formatter');

    return new static($dateFormatter);
  }

  /**
   * View schedule canonical.
   *
   * @return array
   *   The render array.
   */
  public function view($business_rules_schedule) {
    $schedule         = Schedule::load($business_rules_schedule);
    $output['#title'] = $schedule->label();

    $output['name'] = [
      '#type'   => 'item',
      '#title'  => $this->t('Name'),
      '#markup' => $schedule->getName(),
    ];

    $output['status'] = [
      '#type'   => 'item',
      '#title'  => $this->t('Executed'),
      '#markup' => $schedule->isExecuted() ? $this->t('Yes') : $this->t('No'),
    ];

    $output['created'] = [
      '#type'   => 'item',
      '#title'  => $this->t('Created'),
      '#markup' => $this->dateFormatter->format($schedule->getCreatedTime(), 'medium'),
    ];

    $output['changed'] = [
      '#type'   => 'item',
      '#title'  => $this->t('Changed'),
      '#markup' => $this->dateFormatter->format($schedule->getChangedTime(), 'medium'),
    ];

    $output['scheduled'] = [
      '#type'   => 'item',
      '#title'  => $this->t('Scheduled'),
      '#markup' => $this->dateFormatter->format($schedule->getScheduled(), 'medium'),
    ];

    $output['triggered_by'] = [
      '#type'   => 'item',
      '#title'  => $this->t('Triggered by'),
      '#markup' => $schedule->getTriggeredBy()
        ->toLink(NULL, 'edit-form')
        ->toString(),
    ];

    return $output;
  }

  /**
   * Manual execution of a scheduled item.
   */
  public function execute($business_rules_schedule) {
    $task = Schedule::load($business_rules_schedule);

    /** @var \Drupal\business_rules\Entity\Action $action */
    $action    = $task->getTriggeredBy();
    $items     = $action->getSettings('items');
    $container = \Drupal::getContainer();
    $util      = new BusinessRulesUtil($container);

    $reacts_on_definition = \Drupal::getContainer()
      ->get('plugin.manager.business_rules.reacts_on')
      ->getDefinition('cron_runs');


    $task_event   = $task->getEvent();
    $loop_control = time();
    $dummy        = new \stdClass();
    $variables    = new VariablesSet();

    $dummy_event = new BusinessRulesEvent($dummy, [
      'entity_type_id'   => '',
      'bundle'           => NULL,
      'entity'           => NULL,
      'entity_unchanged' => NULL,
      'variables'        => $variables,
      'reacts_on'        => $reacts_on_definition,
      'loop_control'     => $loop_control,
    ]);

    $event = $task_event instanceof BusinessRulesEvent ? $task_event : $dummy_event;
    /** @var \Drupal\Core\Entity\Entity $entity */
    $entity = $task_event->getSubject() instanceof Entity ? $task_event->getSubject() : FALSE;
    if ($entity) {
      $entity = \Drupal::entityTypeManager()
        ->getStorage($entity->getEntityTypeId())
        ->load($entity->id());
      $task_event->setArgument('entity', $entity);
      $event = new BusinessRulesEvent($entity, $task_event->getArguments());
    }

    try {
      foreach ($items as $item) {
        $action_item = Action::load($item['id']);
        $action_item->execute($event);
      }

      $entity = $event->getSubject() instanceof Entity ? $event->getSubject() : FALSE;
      if ($entity && $task->getUpdateEntity()) {
        $entity_exists = \Drupal::entityTypeManager()
          ->getStorage($entity->getEntityTypeId())
          ->load($entity->id());
        if ($entity_exists instanceof Entity) {
          $entity->save();
        }
      }

      $task->setExecuted(1);
      $task->save();
      $util->logger->notice(t('Scheduled task id: @id, name: "@name", triggered by: "@by" has been executed at: @time', [
        '@id'   => $task->id(),
        '@name' => $task->getName(),
        '@by'   => $task->getTriggeredBy()->id(),
        '@time' => $container->get('date.formatter')
          ->format(time(), 'medium'),
      ]));
    }
    catch (\Exception $e) {
      $util->logger->error($e->getMessage());
    }

    return new RedirectResponse('/admin/config/workflow/business_rules/schedule/collection');
  }

  /**
   * Displays a Schedule  revision.
   *
   * @param int $schedule_revision
   *   The Schedule  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($schedule_revision) {
    $schedule     = $this->entityManager()
      ->getStorage('business_rules_schedule')
      ->loadRevision($schedule_revision);
    $view_builder = $this->entityManager()
      ->getViewBuilder('business_rules_schedule');

    // Return $view_builder->view($schedule);
    return $this->view($schedule_revision);
  }

  /**
   * Page title callback for a Schedule  revision.
   *
   * @param int $schedule_revision
   *   The Schedule  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($schedule_revision) {
    $schedule = $this->entityManager()
      ->getStorage('schedule')
      ->loadRevision($schedule_revision);

    return $this->t('Revision of %title from %date', [
      '%title' => $schedule->label(),
      '%date'  => format_date($schedule->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Schedule .
   *
   * @param string $business_rules_schedule
   *   A Schedule object id.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview($business_rules_schedule) {
    $business_rules_schedule = Schedule::load($business_rules_schedule);
    $account                 = $this->currentUser();
    $langcode                = $business_rules_schedule->language()->getId();
    $langname                = $business_rules_schedule->language()->getName();
    $languages               = $business_rules_schedule->getTranslationLanguages();
    $has_translations        = (count($languages) > 1);
    $schedule_storage        = $this->entityManager()
      ->getStorage('business_rules_schedule');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', [
      '@langname' => $langname,
      '%title'    => $business_rules_schedule->label(),
    ]) : $this->t('Revisions for %title', ['%title' => $business_rules_schedule->label()]);
    $header          = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all schedule revisions") || $account->hasPermission('administer schedule entities')));
    $delete_permission = (($account->hasPermission("delete all schedule revisions") || $account->hasPermission('administer schedule entities')));

    $rows = [];

    $vids = $schedule_storage->revisionIds($business_rules_schedule);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\business_rules\ScheduleInterface $revision */
      $revision = $schedule_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)
        ->isRevisionTranslationAffected()
      ) {
        $username = [
          '#theme'   => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $time = $revision->getRevisionCreationTime();
        if (is_numeric($time)) {
          $date = \Drupal::service('date.formatter')
            ->format($revision->getRevisionCreationTime(), 'short');
          if ($vid != $business_rules_schedule->getRevisionId()) {
            $link = $this->l($date, new Url('entity.business_rules_schedule.revision', [
              'schedule'          => $business_rules_schedule->id(),
              'schedule_revision' => $vid,
            ]));
          }
          else {
            $link = $business_rules_schedule->link($date);
          }
        }
        else {
          $link = '';
        }

        $row    = [];
        $column = [
          'data' => [
            '#type'     => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context'  => [
              'date'     => $link,
              'username' => \Drupal::service('renderer')
                ->renderPlain($username),
              'message'  => [
                '#markup'       => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[]  = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url'   => Url::fromRoute('entity.business_rules_schedule.revision_revert', [
                'schedule'          => $business_rules_schedule->id(),
                'schedule_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url'   => Url::fromRoute('entity.business_rules_schedule.revision_delete', [
                'schedule'          => $business_rules_schedule->id(),
                'schedule_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type'  => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['schedule_revisions_table'] = [
      '#theme'  => 'table',
      '#rows'   => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
