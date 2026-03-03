/**
 * API 统一导出模块
 * 
 * 本文件是前端 API 的统一入口，整合所有业务模块的接口
 * 在页面中通过 import api from '@/api' 引入使用
 * 
 * 使用示例：
 * import api from '@/api'
 * 
 * // 调用用户接口
 * api.user.login({ code: 'xxx' })
 * 
 * // 调用订单接口
 * api.order.createOrder({ roomId: 1, startTime: '...' })
 * 
 * // 直接调用 HTTP 方法
 * api.get('/some/url', { param: 'value' })
 */

import http from '@/utils/http.js'
import user from './user.js'       // 用户相关接口
import door from './door.js'       // 门禁相关接口
import order from './order.js'     // 订单相关接口
import coupon from './coupon.js'   // 优惠券相关接口
import balance from './balance.js' // 余额相关接口
import common from './common.js'   // 通用接口

/**
 * API 对象
 * 包含所有业务模块的接口和基础 HTTP 方法
 */
const api = {
	user,     // 用户模块：登录、获取用户信息、更新资料等
	door,     // 门禁模块：开门、获取密码等
	order,    // 订单模块：下单、支付、续费、取消等
	coupon,   // 优惠券模块：领取、使用、查询等
	balance,  // 余额模块：充值、查询余额、余额记录等
	common,   // 通用模块：上传、配置、反馈等
	
	/**
	 * GET 请求
	 * @param {string} url - 请求地址
	 * @param {object} data - 请求参数
	 * @param {object} options - 额外选项
	 */
	get(url, data, options) {
		return http.get(url, data, options)
	},
	
	/**
	 * POST 请求
	 * @param {string} url - 请求地址
	 * @param {object} data - 请求数据
	 * @param {object} options - 额外选项
	 */
	post(url, data, options) {
		return http.post(url, data, options)
	},
	
	/**
	 * PUT 请求
	 * @param {string} url - 请求地址
	 * @param {object} data - 请求数据
	 * @param {object} options - 额外选项
	 */
	put(url, data, options) {
		return http.put(url, data, options)
	},
	
	/**
	 * DELETE 请求
	 * @param {string} url - 请求地址
	 * @param {object} data - 请求参数
	 * @param {object} options - 额外选项
	 */
	delete(url, data, options) {
		return http.delete(url, data, options)
	},
	
	/**
	 * 文件上传
	 * @param {string} filePath - 本地文件路径
	 * @param {object} options - 上传选项
	 */
	upload(filePath, options) {
		return http.upload(filePath, options)
	}
}

export default api
