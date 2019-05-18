<?php

namespace Drupal\markdown\Plugin\Markdown\Extension;

use Drupal\Core\Form\FormStateInterface;
use Drupal\markdown\Plugin\Filter\MarkdownFilterInterface;
use Drupal\markdown\Plugin\Markdown\MarkdownGuidelinesAlterInterface;
use Drupal\user\Entity\User;
use League\CommonMark\Inline\Element\Link;
use League\CommonMark\Inline\Parser\InlineParserInterface;
use League\CommonMark\InlineParserContext;

/**
 * Class AtAutolinker.
 *
 * @MarkdownExtension(
 *   id = "at_autolinker",
 *   parser = "thephpleague/commonmark",
 *   label = @Translation("@ Autolinker"),
 *   description = @Translation("Automatically link commonly used references that come after an at character (@) without having to use the link syntax."),
 * )
 */
class AtAutolinker extends CommonMarkExtension implements InlineParserInterface, MarkdownGuidelinesAlterInterface {

  /**
   * {@inheritdoc}
   */
  public function alterGuidelines(array &$guides = []) {
    $user = \Drupal::currentUser();
    if ($user->isAnonymous()) {
      $user = User::load(1);
    }

    if ($this->getSetting('type') === 'user') {
      $description = [$this->t('Text that starts with an at symbol (@) followed by any character other than a space will be automatically linked to users on this site.')];
      if ($this->getSetting('format_username')) {
        $description[] = $this->t('The formatted user name will be used in place of the text.');
      }
      $description[] = $this->t('If the user does not exist, it will not automatically link.');
      $guides['links']['items'][] = [
        'title' => $this->t('@ Autolinker'),
        'description' => $description,
        'tags' => [
          'a' => '@' . $user->getAccountName(),
        ],
      ];
    }
    elseif ($this->getSetting('type') === 'url') {
      $guides['links']['items'][] = [
        'title' => $this->t('@ Autolinker'),
        'description' => $this->t('Text that starts with an at symbol (@) followed by any character other than a space will automatically be linked to the following URL: <code>@url</code>', [
          '@url' => $this->getSetting('url'),
        ]),
        'tags' => [
          'a' => [
            '@dries',
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
      'type' => 'user',
      'format_username' => TRUE,
      'url' => 'https://www.drupal.org/u/[text]',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCharacters() {
    return ['@'];
  }

  /**
   * {@inheritdoc}
   */
  public function parse(InlineParserContext $inline_context) {
    $cursor = $inline_context->getCursor();

    // The @ symbol must not have any other characters immediately prior.
    $previous_char = $cursor->peek(-1);
    if ($previous_char !== NULL && $previous_char !== ' ') {
      // peek() doesn't modify the cursor, so no need to restore state first.
      return FALSE;
    }

    // Save the cursor state in case we need to rewind and bail.
    $previous_state = $cursor->saveState();

    // Advance past the @ symbol to keep parsing simpler.
    $cursor->advance();

    // Parse the handle.
    $text = $cursor->match('/^[^\s]+/');
    $url = '';
    $title = '';

    $type = $this->getSetting('type');
    if ($type === 'user') {
      $users = \Drupal::entityTypeManager()->getStorage('user');

      /** @var \Drupal\user\UserInterface $user */
      $user = is_numeric($text) ? $users->load($text) : $users->loadByProperties(['name' => $text]);
      if ($user && $user->id()) {
        $url = $user->toUrl('canonical', ['absolute' => TRUE])->toString();
        $title = $this->t('View user profile.');
        $text = $this->getSetting('format_username') ? $user->getDisplayName() : $user->getAccountName();
      }
      else {
        $text = FALSE;
      }
    }
    elseif ($type === 'url' && ($url = $this->getSetting('url')) && strpos($url, '[text]') !== FALSE) {
      $url = str_replace('[text]', $text, $url);
    }
    else {
      $text = FALSE;
    }

    // Regex failed to match; this isn't a valid @ handle.
    if (empty($text) || empty($url)) {
      $cursor->restoreState($previous_state);
      return FALSE;
    }

    $inline_context->getContainer()->appendChild(new Link($url, '@' . $text, $title));

    return TRUE;
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
        'user' => $this->t('User'),
        'url' => $this->t('URL'),
      ],
    ];

    $form['format_username'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Replace username with formatted display name'),
      '#description' => $this->t('If enabled, it will replace the matched text with the formatted username.'),
      '#default_value' => $this->getSetting('format_username'),
      '#states' => [
        'visible' => [
          $selector => ['value' => 'user'],
        ],
      ],
    ];

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#description' => $this->t('A URL to format text with. Use the token "[text]" where it is needed. If you need to include the @, use the URL encoded equivalent: <code>%40</code>. Example: <code>https://twitter.com/search?q=%40[text]</code>.'),
      '#default_value' => $this->getSetting('url'),
      '#states' => [
        'visible' => [
          $selector => ['value' => 'url'],
        ],
      ],
    ];

    return $form;
  }

}
