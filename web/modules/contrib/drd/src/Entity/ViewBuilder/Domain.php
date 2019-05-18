<?php

namespace Drupal\drd\Entity\ViewBuilder;

/**
 * View builder handler for drd_domain.
 *
 * @ingroup drd
 */
class Domain extends Base {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    /** @var \Drupal\drd\Entity\DomainInterface[] $entities */
    parent::buildComponents($build, $entities, $displays, $view_mode);

    if ($displays['drd_domain']->getComponent('monitoring')) {
      foreach ($entities as $id => $entity) {
        $monitoring = $entity->getMonitoring();
        if (!empty($monitoring)) {
          $result = '<table>';
          foreach ($monitoring as $item) {
            switch ($item['status']) {
              case 'CRITICAL':
                $type = 'error';
                break;

              case 'WARNING':
                $type = 'warning';
                break;

              default:
                $type = 'ok';
            }
            $name = '<div class="drd-monitoring-info ' . $type . '"><span class="drd-icon">&nbsp;</span><div class="label">' . $item['status'] . '</div>' . $item['label'] . '</div>';
            $result .= '<tr><td>' . $name . '</td><td>' . $item['message'] . '</td>';
          }
          $result .= '</table>';
          $build[$id]['monitoring'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Monitoring'),
            '#attributes' => [
              'class' => ['monitoring'],
            ],
            'result' => ['#markup' => $result],
          ];
        }
      }
    }

    if ($displays['drd_domain']->getComponent('queryresult')) {
      foreach ($entities as $id => $entity) {
        $queryResult = $entity->getQueryResult();
        if (!empty($queryResult)) {
          $result = '<h4>' . $queryResult['query'] . '</h4><p>' . $queryResult['info'] . '</p>';
          if (!empty($queryResult['rows'])) {
            $result .= '<table><thead><tr>';
            foreach ($queryResult['headers'] as $header) {
              $result .= '<th>' . $header . '</th>';
            }
            $result .= '</tr></thead><tbody>';
            foreach ($queryResult['rows'] as $row) {
              $result .= '<tr>';
              foreach ($row as $item) {
                $result .= '<td>' . $item . '</td>';
              }
              $result .= '</tr>';
            }
            $result .= '</tbody></table>';
          }
          $build[$id]['queryresult'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Query result'),
            '#attributes' => [
              'class' => ['query-result'],
            ],
            'result' => ['#markup' => $result],
          ];
        }
      }
    }

    if ($displays['drd_domain']->getComponent('status_report')) {
      \Drupal::moduleHandler()->loadInclude('drd', 'install');
      foreach ($entities as $id => $entity) {
        $build[$id]['status_report'] = [
          '#theme' => 'status_report',
          '#requirements' => $entity->getRemoteRequirements(),
        ];
      }
    }

    if ($displays['drd_domain']->getComponent('latest_ping_status')) {
      foreach ($entities as $id => $entity) {
        $status = $entity->renderPingStatus();
        if (!empty($status)) {
          $build[$id]['latest_ping_status'] = [
            '#markup' => $status,
          ];
        }
      }
    }

    if ($displays['drd_domain']->getComponent('messages')) {
      foreach ($entities as $id => $entity) {
        $messages = $entity->getMessages();
        if (!empty($messages)) {
          foreach ($messages as &$message) {
            $message['ts'] = \Drupal::service('date.formatter')->format($message['ts']);
          }
          $build[$id]['messages'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Messages'),
            '#attributes' => [
              'class' => ['messages'],
            ],
            'list' => [
              '#theme' => 'status_messages',
              '#message_list' => $messages,
              '#status_headings' => [
                'status' => t('Status message'),
                'error' => t('Error message'),
                'warning' => t('Warning message'),
              ],
            ],
          ];
        }
      }
    }

    if ($displays['drd_domain']->getComponent('review')) {
      foreach ($entities as $id => $entity) {
        $review = $entity->getReview();
        if (!empty($review)) {
          foreach ($review as $item) {
            $result = is_string($item['result']) ?
              ['#markup' => $item['result']] :
              $item['result'];
            $build[$id]['review'][] = [
              '#type' => 'fieldset',
              '#title' => $item['title'],
              '#attributes' => [
                'class' => ['review'],
              ],
              'result' => $result,
            ];
          }
        }
      }
    }

  }

}
