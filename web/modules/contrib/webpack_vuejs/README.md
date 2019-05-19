# Webpack Vue.js

Provides Vue.js configuration for the webpack module.

## Dependencies

- [Webpack](https://drupal.org/project/webpack)
- [Webpack Babel](https://drupal.org/project/webpack_babel)

## Installation

- `yarn add vue vue-loader vue-template-compiler`

## Example usage

_module.libraries.yml_
```yaml
test:
  webpack: true
  js:
    index.js: {}
```

_index.js_
```javascript
import Vue from 'vue';
import Component from './Component.vue';

Vue.component('test-component', Component);

const app = new Vue({
  el: '#page',
  template: '<test-component />',
});
```
