// magicMixin.js - 字符串处理工具函数
export default {
	// 分割字符串（逗号分隔）
	split(tag) {
		if (!tag) return []
		if (Array.isArray(tag)) return tag
		if (typeof tag !== 'string') return []
		return tag.split(',')
	},
	
	// 分割字符串（空格分隔）
	splitkongge(tag) {
		if (!tag) return []
		if (Array.isArray(tag)) return tag
		if (typeof tag !== 'string') return []
		return tag.split(' ')
	},
	
	// 分割时间字符串
	splittime(tag) {
		if (!tag || typeof tag !== 'string') return []
		const atime = tag.split(' ')
		let atime2 = []
		if (atime.length > 1) {
			const atime1 = atime[1]
			atime2 = atime1.split(':')
		}
		return atime2
	},
	
	// 检查数组是否包含某个字符串
	includes(arr, string) {
		if (arr !== undefined) {
			return arr.indexOf(string) > -1
		}
		return false
	}
}
