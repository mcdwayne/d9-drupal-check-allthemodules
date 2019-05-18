<?php

namespace Drupal\scrpr_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Exception;
use Goutte\Client;
use GuzzleHttp\Client as HttpClient;

/**
 * Defines a scrpr block block type.
 *
 * @Block(
 *   id = "scrpr_block",
 *   admin_label = @Translation("scrpr Block"),
 *   category = @Translation("Content"),
 * )
 */
class ScrprBlock extends BlockBase implements BlockPluginInterface {

  /**
   * @var array
   */
  private $config;

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['scrpr_block_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('URL to scrape'),
      '#description' => $this->t('The URL you want to scrape for content'),
      '#default_value' => isset($config['scrpr_block_url']) ? $config['scrpr_block_url'] : 'http://example.com',
    );

    $form['scrpr_block_css_selector'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('CSS Selector of the content to scrape'),
      '#description' => $this->t('Enter a CSS selector, by default the title element of the resource is used'),
      '#default_value' => isset($config['scrpr_block_css_selector']) ? $config['scrpr_block_css_selector'] : 'title',
    );

    $form['scrpr_block_maxcachelifetime'] = array(
      '#type' => 'number',
      '#title' => $this->t('Maximum Cache Lifetime'),
      '#description' => $this->t('Maximum number of seconds that this block may be cached. Defaults to 1 day (86400 seconds).'),
      '#default_value' => isset($config['scrpr_block_maxcachelifetime']) ? $config['scrpr_block_maxcachelifetime'] : '',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['scrpr_block_url'] = $values['scrpr_block_url'];
    $this->configuration['scrpr_block_css_selector'] = $values['scrpr_block_css_selector'];
    $this->configuration['scrpr_block_maxcachelifetime'] = $values['scrpr_block_maxcachelifetime'];
  }

  /**
   * @inheritdoc
   */
  public function build() {

    try {
        $client = new Client();
        $client->setClient(new HttpClient(['timeout' => 60]));

        $url = $this->getConfigValue('scrpr_block_url', 'http://example.com');
        $cssSelector = $this->getConfigValue('scrpr_block_css_selector', 'title');

        $crawler = $client->request('GET', $url);

        $text = $crawler->filter($cssSelector)->text();
    } catch (Exception $exception) {
        $url = 'https://www.drupal.org/project/scrpr_block';
        $text = $this->handleException($exception);
    }

    return [
      '#markup' => sprintf('<a href="%1$s">%2$s</a>', $url, $text),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->getConfigValue('scrpr_block_maxcachelifetime', 86400);
  }

  /**
   * Get a value from the configuration.
   *
   * @param string $key
   * @param mixed $default
   *
   * @return mixed
   */
  private function getConfigValue($key, $default)
  {
    if (is_null($this->config)) {
      $this->config = $this->getConfiguration();
    }

    if (!empty($this->config[$key])) {
      return $this->config[$key];
    }

    return $default;
  }

  private function handleException(Exception $exception)
  {
      $msg = $exception->getMessage();

      switch ($msg) {
          case 'The current node list is empty.':
              $displayText = 'The CSS selector yields an empty node list.';
              break;
          default:
              $displayText = $msg;
              break;
      }

      return $displayText;
  }

}
