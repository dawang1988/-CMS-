/**
 * 全局主题色管理工具
 * 用于在所有页面中统一管理主题色
 */

// 主题色配置
const THEME_COLORS = {
	'default': '#5AAB6E',
	'blue': '#1890ff', 
	'orange': '#fa8c16',
	'purple': '#722ed1'
}

// 主题色阴影配置（用于渐变效果）
const THEME_SHADOW_COLORS = {
	'default': '#4b905c',
	'blue': '#096dd9',
	'orange': '#d46b08',
	'purple': '#531dab'
}

/**
 * 获取当前主题色
 * @returns {string} 主题色值
 */
export function getThemeColor() {
	const cached = uni.getStorageSync('themeColor')
	return cached || THEME_COLORS['default']
}

/**
 * 获取当前主题阴影色
 * @returns {string} 主题阴影色值
 */
export function getThemeShadowColor() {
	const key = getThemeKey()
	return THEME_SHADOW_COLORS[key] || THEME_SHADOW_COLORS['default']
}

/**
 * 获取当前主题key
 * @returns {string} 主题key
 */
export function getThemeKey() {
	return uni.getStorageSync('themeKey') || 'default'
}

/**
 * 设置主题色
 * @param {string} templateKey - 模板key (default/blue/orange/purple)
 * @returns {string} 设置后的主题色值
 */
export function setThemeColor(templateKey) {
	const color = THEME_COLORS[templateKey] || THEME_COLORS['default']
	uni.setStorageSync('themeColor', color)
	uni.setStorageSync('themeKey', templateKey || 'default')
	return color
}

/**
 * 获取主题色样式字符串，用于绑定到根元素
 * @returns {string} CSS变量样式字符串
 */
export function getThemeStyle() {
	const key = getThemeKey()
	const color = getThemeColor()
	const shadowColor = THEME_SHADOW_COLORS[key] || THEME_SHADOW_COLORS['default']
	return `--main-color: ${color}; --main-color-shadow: ${shadowColor};`
}

/**
 * 主题色配置对象
 */
export const themeColors = THEME_COLORS
export const themeShadowColors = THEME_SHADOW_COLORS

export default {
	getThemeColor,
	getThemeShadowColor,
	getThemeKey,
	setThemeColor,
	getThemeStyle,
	themeColors,
	themeShadowColors
}
