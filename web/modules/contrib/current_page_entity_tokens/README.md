# Add current request entity tokens to [current-page]

Taken from a Tokens feature request at https://www.drupal.org/project/token/issues/919760, this micro-module makes the current request's entity information available under the `[current-page]` token. 

For instance, 

- an embedded webform might want to have access to `[current-page:node:field_email]` to autofill a reply-to
- a Paragraph on a page might want access to `[current-page:node:field_tags]` to filter an embedded View