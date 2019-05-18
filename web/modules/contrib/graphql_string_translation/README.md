# GraphQL string translation

The module exposes the `translation` field that can be used to connect Drupal's translation system with GraphQL.

## Usage

```graphql
query translationQuery($language: LanguageId!) {
  textOne: translation("Text one", $language)
  textTwo: translation("Text two", $language)
}
```

The returned values are translations of the given texts in the *graphql* context, so regular drupal interface strings won't be returned. There's a simple page showing just these under `/admin/config/graphql/string-translation`. 

## Adding new strings

The default setting is that the non-existent strings requested with the field are **not added** to the translation system. This means that non-privileged users can request translations for the strings from a pre-defined list (those with the context set to  *graphql*). Strings can be added in the settings form at `/admin/config/graphql/string-translation`.

This behavior can be overridden by the *Request translations of arbitrary strings*. Strings requested by the accounts with this permission will be added to the system. The permission has security implications and should be granted only to trusted roles.

## Requirements

- The translation page requires the patch from https://www.drupal.org/node/2123543 to work correctly.
- The 1.x branch is only compatible with the versions of the GraphQL module that are based on Youshido's library (up to 8.x-3.0-beta5).
- The 2.x branch is only compatible with the versions of the GraphQL module based on webonyx's library (since 8.x-3.0-beta6).
