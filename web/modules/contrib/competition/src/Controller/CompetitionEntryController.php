<?php

namespace Drupal\competition\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Component\Utility\Unicode;
use Drupal\user\Access\RegisterAccessCheck;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\views\ViewExecutableFactory;
use Drupal\competition\Entity\Competition;
use Drupal\competition\CompetitionManager;
use Drupal\competition\CompetitionInterface;
use Drupal\competition\CompetitionEntryInterface;
use Drupal\competition\CompetitionReporter;
use Drupal\competition\CompetitionJudgingSetup;

/**
 * Class CompetitionEntryController.
 *
 * @package Drupal\competition\Controller
 */
class CompetitionEntryController extends ControllerBase {
  protected $storage;
  protected $typeStorage;
  protected $viewStorage;
  protected $viewFactory;
  protected $config;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * The competition manager.
   *
   * @var \Drupal\competition\CompetitionManager
   */
  protected $competitionManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The competition reporter service.
   *
   * @var \Drupal\competition\CompetitionReporter
   */
  protected $competitionReporter;

  /**
   * The judging setup service.
   *
   * @var \Drupal\competition\CompetitionJudgingSetup
   */
  protected $judgingSetup;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityStorageInterface $storage, EntityStorageInterface $type_storage, CompetitionManager $competition_manager, EntityStorageInterface $view_storage, ViewExecutableFactory $view_factory, ConfigFactory $config, DateFormatterInterface $date_formatter, ModuleHandlerInterface $module_handler, FormBuilderInterface $form_builder, CompetitionReporter $competition_reporter, CompetitionJudgingSetup $judging_setup, CurrentRouteMatch $route_match) {

    $this->storage = $storage;
    $this->typeStorage = $type_storage;
    $this->competitionManager = $competition_manager;
    $this->viewStorage = $view_storage;
    $this->viewFactory = $view_factory;
    $this->config = $config;
    $this->dateFormatter = $date_formatter;
    $this->moduleHandler = $module_handler;
    $this->formBuilder = $form_builder;
    $this->competitionReporter = $competition_reporter;
    $this->judgingSetup = $judging_setup;
    $this->routeMatch = $route_match;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    /* @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    /* @var \Drupal\views\ViewExecutableFactory $view_factory */
    $view_factory = $container->get('views.executable');

    $config = $container->get('config.factory');

    return new static(
      $entity_type_manager->getStorage('competition_entry'),
      $entity_type_manager->getStorage('competition'),
      $container->get('competition.manager'),
      $entity_type_manager->getStorage('view'),
      $view_factory,
      $config,
      $container->get('date.formatter'),
      $container->get('module_handler'),
      $container->get('form_builder'),
      $container->get('competition.reporter'),
      $container->get('competition.judging_setup'),
      $container->get('current_route_match')
    );

  }

  /**
   * Display add links for available bundles/types for entity competition_entry.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A render array for a list of the competition_entry bundles/types that
   *   can be added or if there is only one type/bunlde defined for the site,
   *   the function returns the add page for that bundle/type.
   */
  public function add(Request $request) {

    $types = $this->typeStorage->loadMultiple();

    if ($types && count($types) == 1) {
      $type = reset($types);

      if ($type->getStatus() == CompetitionInterface::STATUS_OPEN) {
        return $this->redirect('entity.competition_entry.add_form', [
          'competition' => $type->id(),
        ]);
      }
    }

    foreach ($types as $type => $competition) {
      if ($competition->getStatus() != CompetitionInterface::STATUS_OPEN) {
        unset($types[$type]);
      }
    }

    if (count($types) === 0) {
      if ($this->currentUser()->hasPermission('administer competitions')) {
        drupal_set_message($this->t('There are no open competitions. Create or open a competition to enter.'), 'warning');
        return $this->redirect('entity.competition.collection');
      }
      else {
        drupal_set_message($this->t('No competitions are available!'), 'warning');
        return $this->redirect('<front>');
      }
    }

    return [
      '#theme' => 'competition_list',
      '#content' => $types,
    ];

  }

  /**
   * Provides the page title for this controller.
   *
   * @param \Drupal\competition\Entity\CompetitionInterface $competition
   *   The custom bundle/type being added.
   * @param bool $is_reentry
   *   If this request is for reentry.
   *
   * @return string
   *   The page title.
   */
  public function getFormTitleAdd(CompetitionInterface $competition, $is_reentry) {

    $prefix = ($is_reentry ? 'Re-enter' : 'Enter') . ' the';
    if (in_array($competition->getStatus(), [
      Competition::STATUS_CLOSED,
    ])) {
      $prefix = '';
    }

    return $this->t('@prefix @cycle @label', [
      '@prefix' => $prefix,
      '@cycle' => $competition->getCycle(),
      '@label' => $competition->label(),
    ]);

  }

  /**
   * Serve competition_entry form.
   *
   * @param CompetitionInterface $competition
   *   The custom bundle to add.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function getForm(CompetitionInterface $competition, Request $request, $is_reentry) {

    $entry = $this->storage->create([
      'type' => $competition->id(),
      'cycle' => $competition->getCycle(),
      'uid' => $this->currentUser()->id(),
      'status' => CompetitionEntryInterface::STATUS_CREATED,
    ]);

    $limits = $competition->getEntryLimits();

    // Check for existing entries to this competition...if found, just redirect
    // user right to the edit form instead.
    // TODO: Improve this for multiple entries per cycle. Note that this call
    // does not limit to entries within configured interval (if interval is not
    // just the cycle).
    $current = $this->competitionManager
      ->getCompetitionEntries($entry);

    if (!empty($current)) {
      $entry = array_pop($current);

      if ($entry->getStatus() == CompetitionEntryInterface::STATUS_FINALIZED) {
        // Send user to read-only entry view.
        return $this->redirect('entity.competition_entry.canonical', [
          'competition_entry' => $entry->id(),
        ]);
      }
      elseif ($limits->require_user && $limits->allow_partial_save) {
        // Send user to entry edit form.
        drupal_set_message($this->t('Welcome back to your %cycle %label entry!', [
          '%cycle' => $competition->getCycleLabel(),
          '%label' => $competition->getLabel(),
        ]));

        return $this->redirect('entity.competition_entry.edit_form', [
          'competition_entry' => $entry->id(),
        ]);
      }
    }

    if ($competition->getStatus() == CompetitionInterface::STATUS_CLOSED) {

      drupal_set_message($this->t('The %cycle %label is @status.', [
        '%cycle' => $competition->getCycleLabel(),
        '%label' => $competition->getLabel(),
        '@status' => Unicode::strtolower($competition->getStatusLabel()),
      ]), 'warning');

      return $this->redirect('<front>');

    }

    if ($this->currentUser()->isAnonymous() && !empty($limits->require_user)) {
      drupal_set_message($this->t('Please log in or register before entering the %cycle %label!', [
        '%cycle' => $competition->getCycleLabel(),
        '%label' => $competition->label(),
      ]));

      return $this->redirect('user.register', $this->getRedirectDestination()->getAsArray());
    }

    $form = $this
      ->entityFormBuilder()
      ->getForm($entry, ($is_reentry ? 'reenter' : 'default'), [
        'is_reentry' => $is_reentry,
      ]);

    return $form;
  }

  /**
   * Routing callback for competition.user_register.
   *
   * This presents the core user registration form, but with a competition ID
   * in the URL, which is used contextually for form alters and storing
   * registration data.
   *
   * @param \Drupal\competition\CompetitionInterface $competition
   *   The competition entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If user cannot access register route, per core's access check.
   *
   * @see \Drupal\user\Access\RegisterAccessCheck
   *
   * @see competition_form_alter()
   */
  public function competitionRegister(CompetitionInterface $competition, Request $request) {

    if ($this->currentUser()->isAuthenticated()) {

      // If user is already logged in, attempt to redirect them to entry
      // add/edit.
      // Use '/competition/{competition}/enter' route for now; this goes to
      // ::addForm() which has some logic for determining add, edit, or view.
      return $this->redirect('entity.competition_entry.add_form', [
        'competition' => $competition->id(),
      ]);

    }
    else {

      // Call the access check that core /user/register route uses.
      // @see user.routing.yml
      $access_check = new RegisterAccessCheck();

      /* @var \Drupal\Core\Access\AccessResultInterface $access_result */
      $access_result = $access_check->access($this->currentUser());

      if ($access_result->isForbidden()) {
        throw new AccessDeniedHttpException();
      }

      // Since we don't currently have a login tab on this page, provide a
      // message linking to login form.
      // (Skip for AJAX requests - those should only be form rebuilds.)
      if (!$request->isXmlHttpRequest()) {

        // For now, use core login route, to get the password tab to show.
        // TODO: local task implementation...??
        // @see competition_menu_local_tasks_alter()
        drupal_set_message($this->t('Already registered? <a href=":login_url">Log in</a> to continue your entry.', [
          ':login_url' => Url::fromRoute('user.login')->toString(),
        ]));

      }

      // Show core register form.
      // @see \Drupal\user\Entity\User
      $new_user = $this->entityTypeManager()->getStorage('user')->create([]);
      return $this->entityFormBuilder()->getForm($new_user, 'register');

    }

  }

  /**
   * Routing callback for admin reports page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   */
  public function adminReports(Request $request) {

    // Prepend the date/time when the reporting data was last updated.
    $timestamp_last = $this->competitionReporter->getReportDataLastUpdated();
    if (empty($timestamp_last)) {
      $timestamp_markup = $this->t("<p><strong>Reporting data and counts last updated:</strong> (never)</p>");
    }
    else {
      $timestamp_markup = $this->t("<p><strong>Reporting data and counts last updated:</strong> @datetime (@ago ago)</p>", [
        '@datetime' => $this->dateFormatter->format($timestamp_last, 'medium'),
        '@ago' => $this->dateFormatter->formatTimeDiffSince($timestamp_last),
      ]);
    }

    // Collect all report groups...
    $groups = [];

    // User accounts without entries
    // Pull count from report data table, so it's consistent with the
    // number of records that would actually be exported.
    // TODO: clarify labeling that this is not a live count...?
    $users_count = $this->competitionReporter->getCountReportRecords('user');

    $groups['users'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Registered users with unstarted entries'),
      '#attributes' => [
        'class' => ['report-group'],
      ],
      'reports' => [
        '#theme' => 'item_list',
        '#items' => [],
        '#attributes' => [
          'class' => ['report-list'],
        ],
      ],
    ];

    if ($users_count > 0) {
      $groups['users']['reports']['#items'][] = Link::fromTextAndUrl(
        $this->t('Download report (@count)', [
          '@count' => $users_count,
        ]),
        Url::fromRoute('entity.competition_entry.report_generate', [
          'report_type' => 'user',
        ], [
          'attributes' => [
            'class' => ['report-link'],
          ],
        ])
      )->toString();
    }
    else {
      $groups['users']['reports']['#items'][] = [
        '#markup' => '<span class="report-label">' . $this->t("No user accounts to report.") . '</span>',
      ];
    }

    // Entries.
    $cycles = $this->config
      ->get('competition.settings')
      ->get('cycles');

    $statuses = $this->config
      ->get('competition.settings')
      ->get('statuses');

    $types = $this->typeStorage
      ->loadMultiple();

    foreach ($types as $type => $competition) {

      $reports = [];
      foreach ($cycles as $cycle => $cycle_label) {

        // Get entries count per type by cycle.
        // Pull count from report data table, so it's consistent with the
        // number of records that would actually be exported.
        // TODO: clarify labeling that this is not a live count...?
        $entry_count = $this->competitionReporter->getCountReportRecords('competition_entry', [
          'type' => $type,
          'cycle' => $cycle,
        ]);

        if ($entry_count > 0) {
          // Collect links for this cycle.
          $links = [];

          $links[] = '<span class="report-label cycle">' . $this->t("@label @title report:", [
            '@label' => $cycle_label,
            '@title' => $competition->label(),
          ]) . '</span>';

          $url_base = Url::fromRoute('entity.competition_entry.report_generate', [
            'type' => $competition->id(),
            'cycle' => $cycle,
          ], [
            'attributes' => [
              'class' => ['report-link', 'all'],
            ],
          ]);

          // Master (all entries in cycle)
          $links[] = Link::fromTextAndUrl($this->t('Master (@count)', [
            '@count' => $entry_count,
          ]), $url_base)->toString();

          // Per status.
          foreach ($statuses as $status_key => $status_label) {
            $count_status = $this->competitionReporter->getCountReportRecords('competition_entry', [
              'type' => $type,
              'cycle' => $cycle,
              'status' => $status_key,
            ]);

            $text_status = $this->t('@status (@count)', [
              '@status' => $status_label,
              '@count' => $count_status,
            ]);

            if ($count_status > 0) {
              $url_status = clone $url_base;
              $url_status->setRouteParameter('status', $status_key);
              $url_status->setOption('attributes', [
                'class' => ['report-link', 'status'],
              ]);

              $links[] = Link::fromTextAndUrl($text_status, $url_status)->toString();
            }
            else {
              $links[] = '<span class="report-link status empty">' . $text_status . '</span>';
            }
          }

          // Judging reports
          // TODO: improve this UI/markup; maybe custom theme hook + template?
          $links_judging = [];

          $links_judging[] = '<span class="report-label">' . $this->t("Judging reports:") . '</span><br/>';

          $judging = $competition->getJudging();

          if (empty($judging->rounds)) {

            $links_judging[] = '<span class="report-label empty">' . $this->t("(No judging rounds configured.)") . '</span>';

          }
          else {

            foreach (array_keys($judging->rounds) as $round_id) {

              $count_round = $this->competitionReporter->getCountReportJudgingEntities([
                'type' => $type,
                'cycle' => $cycle,
                'round_id' => $round_id,
              ]);

              $links_judging[] = '<span class="report-label judging-round">' . $this->t("Round @round_id (@count entries):", [
                '@round_id' => $round_id,
                '@count' => $count_round,
              ]) . '</span>';

              // Get applicable reports for this round (based on round type).
              $judging_reports = $this->competitionReporter->getJudgingReportsByRound($competition->id(), $round_id);

              foreach ($judging_reports as $key => $label) {

                $text = $this->t("@label", [
                  '@label' => $label,
                ]);

                if ($count_round > 0) {
                  // Check for placeholder 'entities' column key to indicate a
                  // combined report.
                  $report_cols = array_keys($this->competitionReporter->getJudgingReportColumns($key, $round_id));
                  $report_type = in_array('entities', $report_cols) ? 'combined' : 'judging';

                  $url = clone $url_base;
                  $url->setRouteParameter('report_type', $report_type);
                  $url->setRouteParameter('round_id', $round_id);
                  $url->setRouteParameter('judging_report', $key);

                  $url->setOption('attributes', [
                    'class' => ['report-link', 'judging'],
                  ]);

                  $links_judging[] = Link::fromTextAndUrl($text, $url)->toString();
                }
                else {
                  $links_judging[] = '<span class="report-link judging empty">' . $text . '</span>';
                }

              }

              $links_judging[] = '<br/>';

            }

          }

          // Add basic report links, then judging report links.
          $reports[] = [
            '#markup' => '<p>' . implode("", $links) . '</p><p>' . implode("", $links_judging) . '</p>',
          ];
        }
      }

      $groups[$type] = [
        '#type' => 'fieldset',
        '#title' => $competition->label(),
        '#attributes' => [
          'class' => ['report-group'],
        ],
        'description' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('These reports include competition entry data for each competition cycle.'),
        ],
        'reports' => [
          '#theme' => 'item_list',
          '#items' => $reports,
          '#attributes' => [
            'class' => ['report-list'],
          ],
        ],
      ];

    }

    $build = [
      '#attached' => [
        'library' => [
          'competition/competition',
        ],
      ],
      'last_updated' => [
        '#markup' => $timestamp_markup,
      ],
      'update' => [
        '#type' => 'link',
        '#title' => $this->t('Refresh data'),
        '#prefix' => '<p>',
        '#suffix' => '&nbsp;&nbsp;' . $this->t('Update all report data now. This action may take a while.') . '</p>',
        '#url' => Url::fromRoute('entity.competition_entry.report_update'),
        '#attributes' => [
          'class' => ['button'],
        ],
      ],
      'report_groups' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['competition-entry-reports'],
        ],
      ],
    ];

    $build['report_groups'] = array_merge($build['report_groups'], $groups);

    return $build;
  }

  /**
   * Routing callback for /admin/content/competition/reports/generate.
   *
   * Calls CompetitionReporter method to query for competition entry/user
   * records in `competition_entry_report_data` and writes them out to CSV file,
   * via a batch process.
   *
   * The report may be filtered by certain querystring parameters.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @see CompetitionReporter::generateReportSetBatch(),
   *   CompetitionReporter::generateReportBatchWriteCsv()
   */
  public function generateReport(Request $request) {

    $batch_response = NULL;

    // Pass all query params as-is. CompetitionReporter handles validating
    // both keys and values and throws exceptions if any problems; catch here.
    try {
      $batch_response = $this->competitionReporter->generateReportSetBatch($request->query->all());
    }
    catch (\Exception $e) {
      // TODO? Currently, CompetitionReporter is throwing generic exceptions,
      // so we don't know here exactly which error occurred - since only the
      // exception message distinguishes them. Thus, Reporter also handles
      // setting a user-friendly Drupal message - which might be more
      // appropriately done here?
      //
      // Fall through to redirect below.
    }

    // Note: generateReportSetBatch() passes back the result of batch_process(),
    // which in our case we expect to be a RedirectResponse that kicks off the
    // batch requests. However, if the batch is altered to be non-progressive,
    // it will be NULL. In that case we need to respond with something - go back
    // to admin reports page.
    // @see batch_process()
    if ($batch_response !== NULL) {
      return $batch_response;
    }
    else {
      return $this->redirect('entity.competition_entry.reports');
    }

  }

  /**
   * Routing callback for /admin/content/competition/reports/download.
   *
   * Serves a report CSV file to the browser for download. Includes headers
   * to force browser to download file rather than attempt to open it.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param string $filename
   *   The filename string.
   */
  public function downloadReportCsv(Request $request, $filename) {

    $filepath = drupal_realpath('private://competition/reports/' . $filename);

    if (!file_exists($filepath)) {
      throw new NotFoundHttpException();
    }

    $response = new Response(file_get_contents($filepath) . "\n");
    $response->headers->set('Pragma', 'public');
    $response->headers->set('Expires', '0');
    $response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
    $response->headers->set('Cache-Control', 'private', FALSE);
    $response->headers->set('Content-Type', 'application/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '";');
    $response->headers->set('Content-Transfer-Encoding', 'binary');
    $response->send();

    exit;

  }

  /**
   * Routing callback for /admin/content/competition/reports/update.
   *
   * Uses batch API to add/update any new and changed user and entry entities
   * into the reporting data table.
   *
   * @see CompetitionReporter::updateAllReportData()
   */
  public function updateReportData() {

    $batch_response = $this->competitionReporter->updateAllReportData(TRUE);

    // Note: updateAllReportData() passes back the result of batch_process(),
    // which in our case we expect to be a RedirectResponse that kicks off the
    // batch requests. However, if the batch is altered to be non-progressive,
    // it will be NULL.
    // Also if nothing is found to be added/updated in the report data table,
    // this will not even start the batch.
    // In these cases we need to respond with something - go back to admin
    // reports page.
    // @see batch_process()
    if ($batch_response instanceof RedirectResponse) {
      return $batch_response;
    }
    else {
      return $this->redirect('entity.competition_entry.reports');
    }

  }

  /**
   * Routing callback for admin judging page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string|null $competition
   *   The competition entity ID.
   *   Note: This is not upcast/type-hinted as a CompetitionInterface entity, so
   *   that a single route can be used with or without {competition} route param
   *   and be a local task tab.
   * @param string $callback
   *   Callback type: 'setup', 'round-*' label or queue label.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   If no competition types are defined, or $competition is not the ID of
   *   a competition that exists.
   */
  public function adminJudging(Request $request, $competition, $callback) {

    $is_admin = $this->currentUser()->hasPermission('administer competition judging');
    $is_admin_setup = $this->currentUser()->hasPermission('administer competition judging setup');

    // The arg is actually an ID, not the entity.
    $competition_id = $competition;

    $types = $this->typeStorage->loadMultiple();

    // No competitions exist, or no such competition exists.
    if (empty($types) || (!empty($competition_id) && empty($types[$competition_id]))) {
      throw new NotFoundHttpException();
    }

    // Check that user has selected a competition.
    if (!$competition_id) {
      // Pare down competitions according to current user access.
      $competitions_access = [];

      if (!$is_admin) {
        // For non-admin judges, limit to competitions to which they are
        // assigned.
        $competitions_access = $this->judgingSetup->getJudgeAssignedCompetitions($this->currentUser()->id());

        if (empty($competitions_access)) {
          drupal_set_message($this->t("You are not currently assigned to judge any competitions."));
          return [];
        }
      }
      else {
        // Admins can access all.
        $competitions_access = $types;
      }

      if (count($competitions_access) == 1) {
        // If only one competition, redirect to it.
        $competition = reset($competitions_access);

        return $this->redirect('entity.competition_entry.judging', [
          'competition' => $competition->id(),
        ]);
      }
      else {
        // Provide links to select a competition.
        $links = [];

        foreach ($competitions_access as $competition) {
          $links[$competition->id()] = [
            'title' => $competition->label(),
            'url' => Url::fromRoute('entity.competition_entry.judging', [
              'competition' => $competition->id(),
            ]),
          ];
        }

        drupal_set_message($this->t('Select a competition to judge.'));

        return [
          '#theme' => 'links',
          '#links' => $links,
          '#attributes' => [
            'class' => ['admin-list'],
          ],
        ];
      }
    }

    // Now we are working with a single competition.
    $competition = $types[$competition_id];
    $judging = $competition->getJudging();

    // Check if judging is enabled for the competition.
    if (!$judging->enabled) {
      if ($competition->access('update')) {
        drupal_set_message($this->t('Judging is not yet enabled for %cycle %label entries. You may enable judging by editing the competition settings below.', [
          '%cycle' => $competition->getCycleLabel(),
          '%label' => $competition->getLabel(),
        ]), 'warning');

        return $this->redirect('entity.competition.edit_form', [
          'competition' => $competition->id(),
        ]);
      }
      else {
        drupal_set_message($this->t('Judging is not yet active for %cycle %label entries.', [
          '%cycle' => $competition->getCycleLabel(),
          '%label' => $competition->getLabel(),
        ]), 'warning');

        return [];
      }
    }

    // For judges without permission to access setup, return if there is no
    // active round, since they can't do anything else.
    if (!$is_admin_setup && empty($judging->active_round)) {
      drupal_set_message($this->t('Judging is not yet active for %cycle %label entries.', [
        '%cycle' => $competition->getCycleLabel(),
        '%label' => $competition->getLabel(),
      ]), 'warning');

      return [];
    }

    // Redirect to default callback if path arg not provided.
    if (empty($callback)) {
      $callback_default = '';

      if ($is_admin_setup) {
        $callback_default = (!empty($judging->active_round) ? 'round-' . $judging->active_round : 'setup');
      }
      elseif ($is_admin) {
        $callback_default = 'round-' . $judging->active_round;
      }
      else {
        $callback_default = 'assignments';
      }

      return $this->redirect('entity.competition_entry.judging', [
        'competition' => $competition->id(),
        'callback' => $callback_default,
      ]);
    }

    // Non-admin judges may only access their assignments.
    if (!$is_admin && $callback !== 'assignments') {
      throw new AccessDeniedHttpException();
    }

    // Specific permission is required for the setup tab.
    if ($callback == 'setup' && !$is_admin_setup) {
      throw new AccessDeniedHttpException();
    }

    // Check that requested round is active round (judging admins only).
    if (strstr($callback, 'round-') && (empty($judging->active_round) || $callback != 'round-' . $judging->active_round)) {
      if (empty($judging->active_round)) {
        drupal_set_message($this->t('No judging round is active yet.'), 'warning');

        // Only setup-admins could get here.
        return $this->redirect('entity.competition_entry.judging', [
          'competition' => $competition->id(),
          'callback' => 'setup',
        ]);
      }
      else {
        drupal_set_message($this->t('Redirected to the active judging round, Round @round_id.', [
          '@round_id' => $judging->active_round,
        ]), 'warning');

        return $this->redirect('entity.competition_entry.judging', [
          'competition' => $competition->id(),
          'callback' => 'round-' . $judging->active_round,
        ]);
      }
    }

    // Build page content.
    $links = $this->judgingSetup->getNavLinks($competition, $callback);

    // These aren't true core tabs...there is no option for tertiary tabs. So
    // we're using secondary tab classes, plus a bit of JS hackery to make it
    // work.
    // @see Drupal.behaviors.competition_judging.tabs()
    $build = [
      '#title' => $this->t('@cycle @label Judging', [
        '@cycle' => $competition->getCycleLabel(),
        '@label' => $competition->getLabel(),
      ]),
      '#attached' => [
        'library' => [
          'competition/competition',
        ],
      ],
      'status' => [
        '#markup' => '<div class="competition-entry-judging-list-messages"></div>',
      ],
      'judging' => [
        '#prefix' => '<nav class="tabs is-horizontal position-container is-horizontal-enabled nav-judging">',
        '#suffix' => '</nav>',
        '#theme' => 'links',
        '#links' => $links,
        '#attributes' => [
          'class' => ['tabs', 'secondary', 'clearfix'],
        ],
      ],
      'finalize_scores' => [],
      'content' => [],
    ];

    if ($callback == 'setup') {
      // Setup tab.
      $build['content'] = [
        'form_judges_rounds' => $this->formBuilder->getForm('\Drupal\competition\Form\CompetitionJudgesRoundsSetupForm', $competition),
        'form_round_workflow' => $this->formBuilder->getForm('\Drupal\competition\Form\CompetitionJudgingRoundWorkflowForm', $competition),
      ];
    }
    else {
      // Round, queue, or assignments tab.
      // If this is a scoring round then show finalize scores form.
      // TODO: voting round isolation into voting submodule.
      if ($callback == 'assignments' || strstr($callback, 'round-')) {
        // We've already ensured that round-n == active round.
        if ($judging->rounds[$judging->active_round]['round_type'] != 'voting') {
          $build['finalize_scores'] = $this->formBuilder->getForm('\Drupal\competition\Form\CompetitionJudgingFinalizeScoresForm', $competition);
        }
      }

      // List builder handles logic of retrieving list of entries.
      // @see \Drupal\competition\CompetitionEntryListBuilder
      $list_builder = $this->entityTypeManager()->getListBuilder('competition_entry');
      $build['content'] = $list_builder->render();
    }

    return $build;

  }

  /**
   * Show score details.
   *
   * Render the score details table for an entry in the given round.
   *
   * @param string $is_ajax
   *   Either 'ajax' or 'nojs', according to whether JS is enabled.
   * @param \Drupal\competition\CompetitionEntryInterface $entry
   *   The competition entry object.
   * @param int $round_id
   *   The round ID.
   */
  public function showScoreDetails($is_ajax, CompetitionEntryInterface $entry, $round_id) {

    // Include 'close' link only if this is being added to the page by AJAX;
    // doesn't really make sense if it's displayed on its own page.
    $build = $entry->renderScoreDetailsTable($round_id, [
      'close' => ($is_ajax == 'ajax'),
    ]);

    if ($is_ajax == 'ajax') {
      $response = new AjaxResponse();
      $response->addCommand(new RemoveCommand('.judging-score-details-wrap'));
      $response->addCommand(new AfterCommand('#entry-score-' . $entry->id(), $build));
      return $response;
    }
    else {
      return $build;
    }

  }

  /**
   * Display archives.
   *
   * Presents the archived competition_entry entities of
   * given bundle/type and cycle.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object - this is automatically passed.
   * @param \Drupal\competition\Entity\CompetitionInterface $competition
   *   The competition bundle whose archives are to be displayed.
   * @param string $cycle
   *   The competition cycle names by which to filter the entries. Multiple
   *   cycles may be passed by joining with '+', but note that URL decoding
   *   converts them to ' ' by this point.
   *
   * @return array
   *   A renderable array with #theme set to 'competition_entry_archives'.
   *
   * @see competition_entry-archives.html.twig
   */
  public function displayArchives(Request $request, CompetitionInterface $competition, $cycle) {

    $build = [];

    // Get keys of all cycles that are set as archived on this competition.
    $cycles_archived = array_keys($competition->getCyclesArchived());

    // URL decoding converts '+' in URLs to ' '. To ensure we extract and
    // validate accurately, retrieve $cycle route parameter again from the
    // original (raw) request path.
    // Since the path has been matched to this route already, we know that the
    // 4th path component is the $cycle arg.
    $path_components = explode('/', ltrim($request->getPathInfo(), '/'));
    $cycle = $path_components[3];

    // Split up $cycle path arg; collect only valid cycle keys to filter by.
    $cycles_filter = [];
    foreach (explode('+', $cycle) as $cycle_potential) {
      if (in_array($cycle_potential, $cycles_archived)) {
        $cycles_filter[] = $cycle_potential;
      }
    }

    // Load view, check access, build render array for it.
    $view_name = 'competition_entry';
    $view_display_id = 'embed_archives';

    /* @var \Drupal\views\ViewEntityInterface $view */
    $view = $this->viewStorage->load($view_name);
    if (!empty($view)) {
      /* @var \Drupal\views\ViewExecutable $view_executable */
      $view_executable = $this->viewFactory->get($view);
      if ($view_executable->access($view_display_id)) {

        // Contextual arguments to the view:
        // - the competition_entry type (bundle), i.e. competition id
        // - one or more cycle names, joined by '+' if multiple, as that
        //   indicates to OR them as conditions.
        $view_args = [
          $competition->id(),
          implode('+', $cycles_filter),
        ];

        // Since this view display is rendered by code, allow other modules to
        // alter the contextual filter values.
        \Drupal::moduleHandler()->alter('competition_entry_archives_view_contextual_filters', $view_args);

        $build = [
          '#theme' => 'competition_entry_archives_page',
          '#competition' => $competition,
          '#cycles_filter' => $cycles_filter,

          // There's a 'view' element type defined. Use this as
          // views_embed_view() does.
          // @see \Drupal\views\Element\View
          '#view_renderable' => [
            '#type' => 'view',
            '#name' => $view_name,
            '#display_id' => $view_display_id,
            '#arguments' => $view_args,
          ],
        ];
      }
    }

    // If anything went wrong (could not load view, user does not have access
    // to view, etc.) - just return Page Not Found.
    if (empty($build)) {
      throw new NotFoundHttpException();
    }
    else {
      return $build;
    }

  }

  /**
   * Route callback for: entity.competition_entry.archives_current.
   *
   * Find most recent archived cycle for given competition, and redirect to
   * the archives path using that cycle. (If no archived cycles, throw a
   * Page Not Found exception.)
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   * @param \Drupal\competition\CompetitionInterface $competition
   *   The competition entity object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect user to archive.
   */
  public function displayArchivesCurrent(Request $request, CompetitionInterface $competition) {

    // Get keys of all cycles that are set as archived on this competition.
    $cycles_archived = array_keys($competition->getCyclesArchived());

    if (!empty($cycles_archived)) {
      // Find this competition's most recent archived cycle. (This assumes
      // cycles are configured in oldest-to-newest order on Structure >
      // Competitions > Settings admin form.)
      $cycle = $cycles_archived[count($cycles_archived) - 1];

      // Retain any query params.
      $options = [];
      $query_params = $request->query->all();
      if (!empty($query_params)) {
        // Check params values for '+' characters that got url-decoded to spaces
        // and restore them. (This is important for values that go into view
        // filters, which use '+' to accept multiple values to be OR'd.)
        $raw_uri = \Drupal::request()->getRequestUri();
        foreach ($query_params as $k => &$v) {
          // Position of this value in the raw uri.
          $raw_uri_pos = strpos($raw_uri, $k . '=') + strlen($k . '=');
          $offset = 0;
          while (($pos = strpos($v, ' ', $offset)) !== FALSE) {
            // If raw uri has '+' at this position, replace it in param value.
            if (substr($raw_uri, $raw_uri_pos + $pos, 1) === '+') {
              $v = substr($v, 0, $pos) . '+' . substr($v, $pos + 1);
            }
            $offset = $pos + 1;
          }
        }

        $options['query'] = $query_params;
      }

      $url = Url::fromRoute('entity.competition_entry.archives', [
        'competition' => $this->routeMatch->getRawParameter('competition'),
        'cycle' => $cycle,
      ], $options)->toString();

      // This ain't pretty - but we want actual '+' in the URL.
      $url = str_replace('%2B', '+', $url);

      return new RedirectResponse($url);
    }
    else {
      throw new NotFoundHttpException();
    }
  }

}
