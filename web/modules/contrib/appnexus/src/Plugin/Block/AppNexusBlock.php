<?php

namespace Drupal\appnexus\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\appnexus\Tag;

/**
 * AppNexus Advertisement block
 *
 * @Block(
 *  id = "appnexus",
 *  admin_label = @Translation("AppNexus Block"),
 * )
 */
class AppNexusBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form = parent::blockForm($form, $form_state);

    $form['tagId'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Tag ID'),
      '#description'   => $this->t('Please enter Placement ID/Tag ID value.'),
      '#default_value' => !empty($config['tagId']) ? $config['tagId'] : '',
      '#required'      => TRUE,
    ];

    $form['position'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Position Name'),
      '#description'   => $this->t('Use this field to distinguish ad placements in AppNexus.'),
      '#required'      => TRUE,
      '#maxlength'     => 64,
      '#default_value' => !empty($config['position']) ? $config['position'] : '',
    ];

    $form['sizes'] = [
      '#type'   => 'table',
      '#header' => [
        $this->t('Min Width'),
        $this->t('Size(s)'),
        '',
      ],
      '#tableselect' => FALSE,
      '#tabledrag'   => FALSE,
    ];

    for ($i = 0; $i < 5; $i++) {
      $form['sizes'][$i]['minWidth'] = [
        '#type'          => 'number',
        '#title'         => $this->t('Min Width'),
        '#description'   => $this->t('Specifies the minimum width of the viewport. Example: minWidth: 1024.'),
        '#default_value' => !empty($config['sizes'][$i]['minWidth']) ? $config['sizes'][$i]['minWidth'] : '',
        '#size'          => 10,
      ];
      $form['sizes'][$i]['sizes'] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('Size(s)'),
        '#description'   => $this->t('Specifies the size(s) of the ad. Example: 300x600,300x250.'),
        '#default_value' => !empty($config['sizes'][$i]['sizes']) ? $config['sizes'][$i]['sizes'] : '',
        '#size'          => 45,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('tagId', $form_state->getValue('tagId'));
    $this->setConfigurationValue('sizes', $form_state->getValue('sizes'));
    $this->setConfigurationValue('targetId', uniqid('adblock-'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $tag = (new Tag())
      ->setTagId($config['tagId'])
      ->setSizes($config['sizes'])
      ->setPosition($config['position'])
      ->setTargetId($config['targetId']);
    $div_id = $tag->getTargetId() . '-' . Html::cleanCssIdentifier($tag->getPosition());
    $build = [
      '#type'   => 'markup',
      '#markup' => '<div id="' . $div_id . '"></div>',
    ];
    if ($opts = $tag->build()) {
      $build['#attached']['drupalSettings']['appnexus']['tags'][$div_id] = $opts;
    }
    return $build;
  }

}
