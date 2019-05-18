<?php
namespace Drupal\cloudwords;

class CloudwordsDOMElementFilter extends RecursiveFilterIterator {

  public function accept() {
    return $this->current()->nodeType === XML_ELEMENT_NODE;
  }

}
