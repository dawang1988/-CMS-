/**
 * 通用工具函数（从原项目完整迁移）
 */

/**
 * 格式化时间
 * @param {Date} date - 日期对象
 * @returns {String} 格式化后的时间字符串 YYYY/MM/DD HH:mm:ss
 */
export function formatTime(date) {
	if (!date) return ''
	
	const year = date.getFullYear()
	const month = date.getMonth() + 1
	const day = date.getDate()
	const hour = date.getHours()
	const minute = date.getMinutes()
	const second = date.getSeconds()
	
	return `${[year, month, day].map(formatNumber).join('/')} ${[hour, minute, second].map(formatNumber).join(':')}`
}

/**
 * 数字补零
 * @param {Number} n - 数字
 * @returns {String} 补零后的字符串
 */
export function formatNumber(n) {
	n = n.toString()
	return n[1] ? n : `0${n}`
}

/**
 * 手机号码验证（中国）
 * @param {String} value - 手机号
 * @returns {Boolean} 是否有效
 */
export function checkPhone(value) {
	const reg = /^(13[0-9]|14[01456879]|15[0-35-9]|16[2567]|17[0-8]|18[0-9]|19[0-35-9])\d{8}$/
	return reg.test(value)
}

/**
 * 获取设备类型名称
 * @param {Number} type - 设备类型
 * @returns {String} 设备类型名称
 */
export function getDeviceTypeName(type) {
	const deviceTypes = {
		1: '磁力锁门禁',
		2: '空开',
		3: '云喇叭',
		4: '灯具',
		5: '智能锁',
		6: '智能锁网关',
		7: '插座',
		8: '锁球器控制器（12V）',
		10: '智能语音喇叭',
		11: '二维码识别器',
		12: '红外控制器',
		13: '三路控制器',
		14: 'AI锁球器',
		16: '计时器'
	}
	return deviceTypes[type] || '未知设备'
}

/**
 * 格式化金额
 * @param {Number} amount - 金额（分）
 * @param {Boolean} showSymbol - 是否显示货币符号
 */
export function formatMoney(amount, showSymbol = true) {
	if (amount === null || amount === undefined) return '0.00'
	const money = (amount / 100).toFixed(2)
	return showSymbol ? '¥' + money : money
}

/**
 * 防抖函数
 * @param {Function} func - 要执行的函数
 * @param {Number} wait - 等待时间（毫秒）
 */
export function debounce(func, wait = 500) {
	let timeout
	return function(...args) {
		clearTimeout(timeout)
		timeout = setTimeout(() => {
			func.apply(this, args)
		}, wait)
	}
}

/**
 * 节流函数
 * @param {Function} func - 要执行的函数
 * @param {Number} wait - 等待时间（毫秒）
 */
export function throttle(func, wait = 500) {
	let previous = 0
	return function(...args) {
		const now = Date.now()
		if (now - previous > wait) {
			func.apply(this, args)
			previous = now
		}
	}
}

/**
 * 深拷贝
 * @param {Object} obj - 要拷贝的对象
 */
export function deepClone(obj) {
	if (obj === null || typeof obj !== 'object') return obj
	if (obj instanceof Date) return new Date(obj)
	if (obj instanceof Array) {
		return obj.map(item => deepClone(item))
	}
	const cloneObj = {}
	for (let key in obj) {
		if (obj.hasOwnProperty(key)) {
			cloneObj[key] = deepClone(obj[key])
		}
	}
	return cloneObj
}

/**
 * 手机号脱敏
 * @param {String} phone - 手机号
 */
export function hidePhone(phone) {
	if (!phone) return ''
	return phone.replace(/(\d{3})\d{4}(\d{4})/, '$1****$2')
}

/**
 * 身份证号脱敏
 * @param {String} idCard - 身份证号
 */
export function hideIdCard(idCard) {
	if (!idCard) return ''
	return idCard.replace(/(\d{6})\d{8}(\d{4})/, '$1********$2')
}

/**
 * 验证手机号
 * @param {String} phone - 手机号
 */
export function validatePhone(phone) {
	return /^1[3-9]\d{9}$/.test(phone)
}

/**
 * 验证身份证号
 * @param {String} idCard - 身份证号
 */
export function validateIdCard(idCard) {
	return /(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/.test(idCard)
}

/**
 * 获取 URL 参数
 * @param {String} name - 参数名
 */
export function getUrlParam(name) {
	const reg = new RegExp('(^|&)' + name + '=([^&]*)(&|$)')
	const r = window.location.search.substr(1).match(reg)
	if (r != null) return decodeURIComponent(r[2])
	return null
}

/**
 * 计算两个日期之间的天数
 * @param {Date|String} startDate - 开始日期
 * @param {Date|String} endDate - 结束日期
 */
export function getDaysBetween(startDate, endDate) {
	const start = new Date(startDate)
	const end = new Date(endDate)
	const diff = end.getTime() - start.getTime()
	return Math.floor(diff / (1000 * 60 * 60 * 24))
}

/**
 * 生成随机字符串
 * @param {Number} length - 字符串长度
 */
export function randomString(length = 32) {
	const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
	let result = ''
	for (let i = 0; i < length; i++) {
		result += chars.charAt(Math.floor(Math.random() * chars.length))
	}
	return result
}

/**
 * 判断是否为空
 * @param {*} value - 要判断的值
 */
export function isEmpty(value) {
	return value === null || value === undefined || value === '' || 
	       (Array.isArray(value) && value.length === 0) ||
	       (typeof value === 'object' && Object.keys(value).length === 0)
}

/**
 * 获取文件扩展名
 * @param {String} filename - 文件名
 */
export function getFileExt(filename) {
	if (!filename) return ''
	const index = filename.lastIndexOf('.')
	return index > -1 ? filename.substring(index + 1) : ''
}

/**
 * 计算文件大小
 * @param {Number} size - 文件大小（字节）
 */
export function formatFileSize(size) {
	if (size < 1024) {
		return size + 'B'
	} else if (size < 1024 * 1024) {
		return (size / 1024).toFixed(2) + 'KB'
	} else if (size < 1024 * 1024 * 1024) {
		return (size / (1024 * 1024)).toFixed(2) + 'MB'
	} else {
		return (size / (1024 * 1024 * 1024)).toFixed(2) + 'GB'
	}
}

/**
 * 数组去重
 * @param {Array} arr - 数组
 * @param {String} key - 对象数组的唯一键
 */
export function unique(arr, key) {
	if (!Array.isArray(arr)) return []
	if (key) {
		const map = new Map()
		return arr.filter(item => !map.has(item[key]) && map.set(item[key], 1))
	}
	return [...new Set(arr)]
}

/**
 * 延迟执行
 * @param {Number} ms - 延迟时间（毫秒）
 */
export function sleep(ms) {
	return new Promise(resolve => setTimeout(resolve, ms))
}

export default {
	formatTime,
	formatMoney,
	debounce,
	throttle,
	deepClone,
	hidePhone,
	hideIdCard,
	validatePhone,
	validateIdCard,
	getUrlParam,
	getDaysBetween,
	randomString,
	isEmpty,
	getFileExt,
	formatFileSize,
	unique,
	sleep
}
