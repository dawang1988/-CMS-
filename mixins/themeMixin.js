/**
 * 全局主题色 Mixin
 * 在 main.js 中全局注册，让所有页面自动支持主题色
 */
import { getThemeColor, getThemeShadowColor, setThemeColor, getThemeKey } from '@/utils/theme.js'

// 主题色阴影配置
const THEME_SHADOW_COLORS = {
	'default': '#4b905c',
	'blue': '#096dd9',
	'orange': '#d46b08',
	'purple': '#531dab'
}

export default {
	data() {
		return {
			// 主题色，所有页面都可以使用
			themeColor: getThemeColor()
		}
	},
	
	computed: {
		// 主题色样式，绑定到页面根元素: :style="themeStyle"
		themeStyle() {
			const key = getThemeKey()
			const shadowColor = THEME_SHADOW_COLORS[key] || THEME_SHADOW_COLORS['default']
			return `--main-color: ${this.themeColor}; --main-color-shadow: ${shadowColor};`
		}
	},
	
	onShow() {
		// 每次页面显示时，从缓存恢复最新的主题色
		this.themeColor = getThemeColor()
	},
	
	methods: {
		// 设置主题色（通常在获取门店信息后调用）
		applyThemeColor(templateKey) {
			this.themeColor = setThemeColor(templateKey)
		}
	}
}
