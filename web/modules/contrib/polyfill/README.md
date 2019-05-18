# Polyfill

Provides these polyfill as drupal libraries

- [Babel Polyfill](https://babeljs.io/docs/usage/polyfill/)
- [whatwg-fetch](https://github.com/github/fetch)
- [webcomponents.js](https://github.com/webcomponents/webcomponentsjs)

## Example usage

_modulename_.libraries.yml

```yml
mylibrary:
  version: 1.x
  js:
    js/mylibrary.js: {}
  dependencies:
    - polyfill/babel
    - polyfill/fetch

mywebcomponent:
  version: 1.x
  js:
    js/mywebcomponent.js: {}
  dependencies:
    - polyfill/webcomponents
```
