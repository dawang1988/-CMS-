// #ifndef VUE3
import Vue from 'vue'
import Vuex from 'vuex'
Vue.use(Vuex)
const store = new Vuex.Store({
// #endif

// #ifdef VUE3
import { createStore } from 'vuex'
const store = createStore({
// #endif
	state: {
		// 登录状态
		hasLogin: false,
		loginProvider: "",
		openid: null,
		token: '',
		userInfo: {},
		
		// 门店状态
		currentStoreId: '',
		currentStore: null,
	},
	mutations: {
		// 登录相关
		login(state, provider) {
			state.hasLogin = true
			state.loginProvider = provider || ''
		},
		logout(state) {
			state.hasLogin = false
			state.openid = null
			state.token = ''
			state.userInfo = {}
			uni.removeStorageSync('token')
			uni.removeStorageSync('userInfo')
			uni.removeStorageSync('userDatatoken')
		},
		setOpenid(state, openid) {
			state.openid = openid
		},
		setToken(state, token) {
			state.token = token
			uni.setStorageSync('token', token)
		},
		setUserInfo(state, userInfo) {
			state.userInfo = userInfo
			uni.setStorageSync('userInfo', userInfo)
		},
		
		// 门店相关
		setCurrentStoreId(state, storeId) {
			state.currentStoreId = storeId
			uni.setStorageSync('global_store_id', storeId)
		},
		setCurrentStore(state, storeData) {
			state.currentStore = storeData
		},
	},
	getters: {
		isLoggedIn(state) {
			return state.hasLogin
		}
	},
	actions: {
		// 初始化登录状态（从缓存恢复）
		initLoginState({ commit }) {
			try {
				const token = uni.getStorageSync('token')
				const userInfo = uni.getStorageSync('userInfo')
				if (token) {
					commit('setToken', token)
					commit('login', '')
				}
				if (userInfo && typeof userInfo === 'object') {
					commit('setUserInfo', userInfo)
				}
				const storeId = uni.getStorageSync('global_store_id')
				if (storeId) {
					commit('setCurrentStoreId', storeId)
				}
			} catch (e) {
				// 缓存数据损坏，清除并重置
				uni.removeStorageSync('token')
				uni.removeStorageSync('userInfo')
				uni.removeStorageSync('global_store_id')
			}
		},
		
		getUserOpenId: async function({ commit, state }) {
			return await new Promise((resolve, reject) => {
				if (state.openid) {
					resolve(state.openid)
				} else {
					uni.login({
						success: (data) => {
							commit('login')
							// 实际项目中应调用后端接口获取 openid
							resolve(data.code)
						},
						fail: (err) => {
							reject(err)
						}
					})
				}
			})
		}
	}
})

export default store
