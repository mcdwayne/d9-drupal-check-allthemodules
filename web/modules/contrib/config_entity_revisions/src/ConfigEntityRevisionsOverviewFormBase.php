<?php

namespace Drupal\config_entity_revisions;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Url;
use Drupal\diff\DiffLayoutManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Utility\LinkGenerator;

/**
 * Controller to make library functions available to various consumers.
 */
abstract class ConfigEntityRevisionsOverviewFormBase extends FormBase implements ConfigEntityRevisionsOverviewFormBaseInterface {

  /**
   * The renderer service.
   *
   * @var ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The date formatter service.
   *
   * @var DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var RendererInterface
   */
  protected $renderer;

  /**
   * Wrapper object for simple configuration from diff.settings.yml.
   *
   * @var ImmutableConfig
   */
  protected $config;

  /**
   * Entity type manager
   *
   * @var EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Diff layout manager
   *
   * @var DiffLayoutManager;
   */
  protected $diffLayoutManager;

  /**
   * Link generator service
   *
   * @var LinkGenerator;
   */
  protected $linkService;


  /**
   * Constructs a ConfigEntityRevisionsController object.
   *
   * @param DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param RendererInterface $renderer
   *   The renderer service.
   * @param ImmutableConfig $config
   *   The configuration service.
   * @param ModuleHandler $module_handler
   *   The module handler service.
   * @param EntityTypeManager $entity_type_manager
   *   The entity type manager service.
   * @param DiffLayoutManager $diff_layout_manager
   *   The diff layout manager service
   * @param LinkGenerator $link
   *   The Link generator service.
   */
  public function __construct(
    DateFormatterInterface $date_formatter,
    RendererInterface $renderer,
    ImmutableConfig $config,
    ModuleHandler $module_handler,
    EntityTypeManager $entity_type_manager,
    DiffLayoutManager $diff_layout_manager,
    LinkGenerator $link
  ) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    $this->config = $this->config('diff.settings');
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->diffLayoutManager = $diff_layout_manager;
    $this->linkService = $link;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('config.factory')->get('diff.settings'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.diff.layout'),
      $container->get('link_generator')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'config_entity_revisions_overview';
  }

  /**
   * Set column attributes and return config array.
   *
   * @param string $name
   *   Name attribute.
   * @param string $return_val
   *   Return value attribute.
   * @param string $default_val
   *   Default value attribute.
   *
   * @return array
   *   Configuration array.
   */
  protected function buildSelectColumn($name, $return_val, $default_val) {
    return [
      '#type' => 'radio',
      '#title_display' => 'invisible',
      '#name' => $name,
      '#return_value' => $return_val,
      '#value' => FALSE,
      '#default_value' => $default_val,
    ];
  }

  /**
   * Generates an overview table of older revisions of a config entity.
   *
   * @param array $form
   *   A form being built.
   * @param FormStateInterface $form_state
   *   The form state.
   * @param ConfigEntityInterface $configEntity
   *   A configuration entity.
   *
   * @return array
   *   An array as expected by \Drupal\Core\Render\RendererInterface::render().
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function buildForm(array $form, FormStateInterface $form_state, ConfigEntityInterface $configEntity = NULL) {

    // Disable for now, having seen https://www.drupal.org/project/diff/issues/2452523.
    $haveDiff = $this->moduleHandler->moduleExists('diff') && FALSE;

    $configEntityType = $configEntity->getEntityType()->id();

    /* @var $contentEntityStorage \Drupal\Core\Entity\ContentEntityStorageInterface */
    $contentEntityStorage = $configEntity->contentEntityStorage();

    $account = $this->currentUser();

    /* @var $revisionsEntity \Drupal\config_entity_revisions\Entity\ConfigEntityRevisions */
    $revisionsEntity = $configEntity->getContentEntity();

    if (!$revisionsEntity) {
      $form['#title'] = $this->t('No revisions for %title', ['%title' => $configEntity->label()]);

      $form['message'] = [
        '#theme' => 'markup',
        '#markup' => "No revisions have been created for this configuration entity yet.",
      ];

      return $form;
    }


    /** @var \Drupal\content_moderation\ModerationInformationInterface $moderation_info */
    $moderation_info = \Drupal::service('content_moderation.moderation_information');
    $moderation_enabled = $moderation_info->shouldModerateEntitiesOfBundle($revisionsEntity->getEntityType(), $revisionsEntity->bundle());

    $revert_permission = (($account->hasPermission("revert $configEntityType revisions") || $account->hasPermission('revert all revisions') || $account->hasPermission('administer nodes')) && $configEntity->access('update'));
    $delete_permission = (($account->hasPermission("delete $configEntityType revisions") || $account->hasPermission('delete all revisions') || $account->hasPermission('administer nodes')) && $configEntity->access('delete'));

    $pagerLimit = 10;

    $query = $contentEntityStorage->getQuery()
      ->condition($revisionsEntity->getEntityType()->getKey('id'),
        $revisionsEntity->id())
      ->pager($pagerLimit)
      ->allRevisions()
      ->sort($revisionsEntity->getEntityType()->getKey('revision'), 'DESC')
      // Access to the content has already been verified. Disable query-level
      // access checking so that revisions for unpublished content still
      //appear.
      ->accessCheck(FALSE)
      ->execute();
    $vids = array_keys($query);

    $revision_count = count($vids);

    $table_header = [
      'revision' => $this->t('Revision'),
    ];

    if ($configEntity->has_own_content()) {
      $table_header['submissions'] = $this->t('Submissions');
    }

    // Allow comparisons only if there are 2 or more revisions.
    if ($haveDiff && $revision_count > 1) {
      $table_header += [
        'select_column_one' => '',
        'select_column_two' => '',
      ];
    }

    if ($moderation_enabled) {
      $table_header['status'] = $this->t('Status');
    }

    $table_header['operations'] = $this->t('Operations');

    $form['revisions_table'] = [
      '#type' => 'table',
      '#header' => $table_header,
    ];

    if ($haveDiff) {
      $form['revisions_table'] += [
        '#attributes' => [
          'class' => [
            'diff-revisions',
          ],
        ],
        '#attached' => [
          'library' => [
            'diff/diff.general',
          ],
          'drupalSettings' => [
            'diffRevisionRadios' => $this->config->get('general_settings.radio_behavior'),
          ],
        ],
      ];
    }

    $form['entity_type'] = [
      '#type' => 'hidden',
      '#value' => $configEntityType,
    ];
    $form['entity_id'] = [
      '#type' => 'hidden',
      '#value' => $configEntity->id(),
    ];

    // We need to handle there being no published revisions, in which case
    // every row should have a 'Publish' button. In that situation, we can't
    // simply look at whether a revision is before or after the 'default'
    // revision.
    $published_revision_id = $revisionsEntity->isPublished() ? $revisionsEntity->getRevisionId() : 0;

    foreach ($vids as $vid) {
      /* @var $revision \Drupal\config_entity_revisions\Entity\ConfigEntityRevisions */
      $revision = $contentEntityStorage->loadRevision($vid);

      $username = [
        '#theme' => 'username',
        '#account' => $revision->getRevisionUser(),
      ];

      // Use revision link to link to revisions that are not active.
      $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');

      if ($configEntity->has_canonical_url() && $revision->isDefaultRevision() && $revision->isPublished()) {
        $link = $configEntity->toLink($date)->toString();
      }
      else {
        $url = new Url("entity.{$configEntityType}.revision", [
          $configEntityType => $configEntity->id(),
          "revision_id" => $vid,
        ]);
        $link = $this->linkService->generate($date, $url);
      }

      $column = [
        'data' => [
          '#type' => 'inline_template',
          '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
          '#context' => [
            'date' => $link,
            'username' => $this->renderer->renderPlain($username),
            'message' => [
              '#markup' => $revision->getRevisionLogMessage(),
              '#allowed_tags' => Xss::getHtmlTagList(),
            ],
          ],
        ],
      ];

      $this->renderer->addCacheableDependency($column['data'], $username);
      $row = ['description' => $column];

      if ($configEntity->has_own_content()) {
        $count = $configEntity->contentEntityCount($vid);
        $pluralised = \Drupal::service('string_translation')
          ->formatPlural($count, '1 submission', '@count submissions');
        $row['submissions'] = [
          '#markup' => $count ? $pluralised : $this->t('No submissions'),
        ];
      }

      // Allow comparisons only if there are 2 or more revisions.
      if ($haveDiff && $revision_count > 1) {
        $row += [
          'select_column_one' => $this->buildSelectColumn('radios_left', $vid, FALSE),
          'select_column_two' => $this->buildSelectColumn('radios_right', $vid, $vid),
        ];
      }

      if ($moderation_enabled) {
        $row['status'] = [
          '#markup' => $this->t($revision->moderation_state->value),
        ];
      }

      if ($revision->isDefaultRevision() && $revision->isPublished()) {
        $row += [
          'data' => [
            '#prefix' => '<em>',
            '#markup' => $this->t('Current revision'),
            '#suffix' => '</em>',
          ],
          '#attributes' => [
            'class' => ['revision-current'],
          ],
        ];
      }
      else {
        $links = [];
        if ($revert_permission) {
          $links['revert'] = [
            'title' => $vid < $published_revision_id ? $this->t('Revert') : $this->t('Publish'),
            'url' => Url::fromRoute("entity.{$configEntityType}.revision_revert_confirm", [
              'config_entity' => $configEntity->id(),
              "revision_id" => $vid,
            ]),
          ];
        }

        if ($delete_permission && $revision_count > 1) {
          $links['delete'] = [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute("entity.{$configEntityType}.revision_delete_confirm", [
              'config_entity' => $configEntity->id(),
              'revision_id' => $vid,
            ]),
          ];
        }

        $row['data'] = [
          '#type' => 'operations',
          '#links' => $links,
        ];

      }

      $form['revisions_table'][] = $row;
    }

    // Allow comparisons only if there are 2 or more revisions.
    if ($haveDiff && $revision_count > 1) {
      $form['submit'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => t('Compare selected revisions'),
        '#attributes' => [
          'class' => [
            'diff-button',
          ],
        ],
      ];
    }

    $form['pager'] = ['#type' => 'pager'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();

    if (count($form_state->getValue('revisions_table')) <= 1) {
      $form_state->setErrorByName('revisions_table', $this->t('Multiple revisions are needed for comparison.'));
    }
    elseif (!isset($input['radios_left']) || !isset($input['radios_right'])) {
      $form_state->setErrorByName('revisions_table', $this->t('Select two revisions to compare.'));
    }
    elseif ($input['radios_left'] == $input['radios_right']) {
      $form_state->setErrorByName('revisions_table', $this->t('Select different revisions to compare.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $vid_left = $input['radios_left'];
    $vid_right = $input['radios_right'];
    $entity_type = $input['entity_type'];
    $entity_id = $input['entity_id'];

    // Always place the older revision on the left side of the comparison
    // and the newer revision on the right side (however revisions can be
    // compared both ways if we manually change the order of the parameters).
    if ($vid_left > $vid_right) {
      $aux = $vid_left;
      $vid_left = $vid_right;
      $vid_right = $aux;
    }

    // Builds the redirect Url.
    $redirect_url = Url::fromRoute(
      "entity.${entity_type}_revisions.revisions_diff",
      [
        'config_entity_type' => $entity_type,
        'config_entity_id' => $entity_id,
        'left_revision' => $vid_left,
        'right_revision' => $vid_right,
        'filter' => $this->diffLayoutManager->getDefaultLayout(),
      ]
    );
    $form_state->setRedirectUrl($redirect_url);
  }

}
