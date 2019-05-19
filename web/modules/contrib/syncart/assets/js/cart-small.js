/**
 * @file
 * Vue app for small cart.
 */

(function () {
  "use strict";

  Vue.component('cart-small', {
    data() {
      return {
        cart: {
          items: {},
          quantity: 0,
          total: 0,
        },
        ready: false,
      }
    },
    methods: {
      initCart() {
        axios.get('/api/cart-load').then(
          response => {
            this.cart = response.data;
            this.ready = true;
            this.$root.$emit('update_cart', this.cart);
          },
          response => {
            console.log(response);
          }
        )
      },
    },
    created() {
      setTimeout(() => {
        if (!this.$el.parentElement.offsetParent) {
          return false;
        }
        this.initCart();
        this.$root.$on('update_cart', (cart) => {
          this.cart = cart;
        });
      }, 10);
    }
  });


})();
