/**
 * @file
 * Vue app for favorites.
 */

(function () {
  "use strict";

  Vue.component('favorite-field', {
    props: {
      pid: 0,
    },
    data() {
      return {
        productsLoaded: false,
        fav: false,
      }
    },
    methods: {
      toggleFav() {
        if (this.fav) {
          this.deleteFav();
        }
        else {
          this.addFav();
        }
      },
      addFav() {
        this.fav = true;
        this.likeClicked = true;
        var favorites = this.$cookies.get('favorites');
        if (typeof(favorites) !== 'object' || favorites == null) {
          favorites = {};
        }
        favorites[this.pid] = true;
        this.$cookies.set('favorites', favorites, '30d');
        var favorites_amount = Object.keys(favorites).length;
        this.$root.$emit('update_favorite', favorites_amount);
      },
      // Удалить из избранное.
      deleteFav() {
        this.fav = false;
        this.likeClicked = false;
        var favorites = this.$cookies.get('favorites');
        if (typeof(favorites) !== 'object' || favorites == null) {
          favorites = {};
        }
        delete favorites[this.pid];
        this.$cookies.set('favorites', favorites, '30d');
        if (window.location.pathname == '/favorites') {
          window.location.reload();
        }
        else {
          var favorites_amount = Object.keys(favorites).length;
          this.$root.$emit('update_favorite', favorites_amount);
        }
      },
    },
    created() {
      this.productsLoaded = true;
      let favorites = this.$cookies.get('favorites');
      if (favorites !== null && typeof(favorites) == 'object' && favorites.hasOwnProperty(this.pid)) {
        this.fav = true;
      }
      if (localStorage.watched) {
        var array = localStorage.watched.split('|');
      }
      else {
        var array = [];
      }
      let index = array.indexOf(this.pid.toString());
      if (index === -1) {
        array.push(this.pid);
        localStorage.watched = array.join('|');
      }
    }
  });

  Vue.component('favorites', {
    props: {
      ids: Array,
    },
    data() {
      return {
        ready: false,
        loading: false,
        inCart: false,
      }
    },
    methods: {
      toCart() {
        this.loading = true;
        axios.get('/cart/add-items', {params: {'vids': JSON.stringify(this.ids)}}).then(
          response => {
            this.loading = false;
            this.inCart = true;
            this.$root.$emit('update_cart', response.data);
          },
          response => {
            this.loading = false;
          },
        )
      },
      clearFavorites() {
        this.$cookies.set('favorites', [], '30d');
        window.location.reload();
      },
    },
    created() {
      this.ready = true;
    }
  });

})();
