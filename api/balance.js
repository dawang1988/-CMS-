/**
 * 余额相关接口模块
 * 
 * 字段统一使用后端数据库下划线命名：
 * - ss_user: balance, gift_balance, score
 * - ss_balance_log: type, amount, balance_before, balance_after, remark
 * - ss_recharge_package: amount, gift_amount, total_amount
 * - ss_recharge_order: order_no, amount, gift_amount, pay_type, status
 * 
 * @module api/balance
 */
import http from '@/utils/http.js'

export default {
	/**
	 * 获取余额信息
	 * 
	 * @returns {Promise} 返回余额信息
	 * 返回字段: { balance, gift_balance, score }
	 */
	getBalance() {
		return http.get('/member/balance/info')
	},
	
	/**
	 * 余额充值
	 * 
	 * @param {Object} data - 充值参数
	 * @param {Number} data.package_id - 充值套餐ID
	 * @param {Number} [data.pay_type] - 支付方式 1微信
	 * @returns {Promise} 返回支付参数
	 */
	recharge(data) {
		return http.post('/member/balance/recharge', data)
	},
	
	/**
	 * 获取充值记录
	 * 
	 * @param {Object} params - 查询参数
	 * @param {Number} params.pageNo - 页码
	 * @param {Number} params.pageSize - 每页数量
	 * @returns {Promise} 返回充值记录列表
	 * 列表字段: { order_no, amount, gift_amount, status, pay_time, create_time }
	 */
	getRechargeList(params) {
		return http.get('/member/balance/rechargeList', params)
	},
	
	/**
	 * 获取消费记录（余额变动记录）
	 * 
	 * @param {Object} params - 查询参数
	 * @param {Number} params.pageNo - 页码
	 * @param {Number} params.pageSize - 每页数量
	 * @returns {Promise} 返回消费记录列表
	 * 列表字段: { type, amount, balance_before, balance_after, remark, create_time }
	 * type: 1=充值, 2=消费, 3=退款, 4=赠送
	 */
	getConsumeList(params) {
		return http.get('/member/balance/consumeList', params)
	},
	
	/**
	 * 获取充值套餐列表
	 * 
	 * @returns {Promise} 返回充值套餐列表
	 * 列表字段: { id, name, amount, gift_amount, total_amount, description, status, sort }
	 */
	getRechargePackages() {
		return http.get('/member/balance/packages')
	}
}
