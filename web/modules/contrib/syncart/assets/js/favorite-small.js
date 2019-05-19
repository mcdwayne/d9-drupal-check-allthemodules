/**
 * @file
 * Vue app for small favorites.
 */

(function () {
  "use strict";

  Vue.component('favorite-small', {
    data() {
      return {
        total: 0,
        ready: false,
      }
    },
    methods: {
      initFavorite() {
        var favorites = this.$cookies.get('favorites');
        if (typeof(favorites) !== 'object' || favorites == null) {
          favorites = {};
        }
        this.total = Object.keys(favorites).length;
        this.ready = true;
      }
    },
    created() {
      setTimeout(() => {
        if (!this.$el.parentElement.offsetParent) {
          return false;
        }
        this.initFavorite();
        this.$root.$on('update_favorite', (total) => {
          this.total = total;
        });
      }, 10);
    }
  });


})();
