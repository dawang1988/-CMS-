<template>
	<view class="top-tabs-container" v-if="roomClass && roomClass.length > 1">
		<view 
			class="top-tabs" 
			:class="{ active: tabIndex === item.value }" 
			v-for="item in roomClass" 
			:key="item.value"
			:data-index="item.value" 
			@tap="onChange"
		>
			<iconfont name="majiangfa" size="35" v-if="item.value == 0"></iconfont>
			<iconfont name="taiqiu" size="35" v-if="item.value == 1"></iconfont>
			<iconfont name="KTV" size="35" v-if="item.value == 2"></iconfont>
			<view class="tab">{{ item.text }}</view>
		</view>
	</view>
</template>

<script>
import Iconfont from '@/components/iconfont/iconfont.vue'

export default {
	name: 'RoomTypeTabs',
	options: {
		styleIsolation: 'shared'
	},
	components: { Iconfont },
	props: {
		roomClass: { type: Array, default: () => [] },
		tabIndex: { type: [Number, String], default: 0 }
	},
	methods: {
		onChange(e) {
			const index = Number(e.currentTarget.dataset.index)
			this.$emit('change', index)
		}
	}
}
</script>

<style lang="scss">
/* room-type-tabs 样式 - 与原版 index.wxss 完全一致 */
.top-tabs-container { padding: 0 20rpx 20rpx; background-color: #fff; display: flex; justify-content: space-between; }
.top-tabs { width: 49.6%; height: 80rpx; font-size: 32rpx; line-height: 60rpx; text-align: center; display: flex; align-items: center; background-color: #f5f5f5; border-radius: 10rpx; padding: 10rpx 10rpx; margin: 0rpx 10rpx; justify-content: center; }
.top-tabs .tab { margin-left: 10rpx; }
.top-tabs.active { color: var(--main-color); font-weight: bold; background-image: linear-gradient(181.2deg, #fff, var(--main-color) 150.8%); }
</style>
