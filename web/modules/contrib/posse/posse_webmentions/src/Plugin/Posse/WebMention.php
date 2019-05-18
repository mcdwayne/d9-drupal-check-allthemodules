<?php

namespace Drupal\posse_webmentions\Plugin\Posse;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\posse\PosseInterface;
use Drupal\posse\Plugin\Posse\PosseBase;

/**
* Base class for the Posse plugin functionality.
* @Posse(
*   id = "posse_webmentions",
*   label = "Web Mention Settings",
*   display_label = "Web Mentions",
* )
*
*/
class WebMention extends PosseBase implements PosseInterface {

  /**
  * {@inheritdoc}
  */
  public function configurationForm(&$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->get('posse.webmention');
    $form['webmention'] = [
      '#type' => 'details',
      '#tree' => TRUE,
      '#title' => t('Webmentions Configuration'),
      '#description' => t('Web Mention allows your site to notify other sites of when you reference an article and also allows your site to track when other sites reference your articles.'),
    ];

    $form['webmention']['enable'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable Web Mention'),
      '#default_value' => $config->get('status')
    ];

    $form['webmention']['networks'] = [
      '#type' => 'fieldset',
      '#title' => t('IndieAuth Networks'),
      '#description' => t('See %link for details, needed for setting up your Webmention.io site.', [
        '%link' => 'https://indieauth.com/setup'
      ]),
    ];

    $form['webmention']['networks']['accounts'] = [
      '#type' => 'textfield',
      '#title' => t('Account Url'),
      '#description' => t('Enter account urls seperated by commas.'),
      '#default_value' => $config->get('accounts')
    ];

    $form['webmention']['account'] = [
      '#type' => 'textfield',
      '#title' => t('Webmention.io Account'),
      '#description' => t('Enter your Webmention.io user account name here.'),
      '#default_value' => $config->get('webmention_user')
    ];
  }

  public function addNetwork($form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    return $form['webmention']['networks']['accounts'];
  }

  /**
  * {@inheritdoc}
  */
  public function validateConfiguration($form, FormStateInterface &$form_state) {}

  /**
  * {@inheritdoc}
  */
  public function submitConfiguration($form, FormStateInterface &$form_state) {
    $config = \Drupal::service('config.factory')->getEditable('posse.webmention');
    $config->set('status', $form_state->getValues()['webmention']['enable']);
    $config->set('accounts', $form_state->getValues()['webmention']['networks']['accounts']);
    $config->set('webmention_user', $form_state->getValues()['webmention']['account']);
    $config->save();
  }

  /**
  * {@inheritdoc}
  */
  public function syndicate(ContentEntityBase $entity, $insert = TRUE) {
    $url = $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId());

    $build = $view_builder->view($entity, 'default');
    $content = render($build);
    if ($url) {
      $client = new \IndieWeb\MentionClient();
      $client->enableDebug();
      $sent = $client->sendMentions($url, $content);
    }
  }

}
