import Vue from 'vue';

Vue.mixin({
  methods: {
    ajaxUrl() {
      return window.ajaxurl;
    },
    currentPath() {
      return location.pathname;
    },
    openNotification(title, description = '') {
      this.$notification.open({
        message: title,
        description: description,
        placement: 'bottomRight',
        bottom: '50px',
        duration: 3,
      });
    },
  }
});
