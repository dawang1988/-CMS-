/**
 * 门店与房间相关接口模块
 * 
 * 字段统一使用后端数据库下划线命名：
 * - ss_store: name, address, phone, longitude, latitude, images, wifi_name, wifi_password, business_hours, description, status
 * - ss_room: store_id, name, room_no, type, price, images, facilities, lock_no, status, sort
 * 
 * @module api/door
 */
import http from '@/utils/http.js'

// ==================== 门店相关接口 ====================

/**
 * 获取门店列表
 * 
 * @param {Object} params - 查询参数
 * @param {Number} params.pageNo - 页码
 * @param {Number} params.pageSize - 每页数量
 * @param {String} [params.cityName] - 城市名称
 * @param {Number} [params.lat] - 纬度
 * @param {Number} [params.lon] - 经度
 * @param {String} [params.name] - 门店名称关键词
 * @returns {Promise} 返回门店列表数据
 */
export function getStoreList(params) {
	return http.post('/member/index/getStoreList', params)
}

/**
 * 获取门店管理列表（管理员接口）
 * 
 * @param {Object} params - 查询参数
 * @param {Number} params.pageNo - 页码
 * @param {Number} params.pageSize - 每页数量
 * @param {String} [params.name] - 门店名称关键词
 * @returns {Promise} 返回门店管理列表
 */
export function getStorePageList(params) {
	return http.post('/member/store/getPageList', params)
}

/**
 * 打开门店大门（管理员接口）
 * 
 * @param {Number} store_id - 门店ID
 * @returns {Promise} 返回开门结果
 */
export function openStoreDoorAdmin(store_id) {
	return http.post('/member/store/openStoreDoor/' + store_id, { id: store_id })
}

/**
 * 获取轮播图列表
 * 
 * @returns {Promise} 返回轮播图列表
 */
export function getBannerList() {
	return http.get('/member/index/getBannerList')
}

/**
 * 获取门店详情
 * 
 * @param {Number} id - 门店ID
 * @returns {Promise} 返回门店详情
 */
export function getStoreDetail(id) {
	return http.get('/store/store/get', { id })
}

// ==================== 房间相关接口 ====================

/**
 * 获取房间列表
 * 
 * @param {Number} store_id - 门店ID
 * @returns {Promise} 返回房间列表
 */
export function getRoomList(store_id) {
	return http.get('/store/room/list', { store_id })
}

/**
 * 获取房间详情
 * 
 * @param {Number} id - 房间ID
 * @returns {Promise} 返回房间详情
 */
export function getRoomDetail(id) {
	return http.get('/store/room/get', { id })
}

/**
 * 检查房间可用性
 * 
 * @param {Object} data - 检查参数
 * @param {Number} data.room_id - 房间ID
 * @param {String} data.start_time - 开始时间
 * @param {String} data.end_time - 结束时间
 * @returns {Promise} 返回可用性检查结果
 */
export function checkRoomAvailable(data) {
	return http.post('/store/room/check', data)
}

// ==================== 门禁控制接口 ====================

/**
 * 开房间门
 * 
 * @param {Number} order_id - 订单ID
 * @returns {Promise} 返回开门结果
 */
export function openDoor(order_id) {
	return http.post('/member/order/openRoomDoor', { order_id })
}

/**
 * 开门店大门
 * 
 * @param {Number} store_id - 门店ID
 * @returns {Promise} 返回开门结果
 */
export function openStoreDoor(store_id) {
	return http.post('/store/device/open', { store_id })
}

// ==================== 门店管理接口 ====================

/**
 * 保存门店信息
 * 
 * @param {Object} data - 门店数据（字段与 ss_store 表一致）
 * @returns {Promise} 返回保存结果
 */
export function saveStore(data) {
	return http.post('/store/store/save', data)
}

/**
 * 上传门店图片
 * 
 * @param {String} filePath - 本地图片路径
 * @returns {Promise} 返回上传后的图片URL
 */
export function uploadStoreImg(filePath) {
	return http.upload(filePath)
}

// ==================== 兼容别名 ====================
export const getDoorList = getStoreList
export const getDoorInfo = getStoreDetail

/**
 * 默认导出所有接口
 */
export default {
	getStoreList,
	getDoorList: getStoreList,
	getStorePageList,
	openStoreDoorAdmin,
	getBannerList,
	getStoreDetail,
	getDoorInfo: getStoreDetail,
	getRoomList,
	getRoomDetail,
	checkRoomAvailable,
	openDoor,
	openStoreDoor,
	saveStore,
	uploadStoreImg
}
