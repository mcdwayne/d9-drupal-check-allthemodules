<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the Image entity create form.
 *
 * @ingroup aws_cloud
 */
class ImageCreateForm extends AwsCloudContentForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cloud_context = '') {
    /* @var $entity \Drupal\aws_cloud\Entity\Ec2\Image */
    $form = parent::buildForm($form, $form_state);

    $this->awsEc2Service->setCloudContext($cloud_context);

    $entity = $this->entity;

    $form['cloud_context'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Cloud ID'),
      '#maxlength'     => 255,
      '#size'          => 60,
      '#default_value' => !$entity->isNew()
      ? $entity->getCloudContext()
      : $cloud_context,
      '#required'      => TRUE,
      '#weight'        => -5,
      '#disabled'      => TRUE,
    ];

    $form['name'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Name'),
      '#maxlength'     => 255,
      '#size'          => 60,
      '#default_value' => $entity->label(),
      '#required'      => FALSE,
      '#weight'        => -5,
    ];

    $form['instance_id'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Instance ID'),
      '#maxlength'     => 60,
      '#size'          => 60,
      '#default_value' => $entity->getInstanceId(),
      '#required'      => TRUE,
      '#weight'        => -5,
    ];

    $form['description'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Description'),
      '#maxlength'     => 255,
      '#cols'          => 60,
      '#rows'          => 3,
      '#default_value' => $entity->getDescription(),
      '#weight'        => -5,
      '#required'      => FALSE,
    ];

    $form['actions'] = $this->actions($form, $form_state, $cloud_context);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->trimTextfields($form, $form_state);

    /* @var \Drupal\aws_cloud\Entity\Ec2\Image $entity */
    $entity = $this->entity;

    $result = $this->awsEc2Service->createImage([
      'InstanceId'  => $entity->getInstanceId(),
      'Name'        => $entity->getName(),
      'Description' => $entity->getDescription(),
    ]);

    $account_id = $this->cloudConfigPluginManager->loadConfigEntity()->get('field_account_id')->value;
    if (isset($result['ImageId'])
      && ($entity->setName($form_state->getValue('name')))
      && ($entity->set('ami_name', $form_state->getValue('name')))
      && ($entity->setImageId($result['ImageId']))
      && ($entity->set('account_id', $account_id))
      && ($entity->save())) {

      $this->setUidInAws(
        $this->entity->getImageId(),
        'image_created_by_uid',
        $this->entity->getOwner()->id()
      );

      $message = $this->t('The @label "%label (@image_id)" has been created.', [
        '@label'    => $entity->getEntityType()->getLabel(),
        '%label'    => $entity->label(),
        '@image_id' => $entity->getImageId(),
      ]);

      $this->messenger->addMessage($message);
      $form_state->setRedirect('view.aws_images.page_1', ['cloud_context' => $entity->getCloudContext()]);
    }
    else {
      $message = $this->t('The @label "%label" couldn\'t create.', [
        '@label' => $entity->getEntityType()->getLabel(),
        '%label' => $entity->getName(),
      ]);
      $this->messenger->addError($message);
    }

  }

}
