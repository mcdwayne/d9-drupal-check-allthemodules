<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;

/**
 * Create image from an instance.
 */
class InstanceCreateImageForm extends AwsDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $entity = $this->entity;

    return $this->t('Create an image for instance %instance_id: %name?', [
      '%instance_id' => $entity->getInstanceId(),
      '%name' => $entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Create Image');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['image_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image Name'),
      '#description' => $this->t('A name for the new image'),
      '#required' => TRUE,
    ];
    $form['no_reboot'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('No Reboot'),
      '#description' => $this->t("By default, Amazon EC2 attempts to shut down and reboot the instance before creating the image. If the \'No Reboot\' option is set, Amazon EC2 doesn\'t shut down the instance before creating the image. When this option is used, file system integrity on the created image can\'t be guaranteed."),
      '#default_value' => FALSE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var \Drupal\aws_cloud\Entity\Ec2\Instance $entity */
    $entity = $this->entity;

    $this->awsEc2Service->setCloudContext($entity->getCloudContext());
    $result = $this->awsEc2Service->createImage([
      'InstanceId' => $entity->getInstanceId(),
      'Name' => $form_state->getValue('image_name'),
      'NoReboot' => $form_state->getValue('no_reboot') == 0 ? FALSE : TRUE,
    ]);

    if (isset($result['ImageId'])) {

      // Call image update on this particular image.
      $this->awsEc2Service->updateImages([
        'ImageIds' => [
          $result['ImageId'],
        ],
      ]);

      $message = $this->t('The %type %label (%image_id) has been created.', [
        '%type'    => $entity->getEntityType()->getLabel(),
        '%label'    => $entity->label(),
        '%image_id' => $result['ImageId'],
      ]);

      $this->messenger->addMessage($message);
      $form_state->setRedirect('view.aws_images.page_1', ['cloud_context' => $entity->getCloudContext()]);

    }
    else {
      $message = $this->t('The image for "%label" could not be create.', [
        '%label' => $entity->getName(),
      ]);
      $this->messenger->addError($message);
    }
  }

}
