/**
 * 通用接口
 * 
 * 包含系统级的通用功能：
 * - 文件上传
 * - 系统配置
 * - 意见反馈
 * - 帮助中心
 * 
 * @module api/common
 */
import http from '@/utils/http.js'

export default {
	/**
	 * 上传文件
	 * 
	 * 上传图片或其他文件到服务器
	 * 返回文件的访问URL
	 * 
	 * @param {string} filePath - 本地文件路径
	 * @returns {Promise} 返回上传结果，包含文件URL
	 * 
	 * @example
	 * // 选择图片
	 * const { tempFilePaths } = await uni.chooseImage({ count: 1 })
	 * // 上传图片
	 * const res = await api.common.uploadFile(tempFilePaths[0])
	 * // 获取图片URL
	 * const imageUrl = res.data.url
	 */
	uploadFile(filePath) {
		return http.upload(filePath)
	},
	
	/**
	 * 获取系统配置
	 * 
	 * 获取指定的系统配置项
	 * 如：客服电话、关于我们、用户协议等
	 * 
	 * @param {string} key - 配置键名
	 * @returns {Promise} 返回配置值
	 */
	getConfig(key) {
		return http.get('/system/config/get', { key })
	},
	
	/**
	 * 提交意见反馈
	 * 
	 * 用户提交意见或建议
	 * 
	 * @param {Object} data - 反馈数据
	 * @param {string} data.content - 反馈内容
	 * @param {string[]} [data.images] - 反馈图片URL数组
	 * @returns {Promise} 返回提交结果
	 */
	feedback(data) {
		return http.post('/system/feedback/create', data)
	},
	
	/**
	 * 获取帮助列表
	 * 
	 * 获取帮助中心的文章列表
	 * 
	 * @returns {Promise} 返回帮助文章列表
	 */
	getHelpList() {
		return http.get('/system/help/list')
	},
	
	/**
	 * 获取帮助详情
	 * 
	 * 获取指定帮助文章的详细内容
	 * 
	 * @param {number} id - 帮助文章ID
	 * @returns {Promise} 返回帮助文章详情
	 */
	getHelpDetail(id) {
		return http.get('/system/help/get', { id })
	}
}
