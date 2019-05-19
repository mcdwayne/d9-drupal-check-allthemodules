<?php

namespace Drupal\skimlinks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a configurable block which displays a disclosure badge.
 *
 * @Block(
 *   id = "disclosurebadge",
 *   admin_label = @Translation("Skimlinks Disclosure Badge"),
 *   category = @Translation("Skimlinks"),
 * )
 */
class DisclosureBadgeBlock extends BlockBase {

	/**
	 * @return array 	Numerically keyed array of colour options.
	 */
	private function badgeColours() {
    return [
    	$this->t('blue'),
	    $this->t('cyan'),
	    $this->t('grey'),
	    $this->t('white'),
	  ];
  }

  public function blockAccess(AccountInterface $account) {
  	return AccessResult::allowedIf(!empty($this->configuration['skimlinks_enable_badge']));
  }

	/**
	 * {@inheritdoc}
	 */
	public function blockForm($form, FormStateInterface $form_state) {
		$form['badge'] = [
      '#type' => 'fieldgroup',
      '#title' => t('Badge')
		];

		// Enable the badge
		$form['badge']['skimlinks_enable_badge'] = [
			'#type' => 'checkbox',
			'#title' => $this->t('Enable the Skimlinks Disclosure badge'),
			'#description' => $this->t('A Disclosure Disclosure/Referral Badge will appear in Appearance > Widgets to place wherever you want.<br>
Note: please make sure you have accepted T&Cs first to implement... <a href=":link" target="_blank">click here</a>.', [':link' => 'https://hub.skimlinks.com/toolbox/referral']),
      '#default_value' => isset($this->configuration['skimlinks_enable_badge']) ? $this->configuration['skimlinks_enable_badge'] : 0,
		];

		// The badge settings
    $options = [];
    foreach ($this->badgeColours() as $key => $colour) {
    	$image = [
	    	'#theme' => 'image',
	    	'#uri' => drupal_get_path('module', 'skimlinks') . '/assets/Disclosure_' . $colour . '.png',
	    	'#width' => 90,
	    	'#height' => 60,
	    	'#attributes' => ['style' => 'vertical-align: middle'],
	    ];
      $options[$key + 1] = \Drupal::service('renderer')->renderRoot($image);
    }
    $form['badge']['skimlinks_disclosurebadge'] = [
      '#type' => 'radios',
      '#title' => t('Style'),
      '#default_value' => isset($this->configuration['skimlinks_disclosurebadge']) ? $this->configuration['skimlinks_disclosurebadge'] : 1,
      '#options' => $options,
      '#description' => t("Select a style of badge that best fits with your site design colours."),
    ];

	  return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configuration['skimlinks_enable_badge'] = $values['badge']['skimlinks_enable_badge'];
	  $this->configuration['skimlinks_disclosurebadge'] = $values['badge']['skimlinks_disclosurebadge'];
	}

  /**
   * {@inheritdoc}
   */
  public function build() {
  	$colour = $this->badgeColours()[$this->configuration['skimlinks_disclosurebadge'] - 1];
  	$image_path = drupal_get_path('module', 'skimlinks') . '/assets/Disclosure_' . $colour;
    $image = [
    	'#theme' => 'image',
    	'#uri' => $image_path . '.png',
    	'#alt' => t('Content monetized by Skimlinks - click to find out more.'),
    	'#width' => 120,
    	'#height' => 90,
    	'#attributes' => [
    		'srcset' => base_path() . $image_path . '.png 1x, ' . base_path() . $image_path . '-2x.png 2x',
    	],
    ];
    $url = Url::fromUri('https://skimlinks.com/');
    $url->setOptions([
    	'attributes' => ['target' => '_blank'],
    ]);
    $badge = [
      [
      	'#type' => 'link',
  		  '#title' => $image,
  		  '#url' => $url,
  		  '#html' => TRUE,
      ]
    ];
    return $badge;
  }
}
