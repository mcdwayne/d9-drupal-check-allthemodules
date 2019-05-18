<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;

/**
 * Volume detach form.
 */
class VolumeDetachForm extends AwsDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $entity = $this->entity;

    return $this->t('Are you sure you want to detach volume: %name?', [
      '%name' => $entity->getName(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Detach');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $entity = $this->entity;
    $instance = $this->getInstanceName($entity->getAttachmentInformation());

    $msg = '<h2>Volume Information:</h2>';

    $volume_id = $entity->getVolumeId();
    $name = $entity->getName();
    $attachment_info = $entity->getAttachmentInformation();

    $msg .= $this->t(
      '<ul><li>Volume id: %id</li><li>Volume name: %name</li><li>Attached to instance: %instance with id: %instance_id</li></ul>',
      [
        '%id' => $volume_id,
        '%name' => $name,
        '%instance_id' => $attachment_info,
        '%instance' => $instance,
      ]
    );

    $msg .= 'Make sure to unmount any file systems on the device within your operating system before detaching the volume. Failure to do so can result in the volume becoming stuck in the busy state while detaching. If this happens, detachment can be delayed indefinitely until you unmount the volume, force detachment, reboot the instance, or all three. If an EBS volume is the root device of an instance, it can\'t be detached while the instance is running. To detach the root volume, stop the instance first.';
    return $msg;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var \Drupal\aws_cloud\Entity\Ec2\Volume $entity */
    $entity = $this->entity;
    $volume_id = $entity->getVolumeId();
    $instance_id = $entity->getAttachmentInformation();

    $this->awsEc2Service->setCloudContext($this->entity->getCloudContext());
    $result = $this->awsEc2Service->detachVolume([
      'VolumeId' => $volume_id,
    ]);

    if ($result != NULL) {
      // Set the instance_id in the volume entity and save.
      $entity->setAttachmentInformation('');
      $entity->setState($result['State']);
      $entity->save();

      $this->messenger->addMessage($this->t('Volume %volume is detaching from %instance', ['%volume' => $volume_id, '%instance' => $instance_id]));
      $form_state->setRedirect('view.aws_volume.page_1', ['cloud_context' => $this->entity->getCloudContext()]);
    }
  }

  /**
   * Helper method to get instance name from database.
   *
   * @param string $instance_id
   *   The Instance ID.
   *
   * @return string
   *   The Instance name.
   */
  private function getInstanceName($instance_id) {
    $name = '';
    $instances = $this->entityTypeManager
      ->getStorage('aws_cloud_instance')
      ->loadByProperties([
        'instance_id' => $instance_id,
      ]);

    if (count($instances)) {
      /* @var \Drupal\aws_cloud\Entity\Ec2\Instance $instance */
      $instance = array_shift($instances);
      $name = $instance->getName();
    }
    return $name;
  }

}
