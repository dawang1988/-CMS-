/**
 * 用户相关接口
 * 
 * 包含用户认证和个人信息管理：
 * - 微信登录
 * - 获取/更新用户信息
 * - 绑定手机号
 * - 发送验证码
 * 
 * 字段统一使用后端数据库下划线命名
 * 
 * @module api/user
 */
import http from '@/utils/http.js'

export default {
	/**
	 * 微信登录
	 * 
	 * @param {Object} data - 登录参数
	 * @param {string} data.code - 微信登录凭证
	 * @returns {Promise} 返回用户信息和 token
	 */
	login(data) {
		return http.post('/member/user/login', data)
	},
	
	/**
	 * 获取用户信息
	 * 
	 * 返回字段与 ss_user 表一致：
	 * id, nickname, avatar, phone, balance, gift_balance, score, vip_level, vip_name, status
	 * 
	 * @returns {Promise} 返回用户信息
	 */
	getUserInfo() {
		return http.get('/member/user/info')
	},
	
	/**
	 * 更新用户信息
	 * 
	 * @param {Object} data - 用户信息
	 * @param {string} [data.nickname] - 昵称
	 * @param {string} [data.avatar] - 头像URL
	 * @param {string} [data.phone] - 手机号
	 * @returns {Promise} 返回更新结果
	 */
	updateUserInfo(data) {
		return http.post('/member/user/update', data)
	},
	
	/**
	 * 绑定手机号
	 * 
	 * @param {Object} data - 绑定参数
	 * @param {string} data.mobile - 手机号（后端字段名为 mobile）
	 * @param {string} data.code - 短信验证码
	 * @returns {Promise} 返回绑定结果
	 */
	bindPhone(data) {
		return http.post('/member/user/update', {
			mobile: data.mobile || data.phone,
			code: data.code
		})
	},
	
	/**
	 * 发送短信验证码
	 * 
	 * @param {string} mobile - 手机号
	 * @returns {Promise} 返回发送结果
	 */
	sendSmsCode(mobile) {
		return http.post('/member/user/send-code', { mobile })
	}
}
