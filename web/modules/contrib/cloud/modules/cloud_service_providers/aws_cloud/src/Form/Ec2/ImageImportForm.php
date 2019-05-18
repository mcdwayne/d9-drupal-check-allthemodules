<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\aws_cloud\Service\AwsEc2ServiceInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ImageImportForm.
 *
 * Responsible for image importing.
 *
 * @package Drupal\aws_cloud\Form\Ec2
 */
class ImageImportForm extends FormBase {

  /**
   * The AWS EC2 Service.
   *
   * @var \Drupal\aws_cloud\Service\AwsEc2ServiceInterface
   */
  protected $awsEc2Service;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * AwsDeleteForm constructor.
   *
   * @param \Drupal\aws_cloud\Service\AwsEc2ServiceInterface $aws_ec2_service
   *   The AWS EC2 Service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The Messenger service.
   */
  public function __construct(AwsEc2ServiceInterface $aws_ec2_service, Messenger $messenger) {
    $this->awsEc2Service = $aws_ec2_service;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('aws_cloud.ec2'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'image_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cloud_context = '') {
    $form['markup'] = [
      '#markup' => $this->t('Use this form to import images into the system.  Only one field is needed for searching.  The import process can return a very large set of images.  Please try to be specific in your search.'),
    ];
    $form['owners'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Owners'),
      '#description' => $this->t('Comma separated list of owners.  For example "self, amazon".  Specifying amazon will bring back around 4000 images, which is a rather large set of images.'),
    ];

    $form['image_ids'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image IDs'),
      '#description' => $this->t('Comma separated list of image ids'),
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search for images by AMI name'),
      '#description' => $this->t('You can also use wildcards with the filter values. An asterisk (*) matches zero or more characters, and a question mark (?) matches exactly one character.  For example: *ubuntu-16.04* will bring back all images with name ubuntu-16.04 in the AMI name.'),
    ];

    $form['cloud_context'] = [
      '#type' => 'value',
      '#value' => $cloud_context,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = ['#type' => 'submit', '#value' => $this->t('Import')];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Build the Params array for importImages.
    $params = [];
    $owners = trim($form_state->getValue('owners'));
    if (!empty($owners)) {
      $params['Owners'] = explode(',', $owners);
    }
    $image_ids = trim($form_state->getValue('image_ids'));
    if (!empty($image_ids)) {
      $params['ImageIds'] = explode(',', $image_ids);
    }

    $names = trim($form_state->getValue('name'));
    if (!empty($names)) {
      $params['Filters'] = [
        [
          'Name' => 'name',
          'Values' => [$names],
        ],
      ];
    }

    $cloud_context = $form_state->getValue('cloud_context');

    if (count($params)) {
      $this->awsEc2Service->setCloudContext($cloud_context);
      if (($image_count = $this->awsEc2Service->updateImages($params, FALSE)) !== FALSE) {
        $this->messenger->addMessage($this->t('Imported @count images', ['@count' => $image_count]));
      }
    }

    return $form_state->setRedirect('view.aws_images.page_1', [
      'cloud_context' => $cloud_context,
    ]);
  }

}
