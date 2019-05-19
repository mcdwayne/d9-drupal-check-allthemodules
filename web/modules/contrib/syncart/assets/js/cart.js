/**
 * @file
 * Vue app for cart.
 */

(function () {
  "use strict";

  Vue.component('product', {
    props: {},
    methods: {},
    created() {}
  });

  Vue.component('cart-field', {
    props: {
      pid: 0,
    },
    data() {
      return {
        inCart: false,
        ready: false,
        loading: false,
      }
    },
    methods: {
      addToCart(variation_id) {
        if (this.loading) {
          return false;
        }
        this.loading = true;
        axios.get('/cart/add-item', {params: {'vid': variation_id}}).then(
          response => {
            this.inCart = true;
            this.loading = false;
            this.$root.$emit('update_cart', response.data);
          },
          response => {
            this.loading = false;
          },
        )
      }
    },
    created() {
      this.ready = true;
    }
  });

  Vue.component('cart', {
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
              if (this.cart.quantity > 0 ) {
                this.ready = true;
              }
              this.$root.$emit('update_cart', this.cart);
            }
          )
        },
        changeQuantity(vid, quantity) {
          quantity = !quantity || quantity < 1 ? 1 : quantity;
          let stock = this.cart.items[vid].stock;
          if (quantity <= stock || stock == 0) {
            axios.get('/cart/set-product-quantity/' + vid, {
              params: {
                quantity: quantity,
              },
            }).then(
              response => {
                this.cart = response.data;
                this.initCart();
              }
            );
          }
        },
        removeItem(vid) {
          axios.get('/cart/remove-cart-item/' + vid).then(
            response => {
              this.ready = false;
              this.initCart();
            }
          );
        },
        numberFormat(number, decimals = 2, dec_point = '.', thousands_sep = ' ') {
          let s_number = Math.abs(
            parseInt(number = (+number || 0).toFixed(decimals))
          ) + "";
          let len = s_number.length;
          let tchunk = len > 3 ? len % 3 : 0;
          let ch_first = (tchunk ? s_number.substr(0, tchunk) + thousands_sep : '');
          let ch_rest = s_number.substr(tchunk).replace(/(\d\d\d)(?=\d)/g, '$1' + thousands_sep);
          let ch_last = decimals ? dec_point + (Math.abs(number) - s_number).toFixed(decimals).slice(2) : '';
          return ch_first + ch_rest + ch_last;
        },
        isEmpty(value) {
          if (!!value && value instanceof Array) {
            return value.length < 1
          }
          if (!!value && typeof value === 'object') {
            for (var key in value) {
              if (hasOwnProperty.call(value, key)) {
                return false
              }
            }
          }
          return !value
        }
      },
      created() {
        this.initCart();
      }
  });

})();
