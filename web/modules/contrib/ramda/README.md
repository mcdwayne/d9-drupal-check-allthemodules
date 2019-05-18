# Ramda

Provides [Ramda](http://ramdajs.com/) as a drupal library.

## Example usage

_modulename_.libraries.yml

```yml
mylibrary:
  version: 1.x
  js:
    js/mylibrary.js: {}
  dependencies:
    - ramda/ramda
```

js/_mylibrary_.js

```javascript
const R = global.Ramda;
```
