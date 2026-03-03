<template>
	<view class="info-card">
		<view class="top">
			<view class="left">
				<view class="info">
					<iconfont name="iov-store" size="24"></iconfont>
					<text class="name"> {{ store.store_name }}</text>
				</view>
			</view>
			<view class="change-btn" @tap="$emit('change-store')">
				<text>切换门店</text>
			</view>
		</view>
		<view class="bottom">
			<text class="location">{{ store.address }}</text>
			<view class="item" @tap="$emit('navigate')">
				<image src="/static/icon/navigation.png" />
				<text>导航</text>
			</view>
			<view class="line"></view>
			<view class="item" @tap="$emit('guide')">
				<image src="/static/icon/location.png" />
				<text>指引</text>
			</view>
			<view class="line"></view>
			<view class="item" @tap="$emit('call')">
				<image src="/static/icon/phone.png" />
				<text>电话</text>
			</view>
		</view>
		<view class="distant" @tap="$emit('navigate')">
			<iconfont name="weibiaoti-3" :color="'var(--main-color, #5AAB6E)'"></iconfont>
			<text style="color: var(--main-color);">{{ store.distance }}km</text>
		</view>
	</view>
</template>

<script>
import Iconfont from '@/components/iconfont/iconfont.vue'

export default {
	name: 'StoreInfoCard',
	options: {
		styleIsolation: 'shared'
	},
	components: { Iconfont },
	props: {
		store: { type: Object, default: () => ({}) }
	}
}
</script>

<style lang="scss">
/* store-info-card 样式 - 与原版 index.wxss 完全一致 */
.info-card { background: #fff; padding: 20rpx 34rpx; border-radius: 17rpx; display: flex; flex-direction: column; justify-content: space-between; }
.info-card .top { display: flex; justify-content: space-between; }
.info-card .top .left { display: flex; justify-content: space-between; }
.info-card .top .left .info { display: flex; justify-content: space-between; align-items: center; }
.info-card .top .left .info .name { font-size: 32rpx; font-weight: 600; color: #1d1d1d; font-style: normal; width: 500rpx; margin-left: 5rpx; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.info-card .distant { display: flex; justify-content: space-between; align-items: center; width: fit-content; height: 40rpx; background: #FFFFFF; border-radius: 9rpx; border: 1rpx solid var(--main-color, #5AAB6E); font-size: 22rpx; padding: 0rpx 10rpx; }
.info-card .top .left .iconfont { margin: auto; }
.change-btn { width: 169rpx; height: 47rpx; background: var(--main-color, #5AAB6E); border-radius: 52rpx; display: flex; justify-content: center; align-items: center; font-size: 21rpx; color: #fff; }
.change-btn image { width: 22rpx; height: 20rpx; margin-right: 8rpx; }
.info-card .location { width: 450rpx; font-weight: 300; font-size: 22rpx; color: #817F7F; line-height: 32rpx; margin-top: 5px; }
.info-card .bottom { display: flex; justify-content: space-around; align-items: center; }
.info-card .bottom .line { width: 0rpx; height: 34rpx; border: 0.5rpx solid #BCBCBC; margin-top: 22rpx; }
.info-card .bottom image { width: 39rpx; height: 39rpx; }
.info-card .bottom .item { display: flex; margin-top: 20rpx; flex-direction: column; align-items: center; font-weight: 400; font-size: 21rpx; color: #3E3E3E; }
</style>
