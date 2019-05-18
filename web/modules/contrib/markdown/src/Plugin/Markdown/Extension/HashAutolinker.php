<?php

namespace Drupal\markdown\Plugin\Markdown\Extension;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\markdown\Plugin\Filter\MarkdownFilterInterface;
use Drupal\markdown\Plugin\Markdown\MarkdownGuidelinesAlterInterface;
use Drupal\markdown\Plugin\Markdown\MarkdownGuidelinesInterface;
use League\CommonMark\Inline\Element\Link;
use League\CommonMark\Inline\Parser\InlineParserInterface;
use League\CommonMark\InlineParserContext;

/**
 * Class HashAutolinker.
 *
 * @MarkdownExtension(
 *   id = "hash_autolinker",
 *   parser = "thephpleague/commonmark",
 *   label = @Translation("# Autolinker"),
 *   description = @Translation("Automatically link commonly used references that come after a hash character (#) without having to use the link syntax."),
 * )
 */
class HashAutolinker extends CommonMarkExtension implements InlineParserInterface, MarkdownGuidelinesAlterInterface {

  /**
   * {@inheritdoc}
   */
  public function alterGuidelines(array &$guides = []) {
    if ($this->getSetting('type') === 'node') {
      $description = [t('Text that starts with hash symbol (#) followed by numbers will be automatically be linked to a node on this site.')];
      if ($this->getSetting('node_title')) {
        $description[] = t('The node title will be used in place the text.');
      }
      $description[] = t('If the node does not exist, it will not automatically link.');
      $guides['links']['items'][] = [
        'title' => t('# Autolinker'),
        'description' => $description,
      ];
    }
    elseif ($this->getSetting('type') === 'url') {
      $description = [
        t('Text that starts with a hash symbol (#) followed by any character other than a space will automatically be linked to the following URL: <code>@url</code>', [
          '@url' => $this->getSetting('url'),
        ]),
      ];
      if ($this->getSetting('url_title')) {
        $description[] = t('The URL title will be used in place of the original text.');
      }
      $guides['links']['items'][] = [
        'title' => t('@ Autolinker'),
        'description' => $description,
        'tags' => [
          'a' => [
            '#3060',
            '#2562913',
            '#259843',
          ],
        ],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultSettings() {
    return [
      'type' => 'node',
      'node_title' => TRUE,
      'url' => 'https://www.drupal.org/node/[text]',
      'url_title' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCharacters() {
    return ['#'];
  }

  /**
   * {@inheritdoc}
   */
  public function parse(InlineParserContext $inline_context) {
    $cursor = $inline_context->getCursor();

    // The # symbol must not have any other characters immediately prior.
    $previous_char = $cursor->peek(-1);
    if ($previous_char !== NULL && $previous_char !== ' ' && $previous_char !== '[') {
      // peek() doesn't modify the cursor, so no need to restore state first.
      return FALSE;
    }

    // Save the cursor state in case we need to rewind and bail.
    $previous_state = $cursor->saveState();

    // Advance past the # symbol to keep parsing simpler.
    $cursor->advance();

    // Parse the handle.
    $text = $cursor->match('/^[^\s\]]+/');
    $url = FALSE;
    $title = FALSE;
    $type = $this->getSetting('type');

    // @todo Make entity type abstract and comment aware.
    if ($type === 'node' && is_numeric($text) && ($node = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($text))) {
      $url = $node->toUrl('canonical', ['absolute' => TRUE])->toString();
      if ($this->getSetting('node_title') && ($title = $node->label())) {
        $text = $title;
      }
      else {
        $text = "#$text";
      }
    }
    elseif ($type === 'url' && ($url = $this->getSetting('url')) && strpos($url, '[text]') !== FALSE) {
      $url = str_replace('[text]', $text, $url);
      if ($this->getSetting('url_title') && ($title = $this->getUrlTitle($url))) {
        $text = $title;
        $title = FALSE;
      }
    }
    else {
      $text = FALSE;
    }

    // Regex failed to match; this isn't a valid @ handle.
    if (empty($text) || empty($url)) {
      $cursor->restoreState($previous_state);
      return FALSE;
    }

    $inline_context->getContainer()->appendChild(new Link($url, $text, $title));

    return TRUE;
  }

  /**
   * Retrieves a URL page title.
   *
   * @param string $url
   *   The URL to retrieve the title from.
   *
   * @return string|false
   *   The URL title or FALSE if it could not be retrieved.
   */
  protected function getUrlTitle($url) {
    $response = \Drupal::httpClient()->get($url);
    if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 400) {
      $dom = new \DOMDocument();
      @$dom->loadHTML($response->getBody()->getContents());
      if (($title = $dom->getElementsByTagName('title')) && $title->length) {
        return Html::escape(trim(preg_replace('/\s+/', ' ', $title->item(0)->textContent)));
      }
    }
    return FALSE;
  }

  /**
   * Retrieves an Entity object for the current route.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   An Entity object or NULL if none could be found.
   */
  protected function currentRouteEntity() {
    $route_match = \Drupal::routeMatch();
    foreach ($route_match->getParameters()->all() as $item) {
      if ($item instanceof EntityInterface) {
        return $item;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, MarkdownFilterInterface $filter) {
    $form = parent::settingsForm($form, $form_state, $filter);

    $selector = '';//_commonmark_get_states_selector($filter, $this, 'type');

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Map text to'),
      '#default_value' => $this->getSetting('type'),
      '#options' => [
        'node' => $this->t('Node'),
        'url' => $this->t('URL'),
      ],
    ];

    $form['node_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Replace text with title of node'),
      '#description' => $this->t('If enabled, it will replace the matched text with the title of the node.'),
      '#default_value' => $this->getSetting('node_title'),
      '#states' => [
        'visible' => [
          $selector => ['value' => 'node'],
        ],
      ],
    ];

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#description' => $this->t('A URL to format text with. Use the token "[text]" where it is needed. If you need to include the #, use the URL encoded equivalent: <code>%23</code>. Example: <code>https://twitter.com/search?q=%23[text]</code>.'),
      '#default_value' => $this->getSetting('url'),
      '#states' => [
        'visible' => [
          $selector => ['value' => 'url'],
        ],
      ],
    ];

    $form['url_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Replace text with title of URL'),
      '#description' => $this->t('If enabled, it will replace the matched text with the title of the URL.'),
      '#default_value' => $this->getSetting('url_title'),
      '#states' => [
        'visible' => [
          $selector => ['value' => 'url'],
        ],
      ],
    ];
    return $form;
  }

}
