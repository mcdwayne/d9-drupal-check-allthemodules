<?php
/**
 * @file
 * Contains \Drupal\govdelivery_signup\Plugin\Block\GovDeliverySignupBlock.
 */
namespace Drupal\govdelivery_taxonomy\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'GovDelivery Taxonomy Signup' block.
 *
 * @Block(
 *   id = "govdelivery_taxonomy_signup_block",
 *   admin_label = @Translation("GovDelivery Taxonomy Signup"),
 *   category = @Translation("Services"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class GovDeliveryTaxonomySignupBlock extends BlockBase {
  // Override BlockPluginInterface methods here.

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $gdt_config = \Drupal::config('govdelivery_taxonomy.settings');

    $node = \Drupal::routeMatch()->getParameter('node');

    $links = [];
    foreach ($node->getFieldDefinitions() AS $key => $definition) {
      if (strpos($definition->getSetting('handler'), 'taxonomy') !== FALSE) {
        // we found a field definition that is taxonomy
        $terms = $node->$key->referencedEntities();
        if (!empty($terms)) {
          foreach ($terms as $term) {
            if (isset($term->govDeliveryTopicCode) && !empty($term->govDeliveryTopicCode)) {
              // this is associated with a GovDelivery Topic, add it to the list
              $uri = 'http://' . $gdt_config->get('public_server') . '/accounts/' . $gdt_config->get('clientcode') . '/subscriber/new';
              $url = Url::fromUri($uri,
                [
                  'query' => [
                    'topic_id' => $term->govDeliveryTopicCode,
                  ],
                ]
              );
              $links[] = [
                '#type' => 'link',
                '#url' => $url,
                '#title' => t('Signup for %topic', array('%topic' => $term->getName())),
                '#attributes' => [
                  'target' => '_blank',
                ],
              ];
            }
          }
        }
      }
    }

    return [
      '#subject' => $this->t('Get Notified on updates.'),
      'fieldset' => [
        '#type' => 'fieldset',
        '#title' => $this->t($config['fieldset_title']),
        '#description' => check_markup($this->t($config['description']['value']), $config['description']['format']),
        'links_list' => [
          '#theme' => 'item_list',
          '#list_type' => 'ul',
          '#items' => $links,
          '#attributes' => [
            'class' => 'signup-links-list',
          ],
        ],
      ],
    ];
  }

  public function getCacheMaxAge() {
    return 1;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'fieldset_title' => 'Stay informed',
      'button_label' =>  'Sign me up for %topic',
      'description' => 'Sign up to receive notifications when items with the listed topics are updated.',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Retrieve existing configuration for this block.
    $config = $this->getConfiguration();

    $form['govdelivery_taxonomy'] = array(
      '#tree' => TRUE,
    );
    $form['govdelivery_taxonomy']['fieldset_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Signup Box Label'),
      '#default_value' => $config['fieldset_title'],
      '#maxlength' => 25,
      '#required' => FALSE,
    );
    $form['govdelivery_taxonomy']['button_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Button Label'),
      '#description' => $this->t('Tokens %topic and @topic are available to use for the term name(s).'),
      '#default_value' => $config['button_label'],
      '#maxlength' => 25,
      '#required' => TRUE,
    );
    $form['govdelivery_taxonomy']['description'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Enter a Description'),
      '#description' => $this->t('Text that will appear in the block with the list of topics to subscribe to.'),
      '#default_value' => $config['description']['value'],
      '#format' => $config['description']['format'],
      '#maxlength' => 100,
      '#required' => FALSE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Save our custom settings when the form is submitted.
    $values = $form_state->getValues()['govdelivery_taxonomy'];

    foreach ($values AS $key => $value) {
      $this->setConfigurationValue($key, $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
  /*
    $fax_number = $form_state->getValue('fax_number');

    if (!is_numeric($fax_number)) {
      $form_state->setErrorByName('fax_block_settings', t('Needs to be an integer'));
    }
  /**/
  }
//
//  /**
//   * {@inheritdoc}
//   *//*
//  public function getCacheTags() {
//    //With this when your node change your block will rebuild
//    if ($node = \Drupal::routeMatch()->getParameter('node')) {
//      //if there is node add its cachetag
//      return Cache::mergeTags(parent::getCacheTags(), array('node:' . $node->id()));
//    } else {
//      //Return default tags instead.
//      return parent::getCacheTags();
//    }
//  }
//
//  /**
//   * {@inheritdoc}
//   *//*
//  public function getCacheContexts() {
//    //if you depends on \Drupal::routeMatch()
//    //you must set context of this block with 'route' context tag.
//    //Every new route this block will rebuild
//    return Cache::mergeContexts(parent::getCacheContexts(), array('route'));
//  }
//}

}
