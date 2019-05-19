<?php
/**
 * Created by PhpStorm.
 * User: andy
 * Date: 15/01/2016
 * Time: 22:55
 */

namespace Drupal\subsite;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

class SubsiteManager implements SubsiteManagerInterface {
  use StringTranslationTrait;

  /**
   * @return \Drupal\subsite\SubsitePluginInterface[]
   */
  public function getPluginDefinitions() {
    /** @var SubsitePluginManager $subsitePluginManager */
    $subsitePluginManager = \Drupal::service('plugin.manager.subsite');

    /** @var SubsitePluginInterface[] $pluginDefinitions */
    $pluginDefinitions = $subsitePluginManager->getDefinitions();
    return $pluginDefinitions;
  }

  public function getPlugins($node) {
    /** @var SubsitePluginManager $subsitePluginManager */
    $subsitePluginManager = \Drupal::service('plugin.manager.subsite');

    /** @var SubsitePluginInterface[] $pluginDefinitions */
    $pluginDefinitions = $this->getPluginDefinitions();

    // @todo Move storage details out of here. May end up storing elsewhere.
    $subsite_field = $this->getSubsiteField($node);
    $subsite_field_value = $subsite_field->getValue();

    // Field is an item list and store serialized data in the value column so
    // get the first value.
    $subsite_settings = !empty($subsite_field_value[0]['value']) ? $subsite_field_value[0]['value'] : array();

    $plugins = array();

    foreach ($pluginDefinitions as $plugin_id => $pluginDefinition) {
      $plugin_configuration = isset($subsite_settings[$plugin_id]) ? $subsite_settings[$plugin_id] : array();
      $plugins[$plugin_id] = $subsitePluginManager->createInstance($plugin_id, $plugin_configuration);
    }

    return $plugins;
  }

  public function getPlugin($plugin_id, $node) {
    $plugins = $this->getPlugins($node);

    return isset($plugins[$plugin_id]) ? $plugins[$plugin_id] : FALSE;
  }

  /**
   * Get the subsite field attached to a given node.
   *
   * @param \Drupal\node\Entity\Node $node
   */
  public function getSubsiteField(Node $node) {
    $subsite_fields = array();

    $field_definitions = $node->getFieldDefinitions();
    foreach ($field_definitions as $field_name => $field_definition) {
      /** @var $field_definition FieldConfig */
      if ($field_definition->getType() == 'subsite') {
        $subsite_fields[$field_name] = $node->get($field_name);
      }
    }

    return !empty($subsite_fields) ? reset($subsite_fields) : FALSE;
  }

  public function addFormElements(array $form, FormStateInterface $form_state, NodeInterface $node, AccountInterface $account, $collapsed = TRUE) {
    // TODO: Implement addFormElements() method.
    $form['subsite'] = array(
      '#type' => 'details',
      '#title' => $this->t('Subsite'),
      '#weight' => 10,
      '#open' => !$collapsed,
      '#group' => 'advanced',
      '#tree' => TRUE,
      '#subsite_node' => $node,
    );

    /** @var SubsitePluginManager $subsitePluginManager */
    $subsitePluginManager = \Drupal::service('plugin.manager.subsite');

    $plugins = $this->getPlugins($node);

    foreach ($plugins as $plugin_id => $plugin) {
      $plugin_definition = $subsitePluginManager->getDefinition($plugin_id);
      $form['subsite'][$plugin_id] = array(
        '#tree' => TRUE,
//        '#plugin' => $plugin_id,
      );

      if (!empty($plugin_definition['label'])) {
        $form['subsite'][$plugin_id]['#type'] = 'details';
        $form['subsite'][$plugin_id]['#title'] = $plugin_definition['label'];
      }

      $form['subsite'][$plugin_id] += $plugin->buildConfigurationForm(array(), $form_state);
    }
    return $form;
  }

  public function getFormValues(array $form, FormStateInterface $form_state) {
    $plugin_configurations = array();

    $plugin_ids = Element::children($form['subsite']);

    /** @var Node $subsite_node */
    $subsite_node = $form['subsite']['#subsite_node'];

    $plugins = $this->getPlugins($subsite_node);

    foreach ($plugin_ids as $plugin_id) {
      /** @var SubsitePluginInterface $plugin */
      $plugin = $plugins[$plugin_id];
      $result = $plugin->submitConfigurationForm($form['subsite'][$plugin_id], $form_state);
      $plugin_configurations[$plugin_id] = $plugin->getConfiguration();
    }

    Cache::invalidateTags(array('subsite:' . $subsite_node->id()));

    return $plugin_configurations;
  }

  /**
   * For a given node, gets the node that defines the subsite.
   *
   * @param $node
   */
  public function getSubsiteNode($node) {
    // @todo - move to book plugin and allow any plugin to provide context.
    $current_bid = empty($node->book['bid']) ? 0 : $node->book['bid'];

    if ($current_bid) {
      /** @var Node $subsite_node */
      $subsite_node = \Drupal\node\Entity\Node::load($current_bid);
      if ($this->getSubsiteField($subsite_node)) {
        return $subsite_node;
      }
    }
  }

  public function validateFormElements(array $form, FormStateInterface $form_state, NodeInterface $node, AccountInterface $account) {
    $plugin_configurations = array();

    /** @var Node $subsite_node */
    $subsite_node = $form['subsite']['#subsite_node'];

    $plugins = $this->getPlugins($subsite_node);


    $plugin_ids = Element::children($form['subsite']);
    foreach ($plugin_ids as $plugin_id) {
      /** @var SubsitePluginInterface $plugin */
      $plugin = $plugins[$plugin_id];
      $result = $plugin->validateConfigurationForm($form['subsite'][$plugin_id], $form_state);
    }
  }

  /**
   * Handle the hook_block_view_alter() to add prerenderers for those plugins
   * that need to.
   *
   * @param array $build
   * @param \Drupal\Core\Block\BlockPluginInterface $block
   */
  public function blockViewAlter(array &$build, \Drupal\Core\Block\BlockPluginInterface $block) {
    $prerender_plugins = $this->getBlockPrerenderPluginIds($block->getPluginId());
    if (!empty($prerender_plugins)) {
      $build['#pre_render'][] = '\Drupal\subsite\SubsiteManager::blockPrerender';
    }
  }

  /**
   * Get the set of subsite plugin definitions that need to prerender the block.
   *
   * @param $block_plugin_id
   * @return array
   */
  public function getBlockPrerenderPluginIds($block_plugin_id) {
    $block_prerender_subsite_plugins = array();

    $pluginDefinitions = $this->getPluginDefinitions();
    foreach ($pluginDefinitions as $plugin_id => $pluginDefinition) {
      if (!empty($pluginDefinition['block_prerender'])) {
        foreach ($pluginDefinition['block_prerender'] as $prerender_block_plugin_id) {
          if ($block_plugin_id == $prerender_block_plugin_id) {
            $block_prerender_subsite_plugins[$plugin_id] = $pluginDefinition;
          }
        }
      }
    }

    return $block_prerender_subsite_plugins;
  }

  /**
   * Allow all plugins to alter the current node.
   *
   * @param array $build
   * @param \Drupal\Core\Entity\EntityInterface $node
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   */
  public function nodeViewAlter(array &$build, EntityInterface $node, EntityViewDisplayInterface $display) {
    $pluginDefinitions = $this->getPluginDefinitions();

    foreach ($pluginDefinitions as $plugin_id => $plugin_definition) {
      if (!empty($plugin_definition['node_view_alter'])) {
        if ($subsite_node = $this->getSubsiteNode($node)) {
          /** @var SubsitePluginInterface $plugin */
          $plugin = $this->getPlugin($plugin_id, $subsite_node);
          $plugin->nodeViewAlter($build, $node, $display);
        }
      }
    }
  }

  /**
   * Do block prerendering.
   *
   * @param $build
   * @return mixed
   */
  public static function blockPrerender($build) {
    // @todo This is so wrong...
    /** @var SubsiteManager $subsite_manager */
    $subsite_manager = \Drupal::service('subsite.manager');
    $pluginDefinitions = $subsite_manager->getPluginDefinitions();

    $requestStack = \Drupal::service('request_stack');
    $node = $requestStack->getCurrentRequest()->get('node');

    if ($node) {
      /** @var Node $subsite_node */
      $subsite_node = $subsite_manager->getSubsiteNode($node);

      if ($subsite_node) {
        $prerender_plugins = $subsite_manager->getBlockPrerenderPluginIds($build['#plugin_id']);

        foreach ($prerender_plugins as $plugin_id => $pluginDefinition) {
          /** @var SubsitePluginInterface $social_plugin */
          $plugin = $subsite_manager->getPlugin($plugin_id, $subsite_node);
          $build = $plugin->blockPrerender($build, $node, $subsite_node);
        }

        // Only do this if changed?
        if (!in_array('subsite:' . $subsite_node->id(), $build['#cache']['tags'])) {
          $build['#cache']['tags'][] = 'subsite:' . $subsite_node->id();
        }
      }
    }

    if (!in_array('route.subsite', $build['#cache']['contexts'])) {
      $build['#cache']['contexts'][] = 'route.subsite';
    }

    return $build;
  }

  public function pageAttachmentsAlter(array &$attachments) {
    $pluginDefinitions = $this->getPluginDefinitions();

    $requestStack = \Drupal::service('request_stack');
    $node = $requestStack->getCurrentRequest()->get('node');

    foreach ($pluginDefinitions as $plugin_id => $plugin_definition) {
      if (!empty($plugin_definition['page_attachments_alter'])) {
        if ($subsite_node = $this->getSubsiteNode($node)) {
          /** @var SubsitePluginInterface $plugin */
          $plugin = $this->getPlugin($plugin_id, $subsite_node);
          $plugin->pageAttachmentsAlter($attachments);
        }
      }
    }
  }
}