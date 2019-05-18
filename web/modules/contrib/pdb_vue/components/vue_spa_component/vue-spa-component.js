// Create a vue component that can be reused
Vue.component('vue-spa-component', {
  template: '<div class="test">{{ message }}</div>',
  props: ['textField', 'instanceId'],
  data: function () {
    return {
      message: 'Hello Vue! This is a component so will work in multiple places.'
    };
  },
  mounted: function () {
    if (this.textField) {
      this.message = this.textField;
    }
  }
});
