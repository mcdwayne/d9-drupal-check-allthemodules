<?php

namespace Drupal\aws_cloud\Controller\Ec2;

use Drupal\aws_cloud\Service\AwsEc2ServiceInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Render\RendererInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller responsible for "update" urls.
 *
 * This class is mainly responsible for
 * updating the aws entities from urls.
 */
class ApiController extends ControllerBase implements ApiControllerInterface {

  /**
   * The Aws Ec2 Service.
   *
   * @var \Drupal\aws_cloud\Service\AwsEc2ServiceInterface
   */
  private $awsEc2Service;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  /**
   * ApiController constructor.
   *
   * @param \Drupal\aws_cloud\Service\AwsEc2ServiceInterface $aws_ec2_service
   *   Object for interfacing with AWS Api.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   Messanger Object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack object.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(AwsEc2ServiceInterface $aws_ec2_service, Messenger $messenger, RequestStack $request_stack, RendererInterface $renderer) {
    $this->awsEc2Service = $aws_ec2_service;
    $this->messenger = $messenger;
    $this->requestStack = $request_stack;
    $this->renderer = $renderer;
  }

  /**
   * Dependency Injection.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('aws_cloud.ec2'),
      $container->get('messenger'),
      $container->get('request_stack'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function updateAll() {
    $regions = $this->requestStack->getCurrentRequest()->query->get('regions');
    if ($regions == NULL) {
      $this->messageUser($this->t('No region specified'), 'error');
    }
    else {
      $regions_array = explode(',', $regions);

      foreach ($regions_array as $region) {
        $entity = $this->entityTypeManager()->getStorage('cloud_config')
          ->loadByProperties(
            [
              'cloud_context' => $region,
            ]);
        if ($entity) {
          aws_cloud_update_ec2_resources(array_shift($entity));
        }
      }

      $this->messageUser($this->t('Creating Cloud config was performed successfully.'));
      drupal_flush_all_caches();
    }
    return $this->redirect('entity.cloud_config.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function updateInstanceList($cloud_context) {
    $this->awsEc2Service->setCloudContext($cloud_context);
    $updated = $this->awsEc2Service->updateInstances();

    if ($updated != FALSE) {
      $this->messageUser($this->t('Updated Instances.'));
    }
    else {
      $this->messageUser($this->t('Unable to update Instances.'), 'error');
    }

    return $this->redirect('view.aws_instances.page_1', [
      'cloud_context' => $cloud_context,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function updateImageList($cloud_context) {
    $cloud_config_entities = $this->entityTypeManager()->getStorage('cloud_config')->loadByProperties(
      ['cloud_context' => [$cloud_context]]
    );

    if (!empty($cloud_config_entities)) {
      $cloud_config = reset($cloud_config_entities);
      $account_id = $cloud_config->get('field_account_id')->value;
    }

    if ($account_id) {

      $this->awsEc2Service->setCloudContext($cloud_context);
      $updated = $this->awsEc2Service->updateImages([
        'Owners' => [
          $account_id,
        ],
      ], TRUE);

      if ($updated !== FALSE) {
        $this->messageUser($this->t('Updated Images.'));
      }
      else {
        $this->messageUser($this->t('Unable to update Images.'), 'error');
      }
    }
    else {
      $message = $this->t('AWS User ID is not specified.');
      $account = $this->currentUser();
      if ($account->hasPermission('edit cloud config entities')) {
        $message = Link::createFromRoute($message, 'entity.cloud_config.edit_form', ['cloud_config' => $cloud_config->id()])->toString();
      }
      $this->messageUser($message, 'error');
    }
    return $this->redirect('view.aws_images.page_1', [
      'cloud_context' => $cloud_context,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function updateSecurityGroupList($cloud_context) {
    $this->awsEc2Service->setCloudContext($cloud_context);
    $updated = $this->awsEc2Service->updateSecurityGroups();

    if ($updated != FALSE) {
      $this->messageUser($this->t('Updated Security Groups.'));
    }
    else {
      $this->messageUser($this->t('Unable to update Security Groups.'), 'error');
    }
    return $this->redirect('view.aws_security_group.page_1', [
      'cloud_context' => $cloud_context,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function updateNetworkInterfaceList($cloud_context) {
    $this->awsEc2Service->setCloudContext($cloud_context);
    $updated = $this->awsEc2Service->updateNetworkInterfaces();

    if ($updated != FALSE) {
      $this->messageUser($this->t('Updated Network Interfaces.'));
    }
    else {
      $this->messageUser($this->t('Unable to update Network Interfaces.'), 'error');
    }

    return $this->redirect('view.aws_network_interfaces.page_1', [
      'cloud_context' => $cloud_context,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function updateElasticIpList($cloud_context) {

    $this->awsEc2Service->setCloudContext($cloud_context);
    $updated = $this->awsEc2Service->updateElasticIp();

    if ($updated != FALSE) {
      $this->messageUser($this->t('Updated Elastic IPs.'));
    }
    else {
      $this->messageUser($this->t('Unable to update Elastic IPs.'), 'error');
    }

    return $this->redirect('view.aws_elastic_ip.page_1', [
      'cloud_context' => $cloud_context,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function updateKeyPairList($cloud_context) {

    $this->awsEc2Service->setCloudContext($cloud_context);
    $updated = $this->awsEc2Service->updateKeyPairs();

    if ($updated != FALSE) {
      $this->messageUser($this->t('Updated Key Pairs.'));
    }
    else {
      $this->messageUser($this->t('Unable to update Key Pairs.'), 'error');
    }

    return $this->redirect('view.aws_cloud_key_pairs.page_1', [
      'cloud_context' => $cloud_context,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function updateVolumeList($cloud_context) {

    $this->awsEc2Service->setCloudContext($cloud_context);
    $updated = $this->awsEc2Service->updateVolumes();

    if ($updated != FALSE) {
      $this->messageUser($this->t('Updated Volumes.'));
    }
    else {
      $this->messageUser($this->t('Unable to update Volumes.'), 'error');
    }

    return $this->redirect('view.aws_volume.page_1', [
      'cloud_context' => $cloud_context,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function updateSnapshotList($cloud_context) {

    $this->awsEc2Service->setCloudContext($cloud_context);
    $updated = $this->awsEc2Service->updateSnapshots();

    if ($updated !== FALSE) {
      $this->messageUser($this->t('Updated Snapshots.'));
    }
    else {
      $this->messageUser($this->t('Unable to update Snapshots.'), 'error');
    }

    return $this->redirect('view.aws_snapshot.page_1', [
      'cloud_context' => $cloud_context,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function listInstanceCallback($cloud_context) {
    return $this->getViewResponse('aws_instances');
  }

  /**
   * {@inheritdoc}
   */
  public function listImageCallback($cloud_context) {
    return $this->getViewResponse('aws_images');
  }

  /**
   * {@inheritdoc}
   */
  public function listSnapshotCallback($cloud_context) {
    return $this->getViewResponse('aws_snapshot');
  }

  /**
   * {@inheritdoc}
   */
  public function listVolumeCallback($cloud_context) {
    return $this->getViewResponse('aws_volume');
  }

  /**
   * Helper method to get views output.
   *
   * @param string $view_id
   *   The ID of view list.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response of view list.
   */
  private function getViewResponse($view_id) {
    $view = Views::getView($view_id);

    // Set the display machine id.
    $view->setDisplay('page_1');

    // Render the view as html, and return it as a response object.
    $build = $view->render();
    return new Response($this->renderer->render($build));
  }

  /**
   * Helper method to add messages for the end user.
   *
   * @param string $message
   *   The message.
   * @param string $type
   *   The message type: error or message.
   */
  private function messageUser($message, $type = 'message') {
    switch ($type) {
      case 'error':
        $this->messenger->addError($message);
        break;

      case 'message':
        $this->messenger->addMessage($message);
      default:
        break;
    }
  }

}
