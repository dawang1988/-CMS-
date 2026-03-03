/**
 * 订单相关接口
 * 
 * 字段统一使用后端数据库下划线命名（ss_order 表）：
 * order_no, user_id, store_id, room_id, order_type, start_time, end_time,
 * duration, price, total_amount, discount_amount, pay_amount, pay_type,
 * pay_time, status, coupon_id, remark
 * 
 * @module api/order
 */
import http from '@/utils/http.js'

export default {
	/**
	 * 创建订单
	 * 
	 * @param {Object} data - 订单数据
	 * @param {number} data.room_id - 房间ID
	 * @param {string} data.start_time - 开始时间
	 * @param {string} data.end_time - 结束时间
	 * @param {number} [data.store_id] - 门店ID
	 * @param {number} [data.pkg_id] - 套餐ID
	 * @param {number} [data.coupon_id] - 优惠券ID
	 * @param {number} [data.pay_type] - 支付方式 1微信 2余额
	 * @returns {Promise} 返回订单信息
	 */
	createOrder(data) {
		return http.post('/member/order/save', data)
	},
	
	/**
	 * 获取订单列表
	 * 
	 * @param {Object} params - 查询参数
	 * @param {number} params.pageNo - 页码
	 * @param {number} params.pageSize - 每页数量
	 * @param {number} [params.status] - 订单状态筛选
	 * @returns {Promise} 返回订单列表
	 */
	getOrderList(params) {
		return http.post('/member/order/getOrderList', params)
	},
	
	/**
	 * 获取订单详情
	 * 
	 * @param {number} id - 订单ID
	 * @returns {Promise} 返回订单详情
	 */
	getOrderDetail(id) {
		return http.get(`/member/order/getOrderInfo/${id}`)
	},
	
	/**
	 * 根据订单号获取订单详情
	 * 
	 * @param {string} order_no - 订单编号
	 * @returns {Promise} 返回订单详情
	 */
	getOrderByNo(order_no) {
		return http.get('/member/order/getOrderInfoByNo', { orderNo: order_no })
	},
	
	/**
	 * 获取当前进行中的订单
	 * 
	 * @returns {Promise} 返回当前订单信息
	 */
	getOrderInfo() {
		return http.get('/member/order/getOrderInfo')
	},
	
	/**
	 * 取消订单
	 * 
	 * @param {number} id - 订单ID
	 * @returns {Promise} 返回操作结果
	 */
	cancelOrder(id) {
		return http.post('/member/order/cancel', { id, order_id: id })
	},
	
	/**
	 * 订单支付
	 * 
	 * @param {Object} data - 支付参数
	 * @param {number} data.order_id - 订单ID
	 * @param {number} data.pay_type - 支付方式 1微信 2余额
	 * @returns {Promise} 返回支付参数
	 */
	payOrder(data) {
		return http.post('/member/order/pay', {
			order_id: data.order_id,
			pay_type: data.pay_type
		})
	},
	
	/**
	 * 订单续费
	 * 
	 * @param {Object} data - 续费参数
	 * @param {number} data.order_id - 订单ID
	 * @param {string} data.end_time - 新结束时间
	 * @param {number} [data.pay_type] - 支付方式 1微信 2余额
	 * @param {number} [data.duration] - 续费时长（分钟）
	 * @returns {Promise} 返回续费结果
	 */
	renewOrder(data) {
		return http.post('/member/order/renew', {
			order_id: data.order_id,
			end_time: data.end_time,
			pay_type: data.pay_type || 1,
			duration: data.duration
		})
	},
	
	/**
	 * 更换房间
	 * 
	 * @param {Object} data - 换房参数
	 * @param {number} data.order_id - 订单ID
	 * @param {number} data.room_id - 新房间ID
	 * @returns {Promise} 返回换房结果
	 */
	changeRoom(data) {
		return http.post('/member/order/changeRoom', {
			order_id: data.order_id,
			room_id: data.room_id
		})
	},
	
	/**
	 * 搜索订单
	 * 
	 * @param {string} keyword - 搜索关键词
	 * @returns {Promise} 返回匹配的订单列表
	 */
	searchOrder(keyword) {
		return http.get('/member/order/search', { keyword })
	},
	
	/**
	 * 获取房间信息
	 * 
	 * @param {number} room_id - 房间ID
	 * @returns {Promise} 返回房间信息
	 */
	getRoomInfo(room_id) {
		return http.post(`/member/index/getRoomInfo/${room_id}`, { room_id })
	},
	
	/**
	 * 预下单（计算价格）
	 * 
	 * @param {Object} data - 订单数据
	 * @param {number} data.room_id - 房间ID
	 * @param {string} data.start_time - 开始时间
	 * @param {string} data.end_time - 结束时间
	 * @param {number} [data.coupon_id] - 优惠券ID
	 * @returns {Promise} 返回价格计算结果
	 */
	preOrder(data) {
		return http.post('/member/order/preOrder', data)
	},
	
	/**
	 * 锁定订单
	 * 
	 * @param {Object} data - 锁定参数
	 * @param {number} data.room_id - 房间ID
	 * @param {string} data.start_time - 开始时间
	 * @param {string} data.end_time - 结束时间
	 * @returns {Promise} 返回锁定结果
	 */
	lockOrder(data) {
		return http.post('/member/order/lockWxOrder', data)
	},
	
	/**
	 * 开房间门
	 * 
	 * @param {string} orderKey - 订单凭证
	 * @returns {Promise} 返回开门结果
	 */
	openRoomDoor(orderKey) {
		return http.post(`/member/order/openRoomDoor?orderKey=${orderKey}`)
	},

	/**
	 * 提交评价
	 * 
	 * @param {Object} data - 评价数据（对应 ss_review 表）
	 * @param {number} data.order_id - 订单ID
	 * @param {number} data.store_id - 门店ID
	 * @param {number} [data.room_id] - 房间ID
	 * @param {number} data.score - 评分（1-5星）
	 * @param {string} data.content - 评价内容
	 * @param {string} [data.images] - 评价图片JSON
	 * @returns {Promise} 返回提交结果
	 */
	submitReview(data) {
		return http.post('/member/review/save', data)
	},

	/**
	 * 获取门店评价列表
	 * 
	 * @param {Object} params - 查询参数
	 * @param {number} params.store_id - 门店ID
	 * @param {number} params.pageNo - 页码
	 * @param {number} params.pageSize - 每页数量
	 * @returns {Promise} 返回评价列表
	 */
	getReviewList(params) {
		return http.get('/member/review/list', params)
	},

	/**
	 * 获取我的评价列表
	 * 
	 * @param {Object} params - 查询参数
	 * @param {number} params.pageNo - 页码
	 * @param {number} params.pageSize - 每页数量
	 * @returns {Promise} 返回我的评价列表
	 */
	getMyReviews(params) {
		return http.get('/member/review/myList', params)
	}
}
