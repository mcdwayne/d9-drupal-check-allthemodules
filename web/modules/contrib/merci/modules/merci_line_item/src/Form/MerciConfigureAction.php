<?php

namespace Drupal\merci_line_item\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface;
use Drupal\views_bulk_operations\Form\ViewsBulkOperationsFormTrait;
use Drupal\views_bulk_operations\Form\ConfigureAction;

/**
 * Action configuration form.
 */
class MerciConfigureAction extends ConfigureAction {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $view_id = NULL, $display_id = NULL) {
    $form = parent::buildForm($form, $form_state, $view_id, $display_id);

    $form['actions']['cancel']['#submit'][] = [$this, 'cancelFormData'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_data = $form_state->get('views_bulk_operations');

    $action = $this->actionManager->createInstance($form_data['action_id']);
    if (method_exists($action, 'submitConfigurationForm')) {
      $action->submitConfigurationForm($form, $form_state);
      $form_data['configuration'] = $action->getConfiguration();
    }
    else {
      $form_state->cleanValues();
      $form_data['configuration'] = $form_state->getValues();
    }
    if ($form_state->isRebuilding()) {
      return;
    }

    $definition = $this->actionManager->getDefinition($form_data['action_id']);
    if (!empty($definition['confirm_form_route_name'])) {
      // Update tempStore data.
      $this->setTempstoreData($form_data, $form_data['view_id'], $form_data['display_id']);
      // Go to the confirm route.
      $form_state->setRedirect($definition['confirm_form_route_name'], [
        'view_id' => $form_data['view_id'],
        'display_id' => $form_data['display_id'],
      ]);
    }
    else {
      $this->deleteTempstoreData($form_data['view_id'], $form_data['display_id']);
      $this->actionProcessor->executeProcessing($form_data);
      $form_state->setRedirectUrl($form_data['redirect_url']);
    }
  }

  /**
   * Submit callback to cancel an action and return to the view.
   *
   * @param array $form
   *   The form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function cancelFormData(array &$form, FormStateInterface $form_state) {
    $form_data = $form_state->get('views_bulk_operations');
    $this->getTempstore($form_data['view_id'], $form_data['display_id'])->delete('validation_errors');
  }

}
