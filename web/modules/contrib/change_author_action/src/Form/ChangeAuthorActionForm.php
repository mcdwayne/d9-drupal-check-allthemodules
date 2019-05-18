<?php

namespace Drupal\change_author_action\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\user\Entity\User;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteBuilderInterface;

/**
 * ChangeAuthorActionForm.
 */
class ChangeAuthorActionForm extends FormBase implements FormInterface {

  /**
   * Set a var to make stepthrough form.
   *
   * @var step
   */
  protected $step = 1;
  /**
   * Keep track of user input.
   *
   * @var userInput
   */
  protected $userInput = [];

  /**
   * Tempstorage.
   *
   * @var tempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Session.
   *
   * @var sessionManager
   */
  private $sessionManager;

  /**
   * User.
   *
   * @var currentUser
   */
  private $currentUser;

  /**
   * The route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * Constructs a \Drupal\change_author_action\Form\BulkUpdateFieldsForm.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   Temp storage.
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   Session.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   User.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   Route.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, SessionManagerInterface $session_manager, AccountInterface $current_user, RouteBuilderInterface $route_builder) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->sessionManager = $session_manager;
    $this->currentUser = $current_user;
    $this->routeBuilder = $route_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('session_manager'),
      $container->get('current_user'),
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'change_author_action_form';
  }

  /**
   * {@inheritdoc}
   */
  public function updateFields() {
    $entities = $this->userInput['entities'];
    $new_author = $this->userInput['new_author'];

    $a = 1;
    $batch = [
      'title' => $this->t('Changing author...'),
      'operations' => [
        [
          '\Drupal\change_author_action\ChangeAuthorAction::updateFields',
          [$entities, $new_author],
        ],
      ],
      'finished' => '\Drupal\change_author_action\ChangeAuthorAction::changeAuthorActionFinishedCallback',
    ];
    batch_set($batch);
    return 'Author successfully changed';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    switch ($this->step) {
      case 1:
        $form_state->setRebuild();
        break;

      case 2:
        if (method_exists($this, 'updateFields')) {
          $return_verify = $this->updateFields();
        }
        drupal_set_message($return_verify);
        $this->routeBuilder->rebuild();
        break;
    }
    $this->step++;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (isset($this->form)) {
      $form = $this->form;
    }
    $form['#title'] = $this->t('Choose new author for selected items');
    $submit_label = 'Next';

    switch ($this->step) {
      case 1:
        // Retrieve IDs from the temporary storage.
        $this->userInput['entities'] = $this->tempStoreFactory
          ->get('change_author_ids')
          ->get($this->currentUser->id());
        $form['new_author'] = [
          '#title' => ('Choose new author'),
          '#type' => 'entity_autocomplete',
          '#target_type' => 'user',
          '#required' => TRUE,
          '#selection_settings' => [
            'include_anonymous' => FALSE,
          ],
        ];
        break;

      case 2:
        $uid = $form_state->getValue('new_author');
        $this->userInput['new_author'] = $uid;
        $user = User::load($uid);
        $form['#title'] .= ' - ' . $this->t('Are you sure you want to alter the author to @author_name on @count_entities entities?',
            [
              '@author_name' => $user->label(),
              '@count_entities' => count($this->userInput['entities']),
            ]
        );
        $submit_label = $this->t('Change author on selected entities');

        break;
    }
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $submit_label,
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // TODO.
  }

}
