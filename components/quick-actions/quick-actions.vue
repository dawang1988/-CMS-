<template>
	<view class="quick-actions" :style="{ backgroundColor: bgColor }">
		<view 
			class="action-item" 
			v-for="(item, index) in actions" 
			:key="index"
			@tap="onTap(item)"
		>
			<image v-if="item.icon" :src="item.icon" class="action-icon" />
			<view v-else-if="item.iconClass" class="action-icon-font">
				<text :class="item.iconClass"></text>
			</view>
			<text class="action-text">{{ item.text }}</text>
		</view>
	</view>
</template>

<script>
/**
 * 快捷操作栏组件 - 从首页拆分
 * @property {Array} actions - 操作项列表 [{icon, iconClass, text, action}]
 * @property {String} bgColor - 背景颜色
 * @event action - 点击操作项事件
 */
export default {
	name: 'QuickActions',
	props: {
		actions: {
			type: Array,
			default: () => []
		},
		bgColor: {
			type: String,
			default: 'var(--main-color, #5AAB6E)'
		}
	},
	methods: {
		onTap(item) {
			this.$emit('action', item)
		}
	}
}
</script>

<style lang="scss" scoped>
.quick-actions {
	height: 200rpx;
	display: flex;
	justify-content: space-around;
	align-items: center;
	font-size: 26rpx;
	color: #fff;
	
	.action-item {
		display: flex;
		flex-direction: column;
		align-items: center;
		width: 20%;
		height: 130rpx;
		justify-content: space-around;
		
		&:active {
			opacity: 0.7;
		}
		
		.action-icon {
			width: 68rpx;
			height: 68rpx;
		}
		
		.action-icon-font {
			font-size: 48rpx;
		}
		
		.action-text {
			font-size: 26rpx;
		}
	}
}
</style>
