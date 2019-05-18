<?php

namespace Drupal\aws\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a confirm form for deleting a profile.
 */
class ProfileDeleteConfirmForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the profile %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   * @todo
   */
  public function getDescription() {
    $plugin_manager = \Drupal::service('plugin.manager.aws_service');
    $services = $plugin_manager->getDefinitions();
    foreach ($services as $service) {
      $config = $this->configFactory->get('aws.' . $service['id'] . '.settings');
      $profile_id = $config->get('profile');
      if ($profile_id === $this->entity->id()) {
        $affected_services[] = $profile_id;
      }
    }
    return $this->t('@services', ['@services' => $affected_services]);
    // Should return list of services that will be affected.
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.aws_profile.edit_form', ['aws_profile' => $this->entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Only allow deletion if profile is NOT 'default'.
    if ($this->entity->id() === 'default') {
      drupal_set_message('Cannot delete the default profile.', 'warning');
      $form_state->setRedirect('aws.configuration.profiles');
    }
    else {
      $this->entity->delete();
      drupal_set_message($this->t('The profile %name has been deleted.', ['%name' => $this->entity->label()]));
      $form_state->setRedirect('aws.configuration.profiles');

      // When a profile is deleted, any service that is configured to use that
      // profile should automatically be set to use the default profile.
    }
  }

}
