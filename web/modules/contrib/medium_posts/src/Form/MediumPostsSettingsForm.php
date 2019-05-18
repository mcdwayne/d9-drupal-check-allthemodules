<?php

namespace Drupal\medium_posts\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Configure Medium settings.
 */
class MediumPostsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'medium_posts_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'medium_posts.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('medium_posts.settings');

    $form['node_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Type'),
      '#options' => $this->getNodeTypeOptions(),
      '#default_value' => $config->get('node_type'),
      '#description' => $this->t('Select the content type used for medium publishing.<br>Medium publish uses <em>title</em>, <em>body</em> and <em>field_tags</em> three fields so please make sure the content type have those fields.'),
    ];

    $form['publish_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Publish status on Medium.com'),
      '#options' => [
        'public' => 'public',
        'draft' => 'draft',
        'unlisted' => 'unlisted',
      ],
      '#default_value' => $config->get('publish_status'),
      '#description' => $this->t('The status of the post when the post is created on Medium.com.'),
    ];

    $form['push_on_node_publish'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Push on content publish'),
      '#default_value' => $config->get('push_on_node_publish'),
      '#description' => $this->t("By default, post will be created on Medium.com on the event of your node content's publishing. But you can disable it and use 'medium_posts.manager' service in your code to push the post on any event."),
    ];

    // If workbench moderation module is installed, show some settings for it.
    if (\Drupal::moduleHandler()->moduleExists('workbench_moderation')) {
      $form['workbench'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Workbench'),
        '#description' => $this->t('Workbench settings. Only use this when you are using workbench moderation module.'),
      ];

      $form['workbench']['push_on_workbench_moderation_status'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Push on workbench moderation status'),
        '#default_value' => $config->get('push_on_workbench_moderation_status'),
        '#description' => $this->t("Instead of push on node publish, you may use Workbench Moderation to publish a node. Tick this if you are using Workbench Moderation workflow."),
      ];

      $form['workbench']['workbench_moderation_publish_status'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Publish status machine name'),
        '#default_value' => $config->get('workbench_moderation_publish_status'),
        '#description' => $this->t('The workbench moderation status used for publish. You can find them in <a href="/admin/structure/workbench-moderation/states" target="_blank">Moderation states</a> admin page.'),
        '#states' => [
          'visible' => [
            ':input[name="push_on_workbench_moderation_status"]' => ['checked' => TRUE],
          ],
          'required' => [
            ':input[name="push_on_workbench_moderation_status"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('medium_posts.settings');

    $config->set('node_type', $form_state->getValue('node_type'));
    $config->set('publish_status', $form_state->getValue('publish_status'));
    $config->set('push_on_node_publish', $form_state->getValue('push_on_node_publish'));

    if (\Drupal::moduleHandler()->moduleExists('workbench_moderation')) {
      $config->set('push_on_workbench_moderation_status', $form_state->getValue('push_on_workbench_moderation_status'));
      $config->set('workbench_moderation_publish_status', $form_state->getValue('workbench_moderation_publish_status'));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get all content types as a select options.
   *
   * @return array
   *   An array of options.
   */
  protected function getNodeTypeOptions() {
    $node_types = NodeType::loadMultiple();

    $options = [];
    foreach ($node_types as $node_type) {
      $options[$node_type->id()] = $node_type->label();
    }

    return $options;
  }

}
