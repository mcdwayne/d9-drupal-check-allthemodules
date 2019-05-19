<?php

namespace Drupal\simpleads\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\simpleads\Groups;

/**
 * SimpleAds Advertisement block
 *
 * @Block(
 *  id = "simpleads",
 *  admin_label = @Translation("SimpleAds Block"),
 * )
 */
class SimpleAds extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form = parent::blockForm($form, $form_state);

    $groups = new Groups();
    $items = [];
    $items[''] = $this->t('- none -');
    foreach ($groups->loadAll() as $item) {
      $items[$item->getId()] = $item->getGroupName();
    }

    $form['sizes'] = [
      '#type'   => 'table',
      '#header' => [
        $this->t('Min Width'),
        $this->t('Group'),
        '',
      ],
      '#tableselect' => FALSE,
      '#tabledrag'   => FALSE,
    ];

    for ($i = 0; $i < 4; $i++) {
      $form['sizes'][$i]['minWidth'] = [
        '#type'          => 'number',
        '#title'         => $this->t('Min Width'),
        '#description'   => $this->t('Specifies the minimum width of the viewport. Example: minWidth: 1024.'),
        '#default_value' => !empty($config['sizes'][$i]['minWidth']) ? $config['sizes'][$i]['minWidth'] : '',
        '#size'          => 10,
      ];
      $form['sizes'][$i]['group_id'] = [
        '#type'          => 'select',
        '#title'         => $this->t('Group'),
        '#description'   => $this->t('Select a group of ads to display in speficied view breakpoint (responsive).'),
        '#options'       => $items,
        '#default_value' => !empty($config['sizes'][$i]['group_id']) ? $config['sizes'][$i]['group_id'] : '',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('sizes', $form_state->getValue('sizes'));
    $this->setConfigurationValue('targetId', uniqid('simpleadblock-'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $div_id = Html::cleanCssIdentifier('test');
    $build = [
      '#type'   => 'markup',
      '#markup' => '<div id="' . $div_id . '"></div>',
    ];
    //if ($opts = $tag->build()) {
    //  $build['#attached']['drupalSettings']['appnexus']['tags'][$div_id] = $opts;
    //}
    return $build;
  }

}
