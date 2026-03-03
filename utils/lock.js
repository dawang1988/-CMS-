/**
 * 防重复点击工具
 */

class Lock {
	constructor() {
		this.locks = new Map()
	}
	
	/**
	 * 检查是否被锁定
	 * @param {String} key - 锁的键名
	 * @param {Number} duration - 锁定时长（毫秒），默认 1000ms
	 */
	isLocked(key, duration = 1000) {
		const now = Date.now()
		const lockTime = this.locks.get(key)
		
		if (lockTime && now - lockTime < duration) {
			return true
		}
		
		this.locks.set(key, now)
		return false
	}
	
	/**
	 * 手动解锁
	 * @param {String} key - 锁的键名
	 */
	unlock(key) {
		this.locks.delete(key)
	}
	
	/**
	 * 清除所有锁
	 */
	clear() {
		this.locks.clear()
	}
}

const lock = new Lock()

/**
 * 防重复点击装饰器
 * @param {Number} duration - 锁定时长（毫秒）
 */
export function preventMultiClick(duration = 1000) {
	return function(target, name, descriptor) {
		const originalMethod = descriptor.value
		
		descriptor.value = function(...args) {
			const key = `${target.constructor.name}_${name}`
			
			if (lock.isLocked(key, duration)) {
				return
			}
			
			return originalMethod.apply(this, args)
		}
		
		return descriptor
	}
}

/**
 * 防重复点击函数（用于普通函数）
 * @param {Function} func - 要执行的函数
 * @param {Number} duration - 锁定时长（毫秒）
 */
export function preventClick(func, duration = 1000) {
	let lastTime = 0
	
	return function(...args) {
		const now = Date.now()
		
		if (now - lastTime < duration) {
			return
		}
		
		lastTime = now
		return func.apply(this, args)
	}
}

export default lock
