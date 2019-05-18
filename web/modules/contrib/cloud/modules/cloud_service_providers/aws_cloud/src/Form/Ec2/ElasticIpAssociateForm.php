<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\aws_cloud\Entity\Ec2\Instance;
use Drupal\aws_cloud\Entity\Ec2\NetworkInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Associate elastic ip address.
 */
class ElasticIpAssociateForm extends AwsDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    /* @var \Drupal\aws_cloud\Entity\Ec2\ElasticIp $entity */
    $entity = $this->entity;
    return $this->t('Select the instance OR network interface to which you want to associate this Elastic IP address (@ip_address)', [
      '@ip_address' => $entity->getPublicIp(),
    ]);
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
  public function getConfirmText() {
    return t('Associate');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    if ($this->entity->getAssociationId() != NULL) {
      $form['error'] = [
        '#markup' => '<div>' . $this->t('Elastic IP is already associated') . '</div>',
      ];
      unset($form['actions']['submit']);
    }
    else {
      $form['#attached']['library'][] = 'aws_cloud/aws_cloud_elastic_ips';
      $form['resource_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Resource type'),
        '#options' => [
          'instance' => $this->t('Instance'),
          'network_interface' => $this->t('Network Interface'),
        ],
        '#description' => $this->t('Choose the type of resource to which to associate the Elastic IP address'),
        '#default_value' => 'instance',
      ];

      $form['instance_ip_container'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'instance-ip-container',
        ],
      ];

      $form['instance_ip_container']['instance_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Instance'),
        '#options' => $this->getUnassociatedInstances(),
        '#ajax' => [
          'callback' => '::getPrivateIpsAjaxCallback',
          'event' => 'change',
          'wrapper' => 'instance-ip-container',
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Retrieving...'),
          ],
        ],
      ];

      $form['instance_ip_container']['instance_private_ip'] = [
        '#type' => 'select',
        '#title' => $this->t('Private IP'),
        '#description' => $this->t('The private IP address to which to associate the Elastic IP address. Only private IP addresses that do not already have an Elastic IP associated with them are available.'),
        '#options' => [
          '-1' => $this->t('Select a private ip'),
        ],
      ];

      $form['network_interface_ip_container'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'network-interface-ip-container',
        ],
      ];

      $form['network_interface_ip_container']['network_interface_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Network interface'),
        '#options' => $this->getUnassociatedNetworkInterfaces(),
        '#ajax' => [
          'callback' => '::getNetworkIpsAjaxCallback',
          'event' => 'change',
          'wrapper' => 'network-interface-ip-container',
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Retrieving...'),
          ],
        ],
      ];
      $form['network_interface_ip_container']['network_private_ip'] = [
        '#type' => 'select',
        '#title' => $this->t('Private IP'),
        '#description' => $this->t('The private IP address to which to associate the Elastic IP address. Only private IP addresses that do not already have an Elastic IP associated with them are available.'),
        '#options' => [
          '-1' => $this->t('Select a private ip'),
        ],
      ];

      // Ajax support: Look at the instance value, and rebuild the private_ip
      // options.
      $instance = $form_state->getValue('instance_id');
      if (isset($instance)) {
        if ($instance != -1) {
          $ips = $this->getPrivateIps($instance);
          $form['instance_ip_container']['instance_private_ip']['#options'] = $ips;
        }
        else {
          $form['instance_ip_container']['instance_private_ip']['#options'] = [
            '-1' => $this->t('Select a private ip'),
          ];
        }
      }

      // Ajax support: Look at network interface value and rebuild the private
      // ip portion of the form.
      $network_interface = $form_state->getValue('network_interface_id');
      if (isset($network_interface)) {
        if ($network_interface != -1) {
          $ips = $this->getNetworkPrivateIps($network_interface);
          $form['network_interface_ip_container']['network_private_ip']['#options'] = $ips;
        }
        else {
          $form['network_interface_ip_container']['network_private_ip']['#options'] = [
            '-1' => $this->t('Select a private ip'),
          ];
        }
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('resource_type') == 'instance') {
      if ($form_state->getValue('instance_id') == -1) {
        // Error out.
        $form_state->setErrorByName('instance_id', $this->t('Instance id is empty'));
      }
      if ($form_state->getValue('instance_private_ip') == -1) {
        // Error out.
        $form_state->setErrorByName('instance_private_ip', $this->t('Private ip is empty'));
      }
    }
    else {
      if ($form_state->getValue('network_interface_id') == -1) {
        $form_state->setErrorByName('network_interface_id', $this->t('Network interface is empty'));
      }
      if ($form_state->getValue('network_private_ip') == -1) {
        $form_state->setErrorByName('network_private_ip', $this->t('Private ip is empty'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->awsEc2Service->setCloudContext($this->entity->getCloudContext());

    // Determine if elastic_ip is attaching to instance or network_interface.
    if ($form_state->getValue('resource_type') == 'instance') {
      $instance_id = $form_state->getValue('instance_id');
      $private_ip = $form_state->getValue('instance_private_ip');
      if ($instance_id != -1) {
        $instance = Instance::load($instance_id);

        $network_interface = $this->getNetworkInterfaceByPrivateIp($private_ip);
        $result = $this->awsEc2Service->associateAddress([
          'AllocationId' => $this->entity->getAllocationId(),
          'NetworkInterfaceId' => $network_interface->getNetworkInterfaceId(),
          'PrivateIpAddress' => $private_ip,
        ]);

        if ($result != NULL) {
          $message = $this->t('Elastic IP @ip_address associated with @private_ip for instance: @instance', [
            '@ip_address' => $this->entity->getPublicIp(),
            '@instance' => $instance->getName(),
            '@private_ip' => $private_ip,
          ]);
          $this->updateElasticIpEntity($message);
          $this->clearCacheValues();
        }
        else {
          $this->messenger->addError($this->t('Unable to associate elastic ip'));
        }
      }
      else {
        $this->messenger->addError($this->t('Unable to load instance id. No association performed'));
      }
    }
    else {
      $network_interface_id = $form_state->getValue('network_interface_id');
      $network_private_ip = $form_state->getValue('network_private_ip');

      if ($network_interface_id != -1) {
        $network_interface = NetworkInterface::load($network_interface_id);
        $result = $this->awsEc2Service->associateAddress([
          'AllocationId' => $this->entity->getAllocationId(),
          'NetworkInterfaceId' => $network_interface->getNetworkInterfaceId(),
          'PrivateIpAddress' => $network_private_ip,
        ]);

        if ($result != NULL) {
          $message = $this->t('Elastic IP @ip_address associated with @private_ip for network interface: @network_interface_id', [
            '@ip_address' => $this->entity->getPublicIp(),
            '@network_interface_id' => $network_interface->getNetworkInterfaceId(),
            '@private_ip' => $network_private_ip,
          ]);
          $this->updateElasticIpEntity($message);
          $this->clearCacheValues();
        }
        else {
          $this->messenger->addError($this->t('Unable to associate elastic ip'));
        }
      }
      else {
        $this->messenger->addError($this->t('Unable to load instance id. No association performed'));
      }
    }

    $form_state->setRedirect('entity.aws_cloud_elastic_ip.canonical', [
      'cloud_context' => $this->entity->getCloudContext(),
      'aws_cloud_elastic_ip' => $this->entity->id(),
    ]);
  }

  /**
   * Helper function to update the current aws_cloud_elastic_ip entity.
   *
   * @param string $message
   *   Message to display to use.
   */
  private function updateElasticIpEntity($message) {
    $this->messenger->addMessage($message);

    // Update the following entities from EC2.
    $this->awsEc2Service->updateElasticIp();
    $this->awsEc2Service->updateInstances();
    $this->awsEc2Service->updateNetworkInterfaces();
  }

  /**
   * Ajax callback when the instance dropdown changes.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state interface element.
   *
   * @return mixed
   *   Form element for instance_ip_container.
   */
  public function getPrivateIpsAjaxCallback(array $form, FormStateInterface $form_state) {
    return $form['instance_ip_container'];
  }

  /**
   * Ajax callback when the network interface dropdown changes.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state interface element.
   *
   * @return mixed
   *   Form element for network_interface_ip_container.
   */
  public function getNetworkIpsAjaxCallback(array $form, FormStateInterface $form_state) {
    return $form['network_interface_ip_container'];
  }

  /**
   * Helper function that loads all the private ips for an instance.
   *
   * @param int $instance_id
   *   The instance id.
   *
   * @return array
   *   An array of ip addresses.
   */
  private function getPrivateIps($instance_id) {
    $instance = Instance::load($instance_id);
    $ips = explode(', ', $instance->getPrivateIps());
    foreach ($ips as $ip) {
      // Check if the ip is in the elastic ip table.
      $result = $this->entityTypeManager
        ->getStorage('aws_cloud_elastic_ip')
        ->loadByProperties([
          'private_ip_address' => $ip,
        ]);

      if (count($result) == 0) {
        $private_ips[$ip] = $ip;
      }
    }
    return $private_ips;
  }

  /**
   * Helper function to load primary and secondary private ips.
   *
   * @param int $network_interface_id
   *   The network interface id.
   *
   * @return array
   *   An array of ip addresses.
   */
  private function getNetworkPrivateIps($network_interface_id) {
    /* @var \Drupal\aws_cloud\Entity\Ec2\NetworkInterface $network_interface */
    $network_interface = NetworkInterface::load($network_interface_id);
    $association_id = $network_interface->getAssociationId();
    $secondary_association_id = $network_interface->getSecondaryAssociationId();
    if (is_null($association_id)) {
      $ips[$network_interface->getPrimaryPrivateIp()] = $network_interface->getPrimaryPrivateIp();
    }
    if (is_null($secondary_association_id) && !empty($network_interface->getSecondaryPrivateIps())) {
      $ips[$network_interface->getSecondaryPrivateIps()] = $network_interface->getSecondaryPrivateIps();
    }
    return $ips;
  }

  /**
   * Query the database for instances that do not have elastic ips.
   *
   * @return array
   *   An array of instances formatted for a dropdown.
   */
  private function getUnassociatedInstances() {
    $instances['-1'] = $this->t('Select an instance');
    $account = \Drupal::currentUser();

    $query = $this->entityTypeManager
      ->getStorage('aws_cloud_instance')
      ->getQuery()
      ->condition('cloud_context', $this->entity->getCloudContext());

    if (!$account->hasPermission('view any aws cloud instance')) {
      $query->condition('uid', $account->id());
    }

    $results = $query->execute();

    foreach ($results as $result) {
      /* @var \Drupal\aws_cloud\Entity\Ec2\Instance $instance */
      $instance = Instance::load($result);
      $private_ips = explode(', ', $instance->getPrivateIps());

      foreach ($private_ips as $private_ip) {
        $elastic_ips = $this->entityTypeManager
          ->getStorage('aws_cloud_elastic_ip')
          ->loadbyProperties([
            'private_ip_address' => $private_ip,
          ]);
        if (count($elastic_ips) == 0) {
          /* @var \Drupal\aws_cloud\Entity\Ec2\Instance $result */
          $instances[$instance->id()] = $this->t('%name - %instance_id', [
            '%name' => $instance->getName(),
            '%instance_id' => $instance->getInstanceId(),
          ]);
        }
      }
    }
    return $instances;
  }

  /**
   * Get a network interface given a private_ip address.
   *
   * @param string $private_ip
   *   The private ip used to look up the network interface.
   *
   * @return bool|mixed
   *   False if no network interface found.  The network interface object if
   *   found.
   */
  private function getNetworkInterfaceByPrivateIp($private_ip) {
    $network_interface = FALSE;
    $results = $this->entityTypeManager
      ->getStorage('aws_cloud_network_interface')
      ->loadByProperties([
        'primary_private_ip' => $private_ip,
      ]);
    if (count($results) == 1) {
      $network_interface = array_shift($results);
    }
    return $network_interface;
  }

  /**
   * Query the database for unassociated network interfaces ips.
   *
   * @return array
   *   An array of instances formatted for a dropdown.
   */
  private function getUnassociatedNetworkInterfaces() {
    $interfaces['-1'] = $this->t('Select a network interface');
    $results = $this->entityTypeManager
      ->getStorage('aws_cloud_network_interface')
      ->getQuery('OR')
      ->notExists('association_id')
      ->notExists('secondary_association_id')
      ->execute();

    foreach ($results as $result) {
      /* @var \Drupal\aws_cloud\Entity\Ec2\NetworkInterface $interface */
      $interface = NetworkInterface::load($result);
      if ($interface->getCloudContext() == $this->entity->getCloudContext()) {
        $interfaces[$interface->id()] = $this->t('%name - %interface_id', [
          '%name' => $interface->getName(),
          '%interface_id' => $interface->getNetworkInterfaceId(),
        ]);
      }
    }
    return $interfaces;
  }

}
