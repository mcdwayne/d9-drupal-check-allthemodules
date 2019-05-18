<?php

namespace Drupal\hidden_tab\Plugin\HiddenTabRender;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Drupal\hidden_tab\Plugable\Annotation\HiddenTabRenderAnon;
use Drupal\hidden_tab\Plugable\Render\HiddenTabRenderAdministrativeBase;
use Drupal\hidden_tab\Plugable\Render\HiddenTabRenderSafeTrait;
use Drupal\hidden_tab\Service\HiddenTabEntityHelperInterface;
use Drupal\hidden_tab\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Displays an administrative view of a hidden tab page's mailers.
 *
 * @HiddenTabRenderAnon(
 *   id = "hidden_tab_admin_mailers_list"
 * )
 */
class HiddenTabRenderMailersList extends HiddenTabRenderAdministrativeBase {

  use HiddenTabRenderSafeTrait;

  /**
   * See id().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::id()
   */
  protected $PID = 'hidden_tab_admin_mailers_list';

  /**
   * See label().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::label()
   */
  protected $HTPLabel = 'Admin Mailers';

  /**
   * See description().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::description()
   */
  protected $HTPDescription = "Displays an administrative view of a hidden tab page's mailers";

  /**
   * See weight().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::weight()
   */
  protected $HTPWeight = 7;

  /**
   * See tags().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::tags()
   */
  protected $HTPTags = [];

  /**
   * To load mailers.
   *
   * @var \Drupal\hidden_tab\Service\HiddenTabEntityHelperInterface
   */
  protected $entityHelper;

  /**
   * To format... dates?
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $df;

  /**
   * To protect mail send link.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrf;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration,
                              string $plugin_id,
                              $plugin_definition,
                              HiddenTabEntityHelperInterface $entity_helper,
                              DateFormatterInterface $df,
                              CsrfTokenGenerator $csrf) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityHelper = $entity_helper;
    $this->df = $df;
    $this->csrf = $csrf;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('hidden_tab.entity_helper'),
      $container->get('date.formatter'),
      $container->get('csrf_token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render0(EntityInterface $entity,
                          HiddenTabPageInterface $page,
                          AccountInterface $user,
                          ParameterBag $bag,
                          array &$output) {
    /** @var \Drupal\hidden_tab\Entity\HiddenTabMailerInterface $rower */

    $add_link['@add'] = Url::fromRoute('entity.hidden_tab_mailer.add_form', [
      'page' => $page->id(),
      'target-entity' => $entity->id(),
      'target-entity-type' => $entity->getEntityTypeId(),
      'target-entity-bundle' => $entity->bundle(),
      'lredirect' => Utility::redirectHere(),
    ])->toString();

    $table = [
      '#type' => 'table',
      '#caption' => $this->t('Mailers, <a href="@add">Add a new one</a>.', $add_link),
      '#header' => [
        $this->t('ID'),
        $this->t('Status'),
        $this->t('Schedule'),
        $this->t('Upcoming'),
        $this->t('User'),
        $this->t('Entity'),
        $this->t('Operations'),
      ],
      '#empty' => t('There are no items yet, <a href="@add">Add a new one</a>.', $add_link),
    ];

    $entities = $this->entityHelper->entityMailers($page, $entity);
    foreach ($entities as $rower) {
      // TODO move to generic
      // TODO try catch
      $v['id'] = [
        '#markup' => $rower->id(),
      ];
      $v['status'] = [
        '#markup' => Utility::mark($rower->isEnabled()),
      ];
      $v['schedule'] = [
        '#markup' => $rower->emailSchedule() ?: Utility::CROSS,
      ];
      $v['upcoming'] = [
        '#markup' => $rower->nextSchedule() ? $this->df->format($rower->nextSchedule()) : Utility::CROSS,
      ];
      $v['user'] = [
        '#markup' => $rower->targetUserEntity()
          ? $rower->targetUserEntity()->label()
          : Utility::CROSS,
      ];
      $v['entity'] = [
        '#markup' => $rower->targetEntity()
          ? $rower->targetEntity()->label()
          : Utility::CROSS,
      ];
      $v['operations'] = [
        '#type' => 'operations',
        '#links' => [],
      ];
      $url = Url::fromRoute('hidden_tab.send_mail', [
        'hidden_tab_mailer' => $rower->id(),
        'target_entity_type' => $entity->getEntityTypeId(),
        'target_entity' => $entity->id(),
        'lredirect' => Utility::redirectHere(),
      ]);
      $url->setOptions([
        'absolute' => TRUE,
        'query' => [
          'token' => $this->csrf->get($url->getInternalPath()),
          'lredirect' => Utility::redirectHere(),
        ],
      ]);
      $v['operations']['#links']['send'] = [
        'title' => t('Send'),
        'url' => $url,
      ];
      $v['operations']['#links']['edit'] = [
        'title' => t('Edit'),
        'url' => Url::fromRoute('entity.hidden_tab_mailer.edit_form', [
          'hidden_tab_mailer' => $rower->id(),
          'lredirect' => Utility::redirectHere(),
        ]),
      ];
      $v['operations']['#links']['remove'] = [
        'title' => t('Remove'),
        'url' => Url::fromRoute('entity.hidden_tab_mailer.delete_form', [
          'hidden_tab_mailer' => $rower->id(),
          'lredirect' => Utility::redirectHere(),
        ]),
      ];
      $table[$rower->id()] = $v;
    }
    $output['admin'][$this->id()] = $table;
  }

}
