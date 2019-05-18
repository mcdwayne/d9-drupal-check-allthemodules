# Apollo client

Provides [Apollo Client](https://apollographql.com) as a drupal library.

## Dependencies

There are none, although two components are strongly recommended.

### Polyfill

A polyfill will ensure feature parity between browsers and add missing functions. For a zero-config solution use [Babel polyfill](https://drupal.org/project/babel).

### ES6 transpilation

Javascript transpilation process is already set up for drupal core. You can use it in your project with this this gist: https://gist.github.com/blazeyo/00bdebf35f51085f15642b960f7ebcaa

### GraphQL

The library can be used with any GraphQL server a common use case, however, is to use it for progressive decoupling. If that's what you're looking for then the site's local endpoint can be enabled by installing the [GraphQL](https://drupal.org/project/graphql) module.

## Example usage

_modulename_.libraries.yml

```yml
mylibrary:
  version: 1.x
  js:
    js/mylibrary.js: {}
  dependencies:
    - apollo/client
    - polyfill/babel
```

js/_mylibrary_.js

```javascript
// ApollClient's server url defaults to /graphql, so this will connect to the
// local enpoint exposed by GraphQL module (https://drupal.org/project/graphql).
// See https://www.apollographql.com/docs/react/reference for available
// configuration options.
const client = new global.ApolloClient();

// Create a GraphQL query object by passing a tagged template literal to the gql
// function (provided by apollo/client). This particular query returns username
// of the currently logged in user.
const query = gql`
  query {
    currentUserContext{
      ... on UserUser {
        name
      }
    }
  }
`;

const result = await client.query({ query });
console.log(result);
```
