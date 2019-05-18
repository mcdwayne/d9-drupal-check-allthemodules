<?php

namespace Drupal\hidden_tab\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\Php;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\hidden_tab\Entity\HiddenTabCredit;
use Drupal\hidden_tab\Form\Base\EntityFormBase;
use Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginManager;
use Drupal\hidden_tab\Service\CreditChargingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Form controller for the hidden tab credit entity edit forms.
 */
class HiddenTabCreditForm extends EntityFormBase {

  /**
   * Type of entity being created.
   *
   * @var string
   */
  protected $type = 'hidden_tab_credit';

  /**
   * To get some default entity properties from uri params, if any.
   *
   * @var \Symfony\Component\HttpFoundation\ParameterBag
   */
  protected $params;

  /**
   * To find entities already existing.
   *
   * Used by validation so no more than one exists.
   *
   * @var \Drupal\hidden_tab\Service\CreditChargingInterface;
   */
  protected $creditService;

  /**
   * To find templates.
   *
   * @var \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginManager
   */
  protected $templateMan;

  /**
   * To load user on form validation.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * To load target entity
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityRepository;

  /**
   * To generate a default secret key.
   *
   * @var \Drupal\Component\Uuid\Php
   */
  protected $uuid;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityRepositoryInterface $entity_repository = NULL,
                              EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL,
                              TimeInterface $time = NULL,
                              EntityStorageInterface $user_storage = NULL,
                              CreditChargingInterface $credit_charging = NULL,
                              HiddenTabTemplatePluginManager $template_man = NULL,
                              MessengerInterface $messenger = NULL,
                              ParameterBag $params = NULL,
                              Php $uuid = NULL) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time, $user_storage, $messenger, $params, $uuid);
    if ($credit_charging === NULL || $template_man === NULL) {
      throw new \LogicException('illegal state');
    }
    $this->creditService = $credit_charging;
    $this->templateMan = $template_man;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('hidden_tab.credit_service'),
      $container->get('plugin.manager.hidden_tab_template'),
      $container->get('messenger'),
      $container->get('request_stack')->getCurrentRequest()->query,
      $container->get('uuid')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = $this->templateMan->pluginsForSelectElement('credit');
    $form = parent::buildForm($form, $form_state);
    if (isset($form['low_credit_template']['widget']['#options'])) {
      $form['low_credit_template']['widget']['#options'] = $options;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    return HiddenTabCredit::validateForm(
      $form_state,
      $this->prefix,
      TRUE,
      $this->targetEntityType,
      $this->getEntity()->id()
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity0() {
    $this->entity->set('secret_key', $this->uuid->generate());
    $this->entity->set('low_credit_template', 'hidden_tab_low_credit');
  }

}
