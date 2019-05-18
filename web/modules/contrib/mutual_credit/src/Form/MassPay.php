<?php

namespace Drupal\mcapi\Form;

use Drupal\mcapi\Entity\Wallet;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form builder for multiple payments between one and many wallets.
 */
class MassPay extends ContentEntityForm {

  const MASSINCLUDE = 0;
  const MASSEXCLUDE = 1;

  /**
   * @var Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Either '12many' or 'many21'
   * @var string
   */
  protected $direction;

  /**
   * Mail template
   * @var array
   */
  protected $configfactory;

  /**
   * The payer and payee field names.
   * @var string
   */
  protected $manyfieldName;
  protected $onefieldName;
  protected $mode;
  protected $involved;
  protected $step;
  protected $logger;


  /**
   * @param EntityManagerInterface $entity_manager
   * @param MailManagerInterface $mail_manager
   * @param RouteMatchInterface $route_match
   * @param ConfigFactoryInterface $config_factory
   * @param Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   */
  public function __construct($entity_manager, MailManagerInterface $mail_manager, RouteMatchInterface $route_match, ConfigFactoryInterface $config_factory, LoggerChannel $logger_factory) {
    // @todo deprecated
    parent::__construct($entity_manager);
    $this->mailManager = $mail_manager;
    $this->direction = $route_match->getRouteObject()->getOption('direction');
    $this->configFactory = $config_factory;
    $this->logger = $logger;

    if ($this->direction == '12many') {
      $this->onefieldName = PAYER_FIELDNAME;
      $this->manyfieldName = PAYEE_FIELDNAME;
    }
    else {
      $this->onefieldName = PAYEE_FIELDNAME;
      $this->manyfieldName = PAYER_FIELDNAME;
    }
    $this->mode = mcapi_one_wallet_mode('user') ? 'user' : 'mcapi_wallet';
    $this->involved = [];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('plugin.manager.mail'),
      $container->get('current_route_match'),
      $container->get('config.factory'),
      $container->get('logger.channel.mcapi')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $this->init($form_state);

    if (count($this->involved) < 2) {
      $this->step = 1;
      $this->step1($form, $form_state);
    }
    else {
      $this->step = 2;
      $form_state->setValidationComplete();
      $form['preview'][] = $this->entityTypeManager
        ->getViewBuilder('mcapi_transaction')
        ->viewMultiple($this->entity->flatten(), 'sentence');
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  function actionsElement(array $form, FormStateInterface $form_state) {
    $element = parent::actionsElement($form, $form_state);
    if ($this->step == 1) {
      $element['submit']['#value'] = $this->t('Preview');
    }
    else {
      $element['submit']['#value'] = $this->t('Confirm');
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function step1(array &$form, FormStateInterface $form_state) {
    $display = $this->getFormDisplay($form_state);
    $form['#parents'] = [];

    $display->getRenderer($this->manyfieldName)->cardinalitySetMultiple();
    // Build the default transaction form
    foreach ($display->getComponents() as $name => $options) {
      if (!in_array($name, ['type', 'creator', 'created'])) {
        if ($widget = $display->getRenderer($name)) {
          $items = $this->entity->get($name);
          $items->filterEmptyItems();
          $form[$name] = $widget->form($items, $form, $form_state);
          $form[$name]['#access'] = $items->access('edit');
        }
      }
    }
    $form[$this->manyfieldName]['widget']['#title']  = $this->manyfieldName == PAYER_FIELDNAME ? t('Payers') : t('Payees');
    unset($form[$this->manyfieldName]['widget']['#description']);
    unset($form[$this->onefieldName]['widget'][0]['target_id']['#description']);

    // Should probably set the weights of everything, since the existing ones
    // come from the default form display
    $form['description']['#weight'] = 5;

    $form['invert'] = [
      '#type' => 'radios',
      '#required' => TRUE,
    ];
    if ($this->mode == 'user') {
      $form['invert']['#options'] = [
        $this->t('The named users'),
        $this->t("All users except those named"),
      ];
    }
    else {
      $form['invert']['#options'] = [
        SELF::MASSINCLUDE => $this->t('The named wallets'),
        SELF::MASSEXCLUDE => $this->t("All wallets except those named"),
      ];
    }

    // Some cosmetic alterations
    if ($this->direction == '12many') {
      $form[$this->onefieldName]['widget']['target_id']['#title'] = $this->t('The one payer');
      $form['invert']['#title'] = $this->t('Will pay');
    }
    else {
      $form[$this->onefieldName]['widget']['target_id']['#title'] = $this->t('The one payee');
      $form['invert']['#title'] = $this->t('Will receive from');
    }
    $form[$this->onefieldName]['#weight'] = 1;
    $form['invert']['#weight'] = 2;
    $form[$this->manyfieldName]['#weight'] = 3;
    $form['worth']['#weight'] = 4;
    $form['description']['#weight'] = 5;
    $form[$this->manyfieldName]['#tags'] = TRUE;
    unset($form[$this->manyfieldName]['widget']['target_id']['#description']);
    unset($form[$this->onefieldName]['widget']['target_id']['#description']);

    $mail_setting  = $this->configFactory->get('mcapi.settings')->get('masspay_mail');
    //@todo use rules for this
    $form['notification'] = [
      '#title' => $this->t('Notify all parties', [], array('context' => 'accounting')),
      // @todo decide whether to put rules in a different module
      '#description' => $this->moduleHandler->moduleExists('rules') ?
      $this->t('N.B. Ensure this mail does not clash with mails sent by the rules module.') : '',
      '#type' => 'fieldset',
      '#open' => TRUE,
      '#weight' => 20,
      'subject' => [
        '#title' => $this->t('Subject'),
        '#type' => 'textfield',
        // This needs to be stored per-exchange.
        '#default_value' => $mail_setting['subject'],
      ],
      'body' => [
        '#title' => $this->t('Message'),
        // @todo the tokens?
        '#description' => $this->t('The following tokens are available: [user:name]'),
        '#type' => 'textarea',
        '#default_value' => $mail_setting['body'],
        '#weight' => 1,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (($triggering_element = $form_state->getTriggeringElement()) && isset($triggering_element['#ajax']['callback'])) {
      return;
    }
    if ($this->step == 1) {
      if ($form_state->getValue('invert') == Self::MASSINCLUDE and !$form_state->getValue($this->manyfieldName)) {
        $form_state->setError(
          $form[$this->manyfieldName],
          $this->t("'@field' is required", ['@field' => $form[$this->manyfieldName]['#title']])
        );
      }

      // Unlike normal one-step entity forms, save the entiry here for step 2
      $this->entity = parent::validateForm($form, $form_state);
      // And save the mail
      $form_state->set('mail', [
        'subject' => $form_state->getValue('subject'),
        'body' => $form_state->getValue('body')
      ]);
      // Reload the form for the confirmation page.
      $form_state->setRebuild(TRUE);
    }
  }



  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $entity = parent::buildEntity($form, $form_state);
    $theMany = [];
    foreach ($entity->{$this->manyfieldName}->getValue() as $item) {
      $theMany[] = $item['target_id'];
    }

    if ($form_state->getValue('invert') == SELF::MASSEXCLUDE) {
      $field_definition = $this->entity->get($this->manyfieldName)->getFieldDefinition();
      $theMany = \Drupal::service('plugin.manager.entity_reference_selection')
        ->getSelectionHandler($field_definition) // WalletSelection handler
        ->inverse($theMany);
    }
    print_r($theMany);

    $entity->creator->target_id = $this->currentUser()->id();
    $entity->type->target_id = 'mass';

    // Invoke all specified builders for copying form values to entity
    // properties.
    if (isset($form['#entity_builders'])) {
      foreach ($form['#entity_builders'] as $function) {
        call_user_func_array($function, array($entity->getEntityTypeId(), $entity, &$form, &$form_state));
      }
    }

    if ($theMany) {
      $transactions = [];
      $theOne = $entity->{$this->onefieldName}->target_id;
      $this->involved[] = $theOne;
      // Convert the one entity into a transaction with children.
      foreach ($theMany as $id) {
        if ($id == $theOne) {
          continue;
        }
        $this->involved[] = $id;
        $transaction = $entity->createDuplicate();
        $transaction->set($this->manyfieldName, $id);
        $transaction->set($this->onefieldName, $theOne);
        $transactions[] = $transaction;
      }
      $entity = array_shift($transactions);
      $entity->children = $transactions;
      $this->involved = array_unique($this->involved);
    }
    return $entity;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //NOT calling the parent because we don't want to build the entity again
    $form_state->cleanValues();
    $this->updateChangedTime($this->entity);
    $this->configFactory->getEditable('mcapi.settings')
      ->set('masspay_mail', $form_state->get('mail'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    // @todo make sure this mail is queueing
    $params = $form_state->get('mail');
    $params['serial'] = $this->entity->serial->value;
    foreach (Wallet::loadMultiple($this->involved) as $wallet) {
      $owner = $wallet->getOwner();
      $params['recipient_id'] = $owner->id();
      $this->mailManager->mail(
        'mcapi',
        'mass',
        $owner->getEmail(),
        $owner->getPreferredLangcode(),
        // Should contain subject, body as a string, and recipient_id.
        $params
      );
    }
    // Go to the transaction certificate.
    $form_state->setRedirect(
      'entity.mcapi_transaction.canonical',
      ['mcapi_transaction' => $this->entity->serial->value]
    );

    $this->logger->notice(
      'User @uid created @num mass transactions #@serial',
      [
        '@uid' => $this->currentUser()->id(),
        '@num' => count($this->entity->children) + 1,
        '@serial' => $this->entity->serial->value,
      ]
    );
  }

}
