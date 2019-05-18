<?php
/**
 * @file
 * Contains \Drupal\periscope_on_air_button\Plugin\Block\PeriscopeOnAirBlock.
 */
namespace Drupal\periscope_on_air_button\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Periscope On Air' block.
 *
 * @Block(
 *   id = "periscope_onair_block",
 *   admin_label = @Translation("Periscope On Air block"),
 *   category = @Translation("Periscope status button")
 * )
 */
class PeriscopeOnAirBlock extends BlockBase implements BlockPluginInterface {
  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['periscope_onair_periscopeid'] = array(
      '#type' => 'textfield',
      '#title' => t('Broadcaster username'),
      '#default_value' => isset($config['periscopeid']) ? $config['periscopeid'] : '',
    );

    $form['periscope_onair_button_size'] = array(
      '#type' => 'select',
      '#title' => t('Button Size'),
     '#options' => array(
       'small' => t('Small'),
       'large' => t('Large')),
      '#default_value' => isset($config['button_size']) ? $config['button_size'] : '',
    );

    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['periscopeid'] = $values['periscope_onair_periscopeid'];
    $this->configuration['button_size'] = $values['periscope_onair_button_size'];
  }

  public function build() {
    $config = $this->getConfiguration();
    if (!empty($config['periscopeid'])) {
      $periscopeid = $config['periscopeid'];
    } else {
      $name = '';
    }
    if (!empty($config['button_size'])) {
      $button_size = $config['button_size'];
    } else {
      $name = 'small';
    }

    return [
      'inside' => [
        '#type' => 'html_tag',
        '#tag' => 'script',
        '#value' => 'window.twttr=function(t,e,r){var n,i=t.getElementsByTagName(e)[0],w=window.twttr||{};return t.getElementById(r)?w:(n=t.createElement(e),n.id=r,n.src="https://platform.twitter.com/widgets.js",i.parentNode.insertBefore(n,i),w._e=[],w.ready=function(t){w._e.push(t)},w)}(document,"script","twitter-wjs")',
      ],
      'link'=> [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#value' => $periscopeid,
        '#attributes' => [
          'href' => ['https://www.periscope.tv/' . $periscopeid],
          'class' => ['periscope-on-air'],
          'data-size' => [$button_size]
        ]
      ]
    ];
  }
}
