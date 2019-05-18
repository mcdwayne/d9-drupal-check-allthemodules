<?php

/**
 * @file
 * Contains \Drupal\orgmode\Utils\ParserPHPOrg.
 */

namespace Drupal\orgmode\Utils;

/**
 * Class ParserPHPOrg.
 *
 * @package Drupal\orgmode\Utils
 */
class ParserPHPOrg {

  /**
   * Transform a file content to node html content.
   *
   * @param string $filename
   *   A file name.
   *
   * @return array
   *   Returns a node org.
   */
  public function orgToNode($filename) {

    $lines = file($filename);

    $node = array(
      'title' => '',
      'teaser' => '',
      'body' => '',
    );

    $beg_abstract_p = FALSE;
    foreach ($lines as &$line) {

      $title_reg = "/^#\+TITLE:.*/";
      if (preg_match($title_reg, $line, $matches)) {
        $node['title'] = preg_replace("/^#\+TITLE:(.*)$/", "$1\n", $matches[0]);
        continue;
      }

      $beg_abstract = "/^#\+begin_abstract/";
      if (preg_match($beg_abstract, $line, $matches)) {
        $beg_abstract_p = TRUE;
      }
      $end_abstract = "/^#\+end_abstract/";
      if (preg_match($end_abstract, $line, $matches)) {
        $beg_abstract_p = FALSE;
      }

      if (!(preg_match("/^#\+/", $line, $matches))) {
        $h1reg = "/^\* .*$/";
        if (preg_match($h1reg, $line, $matches)) {
          $line = preg_replace("/^\* (.*)$/", "<h1>$1</h1>\n", $matches[0]);
        }
        $h2reg = "/^\*\* .*$/";
        if (preg_match($h2reg, $line, $matches)) {
          $line = preg_replace("/^\*\* (.*)$/", "<h2>$1</h2>\n", $matches[0]);
        }
        $h3reg = "/^\*\*\* .*$/";
        if (preg_match($h3reg, $line, $matches)) {
          $line = preg_replace("/^\*\*\* (.*)$/", "<h3>$1</h3>\n", $matches[0]);
        }
        if (preg_match("/\[.*\]/", $line, $matches)) {
          if (preg_match("/\[\[.*\]\[.*\]\]/", $line, $matches)) {
            $line = preg_replace("/\[\[(.*)\]\[(.*)\]\]/", "<a href='$1'>$2</a>", $line);
          }
          if (preg_match("/\[\[file:(.)*(jpg|png|gif)\]\]/", $line, $matches)) {
            $line = preg_replace("/\[\[file:(.*)\]\]/", "<img src='$1' alt='text' />", $matches[0]);
          }
        }
        if (!(preg_match($h1reg, $line, $match1)) && !(preg_match($h2reg, $line, $match2)) && !(preg_match($h3reg, $line, $match3))) {
          if ($beg_abstract_p) {
            $node['teaser'] .= $line;
          }
          else {
            $node['body'] .= $line;
          }
        }
      }
    }

    return $node;
  }

  /**
   * Transform a node to orgmode.
   *
   * @param Drupal\node\NodeInterface $node
   *   A node entity.
   *
   * @return mixed|string
   *   Returns org content.
   */
  public function nodeToOrg(NodeInterface $node) {

    $content = "#+TITLE: " . $node->getTitle() . "\n";
    if ($node->language != 'und') {
      $content .= "#+LANGUAGE: " . $node->language()->getId() . "\n";
    }

    $user = $node->getOwner();
    $content .= "#+AUTHOR: " . $user->getDisplayName() . " \n";

    if ($node->body->summary) {
      $content .= "#+begin_abstract \n";
      $content .= $node->body->summary . " \n";
      $content .= "#+end_abstract \n";
    }

    $content .= $node->get('body')->value;

    $h1reg = "/<h1>(.*)<\/h1>/";
    if (preg_match($h1reg, $content, $matches)) {
      $content = preg_replace($h1reg, "\n* $1 \n", $content);
    }

    $h2reg = "/<h2>(.*)<\/h2>/";
    if (preg_match($h2reg, $content, $matches)) {
      $content = preg_replace($h2reg, "\n** $1 \n", $content);
    }

    $h3reg = "/<h3>(.*)<\/h3>/";
    if (preg_match($h3reg, $content, $matches)) {
      $content = preg_replace($h3reg, "\n*** $1 \n", $content);
    }

    $h4reg = "/<h4>(.*)<\/h4>/";
    if (preg_match($h4reg, $content, $matches)) {
      $content = preg_replace($h4reg, "\n**** $1 \n", $content);
    }

    $boldreg = "/<b>(.*)<\/b>/";
    if (preg_match($boldreg, $content, $matches)) {
      $content = preg_replace($boldreg, "*$1*", $content);
    }

    $strongreg = "/<strong>(.*)<\/strong>/";
    if (preg_match($strongreg, $content, $matches)) {
      $content = preg_replace($strongreg, "*$1*", $content);
    }

    $italicreg = "/<i>(.*)<\/i>/";
    if (preg_match($italicreg, $content, $matches)) {
      $content = preg_replace($italicreg, "/$1/", $content);
    }

    $emreg = "/<em>(.*)<\/em>/";
    if (preg_match($emreg, $content, $matches)) {
      $content = preg_replace($emreg, "/$1/", $content);
    }

    $ahreg = "/<a href=('(.*)'|\"(.*)\"|(.*))>(.*)<\/a>/";
    if (preg_match($ahreg, $content, $matches)) {
      $content = preg_replace($ahreg, "[[$2$3$4][$5]]", $content);
    }

    $codereg = "/<code>(.*)<\/code>/";
    if (preg_match($codereg, $content, $matches)) {
      $content = preg_replace($codereg, "#+BEGIN_SRC c\n$1\n#+END_SRC\n", $content);
    }

    $blockquotereg = "/<blockquote>(.*)<\/blockquote>/";
    if (preg_match($blockquotereg, $content, $matches)) {
      $content = preg_replace($blockquotereg, "#+BEGIN_EXAMPLE\n$1\n#+END_EXAMPLE\n", $content);
    }

    $preg = "/<p>(.*)<\/p>/";
    if (preg_match($preg, $content, $matches)) {
      $content = preg_replace($preg, "$1\n", $content);
    }

    return $content;
  }

}
