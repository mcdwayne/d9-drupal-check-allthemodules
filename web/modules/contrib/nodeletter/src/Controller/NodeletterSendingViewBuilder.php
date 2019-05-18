<?php
/**
 * @file
 * Contains \Drupal\nodeletter\Controller\NodeletterSendingViewBuilder.
 */

namespace Drupal\nodeletter\Controller;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Url;
use Drupal\nodeletter\Entity\NodeletterSending;
use Drupal\nodeletter\NodeletterSendException;
use Drupal\nodeletter\SendingStatus;

class NodeletterSendingViewBuilder extends EntityViewBuilder  {

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    /** @var \Drupal\node\NodeInterface $entity */
    parent::alterBuild($build, $entity, $display, $view_mode);

    /** @var NodeletterSending $sending */
    $sending = $entity;
    $node = $sending->getNode();

    $title_twig = "<a href=\"{{url}}\"><h2>{{title}}</h2></a>";
    $build['title'] = [
      '#type' => 'inline_template',
      '#template' => $title_twig,
      '#context' => [
        'url' => Url::fromRoute('entity.node.canonical', ['node' => $node->id()]),
        'title' => $node->getTitle(),
      ]
    ];

    if ($sending->getSubject() != $node->getTitle()) {
      $subject_twig = "<p>E-Mail Subject: <em>{{subject}}</em></p>";
      $build['subject'] = [
        '#type' => 'inline_template',
        '#template' => $subject_twig,
        '#context' => [
          'subject' => $sending->getSubject(),
        ]
      ];
    }

    if ($sending->getMode() == 'test') {
      $test_twig = "<p><strong>{% trans %}Test Sending{% endtrans %}</strong> " .
        "to {{recipient}}</p>";
      $build['test_mode'] = [
        '#type' => 'inline_template',
        '#template' => $test_twig,
        '#context' => [
          'recipient' => $sending->getTestRecipient()
        ]
      ];
    }

    $date_twig = "{% set date_str = date|format_date('long') %}" .
      "<p>{% trans %}Sent{% endtrans %}: <strong>{{date_str}}</strong>" .
      "<br/>{% trans %}By{% endtrans %}: <strong>{{user}}</strong></p>";
    $build['date'] = [
      '#type' => 'inline_template',
      '#template' => $date_twig,
      '#context' => [
        'date' => $sending->getCreatedTime(),
        'user' => $sending->getOwner()->getAccountName(),
      ]
    ];

    $status_twig = "{% trans %}Status{% endtrans %} <strong>{{status}}</strong>";
    $build['status'] = [
      '#type' => 'inline_template',
      '#template' => $status_twig,
      '#context' => [
        'status' => $sending->getSendingStatus(),
      ]
    ];

    if ($sending->getSendingStatus() == SendingStatus::FAILED) {
      $error_twig = "<div class=\"messages messages--warning\">".
        "<strong>{{error}}</strong><br/>".
        "{{message}}" .
        "</div>";
      $build['error'] = [
        '#type' => 'inline_template',
        '#template' => $error_twig,
        '#context' => [
          'error' => NodeletterSendException::describe($sending->getErrorCode()),
          'code' => $sending->getErrorCode(),
          'message' => $sending->getErrorMessage(),
        ]
      ];
    }

    if (!empty($sending->getComment())) {
      $comment_twig = "<p>{% trans %}Comment{% endtrans %}: <pre>{{comment}}</pre></p>";
      $build['error'] = [
      ];
      $build['comment'] = [
        '#type' => 'inline_template',
        '#template' => $comment_twig,
        '#context' => [
          'comment' => $sending->getComment(),
        ]
      ];
    }

    $build['variables'] = [
      '#type' => 'details',
      '#title' => $this->t('Variables'),
      '#open' => FALSE,
    ];
    $variable_twig = "<strong>{{name}}</strong> <pre>{{value}}</pre>";
    foreach($sending->getVariables() as $variable) {
      $build['variables'][] = [
        '#type' => 'inline_template',
        '#template' => $variable_twig,
        '#context' => [
          'name' => $variable->getName(),
          'value' => $variable->getValue(),
        ],
      ];
    }

  }

}
