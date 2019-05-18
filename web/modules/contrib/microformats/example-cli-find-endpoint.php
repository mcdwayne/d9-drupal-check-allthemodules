<?php

/**
 * @file Example can be executed on CLI to test if endpoints can be discovered.
 */

use IndieWeb\MentionClient;

include './vendor/autoload.php';

// $url = 'https://github.com/aaronpk/mention-client';
$url = 'https://webmention.rocks/test/11';
$client = new MentionClient();

// More results will be emitted.
$client->enableDebug();


$sent = 'zero';
// $sent = $client->sendMentions($url);
$endpoint = $client->discoverWebmentionEndpoint($url);


echo "Sent $sent mentions. endpoint is: $endpoint \n";
var_dump($endpoint);
