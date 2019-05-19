<?php

namespace Drupal\striptag_filter\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to strip HTML tags.
 *
 * The attributes in the annotation shows example how to strip script tag(s)
 * from display
 *
 * @Filter(
 *   id = "strip_html_tags",
 *   title = @Translation("Strip HTML tags"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   settings = {
 *     "strip_tags" = "<script>",
 *     "strip_tags_help" = TRUE,
 *   },
 *   weight = -10
 * )
 */
class StripHtmlTags extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['strip_tags'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Strip these HTML tags'),
      '#default_value' => $this->settings['strip_tags'],
      '#description' => $this->t('A list of HTML tags to be stripped'),
      '#attached' => [
        'library' => [
          'filter/drupal.filter.filter_html.admin',
        ],
      ],
    ];
    $form['strip_tags_help'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display basic HTML help in long filter tips'),
      '#default_value' => $this->settings['strip_tags_help'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    if (isset($configuration['settings']['strip_tags'])) {
      // The javascript in core/modules/filter/filter.filter_html.admin.js
      // removes new lines and double spaces so, for consistency when javascript
      // is disabled, remove them.
      $configuration['settings']['strip_tags'] = preg_replace('/\s+/', ' ', $configuration['settings']['strip_tags']);
    }
    parent::setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $strip_tags = explode(' ', $this->settings['strip_tags']);
    $dom = HTML::load($text);
    foreach($strip_tags as $strip_tag) {
      $tags = $dom->getElementsByTagName(str_replace(['<', '>'], '', $strip_tag));
      $remove = [];
      foreach ($tags as $item) {
        $remove[] = $item;
      }
      foreach ($remove as $item) {
        $item->parentNode->removeChild($item);
      }
    }
    $text = HTML::serialize($dom);
    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    global $base_url;

    if (!($strip_tags = $this->settings['strip_tags'])) {
      return;
    }
    $output = $this->t('HTML tags that get stripped: @tags', ['@tags' => $strip_tags]);
    if (!$long) {
      return $output;
    }

    $output = '<p>' . $output . '</p>';
    if (!$this->settings['strip_tags_help']) {
      return $output;
    }

    $output .= '<p>' . $this->t('This site allows HTML content. While learning all of HTML may feel intimidating, learning how to use a very small number of the most basic HTML "tags" is very easy. This table provides examples for each tag that is enabled on this site.') . '</p>';
    $output .= '<p>' . $this->t('For more information see W3C\'s <a href=":html-specifications">HTML Specifications</a> or use your favorite search engine to find other sites that explain HTML.', [':html-specifications' => 'http://www.w3.org/TR/html/']) . '</p>';
    $tips = [
      'a' => [$this->t('Anchors are used to make links to other pages.'), '<a href="' . $base_url . '">' . Html::escape(\Drupal::config('system.site')->get('name')) . '</a>'],
      'br' => [$this->t('By default line break tags are automatically added, so use this tag to add additional ones. Use of this tag is different because it is not used with an open/close pair like all the others. Use the extra " /" inside the tag to maintain XHTML 1.0 compatibility'), $this->t('Text with <br />line break')],
      'p' => [$this->t('By default paragraph tags are automatically added, so use this tag to add additional ones.'), '<p>' . $this->t('Paragraph one.') . '</p> <p>' . $this->t('Paragraph two.') . '</p>'],
      'strong' => [$this->t('Strong', [], ['context' => 'Font weight']), '<strong>' . $this->t('Strong', [], ['context' => 'Font weight']) . '</strong>'],
      'em' => [$this->t('Emphasized'), '<em>' . $this->t('Emphasized') . '</em>'],
      'cite' => [$this->t('Cited'), '<cite>' . $this->t('Cited') . '</cite>'],
      'code' => [$this->t('Coded text used to show programming source code'), '<code>' . $this->t('Coded') . '</code>'],
      'b' => [$this->t('Bolded'), '<b>' . $this->t('Bolded') . '</b>'],
      'u' => [$this->t('Underlined'), '<u>' . $this->t('Underlined') . '</u>'],
      'i' => [$this->t('Italicized'), '<i>' . $this->t('Italicized') . '</i>'],
      'sup' => [$this->t('Superscripted'), $this->t('<sup>Super</sup>scripted')],
      'sub' => [$this->t('Subscripted'), $this->t('<sub>Sub</sub>scripted')],
      'pre' => [$this->t('Preformatted'), '<pre>' . $this->t('Preformatted') . '</pre>'],
      'abbr' => [$this->t('Abbreviation'), $this->t('<abbr title="Abbreviation">Abbrev.</abbr>')],
      'acronym' => [$this->t('Acronym'), $this->t('<acronym title="Three-Letter Acronym">TLA</acronym>')],
      'blockquote' => [$this->t('Block quoted'), '<blockquote>' . $this->t('Block quoted') . '</blockquote>'],
      'q' => [$this->t('Quoted inline'), '<q>' . $this->t('Quoted inline') . '</q>'],
      // Assumes and describes tr, td, th.
      'table' => [$this->t('Table'), '<table> <tr><th>' . $this->t('Table header') . '</th></tr> <tr><td>' . $this->t('Table cell') . '</td></tr> </table>'],
      'tr' => NULL,
      'td' => NULL,
      'th' => NULL,
      'del' => [$this->t('Deleted'), '<del>' . $this->t('Deleted') . '</del>'],
      'ins' => [$this->t('Inserted'), '<ins>' . $this->t('Inserted') . '</ins>'],
       // Assumes and describes li.
      'ol' => [$this->t('Ordered list - use the &lt;li&gt; to begin each list item'), '<ol> <li>' . $this->t('First item') . '</li> <li>' . $this->t('Second item') . '</li> </ol>'],
      'ul' => [$this->t('Unordered list - use the &lt;li&gt; to begin each list item'), '<ul> <li>' . $this->t('First item') . '</li> <li>' . $this->t('Second item') . '</li> </ul>'],
      'li' => NULL,
      // Assumes and describes dt and dd.
      'dl' => [$this->t('Definition lists are similar to other HTML lists. &lt;dl&gt; begins the definition list, &lt;dt&gt; begins the definition term and &lt;dd&gt; begins the definition description.'), '<dl> <dt>' . $this->t('First term') . '</dt> <dd>' . $this->t('First definition') . '</dd> <dt>' . $this->t('Second term') . '</dt> <dd>' . $this->t('Second definition') . '</dd> </dl>'],
      'dt' => NULL,
      'dd' => NULL,
      'h1' => [$this->t('Heading'), '<h1>' . $this->t('Title') . '</h1>'],
      'h2' => [$this->t('Heading'), '<h2>' . $this->t('Subtitle') . '</h2>'],
      'h3' => [$this->t('Heading'), '<h3>' . $this->t('Subtitle three') . '</h3>'],
      'h4' => [$this->t('Heading'), '<h4>' . $this->t('Subtitle four') . '</h4>'],
      'h5' => [$this->t('Heading'), '<h5>' . $this->t('Subtitle five') . '</h5>'],
      'h6' => [$this->t('Heading'), '<h6>' . $this->t('Subtitle six') . '</h6>'],
    ];
    $header = [$this->t('Tag Description'), $this->t('You Type'), $this->t('You Get')];
    preg_match_all('/<([a-z0-9]+)[^a-z0-9]/i', $strip_tags, $out);
    foreach ($out[1] as $tag) {
      if (!empty($tips[$tag])) {
        $rows[] = [
          ['data' => $tips[$tag][0], 'class' => ['description']],
          // The markup must be escaped because this is the example code for the
          // user.
          [
            'data' => [
              '#prefix' => '<code>',
              '#plain_text' => $tips[$tag][1],
              '#suffix' => '</code>',
            ],
            'class' => ['type'],
          ],
          // The markup must not be escaped because this is the example output
          // for the user.
          ['data' => ['#markup' => $tips[$tag][1]], 'class' => ['get']],
        ];
      }
      else {
        $rows[] = [
          ['data' => $this->t('No help provided for tag %tag.', ['%tag' => $tag]), 'class' => ['description'], 'colspan' => 3],
        ];
      }
    }
    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
    $output .= \Drupal::service('renderer')->render($table);

    $output .= '<p>' . $this->t('Most unusual characters can be directly entered without any problems.') . '</p>';
    $output .= '<p>' . $this->t('If you do encounter problems, try using HTML character entities. A common example looks like &amp;amp; for an ampersand &amp; character. For a full list of entities see HTML\'s <a href=":html-entities">entities</a> page. Some of the available characters include:', [':html-entities' => 'http://www.w3.org/TR/html4/sgml/entities.html']) . '</p>';

    $entities = [
      [$this->t('Ampersand'), '&amp;'],
      [$this->t('Greater than'), '&gt;'],
      [$this->t('Less than'), '&lt;'],
      [$this->t('Quotation mark'), '&quot;'],
    ];
    $header = [$this->t('Character Description'), $this->t('You Type'), $this->t('You Get')];
    unset($rows);
    foreach ($entities as $entity) {
      $rows[] = [
        ['data' => $entity[0], 'class' => ['description']],
        // The markup must be escaped because this is the example code for the
        // user.
        [
          'data' => [
            '#prefix' => '<code>',
            '#plain_text' => $entity[1],
            '#suffix' => '</code>',
          ],
          'class' => ['type'],
        ],
        // The markup must not be escaped because this is the example output
        // for the user.
        [
          'data' => ['#markup' => $entity[1]],
          'class' => ['get'],
        ],
      ];
    }
    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
    $output .= \Drupal::service('renderer')->render($table);
    return $output;
  }

}
