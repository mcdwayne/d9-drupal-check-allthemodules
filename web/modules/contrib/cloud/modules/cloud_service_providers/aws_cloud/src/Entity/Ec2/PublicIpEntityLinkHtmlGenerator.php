<?php

namespace Drupal\aws_cloud\Entity\Ec2;

use Drupal\Core\Url;
use Drupal\cloud\Service\Util\EntityLinkHtmlGenerator;

/**
 * Html generator for public_ip field.
 */
class PublicIpEntityLinkHtmlGenerator extends EntityLinkHtmlGenerator {

  /**
   * {@inheritdoc}
   */
  public function generate(Url $url, $id, $name = '', $alt_text = '') {
    if (!empty($name) && $name != $id) {
      $html = $this->linkGenerator->generate($name, $url);
      $html = "$id ($html)";
    }
    else {
      $html = $this->linkGenerator->generate($id, $url);
    }

    return $html;
  }

}
