<?php

namespace Drupal\couchbasedrupal;

interface SerializerInterface {

  function serialize($data);

  function unserialize($data, bool &$success);
}