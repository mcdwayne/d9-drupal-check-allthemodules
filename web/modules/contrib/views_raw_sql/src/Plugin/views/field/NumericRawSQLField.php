<?php

namespace Drupal\views_raw_sql\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\views\Plugin\views\field\NumericField;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 * @ViewsField("field_views_raw_sql_numeric")
 */
class NumericRawSQLField extends NumericField implements ContainerFactoryPluginInterface {

  /**
   * Provides current_user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * Class constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Instantiates this form class.
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * Sets the initial field data at zero.
   */
  public function query() {
    $this->ensureMyTable();
    // Add the field.
    $group_type = $this->options['group_type'];
    $params = $group_type != 'group' ? ['function' => $group_type] : [];

    $sql = $this->options['raw_sql'];
    $this->field_alias = $this->query->addField(NULL, $sql, 'raw_sql_field', $params);
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['raw_sql'] = ['default' => 0];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    if ($this->account->hasPermission('edit views raw sql')) {
      $form['raw_sql'] = [
        '#type' => 'textfield',
        '#title' => t('Raw SQL'),
        '#default_value' => $this->options['raw_sql'],
        '#weight' => -6,
      ];
    }
  }

}
