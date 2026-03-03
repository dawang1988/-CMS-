import App from './App'
import http from '@/utils/http'
import uView from 'uview-ui'
import themeMixin from '@/mixins/themeMixin.js'

// #ifndef VUE3
import Vue from 'vue'
Vue.config.productionTip = false
Vue.prototype.$http = http
Vue.use(uView)

// 全局注册主题色 mixin，让所有页面自动支持主题色
Vue.mixin(themeMixin)

App.mpType = 'app'
const app = new Vue({
	...App
})
app.$mount()
// #endif

// #ifdef VUE3
import { createSSRApp } from 'vue'

export function createApp() {
	const app = createSSRApp(App)
	app.config.globalProperties.$http = http
	// Vue3 全局 mixin
	app.mixin(themeMixin)
	return {
		app
	}
}
// #endif
