# Wikipedia Client

A client that can retrieve an extract of a Wikipedia page by searching for a page title that matches a given string. This works best for simple proper nouns, like the names of companies, people, countries, states, etc. 

Example usage:

```

$client = \Drupal::service('wikipedia_client.client');

// Get the title of a node.
$title = $entity->get('title')->value;

// Search Wikipedia for a page matching this title.
$wiki_data = $client->getResponse($title);

// If no match was found, the result will be empty.
if (empty($wiki_data)) {
  return;
}

// Returns markup that contains the extract with
// a link back to the Wikipedia source document.
$markup = $client->getMarkup($wiki_data);

// Update the body with the Wikipedia markup.
$entity->set('body', $markup);

```