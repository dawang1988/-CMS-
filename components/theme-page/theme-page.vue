<template>
	<view class="theme-page" :style="themeStyle">
		<slot></slot>
	</view>
</template>

<script>
/**
 * 主题页面包装组件
 * 自动应用主题色CSS变量
 * 用法: 在页面根元素外包裹 <theme-page>...</theme-page>
 */
export default {
	name: 'ThemePage'
}
</script>

<style lang="scss">
.theme-page {
	width: 100%;
	min-height: 100vh;
}
</style>
