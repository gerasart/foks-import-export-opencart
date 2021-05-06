import Vue from 'vue';

Vue.mixin({
    data() {
        return {
            big_url: 'https://my.foks.biz/s/pb/f?key=547d2e64-c4b9-417e-bd28-3760c25409cd&type=yml_catalog&ext=xml',
            short_url: 'https://my.foks.biz/s/pb/f?key=547d2e64-c4b9-417e-bd28-3760c25409cd&type=drop_foks&ext=xml'
        }
    },
    methods: {
        ajaxUrl() {
            return window.ajaxurl;
        },
        currentPath() {
            return location.pathname;
        },
        colorLog(message, color) {
            color = color || "black";

            switch (color) {
                case "success":
                    color = "Green";
                    break;
                case "info":
                    color = "DodgerBlue";
                    break;
                case "error":
                    color = "Red";
                    break;
                case "warning":
                    color = "Orange";
                    break;
            }

            console.log("%c" + JSON.stringify(message), "color:" + color);
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
