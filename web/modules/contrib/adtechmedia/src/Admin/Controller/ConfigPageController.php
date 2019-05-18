<?php

namespace Drupal\atm\Admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides routers for admin area controller atm.
 */
class ConfigPageController extends ControllerBase {

  /**
   * ConfigPageController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   Form builder.
   */
  public function __construct(FormBuilderInterface $formBuilder) {
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Return content for config page ATM.
   */
  public function content() {

    return [
      '#theme' => 'atm-admin-config-page',
      '#general_configuration' => [
        '#theme' => 'admin_block',
        '#block' => [
          'title' => $this->t('General Configuration'),
          'content' => [
            [
              '#theme' => 'container',
              '#attributes' => [
                'class' => 'clearfix',
              ],
              '#children' => [
                [
                  '#theme' => 'container',
                  '#attributes' => [
                    'class' => 'layout-column layout-column--half',
                  ],
                  '#children' => [
                    [
                      '#theme' => 'admin_block',
                      '#block' => [
                        'content' => $this->formBuilder->getForm('\Drupal\atm\Form\AtmGeneralConfigForm'),
                      ],
                    ],
                  ],
                ],
                [
                  '#theme' => 'container',
                  '#attributes' => [
                    'class' => 'layout-column layout-column--half',
                  ],
                  '#children' => [
                    [
                      '#theme' => 'admin_block',
                      '#block' => [
                        'content' => $this->formBuilder->getForm('\Drupal\atm\Form\AtmRegisterForm'),
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
      '#content_configuration' => [
        '#theme' => 'admin_block',
        '#block' => [
          'title' => $this->t('Content configuration'),
          'content' => $this->formBuilder->getForm('\Drupal\atm\Form\AtmContentConfigurationForm'),
        ],
      ],
      '#templates_management' => [
        '#theme' => 'admin_block',
        '#block' => [
          'title' => $this->t('Templates management'),
          'content' => [
            'overall_position_and_styling' => [
              '#theme' => 'admin_block',
              '#block' => [
                'title' => $this->t('Overall Position And Styling'),
                'content' => $this->formBuilder->getForm('\Drupal\atm\Form\AtmOverallStylingAndPositionForm'),
              ],
            ],
            'content_templates' => [
              '#theme' => 'admin_block',
              '#block' => [
                'content' => $this->formBuilder->getForm('\Drupal\atm\Form\AtmTemplatesForm'),
              ],
            ],
          ],
        ],
      ],
    ];
  }

}
