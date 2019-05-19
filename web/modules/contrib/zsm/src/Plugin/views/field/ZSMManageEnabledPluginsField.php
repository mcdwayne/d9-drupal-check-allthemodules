<?php
/**
 * @file
 */

namespace Drupal\zsm\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Utility\Html;

/**
 * Defines a views field plugin.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("zsm_manage_enabled_plugins")
 */

class ZSMManageEnabledPluginsField extends FieldPluginBase {
  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }
  /**
   * Define the available options
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_classes'] = array('default' => '');
    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['link_classes'] = array(
      '#type' => 'textfield',
      '#title' => t('Mange Plugins Link Classes'),
      '#description' => t('The classes to add to the plugin when generating a link.'),
      '#default_value' => isset($this->options['link_classes']) ? $this->options['link_classes'] : '',
    );
  }

  /**
   * Create the link for
   */

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values)
  {
    $data_entity = $this->getEntity($values);
    $plugins = $data_entity->get('field_zsm_enabled_plugins')->getValue();
    $user_id = \Drupal::currentUser()->id();
    $url_data = array('zsm_core' => $data_entity->id(), 'user_id' => $user_id);
    $url_opts = array('attributes' => array('class' => 'use-ajax ' . $this->options['link_classes']));
    $url_opts['attributes']['data-dialog-type'] = 'modal';
    $url = Url::fromRoute('zsm.zsm_manage_plugins', $url_data, $url_opts);
    $link = Link::fromTextAndUrl('Manage Plugins', $url);
    $l = $link->toString();

    $ret = $l;
    $ret .= '<div id="zsm_managed_plugins_' . $data_entity->id() . '">';
    foreach($plugins as $plugin) {
      $plugin_edit_url = new Url('entity.' . $plugin['target_type'] . '.edit_form', array($plugin['target_type'] => $plugin['target_id']));
      $params = array('zsm_core' => $data_entity->id(), 'entity_type' => $plugin['target_type'], 'entity_id' => $plugin['target_id']);
      $plugin_remove_url = new Url('zsm.zsm_core_remove_plugin', $params);

      $p = \Drupal::entityTypeManager()->getStorage($plugin['target_type'])->load($plugin['target_id']);

      $e_link = new Link('Edit', $plugin_edit_url);
      $plugin_edit_link = $e_link->toString()->getGeneratedLink();
      $plugin_remove_link = Link::fromTextAndUrl('Remove from Monitor', $plugin_remove_url)->toString();

      $ret .= $p->label() . ': ' . $plugin_edit_link .  ' - ' . $plugin_remove_link . '<br>';
    }
    $ret .= '</div>';

    return array(
      '#markup' => $ret,
    );
  }
}