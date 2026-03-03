import config from '@/config/index.js'

class Http {
	constructor() {
		this.baseUrl = config.baseUrl
		this.timeout = config.timeout
	}
	
	// 请求拦截器
	interceptRequest(options) {
		// 添加 token
		const token = uni.getStorageSync('token')
		if (token) {
			options.header = {
				...options.header,
				'Authorization': 'Bearer ' + token
			}
		}
		
		// 添加租户ID
		options.header = {
			...options.header,
			'tenant-id': config.tenantId
		}
		
		// 添加完整 URL
		if (!options.url.startsWith('http')) {
			options.url = this.baseUrl + options.url
		}
		
		// 打印请求信息（调试模式）
		if (config.debug) {
			console.log('【请求】', options.url)
			console.log('【参数】', options.data)
		}
		
		return options
	}
	
	// 响应拦截器
	interceptResponse(response, resolve, reject) {
		// 打印响应信息（调试模式）
		if (config.debug) {
			console.log('【响应】', response)
		}
		
		const { statusCode, data } = response
		
		// HTTP 状态码判断
		if (statusCode !== 200) {
			uni.showToast({
				title: '网络请求失败',
				icon: 'none',
				duration: 2000
			})
			reject(response)
			return
		}
		
		// 业务状态码判断
		if (data.code === 0 || data.code === 200) {
			// 返回完整的响应对象，包含 code、msg、data
			resolve(data)
		} else if (data.code === 401) {
			// token 失效，清除登录信息但不自动跳转
			
			// 清除登录信息
			uni.removeStorageSync('token')
			uni.removeStorageSync('userInfo')
			uni.removeStorageSync('userDatatoken')
			
			// 更新全局状态
			const app = getApp()
			if (app && app.globalData) {
				app.globalData.isLogin = false
				app.globalData.token = ''
				app.globalData.userDatatoken = {}
			}
			
			// 不自动跳转，让业务层处理
			reject(data)
		} else {
			// 其他错误，不自动弹窗，让业务层处理
			reject(data)
		}
	}
	
	// 通用请求方法
	request(options = {}) {
		return new Promise((resolve, reject) => {
			// 请求拦截
			options = this.interceptRequest(options)
			
			// 发起请求
			uni.request({
				...options,
				timeout: this.timeout,
				success: (response) => {
					this.interceptResponse(response, resolve, reject)
				},
				fail: (error) => {
					if (config.debug) console.error('【请求失败】', error)
					uni.showToast({
						title: '网络请求失败',
						icon: 'none',
						duration: 2000
					})
					reject(error)
				}
			})
		})
	}
	
	// GET 请求
	get(url, data = {}, options = {}) {
		return this.request({
			url,
			data,
			method: 'GET',
			...options
		})
	}
	
	// POST 请求
	post(url, data = {}, options = {}) {
		return this.request({
			url,
			data,
			method: 'POST',
			header: {
				'Content-Type': 'application/json',
				...options.header
			},
			...options
		})
	}
	
	// PUT 请求
	put(url, data = {}, options = {}) {
		return this.request({
			url,
			data,
			method: 'PUT',
			header: {
				'Content-Type': 'application/json',
				...options.header
			},
			...options
		})
	}
	
	// DELETE 请求
	delete(url, data = {}, options = {}) {
		return this.request({
			url,
			data,
			method: 'DELETE',
			...options
		})
	}
	
	// 文件上传
	upload(filePath, options = {}) {
		return new Promise((resolve, reject) => {
			const token = uni.getStorageSync('token')
			
			uni.uploadFile({
				url: config.uploadUrl,
				filePath,
				name: 'file',
				header: {
					'Authorization': 'Bearer ' + token,
					'tenant-id': config.tenantId
				},
				success: (response) => {
					const data = JSON.parse(response.data)
					if (data.code === 0 || data.code === 200) {
						resolve(data.data)
					} else {
						uni.showToast({
							title: data.msg || '上传失败',
							icon: 'none'
						})
						reject(data)
					}
				},
				fail: (error) => {
					uni.showToast({
						title: '上传失败',
						icon: 'none'
					})
					reject(error)
				}
			})
		})
	}
}

export default new Http()

/**
 * 兼容 http-legacy.js 的回调风格 API
 * 签名: legacyRequest(url, urltype, method, data, token, message, successBack, failBack)
 * 这样旧页面只需改 import 路径，不需要改业务代码
 */
export function legacyRequest(url, urltype, method, data, token, message, successBack, failBack) {
	const app = getApp()

	if (message && message !== '') {
		uni.showLoading({ mask: true, title: message })
	}

	// token 兼容：优先用传入的，再取 globalData，最后取 storage
	let headerToken = token || ''
	if (!headerToken && app && app.globalData && app.globalData.userDatatoken) {
		headerToken = app.globalData.userDatatoken.accessToken || app.globalData.userDatatoken.access_token || ''
	}
	if (!headerToken) {
		headerToken = uni.getStorageSync('token') || ''
	}

	const fullUrl = url.startsWith('http') ? url : config.baseUrl + url

	uni.request({
		url: fullUrl,
		data: data,
		header: {
			'tenant-id': config.tenantId,
			'Content-Type': 'application/json',
			'Authorization': 'Bearer ' + headerToken
		},
		method: (method || 'GET').toUpperCase(),
		success: function(res) {
			if (res.statusCode === 200) {
				if (res.data.code === 401) {
					// 401：清除登录状态
					if (app && app.globalData) {
						app.globalData.isLogin = false
						app.globalData.token = ''
						app.globalData.userDatatoken = {}
					}
					uni.removeStorageSync('token')
					uni.removeStorageSync('userDatatoken')
					if (typeof failBack === 'function') failBack(res.data)
				} else {
					if (typeof successBack === 'function') successBack(res.data)
				}
			} else if (res.statusCode === 401) {
				if (app && app.globalData) {
					app.globalData.isLogin = false
					app.globalData.token = ''
					app.globalData.userDatatoken = {}
				}
				uni.removeStorageSync('token')
				uni.removeStorageSync('userDatatoken')
				if (typeof failBack === 'function') failBack(res.data || res)
			} else {
				if (typeof failBack === 'function') failBack(res.data || res)
			}
			if (message && message !== '') {
				setTimeout(() => uni.hideLoading(), 500)
			}
		},
		fail: function(err) {
			if (message && message !== '') {
				setTimeout(() => uni.hideLoading(), 500)
			}
			if (typeof failBack === 'function') failBack(err)
		}
	})
}

export function legacyUploadFile(url, data, message, success, fail) {
	if (message && message !== '') {
		uni.showLoading({ title: message })
	}
	uni.showNavigationBarLoading()

	const token = uni.getStorageSync('token') || ''

	uni.uploadFile({
		url: config.baseUrl + url,
		filePath: data.temFile,
		name: data.file,
		header: {
			'Content-Type': 'multipart/form-data',
			'Authorization': 'Bearer ' + token,
			'tenant-id': config.tenantId
		},
		success: function(res) {
			uni.hideNavigationBarLoading()
			if (res.statusCode === 200) {
				if (typeof success === 'function') success(typeof res.data === 'string' ? JSON.parse(res.data) : res.data)
			} else {
				if (typeof fail === 'function') fail(res)
			}
		},
		fail: function(err) {
			uni.hideNavigationBarLoading()
			if (message && message !== '') uni.hideLoading()
			if (typeof fail === 'function') fail(err)
		}
	})
}
