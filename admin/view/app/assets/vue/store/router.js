import Vue from 'vue';
import VueRouter from 'vue-router';

Vue.use(VueRouter);

// Pages
import AuthUsers from '../components/AuthUsers/AuthUsers'
import Logs from '../components/Logs/Logs'
import Board from '../components/Board/Board'

const router = new VueRouter({
    mode: 'history',
    base: '/wp-admin/plugins.php?page=users_control#',
    routes: [
        {
            path: 'users',
            name: 'users',
            component: AuthUsers,
        },
        {
            path: 'logs',
            name: 'logs',
            component: Logs,
        },
        {
            path: 'board',
            name: 'board',
            component: Board,
        },
    ],
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition
        } else {
            return {x: 0, y: 0}
        }
    }
});

export default router;
