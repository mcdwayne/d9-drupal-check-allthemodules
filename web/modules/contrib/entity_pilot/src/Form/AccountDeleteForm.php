<?php

namespace Drupal\entity_pilot\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_pilot\LegacyMessagingTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Delete form.
 */
class AccountDeleteForm extends EntityConfirmFormBase {

  use LegacyMessagingTrait;

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  public $queryFactory;

  /**
   * Logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a query factory object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query object.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger channel.
   */
  public function __construct(QueryFactory $query_factory, LoggerInterface $logger) {
    $this->queryFactory = $query_factory;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('logger.factory')->get('entity_pilot')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the account named %name?', [
      '%name' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity_pilot.account_list');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $flights = $this->queryFactory->get('ep_departure')->condition('account', $this->entity->id())->execute();
    if (!empty($flights)) {
      $caption = '<p>' . $this->formatPlural(count($flights), '%label is used by 1 flight on your site. You can not remove this account until you have removed all of the associated flights.', '%label is used by @count flights on your site. You may not remove %label until you have removed all of the associated flights.', ['%label' => $this->entity->label()]) . '</p>';
      $form['description'] = ['#markup' => $caption];
      return $form;
    }
    else {
      return parent::buildForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    $form_state->setRedirect('entity_pilot.account_list');
    $this->setMessage(t('Entity Pilot account %label has been deleted.', ['%label' => $this->entity->label()]));
    $this->logger->notice('Entity Pilot account %label has been deleted.', ['%label' => $this->entity->label()]);
  }

}
