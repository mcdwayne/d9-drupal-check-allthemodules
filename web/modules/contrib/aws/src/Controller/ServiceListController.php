<?php

namespace Drupal\aws\Controller;

use Drupal\Core\Entity\Controller\EntityListController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\aws\Aws;
use Drupal\Core\Url;

/**
 * Defines a controller to list services.
 */
class ServiceListController extends EntityListController {

  /**
   * Constructs the ServiceListController.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\aws\Aws $aws
   *   The AWS service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Aws $aws) {
    $this->configFactory = $config_factory;
    $this->aws = $aws;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('aws')
    );
  }

  /**
   * Shows the profile administration page.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function listing() {
    $profile_options = [];
    foreach ($this->aws->getProfiles() as $profile) {
      $profile_options += [
        $profile->id() => $profile->label(),
      ];
    }

    $rows = [];
    foreach ($this->aws->getServices() as $service) {
      $profile = $this->aws->getProfile($service['id']);
      $rows[] = [
        [
          'data' => [
            '#plain_text' => $service['label'],
          ],
        ],
        [
          'data' => [
            '#plain_text' => $service['description'],
          ],
        ],
        [
          'data' => [
            '#title' => $profile->label(),
            '#type' => 'link',
            '#url' => Url::fromRoute(
              'entity.aws_profile.edit_form',
              ['aws_profile' => $profile->id()]
            ),
          ],
        ],
        [
          'data' => [
            '#title' => 'Configure',
            '#type' => 'link',
            '#url' => Url::fromRoute('aws.service_settings', ['service_id' => $service['id']]),
          ],
        ],
      ];
    }
    $form = [
      '#type' => 'table',
      '#header' => [
        $this->t('Service'),
        $this->t('Description'),
        $this->t('Profile'),
        $this->t('Configure'),
      ],
      '#rows' => $rows,
    ];

    return $form;
  }

}
