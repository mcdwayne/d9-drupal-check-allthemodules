<?php

namespace Drupal\yasm\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\yasm\Services\EntitiesStatisticsInterface;
use Drupal\yasm\Utility\YasmUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * YASM Statistics dashboard controller.
 */
class Dashboard extends ControllerBase {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The Date Fromatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entities statitistics service.
   *
   * @var \Drupal\yasm\Services\EntitiesStatisticsInterface
   */
  protected $entitiesStatistics;

  /**
   * Page site content output.
   */
  public function siteContent() {
    $this->messenger->addMessage($this->t('Expensive statistics updates their data 1 time an hour, less expensive statistics updates when the data changes.'));

    return $this->buildContent([
      $this->getGlobalCards(),
      $this->getSiteCards(),
    ]);
  }

  /**
   * Page my content output.
   */
  public function myContent() {
    $this->messenger->addMessage($this->t('Statistics filtered with content authored by @user.', [
      '@user' => $this->currentUser->getDisplayName(),
    ]));
    $conditions = ['uid' => $this->currentUser->id()];

    $build = $this->buildContent([
      $this->getGlobalCards($conditions),
      $this->getUserRankings($conditions),
    ]);
    // Add user cache context because this can change for every user.
    $build['#cache']['contexts'] = ['user'];

    return $build;
  }

  /**
   * Build content output.
   */
  private function buildContent($cards_stack) {
    $cards = [];
    foreach ($cards_stack as $stack) {
      $cards = array_merge($cards, $stack);
    }
    $build = [];
    $build[] = YasmUtility::columns($cards, ['yasm-dashboard']);
    $build[] = [
      '#attached' => [
        'library' => ['yasm/global', 'yasm/fontawesome'],
      ],
      '#cache' => ['max-age' => 3600],
    ];

    return $build;
  }

  /**
   * Get users rankings cards.
   */
  private function getUserRankings($conditions) {
    $node_count = $this->entitiesStatistics->count('node', $conditions);
    $cards = [];
    if ($node_count > 0) {
      $year_timestamp = strtotime('first day of this year');
      $month_timestamp = strtotime('first day of this month');
      $filters = [
        'overall' => [],
        'year' => [
          [
            'key'      => 'created',
            'value'    => $year_timestamp,
            'operator' => '>=',
          ],
        ],
        'month' => [
          [
            'key'      => 'created',
            'value'    => $month_timestamp,
            'operator' => '>=',
          ],
        ],
      ];
      $labels = [
        'overall' => $this->t('Overall creator'),
        'year'    => $this->t('@year creator', [
          '@year' => $this->dateFormatter->format($year_timestamp, 'custom', 'Y'),
        ]),
        'month'   => $this->t('@month creator', [
          '@month' => $this->dateFormatter->format($month_timestamp, 'custom', 'F Y'),
        ]),
      ];
      $uid = $this->currentUser->id();

      foreach ($filters as $filter_key => $filter) {
        $ranking_data = $this->entitiesStatistics->aggregate('node', ['nid' => 'COUNT'], 'uid', $filter);
        $ranking = [];
        foreach ($ranking_data as $data) {
          $ranking[$data['uid']] = $data['nid_count'];
        }
        // Sort ranking by value to count position.
        arsort($ranking);

        $position = 1;
        foreach ($ranking as $ranking_key => $value) {
          if ($ranking_key == $uid) {
            $cards[] = $this->buildCard('fas fa-crown', $labels[$filter_key], '#' . $position);
            break;
          }
          $position++;
        }
      }
    }

    return $cards;
  }

  /**
   * Get user and site cards.
   */
  private function getGlobalCards($conditions = []) {
    $cards = [];
    if ($this->moduleHandler->moduleExists('file')) {
      $cards[] = $this->buildCard('far fa-file', $this->t('Files'), $this->entitiesStatistics->count('file', $conditions));
    }
    if ($this->moduleHandler->moduleExists('node')) {
      $cards[] = $this->buildCard('far fa-file', $this->t('Nodes'), $this->entitiesStatistics->count('node', $conditions));
    }
    if ($this->moduleHandler->moduleExists('comment')) {
      $cards[] = $this->buildCard('fas fa-comment', $this->t('Comments'), $this->entitiesStatistics->count('comment', $conditions));
    }
    if ($this->moduleHandler->moduleExists('webform')) {
      $cards[] = $this->buildCard('fab fa-wpforms', $this->t('Webforms'), $this->entitiesStatistics->count('webform', $conditions));
      $cards[] = $this->buildCard('fab fa-wpforms', $this->t('Webform submissions'), $this->entitiesStatistics->count('webform_submission', $conditions));
    }

    return $cards;
  }

  /**
   * Get site cards.
   */
  private function getSiteCards() {
    $cards = [];
    if ($this->moduleHandler->moduleExists('user')) {
      $cards[] = $this->buildCard('fas fa-user', $this->t('Users'), $this->entitiesStatistics->count('user'));
      $cards[] = $this->buildCard('fas fa-users', $this->t('Roles'), $this->entitiesStatistics->count('user_role'));
    }
    if ($this->moduleHandler->moduleExists('node')) {
      $cards[] = $this->buildCard('far fa-file', $this->t('Node types'), $this->entitiesStatistics->count('node_type'));
    }
    if ($this->moduleHandler->moduleExists('taxonomy')) {
      $cards[] = $this->buildCard('fas fa-tag', $this->t('Vocabularies'), $this->entitiesStatistics->count('taxonomy_vocabulary'));
      $cards[] = $this->buildCard('fas fa-tags', $this->t('Terms'), $this->entitiesStatistics->count('taxonomy_term'));
    }
    if ($this->moduleHandler->moduleExists('group')) {
      $cards[] = $this->buildCard('fas fa-users', $this->t('Groups'), $this->entitiesStatistics->count('group'));
    }
    if ($this->moduleHandler->moduleExists('menu_link_content')) {
      $cards[] = $this->buildCard('fas fa-bars', $this->t('Menus'), $this->entitiesStatistics->count('menu'));
    }
    if ($this->moduleHandler->moduleExists('block')) {
      $cards[] = $this->buildCard('fas fa-th-large', $this->t('Blocks'), $this->entitiesStatistics->count('block'));
      $cards[] = $this->buildCard('fas fa-th-large', $this->t('Custom blocks'), $this->entitiesStatistics->count('block_content_type'));
    }
    if ($this->moduleHandler->moduleExists('views')) {
      $cards[] = $this->buildCard('far fa-list-alt', $this->t('Views'), $this->entitiesStatistics->count('view'));
    }
    if ($this->moduleHandler->moduleExists('language')) {
      // Exclude from count undefined (und) and not applicable (zxx) langcodes.
      $cards[] = $this->buildCard('fas fa-language', $this->t('Languages'), $this->entitiesStatistics->count('configurable_language', [
        ['key' => 'id', 'value' => ['und', 'zxx'], 'operator' => 'NOT IN'],
      ]));
    }
    if ($this->moduleHandler->moduleExists('field')) {
      $cards[] = $this->buildCard('fas fa-list', $this->t('Fields'), $this->entitiesStatistics->count('field_config'));
    }
    if ($this->moduleHandler->moduleExists('image')) {
      $cards[] = $this->buildCard('fas fa-images', $this->t('Image styles'), $this->entitiesStatistics->count('image_style'));
    }

    return $cards;
  }

  /**
   * Build dashboard card.
   */
  private function buildCard($picto, $label, $count) {
    return YasmUtility::picto($picto) . $label . ': ' . $count;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountInterface $current_user, DateFormatterInterface $date_formatter, MessengerInterface $messenger, ModuleHandlerInterface $module_handler, EntitiesStatisticsInterface $entities_statistics) {
    $this->currentUser = $current_user;
    $this->dateFormatter = $date_formatter;
    $this->messenger = $messenger;
    $this->moduleHandler = $module_handler;
    $this->entitiesStatistics = $entities_statistics;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('date.formatter'),
      $container->get('messenger'),
      $container->get('module_handler'),
      $container->get('yasm.entities_statistics')
    );
  }

}
