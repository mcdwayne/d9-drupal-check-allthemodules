<?php

namespace Drupal\hidden_tab\Plugin\HiddenTabTplContext;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\hidden_tab\Entity\HiddenTabCreditInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Drupal\hidden_tab\Plugable\Annotation\HiddenTabTplContextAnon;
use Drupal\hidden_tab\Plugable\TplContext\HiddenTabTplContextPluginBase;
use Drupal\hidden_tab\Service\CreditChargingInterface;
use Drupal\hidden_tab\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create a mail containing a link to hidden tab (secret URI).
 *
 * Renders the actual page
 *
 * @HiddenTabTplContextAnon(
 *   id = "hidden_tab_hidden_tab_credit_tpl_context"
 * )
 */
class HiddenTabHiddenTabCreditTplContext extends HiddenTabTplContextPluginBase {

  /**
   * See id().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::id()
   */
  protected $PID = 'hidden_tab_hidden_tab_credit_tpl_context';

  /**
   * See label().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::label()
   */
  protected $HTPLabel = 'Hidden Tab Credit Context Maker';

  /**
   * See description().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::description()
   */
  protected $HTPDescription = 'Finds suitable context variables for mailing secret uri links (hidden tab credit entities).';

  /**
   * See weight().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::weight()
   */
  protected $HTPWeight = 0;

  /**
   * See tags().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::tags()
   */
  protected $HTPTags = [];

  /**
   * To find credit entities.
   *
   * @var \Drupal\hidden_tab\Service\CreditChargingInterface
   */
  protected $cc;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration,
                              string $plugin_id,
                              $plugin_definition,
                              CreditChargingInterface $cc) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cc = $cc;
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
      $container->get('hidden_tab.credit_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function provide(array $entities, array $extra): array {
    /** @var \Drupal\hidden_tab\Entity\HiddenTabPageInterface $page */
    /** @var \Drupal\Core\Entity\EntityInterface $target_entity */
    $none = [
      'credit' => NULL,
      'credit_link' => '',
    ];
    $page = $entities['page'];
    $target_entity = $entities['target_entity'];

    if (!$page->secretUri()) {
      return $none;
    }

    $credits = $this->cc->he(
      $page,
      $target_entity,
      NULL
    );
    if(empty($credit)) {
      $credits = $this->cc->he(
        $page,
        NULL,
        NULL
      );
    }
    $credits = \array_filter($credits, function (HiddenTabCreditInterface $credit) use ($target_entity): bool {
      if ($credit->targetEntityType() && !$credit->targetEntityType() === $target_entity->getEntityTypeId()) {
        return FALSE;
      }
      if ($credit->targetEntityBundle() && !$credit->targetEntityBundle() === $target_entity->bundle()) {
        return FALSE;
      }
      return $this->cc->canBeCharged($credit);
    });
    foreach ($credits as $credit) {
      try {
        return [
          'credit' => $credit,
          'credit_link' => \Drupal::request()->getSchemeAndHttpHost() . static::make($page, $credit, $target_entity)->toString(),
        ];
      }
      catch (\Throwable $e) {
        Utility::error($e, 'mail');
        return $none;
      }
    }
    return $none;
  }

  private static function make(HiddenTabPageInterface $page,
                               HiddenTabCreditInterface $credit,
                               EntityInterface $target_entity): Url {
    return Url::fromRoute('hidden_tab.uri_' . $page->id(), [
      $target_entity->getEntityTypeId() => $target_entity->id(),
      'hash' => Utility::hash($target_entity->id(), $credit->secretKey()),
    ], [
      'hash' => Utility::hash($target_entity->id(), $credit->secretKey()),
    ]);
  }

}
