import Vue from 'vue'
import './store/mixin';
import store from './store/store';
// import router from './store/router';
import './store/mixin';

import Antd from 'ant-design-vue';
Vue.config.productionTip = false;

Vue.use(Antd);
// Vue.use(Select);
// Vue.use(Button);

//Template
import app from './App';

setTimeout(() => {
    const main = document.querySelector('#foks_ie');
    if (main) {
        new Vue({
            el: main,
            render: h => h(app),
            store,
        });
    }
}, 0);

export default () => {
    return {app, store}
};
