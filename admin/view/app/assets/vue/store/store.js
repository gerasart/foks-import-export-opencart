/* eslint-disable */
import Vue from 'vue';
import Vuex from 'vuex';

import axios from 'axios';
import * as qs from 'qs';

Vue.use(Vuex);

export default new Vuex.Store({
  state: {
    foks: window.foks || [],
  },
  mutations: {
    users(state, v) {
      state.users = v;
    },
    logs(state, v) {
      state.logs = v;
    },
    query(state, v) {
      state.query = v;
    },
    setter(state, object) {
      Object.entries(object).forEach(([key, value]) => {
        Vue.set(state, key, value);
      });
    },
  },
  actions: {
    sendRequest({state}, requestBody) {
      return axios.post(window.ajaxurl, qs.stringify(requestBody));
    },
    send({state}, requestBody) {
      if (requestBody.data) {
        return axios.post(requestBody.url, qs.stringify(requestBody.data));
      } else {
        return axios.post(requestBody.url);
      }
    },
    get({state}, requestBody) {
      return axios.get(requestBody.url);
    },
    sendApi({state}, requestBody, url) {
      return axios.post(url, qs.stringify(requestBody));
    },
  },
  getters: {}
});
