<?php

namespace Drupal\hidden_tab\Plugin\HiddenTabRender;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Drupal\hidden_tab\Plugable\Annotation\HiddenTabRenderAnon;
use Drupal\hidden_tab\Plugable\Render\HiddenTabRenderAdministrativeBase;
use Drupal\hidden_tab\Plugable\Render\HiddenTabRenderSafeTrait;
use Drupal\hidden_tab\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Displays an administrative view of a hidden tab page's credits.
 *
 * @HiddenTabRenderAnon(
 *   id = "hidden_tab_admin_credits_list"
 * )
 */
class HiddenTabRenderCreditsList extends HiddenTabRenderAdministrativeBase {

  use HiddenTabRenderSafeTrait;

  /**
   * See id().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::id()
   */
  protected $PID = 'hidden_tab_admin_credit_list';

  /**
   * See label().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::label()
   */
  protected $HTPLabel = 'Admin Credit';

  /**
   * See description().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::description()
   */
  protected $HTPDescription = "Displays an administrative view of a hidden tab page's credits.";

  /**
   * See weight().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::weight()
   */
  protected $HTPWeight = 5;

  /**
   * See tags().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::tags()
   */
  protected $HTPTags = [];

  /**
   * To load credits.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration,
                              string $plugin_id,
                              $plugin_definition,
                              EntityStorageInterface $storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
        ->getStorage('hidden_tab_credit')
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
    $add_link['@add'] = Url::fromRoute('entity.hidden_tab_credit.add_form', [
      'page' => $page->id(),
      'target-entity' => $entity->id(),
      'target-entity-type' => $entity->getEntityTypeId(),
      'target-entity-bundle' => $entity->bundle(),
      'lredirect' => Utility::redirectHere(),
    ])->toString();

    $table = [
      '#type' => 'table',
      '#caption' => $this->t('Credits, <a href="@add">Add a new one</a>.', $add_link),
      '#header' => [
        $this->t('ID'),
        $this->t('Status'),
        $this->t('Per IP'),
        $this->t('User'),
        $this->t('Entity'),
        $this->t('Credit'),
        $this->t('Credit Span'),
        $this->t('Infinite Credit'),
        $this->t('Operations'),
      ],
      '#empty' => t('There are no items yet, <a href="@add">Add a new one</a>.', $add_link),
    ];

    /** @var \Drupal\hidden_tab\Entity\HiddenTabCreditInterface[] $entities */
    $entities = $this->storage->loadByProperties([
      'target_hidden_tab_page' => $page->id(),
    ]);
    foreach ($entities as $credit) {
      if ($credit->targetEntityId() && $credit->targetEntityId() !== $entity->id()) {
        continue;
      }
      // TODO move to generic
      // TODO try catch
      $v['id'] = [
        '#markup' => $credit->id(),
      ];
      $v['status'] = [
        '#markup' => Utility::mark($credit->isEnabled()),
      ];
      $v['per_ip'] = [
        '#markup' => Utility::mark($credit->isPerIp()),
      ];
      $v['user'] = [
        '#markup' => $credit->targetUserEntity()
          ? $credit->targetUserEntity()->label()
          : Utility::CROSS,
      ];
      $v['entity'] = [
        '#markup' => $credit->targetEntity()
          ? $credit->targetEntity()->label()
          : Utility::CROSS,
      ];
      $v['credit'] = [
        '#markup' => $credit->credit(),
      ];
      $v['credit_span'] = [
        '#markup' => $credit->creditSpan(),
      ];
      $v['infinite_credit'] = [
        '#markup' => Utility::mark($credit->isInfiniteCredit()),
      ];
      $v['operations'] = [
        '#type' => 'operations',
        '#links' => [],
      ];
      $u = Url::fromRoute('hidden_tab.uri_' . $page->id(), [
        'hash' => Utility::hash($credit->id(), $credit->secretKey()),
        'node' => $entity->id(),
      ]);
      $v['operations']['#links']['view'] = [
        'title' => t('View'),
        'url' => $u,
      ];
      $v['operations']['#links']['remove'] = [
        'title' => t('Remove'),
        'url' => Url::fromRoute('entity.hidden_tab_credit.delete_form', [
          'hidden_tab_credit' => $credit->id(),
          'lredirect' => Utility::redirectHere(),
        ]),
      ];
      $v['operations']['#links']['edit'] = [
        'title' => t('Edit'),
        'url' => Url::fromRoute('entity.hidden_tab_credit.edit_form', [
          'hidden_tab_credit' => $credit->id(),
          'lredirect' => Utility::redirectHere(),
        ]),
      ];
      $table[$credit->id()] = $v;
    }
    $output['admin'][$this->id()] = $table;
  }

}
