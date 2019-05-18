<?php
/**
 * Created by Marek Kisiel (marek.kisiel@unic.com).
 *
 * Date: 10/01/17
 */
use Drupal\ext_redirect\Entity\RedirectRule;

/**
 * Implements hook_drush_command()
 */
function ext_redirect_drush_command() {
  return [
    'ext-redirect-apache-parse' => [
      'description' => 'Parse Apache2 rewrite rules file',
      'arguments' => [
        'file_path' => 'Absolute file path',
      ],
      'drupal dependencies' => ['ext_redirect'],
      'aliases' => ['era'],
      'callback' => 'drush_ext_redirect_apache_parse',
    ],
    'ext-redirect-import-rules' => [
      'description' => 'Import rules data  extracted from Apache config',
      'arguments' => [
        'rules_file' => 'Absolute file path to rules data in JSON format',
      ],
      'drupal dependencies' => ['ext_redirect'],
      'aliases' => ['eri'],
      'callback' => 'drush_ext_redirect_rules_import',
    ],
    'ext-redirect-create-rule' => [
      'description' => 'Create single RedirectRule instance',
      'arguments' => [
        'source site' => 'Rule source domain name',
        'source paths' => 'List of source paths, separated by comma',
        'destination' => 'Redirect destination',
      ],
      'drupal dependencies' => ['ext_redirect'],
      'aliases' => ['erc'],
      'callback' => 'drush_ext_redirect_create_rule',
    ],
    'ext-redirect-delete-source-rules' => [
      'description' => 'Delete all rules for a source site',
      'arguments' => [
        'source site' => 'Rule source site name',
      ],
      'drupal dependencies' => ['ext_redirect'],
      'aliases' => ['erds'],
      'callback' => 'drush_ext_redirect_delete_source_rules',
    ],
    'ext-redirect-delete-rules' => [
      'description' => 'Delete specified rules by an ID',
      'arguments' => [
        'id' => 'Single id, or multiple separated  by comma',
      ],
      'drupal dependencies' => ['ext_redirect'],
      'aliases' => ['erd'],
      'callback' => 'drush_ext_redirect_delete',
    ],
    'ext-redirect-export-rules' => [
      'description' => 'Export all rules as json',
      'drupal dependencies' => ['ext_redirect'],
      'aliases' => ['ere'],
      'callback' => 'drush_ext_redirect_export',
      'options' => array(
        'env' => 'Target environment indicator, dev or prd',
      ),
    ],
  ];
}

/**
 * @param $file_path absolute file path
 * @throws \Exception
 */
function drush_ext_redirect_apache_parse($file_path) {
  if (!file_exists($file_path)) {
    throw new Exception('File not found');
  }
  $file = new \SplFileObject($file_path);
  if (!$file->isFile()) {
    throw new Exception('File not found');
  }

  $rewrite_data = [];
  $conds = [];
  $hosts = [];

  $get_q_mark_position = function ($string) {
    return strpos($string, '?');
  };

  $sanitize = function ($string) {
    $string = str_replace('[OR]', '', $string);
    $string = trim($string);
    $string = trim($string, '$');
    $string = trim($string, '^');
    $string = trim($string, '?');
    $string = rtrim($string, '/');
    $string = str_replace('\\', '', $string);
    $string = str_replace('(', '', $string);
    $string = str_replace(')', '', $string);
    $string = str_replace('.*.', '', $string);
    $string = str_replace('(www.)', '', $string);
    return $string;
  };

  $extract_internal_path = function ($string) {
    $matches = [];
    preg_match("~(/.*?)((?:[^/]|\\\\/)+?)(?:(?<!\\\\)\s|$)~", $string, $matches);
    if ($matches) {
      return $matches[0];
    }
    return FALSE;
  };

  while (!$file->eof()) {
    $line = $file->fgets();
    $line = trim($line);
    if (strpos($line, 'RewriteCond %{HTTP_HOST}') !== FALSE) {
      $q_mark_pos = $get_q_mark_position($line);
      if ($q_mark_pos !== FALSE) {
        $host = substr($line, $q_mark_pos);
        $host = $sanitize($host);
        if (strpos($host, '|') !== FALSE) {

          $pos = strpos($host, '.');
          $suffix = substr($host, $pos + 1);
          $host = str_replace('.' . $suffix, '', $host);
          $rule_hosts = explode('|', $host);
          foreach ($rule_hosts as $_host) {
            $hosts[] = $_host . '.' . $suffix . '.dev';
          }
        }
        else {
          $hosts[] = $host . '.dev';
        }

      }
      else {
        $carat_pos = strpos($line, '^');
        if ($carat_pos !== FALSE) {
          $host = substr($line, $carat_pos);
          $hosts[] = $sanitize($host);
        }
      }

    }
    elseif (strpos($line, 'RewriteCond %{REQUEST_URI}') !== FALSE) {
      $urn = $extract_internal_path($line);
      if ($urn) {
        $q_mark_pos = $get_q_mark_position($urn);
        if ($q_mark_pos !== FALSE) {
          $urn = substr_replace($urn, '', $q_mark_pos);
        }
        $urn = $sanitize($urn);
        $conds[] = $urn;
      }

    }
    elseif (strpos($line, 'RewriteRule') !== FALSE) {
      $rule = new stdClass();
      $matches = [];
      preg_match('#\b(https|http)?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#', $line, $matches);
      if (!empty($matches)) {
        $url = $matches[0];
        $parsed = parse_url($url);
        if ($parsed) {
          $rule->destination = $url;
        }
      }
      else {
        $urn = $extract_internal_path($line);
        if ($urn) {
          $rule->destination = $sanitize($urn);
        }
      }

      if ($rule->destination == '/?.*') {
        /*
         * Exclude rewrite rule like:
         *
         * RewriteCond %{HTTP_HOST}:%{ENV:base_host} !^(.+):\1$
         * RewriteCond %{HTTP_HOST} !^olma.dev:8080$
         * RewriteRule ^/?(.*)$ %{ENV:base_url}/$1 [R=301,L,QSD]
         *
         */
        continue;
      }

      $rule->hosts = $hosts;
      $rule->conds = $conds;
      $conds = [];
      $hosts = [];
      $rewrite_data[] = $rule;
    }
  }

  $output = <<<HTML
    <table class="confluenceTable">
      <tbody>
        <tr>
          <td class="confluenceTh">#</td>   
          <td class="confluenceTh">Host</td>   
          <td class="confluenceTh">Path</td>
          <td class="confluenceTh">Destination</td>
        </tr>
HTML;
  $counter = 0;
  $valid_data = [];
  foreach ($rewrite_data as $rewrite) {
    if (empty($rewrite->hosts) && empty($rewrite->conds)) {
      continue;
    }

    if (empty($rewrite->destination)) {
      continue;
    }

    $rewrite->hosts = empty($rewrite->hosts) ? ['any'] : $rewrite->hosts;
    $valid_data[] = $rewrite;

    foreach ($rewrite->hosts as $host) {
      $output .= '<tr>';
      $output .= '<td class="confluenceTh">' . ++$counter . '</td>';
      $output .= '<td class="confluenceTh">' . $host . '</td>';
      $output .= '<td class="confluenceTh">' . implode("<br>", $rewrite->conds) . '</td>';
      $output .= '<td class="confluenceTh">' . $rewrite->destination . '</td>';
      $output .= '</tr>';
    }
  }

  $output .= '</tbody></table>';
  file_put_contents(__DIR__ . '/output.html', $output);
  file_put_contents(__DIR__ . '/rules.json', json_encode($valid_data));
}

function drush_ext_redirect_rules_import($json_path) {
  $rules_data = json_decode(file_get_contents($json_path));
  $counter = 0;
  foreach ($rules_data as $rule) {


    foreach ($rule->hosts as $host) {
      // If there are no conditions, we need to set * as source path.
      // Otherwise the meaning of the rule and the apache rewrite rule would
      // not be the same.
      if (empty(array_filter($rule->conds))) {
        $rule->conds = array('*');
      }

      $redirect_rule = new RedirectRule([], 'redirect_rule');
      $redirect_rule
        ->setOwnerId(1)
        ->setSourceSite($host)
        ->setSourcePath($rule->conds)
        ->setStatusCode(301)
        ->setEnabled();

      if (isset($rule->weight)) {
        $redirect_rule->setWeight($rule->weight);
      }
      else {
        $redirect_rule->setWeight(++$counter);
      }

      $destination = str_replace('internal:', '', $rule->destination);
      $redirect_rule->setDestination($destination);
      $redirect_rule->save();
    }

  }

}

function drush_ext_redirect_create_rule($source_site, $source_paths, $destination) {

  if (!(isset($source_site) || isset($source_paths))) {
    throw new Exception('Missing required arguments');
  }

  if ($source_paths == '<front>') {
    $source_paths = '';
  }

  $redirect_rule = new RedirectRule([], 'redirect_rule');
  $source_paths = explode(',', $source_paths);
  $redirect_rule->setSourceSite($source_site)
    ->setSourcePath($source_paths)
    ->setDestination($destination)
    ->setOwnerId(1)
    ->setStatusCode(301)
    ->setEnabled()
    ->save();

  drush_print($redirect_rule->getName() . ' created.');
}

function drush_ext_redirect_delete_source_rules($source_site) {
  $query = Drupal::entityQuery('redirect_rule');
  $query->condition('source_site', $source_site);
  $rids = $query->execute();
  $count = 0;
  foreach ($rids as $id) {
    $rule = \Drupal::entityTypeManager()
      ->getStorage('redirect_rule')
      ->load($id);
    $rule->delete();
    $count++;
  }
  drush_print('Deleted ' . $count . ' rules');
}

function drush_ext_redirect_delete($id) {
  $ids = explode(',', $id);
  $entities = Drupal::entityTypeManager()
    ->getStorage('redirect_rule')
    ->loadMultiple($ids);
  Drupal::entityTypeManager()->getStorage('redirect_rule')->delete($entities);
}

function drush_ext_redirect_export() {
  $query = Drupal::entityQuery('redirect_rule');
  $ids = $query->execute();
  $env = drush_get_option('env', 'dev');
  $json = [];
  foreach ($ids as $id) {
    /**
     * @var $rule RedirectRule
     */
    $rule = \Drupal::entityTypeManager()
      ->getStorage('redirect_rule')
      ->load($id);
    $data = new stdClass();
    $source = $rule->getSourceSite();
    if ($env == 'prd') {
      $source = str_replace('.dev', '', $source);
    }
    else {
      $source = str_replace('olma-messen.ch', 'olma.dev', $source);
      if ($source <> 'any' && strpos($source, '.dev') === FALSE) {
        $source = $source . '.dev';
      }
    }
    $data->hosts = [$source];
    $paths = $rule->getSourcePath();
    $conds = explode("\n", $paths);
    $data->conds = $conds;
    $destination = $rule->getDestination();
    $destination = str_replace('internal:', '', $destination);
    $data->destination = $destination;
    $data->weight = $rule->getWeight();

    $json[] = $data;
  }

  file_put_contents(__DIR__ . '/exported_' . $env . '.json', json_encode($json));
}

