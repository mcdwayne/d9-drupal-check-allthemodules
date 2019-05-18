<?php

namespace Drupal\node_revisions_autoclean\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class NodeRevisionsByBundleForm.
 */
class NodeRevisionsByBundleForm extends FormBase {

  /**
   * EntityTypeBundleInfo.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;
  /**
   * ConfigFactory.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * NodeRevisionsByBundleForm constructor.
   *
   * @param Drupal\Core\Entity\EntityTypeBundleInfo $entityTypeBundleInfo
   *   EntityTypeBundleInfo.
   * @param Drupal\Core\Config\ConfigFactory $configFactory
   *   ConfigFactory.
   */
  public function __construct(EntityTypeBundleInfo $entityTypeBundleInfo, ConfigFactory $configFactory) {
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->configFactory = $configFactory;
  }

  /**
   * Creates.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   ContainerInterface.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_revisions_by_bundle_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $types = $this->entityTypeBundleInfo->getBundleInfo('node');
    $config = $this->configFactory->get('node_revisions_autoclean.settings');

    $form['enable_on_cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable old revisions deletion during cronjobs'),
      '#return_value' => '1',
      '#default_value' => $config->get('enable_on_cron') ? $config->get('enable_on_cron') : '0',
      '#description' => $this->t('Cronjobs will delete old revisions according your parameters.'),
    ];

    $form['enable_on_node_update'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable old revisions deletion on node update'),
      '#description' => $this->t("Each node's revisions will be autoclean on node update"),
      '#return_value' => '1',
      '#default_value' => $config->get('enable_on_node_update') ? $config->get('enable_on_node_update') : '0',
    ];

    $form['explain'] = [
      '#markup' => '<p><i>' . $this->t('You can select none of the above if you wish to delete old revisions using a drush command (drush nra:dor).') . '</i></p>',
    ];

    foreach ($types as $machine_name => $arr) {
      $form['fs_' . $machine_name] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Content type : @content_type', ['@content_type' => $arr['label']]),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      ];

      $form['fs_' . $machine_name]["node__$machine_name"] = [
        '#type' => 'number',
        '#title' => $this->t('Limit revisions for node type @label', ['@label' => $arr['label']]),
        '#description' => $this->t('Max revisions for @label type, "0" means unlimited number of revisions.', ['@label' => $arr['label']]),
        '#default_value' => $config->get("node.$machine_name") ? $config->get("node.$machine_name") : 0,
        '#required' => TRUE,
      ];
      $val = $config->get("interval.$machine_name");
      $form['fs_' . $machine_name]['node_enable_date_' . $machine_name] = [
        '#type' => 'checkbox',
        '#title' => $this->t("Keep latest revisions based on date"),
        '#return_value' => 1,
        '#default_value' => isset($val) && $val ? 1 : 0,
      ];

      $form['fs_' . $machine_name]['interval__' . $machine_name] = [
        '#type' => 'select',
        '#title' => $this->t("Keep latests revisions"),
        '#states' => [
          'visible' => [
            ':input[name="node_enable_date_' . $machine_name . '"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
        '#options' => [
          '0' => $this->t('Choose value'),
          'P1W' => $this->t('1 week'),
          'P2W' => $this->t('2 weeks'),
          'P1M' => $this->t('1 month'),
          'P2M' => $this->t('2 months'),
          'P6M' => $this->t('6 months'),
          'P1Y' => $this->t('1 year'),
        ],
        '#default_value' => isset($val) && $val ? $val : 0,
      ];
    }
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('node_revisions_autoclean.settings');
    $values = $form_state->getValues();

    $enable_on_cron = (int) $form_state->getValue('enable_on_cron');
    $config->set('enable_on_cron', $enable_on_cron);

    $enable_on_node_update = (int) $form_state->getValue('enable_on_node_update');
    $config->set('enable_on_node_update', $enable_on_node_update);

    foreach ($values as $key => $val) {
      if (strpos($key, 'interval__') === 0) {
        $machine_name = str_replace('interval__', '', $key);
        $key = str_replace('__', '.', $key);
        if ($form_state->getValue('node_enable_date_' . $machine_name)) {
          $config->set($key, "$val");
        }
        else {
          $config->set($key, '0');
        }
      }
      elseif (strpos($key, 'node__') === 0) {
        $key = str_replace('__', '.', $key);
        $config->set($key, (int) $val);
      }
    }

    $config->save(TRUE);
    drupal_set_message($this->t('Node revisions settings have been updated.'));
  }

}
