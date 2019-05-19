<?php

namespace Drupal\sms_vbo\Form;


use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\views\Entity\View;
use Drupal\views_bulk_operations\Form\ConfirmAction;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessor;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SmsVboConfirmAction extends ConfirmAction {
  protected $entity_type_manager;

  public function __construct(PrivateTempStoreFactory $tempStoreFactory,
                              ViewsBulkOperationsActionManager $actionManager,
                              ViewsBulkOperationsActionProcessor $actionProcessor,
                              EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($tempStoreFactory, $actionManager, $actionProcessor);
    $this->entity_type_manager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('plugin.manager.views_bulk_operations_action'),
      $container->get('views_bulk_operations.processor'),
      $container->get('entity_type.manager')
    );
  }

  public function getFormId() {
    return 'sms_vbo_confirm_action';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $view_id = NULL, $display_id = NULL) {
    $form = parent::buildForm($form, $form_state, $view_id, $display_id);

    // Get views display's relationship info to generate token types
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = $this->entity_type_manager->getStorage('view')->load($view_id);
    $view_executatble = $view->getExecutable();
    $view_executatble->setDisplay($display_id);
    $relationships = $view_executatble->display_handler->getHandlers('relationship');

    // Get base entity type of the view
    $entity_type = $view_executatble->getBaseEntityType()->id();
    $relationship_token_types[$entity_type] = $entity_type;

    foreach ($relationships as $relationship) {
      if (array_key_exists('entity type', $relationship->definition)) {
        $entity_type = $relationship->definition['entity type'];
        $relationship_token_types[$entity_type] = $entity_type;
      }
    }

    $state = \Drupal::state();
    $state_value = $state->get('sms_vbo');

    $form['sms_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#description' => $this->t('Enter message'),
      '#default_value' => isset($state_value['sms_message']) ? $state_value['sms_message'] : NULL,
      '#rows' => 10,
    ];
    $form['remember_sms_message'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remember SMS message'),
      '#default_value' => isset($state_value['remember_sms_message']) ? $state_value['remember_sms_message'] : TRUE,
    ];
    $form['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#show_restricted' => TRUE,
      '#token_types' => $relationship_token_types,
      '#weight' => 90,
    ];

    $form['#title'] = $this->t('Send SMS');

    $actions = $form['actions'];
    unset($form['actions']);
    $form['actions'] = $actions;

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $view_data = $form_state->getStorage();
    $form_state->setRedirectUrl($view_data['redirect_url']);

    $sms_message = $form_state->getValue('sms_message');
    $remember_sms_message = $form_state->getValue('remember_sms_message');

    if ($remember_sms_message) {
      $state_value = [
        'remember_sms_message' => $remember_sms_message,
        'sms_message' => $sms_message,
      ];
    } else {
      $state_value = [
        'remember_sms_message' => $remember_sms_message,
      ];
    }

    $state = \Drupal::state();
    $state->set('sms_vbo', $state_value);

    // Pass the sms_message content to the action processor
    $view_data['sms_message'] = $sms_message;

    $this->actionProcessor->executeProcessing($view_data);
    $this->tempStoreFactory->get($view_data['tempstore_name'])->delete($this->currentUser()->id());
  }

}
