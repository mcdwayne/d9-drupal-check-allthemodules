<?php

namespace Drupal\mcapi\Form;

use Drupal\mcapi\Event\McapiEvents;
use Drupal\mcapi\Event\TransactionSaveEvents;
use Drupal\mcapi\TransactionOperations;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Render\Renderer;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Utility\Token;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * I don't know if it is a good idea to extend the confirm form if we want ajax.
 */
class OperationForm extends ContentEntityConfirmFormBase {

  private $action;
  private $plugin;
  private $config;
  private $eventDispatcher;
  private $renderer;
  private $destination;
  protected $transactionViewBuilder;

  /**
   *
   * @param \Drupal\mcapi\Form\EntityRepositoryInterface $entity_repository
   * @param \Drupal\mcapi\Form\EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param \Drupal\mcapi\Form\TimeInterface $time
   * @param CurrentRouteMatch $route_match
   * @param RequestStack $request_stack
   * @param ContainerAwareEventDispatcher $event_dispatcher
   * @param Renderer $renderer
   * @param Token $token
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, EntityTypeManager $entity_type_manager, CurrentRouteMatch $route_match, RequestStack $request_stack, ContainerAwareEventDispatcher $event_dispatcher, Renderer $renderer, Token $token) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->transactionViewBuilder = $entity_type_manager->getViewBuilder('mcapi_transaction');
    $this->action = TransactionOperations::loadOperation($route_match->getparameter('operation'));
    $this->plugin = $this->action->getPlugin();
    $this->config = $this->plugin->getConfiguration();
    $this->eventDispatcher = $event_dispatcher;
    $this->renderer = $renderer;
    $this->token = $token;
    $query = $request_stack->getCurrentRequest()->query;
    if ($query->has('destination')) {
      $this->destination = $query->get('destination');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('request_stack'),
      $container->get('event_dispatcher'),
      $container->get('renderer'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mcapi_' . $this->action->id() . '_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->config['page_title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // The transaction hasn't been created yet.
    if ($this->plugin->getPluginId() == 'mcapi_transaction.save_action') {
      // We really want to go back the populated transaction form using the back
      // button in the browser. Failing that we want to go back to whatever form
      // it was, fresh failing that we go to the user page user.page.
      return new Url('user.page');
    }
    return $this->entity->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->config['cancel_link'] ?: '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    // Provides the transaction_view part of the form from the action's config.
    $format = $this->config['format'];
    if ($format == 'twig') {
      // module_load_include('inc', 'mcapi', 'src/ViewBuilder/theme');.
      $renderable = [
        '#type' => 'inline_template',
        '#template' => $this->config['twig'],
        '#context' => $this->token->replace(
          $this->config['twig'],
          ['mcapi_transaction' => $this->entity],
          ['sanitize' => TRUE]
        ),
      ];
    }
    else {
      $renderable = $this->transactionViewBuilder->view($this->entity, $format);
    }
    return $this->renderer->render($renderable);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['description']['#weight'] = -1;
    // Not sure why this is sometimes not included from TransactionViewBuilder.
    $form['#attached']['library'][] = 'mcapi/mcapi.transaction';
    $form['#attributes']['class'][] = 'transaction-operation-form';
    $form['actions']['submit']['#value'] = $this->config['button'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Sets this->entity based on $form_state.
    parent::submitForm($form, $form_state);
    $this->entity->setValidationRequired(FALSE);
    try {
      // The op might have injected values into the form, so it needs to be able
      // to access them.
      $this->plugin->execute($this->entity);
      if ($this->action->id() == 'transaction_delete') {
        if (!$this->destination) {
          // Front page.
          $this->destination = '/';
        }
      }
      $args = [
        'values' => $form_state->getValues(),
        'old_state' => $this->entity->state->value,
        'action' => $this->action,
      ];
      $events = new TransactionSaveEvents(clone($this->entity), $args);
      $events->setMessage($this->config['message']);
      $this->eventDispatcher->dispatch(McapiEvents::ACTION, $events);
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t(
        "Error performing @action action: @error",
        [
          '@action' => $this->config['title'],
          '@error' => $e->getMessage(),
        ]
      ));
      return;
    }
    foreach ($events->getMessages() as $type => $messages) {
      foreach ($messages as $message) {
        $this->messenger()->addMessage($message, $type);
      }
    }
    if ($this->destination) {
      $path = $this->destination;
    }
    else {
      $path = 'transaction/' . $this->entity->serial->value;
    }
    $form_state->setRedirectUrl(Url::fromUri('base:' . $path));
  }

  /**
   * {@inheritdoc}
   *
   * @todo can't see how to make a back link using javascript
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    return [
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->getConfirmText(),
        '#submit' => [
          [$this, 'submitForm'],
        ],
      ],
        //this doesn't go back like when the browser button is used.
//      'cancel' => [
//        '#type' => 'link',
//        '#markup' => Markup::create('<a href="" onclick=\'window.history.back();\'">'.$this->getCancelText().'</a>'),
//        '#attributes' => [
//          //'class' => ['button-user'],
//          'onclick' => "onclick='window.history.back();"
//        ]
//      ]
    ];
  }


}
