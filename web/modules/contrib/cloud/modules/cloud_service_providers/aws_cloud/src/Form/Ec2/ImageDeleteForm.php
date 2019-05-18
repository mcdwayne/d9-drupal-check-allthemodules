<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting a Image entity.
 *
 * @ingroup aws_cloud
 */
class ImageDeleteForm extends AwsDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    if ($this->entity->getStatus() == 'pending') {
      return $this->t('Cannot delete an instance in pending state');
    }
    return parent::getDescription();
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    if ($this->entity->getStatus() == 'pending') {
      unset($actions['submit']);
    }
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $entity = $this->entity;
    $this->awsEc2Service->setCloudContext($entity->getCloudContext());
    $account_id = $this->cloudConfigPluginManager->loadConfigEntity()->get('field_account_id')->value;

    // If the image isn't owned by the aws user,
    // the calling for deregisterImage will be skipped.
    if ($entity->getAccountId() != $account_id || $this->awsEc2Service->deregisterImage([
      'ImageId' => $entity->getImageId(),
    ]) != NULL) {
      $message = $this->t('The @type "@label" has been deleted.', [
        '@type'  => $entity->getEntityType()->getLabel(),
        '@label' => $entity->getName(),
      ]);

      $entity->delete();
      $this->messenger->addMessage($message);
    }
    else {
      $message = $this->t('The @type "@label" couldn\'t delete.', [
        '@type'  => $entity->getEntityType()->getLabel(),
        '@label' => $entity->getName(),
      ]);
      $this->messenger->addError($message);
    }

    $form_state->setRedirect('view.aws_images.page_1', ['cloud_context' => $entity->getCloudContext()]);
  }

}
