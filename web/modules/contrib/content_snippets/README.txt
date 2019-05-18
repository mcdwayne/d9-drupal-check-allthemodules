When enabled, this module adds a menu item under "Content" called
"Snippets" and an administrative item under "Configuration" called "Content
Snippets Admin".

The Admin is a place to create new snippets, which can then be edited on the
Content page. Once the snippet has a value, you can use that value anywhere in
your custom code.

In PHP, access it by calling `content_snippets_retrieve($snippetname)`, or by
pulling straight from configuration:
`\Drupal::config('content_snippets.content')->get($snippetname);`

In Twig templates, snippets are available as variables:
`{{ contentSnippets.snippetname }}`

Your snippets are also available via Tokens.
