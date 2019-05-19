import Vue from 'vue';
import Component from './Component.vue';

Vue.component('test-component', Component);

const app = new Vue({
  el: '#page',
  template: '<test-component />',
});

console.log(app);
