<?php

namespace Drupal\weibo_widget\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class WeiboWidgetConfigForm.
 *
 * @package Drupal\weibo_widget\Form
 */
class WeiboWidgetConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'weibo_widget.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'weibo_widget_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('weibo_widget.settings');

    $form['weibo_widget_appkey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('AppKey'),
      '#description' => $this->t('Required to use the Like button. Needs a Weibo Page.'),
      '#maxlength' => 50,
      '#size' => 50,
      '#default_value' => $config->get('weibo_widget_appkey'),
    ];

    $form['weibo_widget_uid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User UID'),
      '#description' => $this->t('Required to use the Follow button.'),
      '#maxlength' => 50,
      '#size' => 50,
      '#default_value' => $config->get('weibo_widget_uid'),
    ];

    // Help text.
    $url_param = [
      'absolute' => TRUE,
      'attributes' => [
        'target' => '_blank',
      ],
    ];

    $url = Url::fromUri('http://open.weibo.com/widgets?cat=wb', $url_param);
    $link = Link::fromTextAndUrl('open.weibo.com/widgets', $url);
    $link_renderable = $link->toRenderable();

    $form['info'] = [
      '#markup' => $this->t('You can get this information accessing logged in Weibo Widget page: %link.', ['%link' => render($link_renderable)]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('weibo_widget.settings')
      ->set('weibo_widget_appkey', $form_state->getValue('weibo_widget_appkey'))
      ->set('weibo_widget_uid', $form_state->getValue('weibo_widget_uid'))
      ->save();
  }

}
