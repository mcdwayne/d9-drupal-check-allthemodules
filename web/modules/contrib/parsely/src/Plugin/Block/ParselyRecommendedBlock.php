<?php

namespace Drupal\parsely\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides content recommendations based on Parsely's API
 *
 * @Block(
 *   id = "parsely_recommended_widget",
 *   admin_label = @Translation("Parsely Recommended Widget"),
 *   category = @Translation("Content Blocks"),
 * )
 */
class ParselyRecommendedBlock extends BlockBase implements BlockPluginInterface {

	/**
	 * {@inheritdoc}
	 */
	public function build() {
		return array(
			'#markup' => $this->t('Hello, World!'),
		);
	}

	public function blockForm($form, FormStateInterface $form_state) {
		$form = parent::blockForm($form, $form_state);

		$config = $this->getConfiguration();

		$form['parsely_recommended_block_title'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Block Title'),
			'#description' => $this->t('What is the block called?'),
			'#default_value' => isset($config['parsely_recommended_block_title']) ? $config['parsely_recommended_block_title'] : 'Replace Me!'
		);

		$form['parsely_recommended_block_published_within'] = array(
			'#type' => 'number',
			'#title' => $this->t('Published Within'),
			'#description' => $this->t('Only return articles published within the last X days (leave blank for no limit)'),
			'#default_value' => isset($config['parsely_recommended_block_published_within']) ? $config['parsely_recommended_block_published_within'] : 10
		);

		$form['parsely_recommended_block_number_of_entries'] = array(
			'#type' => 'number',
			'#title' => $this->t('Number of entries to return'),
			'#description' => $this->t('Number of entries to return from the API call (max 20)'),
			'#default_value' => isset($config['parsely_recommended_block_number_of_entries']) ? $config['parsely_recommended_block_number_of_entries'] : 10
		);

		$form['parsely_recommended_block_sort_by'] = array(
			'#type' => 'select',
			'#title' => $this->t('Sort By'),
			'#description' => 'Sort by score (relevance) or recency (when it was published)',
			'#options' => [
				'score' => $this->t('score'),
				'recency' => $this->t('recency'),
			],
			'#multiple' => false,
			'#default_value' => 'score'
		);

		$form['parsely_recommended_block_boost_by'] = array(
			'#type' => 'select',
			'#title' => $this->t('Boost By'),
			'#description' => $this->t('Give a higher weight to a certain metric (for example, if you want views, engaged minutes, etc. to make a page rank higher in score)'),
			'#options' => [
			'views' => $this->t('Views'),
			'mobile_views' => $this->t('Mobile Views'),
			'tablet_views' => $this->t('Tablet Views'),
			'desktop_views' => $this->t('Desktop Views'),
			'visitors' => $this->t('Visitors'),
			'visitors_new' => $this->t('New Visitors'),
			'visitors_returning' => $this->t('Returning Visitors'),
			'engaged_minutes' => $this->t('Engaged Minutes'),
			'avg_engaged' => $this->t('Average Engaged Minutes'),
			'avg_engaged_new' => $this->t('Average Engaged Minutes for New Visitors'),
			'avg_engaged_returning' => $this->t('Average Engaged Minutes for Returning Visitors'),
			'social_interactions' => $this->t('Social Interactions'),
			'fb_interactions' => $this->t('Facebook Interactions'),
			'tw_interactions' => $this->t('Twitter Interactions'),
			'li_interactions' => $this->t('LinkedIn Interactions'),
			'pi_interactions' => $this->t('Pinterest Interactions'),
			'social_referrals' => $this->t('Social Referrals'),
			'fb_referrals' => $this->t('Facebook Referrals'),
			'tw_referrals' => $this->t('Twitter Referrals'),
			'li_referrals' => $this->t('LinkedIn Referrals'),
			'pi_referrals' => $this->t('Pinterest Referrals')
			],
			'#multiple' => false,
			'#default_value' => 'views'
		);



		return $form;
	}

}