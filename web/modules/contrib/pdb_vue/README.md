# Decoupled Blocks: Vue.js

This is a [Vue.js](https://vuejs.org/) implementation for the
[Decoupled Blocks](https://www.drupal.org/project/pdb) module. Blocks built with
Vue.js can now easily be added to a site via a module or in a custom theme.

The [Decoupled Blocks](https://www.drupal.org/project/pdb) module is required
for this to work.

## Development Mode
Demo blocks can be enabled by turning on Development mode at
`/admin/config/services/pdb-vue`.

Development mode also uses the un-minified version of Vue so that
[Vue Devtools](https://github.com/vuejs/vue-devtools) can be used.

## Per-block Vue Instances

Vue.js is very flexible so several methods of creating vue blocks has been
implemented. By default it is assumed that each block is its own Vue instance.

### Basic Examples
`vue_example_1` is the simplest example of using Vue to render in a single
block. The downside is that multiple blocks of the same type will not render.
Vue.js is different from jQuery in that it only renders the first instance it
finds and then stops. So even if a site editor adds multiples of the same block,
only the first one will render. This is fine if the block is really only
designed to be placed once per page.

```js
new Vue({
  el: '.vue-example-1',
  template: '<div class="test">{{ message }}</div>',
  data: {
    message: 'Hello Vue! This will only work on a single Example 1 block per page.'
  }
});
```

### Using a separate template file
The `vue_example_2` demo block uses a separate template file to place the
markup. This template is indicated in the `vue_example_2.info.yml` file by
adding:

```yaml
template: template_name.html
```

### Basic Vue instance rendered multiple times
The `vue_example_2` demo block also demonstrates how to make a block render
multiple times in the cases where it is possible that a site editor would place
more than one block of the same type. Truthfully, this is not very elegant.

With Javascript, first look for each block via a class name and then loop
through each one. Add a Vue instance within the loop that looks for the element
of the current instance of the loop.

```js
// Find all blocks that match the class name.
var vueElements = document.getElementsByClassName('vue-example-2');
var count = vueElements.length;

// Loop through each block.
for (var i = 0; i < count; i++) {
  // Add a vue instance here with the 'el' set to vueElements[i]
}
```

### Getting the instance ID
The ID of a block can be accessed so that configuration from the block may be
fetched. Get the block id within a Vue instance using `$el`.
```js
this.$el.id
```
With the Instance ID, raw settings for that block instance can now be accessed
from `drupalSettings.pdb.configuration[this.instanceId]`.

## Vue Components

Vue allows the use of [components](https://vuejs.org/v2/guide/components.html)
which are ideal for reuse. Using components requires a global Vue instance in
order to render all components.

### Enabling "Single Page App" mode
Go to `/admin/config/services/pdb-vue` and check the box to "Use Vue components
in a Single Page App format." This will then present a text field where you can
define the element which the vue instance will attach itself to. By default it
uses the `#page-wrapper` provided by the Classy theme.

By enabling this mode, a `spa-init.js` javascript file will be added after all
components, thus rendering them.

Finally, each component needs to specify that it is a Vue component in the
`*.info.yml` file.

```yaml
component: true
```

### Passing block configuration settings as component properties
Each block can be set up to allow a user to add settings which will be passed
into a component as [Props](https://vuejs.org/v2/guide/components.html#Props).

To add fields to the block configuration, add them to the `*.info.yml` file.
```yaml
configuration:
  fieldName:
    type: textfield
    default_value: 'I am a default value'
```
Then in the Vue component just add `props` for those fields.
```js
props: ['textField']
```

### Getting the instance ID
Each component is automatically passed an `instance-id` prop. This can be
accessed in the Vue instance by just adding the camelCase name into `props`
```js
props: ['textField', 'instanceId']
```
With the Instance ID, raw settings for that block instance can now be accessed
from `drupalSettings.pdb.configuration[this.instanceId]`.

### Slots

Currently the use of [Slots](https://vuejs.org/v2/guide/components.html#Content-Distribution-with-Slots)
in top level components is not possible. This means that when a block is added
and configured, that configuration will only pass as Props. However, there is no
reason that the top level component can't just be a wrapper that then passes
the content of those props into a child component which implements slots.

### Advanced Stuff

#### Vue CLI and Webpack
The `vue_example_webpack` block is an example of using the
[Vue CLI](https://github.com/vuejs/vue-cli) to generate single-file components
using ES6 with [Webpack](https://webpack.github.io/).

Be sure to tell webpack that you are using an external version of Vue.js so that
it doesn't load in a second version.
```js
module.exports.externals = {
  "vue": "Vue"
}
```

#### Vuex
For more robust state management, the [Vuex](https://vuex.vuejs.org) library is
included. Create your own Store file and be sure to add the `pdb_vue/vue.vuex`
library as a dependency of any blocks.

You can include the Vuex library by adding a "libraries" parameter to the
`*.info.yml` file like in a theme. This will create a dependency on this
library.

```yaml
libraries:
  - pdb_vue/vue.vuex
```

## Override javascript libraries

It is possible that you will need a different version of vue.js or that you will
want to modify the global `spa-init.js` file loaded by the `pdb_vue` module.
Overriding a library is easy in a theme and can be done by adding some lines to
a custom theme's `themename.info.yml` file.

```yaml
# Replace an entire library.
libraries-override:
  pdb_vue/vue: themename/vue
```
or overriding a single file in a library
```yaml
libraries-override:
  vue.spa-init:
    js:
      js/spa-init.js: js/spa-init.js
```
