/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');

import VueRouter from 'vue-router';
import vSelect from "vue-select";
import { BootstrapVue, IconsPlugin } from 'bootstrap-vue'

import routes from './routes';
import HomeComponent from "./components/HomeComponent.vue";

import "vue-select/dist/vue-select.css";
import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap-vue/dist/bootstrap-vue.css'

import "./assets/css/style.scss";

Vue.use(VueRouter);
// Install BootstrapVue
Vue.use(BootstrapVue)
// Optionally install the BootstrapVue icon components plugin
Vue.use(IconsPlugin)

Vue.component("v-select", vSelect);

const router = new VueRouter({  routes });

Vue.router = router;

const app = new Vue({ el: '#app', router: router, render: t => t(HomeComponent) });