<?php

namespace Drupal\moon_phases\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\moon_phases\MoonCalc;
use DateTime;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Moon Phase block.
 *
 * @Block(
 *  id = "moon_phase_block",
 *  admin_label = @Translation("Moon Phase"),
 *  module = "moon_phases"
 * )
 */
class MoonPhaseBlock extends BlockBase {

  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   *
   * {@inheritdoc}
   */
  public function build() {
    $date = new DateTime();
    $moon = new MoonCalc($date);

    $config = $this->getConfiguration();
    $items = [];
    if (array_key_exists('moon_block_items', $config)) {
      foreach ($config['moon_block_items'] as $key => $show) {
        if ($show) {
          switch ($key) {
            case 'getDaysUntilNextFullMoon':
              $items[] = $this->t('%days days until the next full moon',
                  ['%days' => floor($moon->getDaysUntilNextFullMoon())]);
              break;

            case 'getDaysUntilNextNewMoon':
              $items[] = $this->t('%days days until the next new moon',
                  ['%days' => floor($moon->getDaysUntilNextNewMoon())]);
              break;

            case 'getPercentOfIllumination':
              $items[] = $this->t('%illum% illuminated',
                  ['%illum' => round($moon->getPercentOfIllumination())]);
              break;
          }
        }
      }
    }

    $moreInfoUrl = Url::fromRoute('moon_phases.content', [], [
      'attributes' => [
        'class' => [
          'moon-more-link',
        ],
      ],
    ]);
    $items[] = Link::fromTextAndUrl($this->t('See more'), $moreInfoUrl);

    // Get an unorderd list.
    $item_list = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => $items,
      '#title' => '',
      '#attributes' => [
        'class' => ['moon-phase-summary'],
      ],
    ];
    $summary = render($item_list);

    $phase = $moon->getMoonPhaseName();
    $build = [
      '#type' => 'moon',
      '#theme' => 'moon_block',
      '#phase_name' => $phase,
      '#image' => [
        '#theme' => 'image',
        '#uri' => $moon->getImageUri(),
        '#alt' => $phase,
        '#title' => $phase,
      ],
      '#summary' => $summary,
    ];

    $output = render($build);

    return [
      '#markup' => $output,
    ];
  }

  /**
   * Implements \Drupal\block\BlockBase:blockForm().
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['moon_block_items'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Block elements'),
      '#default_value' => (isset($config['moon_block_items'])) ? $config['moon_block_items'] : '',
      '#options' => [
        'getDaysUntilNextFullMoon' => $this->t('Days until next Full Moon'),
        'getDaysUntilNextNewMoon' => $this->t('Days until next New Moon'),
        'getPercentOfIllumination' => $this->t('Percentage of illumination'),
      ],
    ];

    return $form;
  }

  /**
   * Implements \Drupal\block\BlockBase:blockSubmit().
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('moon_block_items', $form_state->getValue('moon_block_items'));
  }

}
