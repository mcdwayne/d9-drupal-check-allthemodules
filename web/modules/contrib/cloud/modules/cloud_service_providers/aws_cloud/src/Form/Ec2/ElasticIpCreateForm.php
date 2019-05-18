<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the ElasticIp entity create form.
 *
 * @ingroup aws_cloud
 */
class ElasticIpCreateForm extends AwsCloudContentForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cloud_context = '') {
    /* @var $entity \Drupal\aws_cloud\Entity\Ec2\ElasticIp */
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
      '#required'      => TRUE,
      '#weight'        => -5,
    ];

    $form['domain'] = [
      '#type'          => 'select',
      '#options'       => [
        'standard' => 'standard',
        'vpc' => 'vpc',
      ],
      '#title'         => $this->t('Domain (standard | vpc)'),
      '#default_value' => 'standard',
      '#required'      => TRUE,
      '#weight'        => -5,
    ];

    $form['actions'] = $this->actions($form, $form_state, $cloud_context);

    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->trimTextfields($form, $form_state);

    $entity = $this->entity;

    $result = $this->awsEc2Service->allocateAddress([
      'Domain' => $entity->getDomain(),
    ]);

    if (isset($result['PublicIp'])
    && ($entity->setPublicIp($result['PublicIp']))
    && ($entity->setAllocationId($result['AllocationId']))
    && ($entity->setDomain($result['Domain']))
    && ($entity->save())) {

      // Update the Name tag.
      $this->awsEc2Service->createTags([
        'Resources' => [$entity->getAllocationId()],
        'Tags' => [
          [
            'Key' => 'Name',
            'Value' => $entity->getName(),
          ],
        ],
      ]);

      $message = $this->t('The @label "%label (@elastic_ip)" has been created.', [
        '@label'      => $entity->getEntityType()->getLabel(),
        '%label'      => $entity->label(),
        '@elastic_ip' => $result['PublicIp'],
      ]);
      $this->messenger->addMessage($message);
    }
    else {
      $message = $this->t('The @label "%label" couldn\'t create.', [
        '@label' => $entity->getEntityType()->getLabel(),
        '%label' => $entity->label(),
      ]);
      $this->messenger->addError($message);
    }

    $form_state->setRedirect('view.aws_elastic_ip.page_1', ['cloud_context' => $entity->getCloudContext()]);
  }

}
