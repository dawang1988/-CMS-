<template>
	<text 
		class="custom-icon" 
		:style="iconStyle"
	>
		{{ iconText }}
	</text>
</template>

<script>
/**
 * iconfont 图标组件
 * 用于显示图标，兼容原生小程序的 iconfont 组件
 * 
 * @property {String} name - 图标名称
 * @property {String|Number} size - 图标大小（rpx）
 * @property {String} color - 图标颜色
 */

// 图标文字映射表（使用 emoji 或特殊字符作为临时方案）
const iconTextMap = {
	// 首页图标
	'shouye1': '🏠',
	
	// 门店图标
	'iov-store': '🏪',
	
	// 位置图标
	'weibiaoti-3': '📍',
	
	// 麻将图标
	'majiangfa': '🀄',
	
	// 台球图标
	'taiqiu': '🎱',
	
	// KTV图标
	'KTV': '🎤',
	
	// 导航图标
	'daohang1': '🧭',
	
	// 电话图标
	'dianhua': '📞',
	
	// 切换图标
	'24gl-swapHorizontal3': '🔄',
	
	// 优惠券图标
	'cuxiaohuodong-youhuiquan': '🎫',
	
	// 微信支付
	'weixinzhifu': '💳',
	
	// 余额
	'yue': '💰',
	
	// 扫码
	'scan-qr-code': '📷'
}

export default {
	name: 'Iconfont',
	props: {
		// 图标名称
		name: {
			type: String,
			required: true,
			default: ''
		},
		// 图标大小（rpx）
		size: {
			type: [String, Number],
			default: 16
		},
		// 图标颜色
		color: {
			type: String,
			default: '#000'
		}
	},
	computed: {
		// 图标样式
		iconStyle() {
			return {
				display: 'inline-block',
				fontSize: this.size + 'rpx',
				color: this.color,
				lineHeight: '1',
				verticalAlign: 'middle'
			}
		},
		// 图标文字内容
		iconText() {
			return iconTextMap[this.name] || '●'
		}
	}
}
</script>

<style scoped>
.custom-icon {
	display: inline-block;
	vertical-align: middle;
	text-align: center;
}
</style>
