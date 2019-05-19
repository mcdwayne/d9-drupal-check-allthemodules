<?php

/**
 * @file
 * Contains \Drupal\social_timeline\Plugin\Block\SocialTimelineBlock.
 */

namespace Drupal\social_timeline\Plugin\Block;

use Drupal\block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\social_timeline\SocialTimelineManager;

/**
 * Provides a 'Social Timeline' block.
 *
 * @Block(
 *   id = "social_timeline_block",
 *   admin_label = @Translation("Social Timeline"),
 *   category = @Translation("Content")
 * )
 */
class SocialTimelineBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'social_timeline_global' => array(
        'skin' => 'light',
        'layout_mode' => 'timeline',
        'total' => 10,
        'item_width' => 200,
        'add_lightbox' => 0,
        'show_social_icons' => 1,
        'show_filter' => 1,
        'show_layout' => 1,
        'show_share_buttons' => 1,
        'timeline_item_width' => '260px',
        'columns_item_width' => '275px',
        'one_column_item_width' => '98%',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $manager = \Drupal::service('social_timeline.manager');
    $feeds = $manager::getFeeds();
    $limit_range = range(1, 100);
    $limit = array_combine($limit_range, $limit_range);
    $table_vals = isset($this->configuration['social_timeline_table']) ? $this->configuration['social_timeline_table'] : array();
    $form['instance_id'] = array(
      '#type' => 'value',
      '#value' => $form_state->getFormObject()->getEntity()->get('id'),
    );

    $form['#prefix'] = '<div id="social-timeline-block-form">';
    $form['#suffix'] = '</div>';

    $form['social_timeline_global'] = array(
      '#type' => 'details',
      '#weight' => 2,
      '#open' => TRUE,
      '#title' => t('General Settings'),
    );

    $form['social_timeline_global']['skin'] = array(
      '#type' => 'select',
      '#title' => t('Skin'),
      '#description' => t('Select the skin style.'),
      '#options' => array(
        'light' => t('Light'),
        'dark' => t('Dark'),
      ),
      '#default_value' => $this->configuration['social_timeline_global']['skin'],
    );

    $form['social_timeline_global']['layout_mode'] = array(
      '#type' => 'select',
      '#title' => t('Layout Mode'),
      '#options' => array(
        'timeline' => t('Timeline'),
        'columns' => t('Columns'),
        'one_column' => t('One Column'),
      ),
      '#description' => t('Select the layout mode'),
      '#default_value' => $this->configuration['social_timeline_global']['layout_mode'],
    );

    $form['social_timeline_global']['total'] = array(
      '#type' => 'textfield',
      '#title' => t('Total'),
      '#description' => t('Total number of items to retrieve'),
      '#default_value' => $this->configuration['social_timeline_global']['total'],
      '#size' => 4,
    );

    $form['social_timeline_global']['timeline_item_width'] = array(
      '#type' => 'textfield',
      '#title' => t('Timeline Items Width'),
      '#description' => t('Set the width of each item in the timeline view (in pixels or %)'),
      '#default_value' => $this->configuration['social_timeline_global']['timeline_item_width'],
      '#size' => 4,
    );

    $form['social_timeline_global']['columns_item_width'] = array(
      '#type' => 'textfield',
      '#title' => t('Columns Items Width'),
      '#description' => t('Set the width of each item in the columns view (in pixels or %)'),
      '#default_value' => $this->configuration['social_timeline_global']['columns_item_width'],
      '#size' => 4,
    );

    $form['social_timeline_global']['one_column_item_width'] = array(
      '#type' => 'textfield',
      '#title' => t('One Column Items Width'),
      '#description' => t('Set the width of each item in the one column view (in pixels or %)'),
      '#default_value' => $this->configuration['social_timeline_global']['one_column_item_width'],
      '#size' => 4,
    );

    $form['social_timeline_global']['add_lightbox'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add Lightbox'),
      '#description' => t('Add Lightbox support for images and videos'),
      '#default_value' => $this->configuration['social_timeline_global']['add_lightbox'],
    );

    $form['social_timeline_global']['show_social_icons'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Social Icons'),
      '#description' => t('Set if you want to show the social icons'),
      '#default_value' => $this->configuration['social_timeline_global']['show_social_icons'],
    );

    $form['social_timeline_global']['show_filter'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Filter'),
      '#description' => t('Set if you want to show the filter buttons'),
      '#default_value' => $this->configuration['social_timeline_global']['show_filter'],
    );

    $form['social_timeline_global']['show_layout'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Layout'),
      '#description' => t('Set if you want to show the layout buttons'),
      '#default_value' => $this->configuration['social_timeline_global']['show_layout'],
    );

    $form['social_timeline_global']['share'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show "Share" Buttons'),
      '#description' => t('Set if you want to show the share buttons'),
      '#default_value' => $this->configuration['social_timeline_global']['share'],
    );

    $form['add_feed'] = array(
      '#type' => 'details',
      '#title' => t('Add Custom Feed'),
      '#weight' => 3,
      '#open' => FALSE,
    );

    $form['add_feed']['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Feed Name'),
      '#description' => t('The name of the feed'),
    );

    $form['add_feed']['data'] = array(
      '#type' => 'textfield',
      '#title' => t('URL'),
      '#description' => t('The URL to the custom feed'),
    );

    $form['add_feed']['icon'] = array(
      '#type' => 'textfield',
      '#title' => t('Feed Icon'),
      '#description' => t('The icon to represent the feed'),
    );

    $form['add_feed']['link'] = array(
      '#type' => 'submit',
      '#value' => t('Add New Feed'),
      '#submit' => array(array($this, 'ajaxSubmit')),
      '#ajax' => array(
        'callback' => array($this, 'ajaxCallback'),
        'wrapper' => 'block-form',
        'progress' => array(
          'type' => 'throbber',
          'message' => t('Please wait...'),
        ),
      ),
    );

    $form['social_timeline_table'] = array(
      '#type' => 'table',
      '#weight' => 4,
      '#header' => array(t('Feed'), t('Config'), t('Icon'), t('Limit'), t('Active'), t('Delete'), t('Weight')),
      '#empty' => t('There are no items yet.'),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'social-timeline-table-order-weight',
        ),
      ),
    );

    // Loop through the default feeds and add them to the table.
    foreach ($feeds as $id => $value) {
      $form['social_timeline_table'][$id] = array(
        '#attributes' => array(
          'class' => array(
            'draggable',
          ),
        ),
        '#weight' => $table_vals[$id]['weight'],
        'feed' => array(
          '#markup' => $value['title'],
        ),
        'data' => array(
          '#type' => 'textfield',
          '#title' => SafeMarkup::checkPlain($value['data']),
          '#default_value' => (isset($table_vals[$id]['data'])) ? $table_vals[$id]['data'] : NULL,
        ),
        'icon' => array(
          '#markup' => '',
        ),
        'limit' => array(
          '#type' => 'select',
          '#title' => t('Limit'),
          '#options' => $limit,
          '#default_value' => (isset($table_vals[$id]['limit'])) ? $table_vals[$id]['limit'] : 5,
        ),
        'active' => array(
          '#type' => 'checkbox',
          '#default_value' => (isset($table_vals[$id]['active'])) ? $table_vals[$id]['active'] : 0,
        ),
        'delete' => array(
          '#markup' => '',
        ),
        'weight' => array(
          '#type' => 'weight',
          '#title' => t('Weight'),
          '#title_display' => 'invisible',
          '#default_value' => (isset($table_vals[$id]['weight'])) ? $table_vals[$id]['weight'] : 0,
          '#delta' => 10,
          '#attributes' => array('class' => array('social-timeline-table-order-weight')),
        ),
      );
    }

    // Loop through the custom feeds.
    foreach ($table_vals as $tk => $tv) {
      if (strpos($tk, 'custom_') !== FALSE) {
        $test = '';
        $form['social_timeline_table'][$tk] = array(
          '#attributes' => array(
            'class' => array(
              'draggable',
            ),
          ),
          '#weight' => $tv['weight'],
          'feed' => array(
            '#markup' => $tv['title_val'],
          ),
          'data' => array(
            '#type' => 'textfield',
            '#title' => SafeMarkup::checkPlain('URL'),
            '#default_value' => (isset($tv['data'])) ? $tv['data'] : NULL,
          ),
          'icon' => array(
            '#type' => 'textfield',
            '#default_value' => $tv['icon'],
          ),
          'limit' => array(
            '#type' => 'select',
            '#title' => t('Limit'),
            '#options' => $limit,
            '#default_value' => 5,
          ),
          'active' => array(
            '#type' => 'checkbox',
            '#default_value' => $tv['active'],
          ),
          'delete' => array(
            '#type' => 'checkbox',
            '#default_value' => 0,
          ),
          'weight' => array(
            '#type' => 'weight',
            '#title' => t('Weight'),
            '#title_display' => 'invisible',
            '#default_value' => $tv['weight'],
            '#delta' => 10,
            '#attributes' => array('class' => array('social-timeline-table-order-weight')),
          ),
          'title_val' => array(
            '#type' => 'value',
            '#value' => $tv['title_val'],
          ),
        );
      }
    }

    // If a custom feed has been added.
    if (!is_null($form_state->get('custom'))) {
      $custom_fields = $form_state->get('custom_fields');
      foreach ($custom_fields as $custom_key => $custom_val) {
        $form['social_timeline_table'][$custom_key] = array(
          '#attributes' => array(
            'class' => array(
              'draggable',
            ),
          ),
          '#weight' => 0,
          'feed' => array(
            '#markup' => $custom_val['title'],
          ),
          'data' => array(
            '#type' => 'textfield',
            '#title' => SafeMarkup::checkPlain('URL'),
            '#default_value' => (isset($custom_val['data'])) ? $custom_val['data'] : NULL,
          ),
          'icon' => array(
            '#type' => 'textfield',
            '#default_value' => $custom_val['icon'],
          ),
          'limit' => array(
            '#type' => 'select',
            '#title' => t('Limit'),
            '#options' => $limit,
            '#default_value' => 5,
          ),
          'active' => array(
            '#type' => 'checkbox',
            '#default_value' => 1,
          ),
          'delete' => array(
            '#type' => 'checkbox',
            '#default_value' => 0,
          ),
          'weight' => array(
            '#type' => 'weight',
            '#title' => t('Weight'),
            '#title_display' => 'invisible',
            '#default_value' => 0,
            '#delta' => 10,
            '#attributes' => array('class' => array('social-timeline-table-order-weight')),
          ),
          'title_val' => array(
            '#type' => 'value',
            '#value' => $custom_val['title'],
          ),
        );
      }
    }

    uasort($form['social_timeline_table'], array($manager, 'sortFeeds'));
    return $form;
  }

  /**
   * AJAX submit handler for the social_timeline custom feed.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The form array.
   */
  public function ajaxSubmit($form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    $format_value = $form_state->getValues();
    $title = $format_value['settings']['add_feed']['title'];

    // Format the title to a machine name.
    $str = preg_replace('/[^a-z0-9]+/i', ' ', $title);
    $str = trim($str);
    $str = str_replace(" ", "_", $str);
    $str = strtolower($str);

    $form_state->set('custom', TRUE);
    $form_state->set(array('custom_fields', 'custom_' . $str), $format_value['settings']['add_feed']);
  }

  /**
   * AJAX submit callback for the social_timeline custom feed.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The form array.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $format_value = $form_state->getValues();

    // Check for deletions.
    foreach ($format_value['social_timeline_table'] as $feed_key => $feed_val) {
      if (isset($feed_val['delete'])) {
        if ($feed_val['delete']) {
          unset($format_value['social_timeline_table'][$feed_key]);
        }
      }
    }

    // Save the settings.
    $this->setConfigurationValue('social_timeline_global', $format_value['social_timeline_global']);
    $this->setConfigurationValue('social_timeline_table', $format_value['social_timeline_table']);
    $this->setConfigurationValue('instance_id', $format_value['instance_id']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $instance_id = $this->configuration['instance_id'];
    $manager = \Drupal::service('social_timeline.manager');
    $feeds = $this->configuration['social_timeline_table'];
    $global_settings = $this->configuration['social_timeline_global'];
    uasort($feeds, array($manager, 'sortFeeds'));

    $render_array = array();
    foreach ($global_settings as $gk => $gv) {
      $new_key = $manager::underscoreToCamelCase($gk);
      $render_array[$new_key] = $gv;
    }

    // Format the feeds array.
    $feed_settings = $manager::formatDefaultFeeds($feeds);
    $custom_feeds = $manager::formatCustomFeeds($feeds);

    // Format the object to send to javascript.
    $render_array['feeds'] = $feed_settings;
    $render_array['custom'] = $custom_feeds;

    // Return the HTML div that will contain the Social Timeline.
    return array(
      '#markup' => '<div id="' . $instance_id . '"> </div>',
      '#attached' => array(
        'drupalSettings' => array(
          'social_timeline' => array(
            'feeds' => $render_array,
            'instance_id' => $instance_id,
          ),
        ),
        'library' => array(
          'social_timeline/social_timeline'
        ),
      ),
    );
  }
}
