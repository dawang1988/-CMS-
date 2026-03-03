/**
 * 通用格式化工具函数
 * 统一管理日期、金额等格式化逻辑
 */

import {
  ROOM_TYPE_MAP,
  ROOM_CLASS_MAP,
  ORDER_STATUS_MAP,
  PAY_TYPE_MAP,
  COUPON_TYPE_MAP,
  COUPON_STATUS_MAP,
  PRODUCT_ORDER_STATUS_MAP,
  USER_TYPE_MAP,
  BALANCE_TYPE_MAP,
  ROOM_STATUS_MAP
} from './constants'

/**
 * 格式化日期字符串，只保留日期部分 YYYY-MM-DD
 * @param {string} dateStr - 日期字符串
 * @returns {string}
 */
export function formatDate(dateStr) {
  if (!dateStr) return ''
  return dateStr.substring(0, 10)
}

/**
 * 格式化日期时间 YYYY-MM-DD HH:mm
 * @param {string|Date} date - 日期
 * @returns {string}
 */
export function formatDateTime(date) {
  if (!date) return ''
  const d = typeof date === 'string' ? new Date(date.replace(/-/g, '/')) : date
  const pad = n => n < 10 ? '0' + n : n
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`
}

/**
 * 格式化时间 HH:mm
 * @param {string} timeStr - 时间字符串 "YYYY-MM-DD HH:mm:ss"
 * @returns {string}
 */
export function formatTime(timeStr) {
  if (!timeStr) return ''
  const parts = timeStr.split(' ')
  if (parts.length < 2) return ''
  const time = parts[1].split(':')
  return `${time[0] || ''}:${time[1] || ''}`
}

/**
 * 格式化金额，保留两位小数
 * @param {number|string} amount - 金额
 * @returns {string}
 */
export function formatMoney(amount) {
  if (amount === null || amount === undefined) return '0.00'
  return Number(amount).toFixed(2)
}

/**
 * 分转元
 * @param {number} fen - 分
 * @returns {string}
 */
export function fenToYuan(fen) {
  if (!fen) return '0.00'
  return (fen / 100).toFixed(2)
}

/**
 * 获取房间类型名称
 * @param {number} type - 房间类型
 * @returns {string}
 */
export function getRoomTypeName(type) {
  return ROOM_TYPE_MAP[type] || '未知'
}

/**
 * 获取房间业态名称
 * @param {number} roomClass - 房间业态
 * @returns {string}
 */
export function getRoomClassName(roomClass) {
  return ROOM_CLASS_MAP[roomClass] !== undefined ? ROOM_CLASS_MAP[roomClass] : '不限制'
}

/**
 * 获取订单状态名称
 * @param {number} status - 订单状态
 * @returns {string}
 */
export function getOrderStatusName(status) {
  return ORDER_STATUS_MAP[status] || '未知'
}

/**
 * 获取支付方式名称
 * @param {number} payType - 支付方式
 * @returns {string}
 */
export function getPayTypeName(payType) {
  return PAY_TYPE_MAP[payType] || '未知'
}

/**
 * 获取优惠券类型名称
 * @param {number} type - 优惠券类型
 * @returns {string}
 */
export function getCouponTypeName(type) {
  return COUPON_TYPE_MAP[type] || '未知'
}

/**
 * 获取优惠券状态名称
 * @param {number} status - 优惠券状态
 * @returns {string}
 */
export function getCouponStatusName(status) {
  return COUPON_STATUS_MAP[status] || '未知'
}

/**
 * 获取商品订单状态名称
 * @param {number} status - 商品订单状态
 * @returns {string}
 */
export function getProductOrderStatusName(status) {
  return PRODUCT_ORDER_STATUS_MAP[status] || '未知'
}

/**
 * 获取用户类型名称
 * @param {number} userType - 用户类型
 * @returns {string}
 */
export function getUserTypeName(userType) {
  return USER_TYPE_MAP[userType] || '普通用户'
}

/**
 * 获取余额变动类型名称
 * @param {number} type - 余额变动类型
 * @returns {string}
 */
export function getBalanceTypeName(type) {
  return BALANCE_TYPE_MAP[type] || '其他'
}

/**
 * 获取房间状态名称
 * @param {number} status - 房间状态
 * @returns {string}
 */
export function getRoomStatusName(status) {
  return ROOM_STATUS_MAP[status] || '未知'
}

/**
 * 合并日期时间显示 (MM-DD HH:mm - HH:mm)
 * @param {string} startTime - 开始时间
 * @param {string} endTime - 结束时间
 * @returns {string}
 */
export function combineDateTime(startTime, endTime) {
  if (!startTime) return ''
  const startParts = startTime.split(' ')
  const endParts = endTime ? endTime.split(' ') : []
  
  const startDate = startParts[0] || ''
  const startT = startParts[1] ? startParts[1].substring(0, 5) : ''
  const endT = endParts[1] ? endParts[1].substring(0, 5) : ''
  
  // 格式化日期为 MM-DD
  const dateParts = startDate.split(/[-/]/)
  const formattedDate = dateParts.length >= 3 ? `${dateParts[1]}-${dateParts[2]}` : startDate
  
  return endT ? `${formattedDate} ${startT} - ${endT}` : `${formattedDate} ${startT}`
}

/**
 * 计算两个时间之间的小时数
 * @param {string} startTime - 开始时间
 * @param {string} endTime - 结束时间
 * @returns {number}
 */
export function calculateHours(startTime, endTime) {
  if (!startTime || !endTime) return 0
  const start = new Date(startTime.replace(/-/g, '/'))
  const end = new Date(endTime.replace(/-/g, '/'))
  const diff = end - start
  return Math.round(diff / (1000 * 60 * 60) * 10) / 10
}

/**
 * 添加小时到时间
 * @param {string} timeStr - 时间字符串
 * @param {number} hours - 要添加的小时数
 * @returns {string}
 */
export function addHours(timeStr, hours) {
  if (!timeStr) return ''
  const date = new Date(timeStr.replace(/-/g, '/'))
  date.setTime(date.getTime() + hours * 60 * 60 * 1000)
  return formatDateTime(date)
}
