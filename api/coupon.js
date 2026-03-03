/**
 * 优惠券相关接口模块
 * 
 * 字段统一使用后端数据库下划线命名：
 * - ss_coupon: name, type, amount, min_amount, start_time, end_time, store_id, room_class, status
 * - ss_user_coupon: user_id, coupon_id, status, use_time, order_id
 * 
 * type: 1=抵扣券, 2=满减券, 3=加时券
 * status(user_coupon): 0=未使用, 1=已使用, 2=已过期
 * 
 * @module api/coupon
 */
import http from '@/utils/http.js'

export default {
	/**
	 * 获取优惠券列表（领券中心）
	 * 
	 * @param {Object} params - 查询参数
	 * @param {Number} params.pageNo - 页码
	 * @param {Number} params.pageSize - 每页数量
	 * @returns {Promise} 返回优惠券列表
	 */
	getCouponList(params) {
		return http.get('/member/coupon/group-list', params)
	},
	
	/**
	 * 领取优惠券
	 * 
	 * @param {Number} coupon_id - 优惠券ID
	 * @returns {Promise} 返回领取结果
	 */
	receiveCoupon(coupon_id) {
		return http.post('/member/couponActive/putCoupon', { coupon_id })
	},
	
	/**
	 * 获取我的优惠券列表
	 * 
	 * @param {Object} params - 查询参数
	 * @param {Number} params.pageNo - 页码
	 * @param {Number} params.pageSize - 每页数量
	 * @param {Number} [params.status] - 状态筛选（0=未使用, 1=已使用, 2=已过期）
	 * @returns {Promise} 返回我的优惠券列表
	 */
	getMyCoupons(params) {
		return http.get('/member/user/getCouponPage', params)
	},
	
	/**
	 * 获取可用优惠券列表
	 * 
	 * @param {Object} params - 查询参数
	 * @param {Number} params.amount - 订单金额
	 * @param {Number} [params.store_id] - 门店ID
	 * @param {Number} [params.room_class] - 业态类型 0棋牌 1台球 2KTV
	 * @returns {Promise} 返回可用优惠券列表
	 */
	getAvailableCoupons(params) {
		return http.get('/member/coupon/available', params)
	},
	
	/**
	 * 团购券核销
	 * 
	 * @param {Object} data - 核销参数
	 * @param {String} data.code - 团购券码
	 * @param {String} data.platform - 平台标识
	 * @returns {Promise} 返回核销结果
	 */
	verifyGroupBuy(data) {
		return http.post('/member/coupon/verify', data)
	},
	
	/**
	 * 获取团购订单列表
	 * 
	 * @param {Object} params - 查询参数
	 * @param {Number} params.pageNo - 页码
	 * @param {Number} params.pageSize - 每页数量
	 * @returns {Promise} 返回团购订单列表
	 */
	getGroupBuyOrders(params) {
		return http.get('/member/coupon/group-list', params)
	}
}
