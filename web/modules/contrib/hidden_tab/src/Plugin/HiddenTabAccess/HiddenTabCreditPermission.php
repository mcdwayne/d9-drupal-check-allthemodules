<?php

namespace Drupal\hidden_tab\Plugin\HiddenTabAccess;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Drupal\hidden_tab\Plugable\Access\HiddenTabAccessPluginBase;
use Drupal\hidden_tab\Plugable\Annotation\HiddenTabAccessAnon;
use Drupal\hidden_tab\Service\CreditChargingInterface;
use Drupal\hidden_tab\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Plugin implementation of the hidden_tab_secret_uri.
 *
 * @HiddenTabAccessAnon(
 *   id = "hidden_tab_credit"
 * )
 */
class HiddenTabCreditPermission extends HiddenTabAccessPluginBase {

  /**
   * See id().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::id()
   */
  protected $PID = 'hidden_tab_credit';

  /**
   * See label().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::label()
   */
  protected $HTPLabel = 'Credit';

  /**
   * See description().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::description()
   */
  protected $HTPDescription = 'TODO';

  /**
   * See weight().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::weight()
   */
  protected $HTPWeight = 3;

  /**
   * See tags().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::tags()
   */
  protected $HTPTags = [];

  /**
   * Well, to charge user for credit.
   *
   * @var \Drupal\hidden_tab\Service\CreditChargingInterface
   */
  protected $cc;

  /**
   * To log.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * To translate.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $t;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration,
                              string $plugin_id,
                              $plugin_definition,
                              LoggerChannel $logger,
                              TranslationInterface $t,
                              CreditChargingInterface $cc) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cc = $cc;
    $this->logger = $logger;
    $this->t = $t;
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
      $container->get('logger.factory')->get('hidden_tab'),
      $container->get('string_translation'),
      $container->get('hidden_tab.credit_service')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function canAccess(EntityInterface $context_entity,
                            AccountInterface $account,
                            ?HiddenTabPageInterface $page,
                            ParameterBag $query,
                            string $access): AccessResult {
    if (!$page
      || $access !== HiddenTabPageInterface::PERMISSION_VIEW_SECRET_URI
      || !$query->get(Utility::QUERY_NAME)) {
      return AccessResult::neutral();
    }
    $hash = $query->get(Utility::QUERY_NAME);

    if ($account->hasPermission(Utility::ADMIN_PERMISSION)) {
      return AccessResult::allowed();
    }

    foreach ($page->creditCheckOrder() as $order) {
      switch ($order) {
        case 'peu':
          foreach ($this->cc->peu($page, $context_entity, $account) as $h) {
            if (Utility::matches($h, $hash)) {
              $charge = $this->cc->charge($h, $account);
              if ($charge) {
                return AccessResult::allowed();
              }
            }
          }
          break;

        case 'pex':
          foreach ($this->cc->pex($page, $context_entity, FALSE) as $h) {
            if (Utility::matches($h, $hash)) {
              $charge = $this->cc->charge($h, $account);
              if ($charge) {
                return AccessResult::allowed();
              }
            }
          }
          break;

        case 'xeu':
          foreach ($this->cc->xeu(FALSE, $context_entity, $account) as $h) {
            if (Utility::matches($h, $hash)) {
              $charge = $this->cc->charge($h, $account);
              if ($charge) {
                return AccessResult::allowed();
              }
            }
          }
          break;

        case 'xex':
          foreach ($this->cc->xex(FALSE, $context_entity, FALSE) as $h) {
            if (Utility::matches($h, $hash)) {
              $charge = $this->cc->charge($h, $account);
              if ($charge) {
                return AccessResult::allowed();
              }
            }
          }
          break;

        // -------------------------------------------------------------------

        case 'pxu':
          foreach ($this->cc->pxu($page, FALSE, $account) as $h) {
            if (Utility::matches($h, $hash)) {
              $charge = $this->cc->charge($h, $account);
              if ($charge) {
                return AccessResult::allowed();
              }
            }
          }
          break;

        case 'pxx':
          foreach ($this->cc->pxx($page, FALSE, FALSE) as $h) {
            if (Utility::matches($h, $hash)) {
              $charge = $this->cc->charge($h, $account);
              if ($charge) {
                return AccessResult::allowed();
              }
            }
          }
          break;

        case 'xxu':
          foreach ($this->cc->xxu(FALSE, FALSE, $account) as $h) {
            if (Utility::matches($h, $hash)) {
              $charge = $this->cc->charge($h, $account);
              if ($charge) {
                return AccessResult::allowed();
              }
            }
          }
          break;

        // -------------------------------------------------------------------

        default:
          $this->logger->error('Illegal state when checking access by HiddenTabCreditPermission, entity-type={h_type} entity={id} access={}', [
            'h_type' => $context_entity->getEntityTypeId(),
            'id' => $context_entity->id(),
            'access' => '',
          ]);
          throw new \LogicException('illegal state');
      }
    }

    return AccessResult::neutral($this->t->translate('You do not have enough credit to access this page.'));
  }

}

