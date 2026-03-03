<template>
	<view class="page" :style="themeStyle">
		<view class="card">
			<!-- 用户基本信息 -->
			<view class="user-header">
				<image class="avatar" :src="info.avatar || '/static/logo.png'" mode="aspectFill"></image>
				<view class="user-info">
					<view class="nickname">{{ info.nickname || '未设置昵称' }}</view>
					<view class="mobile">{{ info.phone }}</view>
				</view>
				<view class="vip-badge" v-if="info.vip_level > 0">VIP{{ info.vip_level }}</view>
			</view>
			
			<!-- 用户数据 -->
			<view class="stats-grid">
				<view class="stat-item">
					<text class="num">{{ info.balance || 0 }}</text>
					<text class="label">余额</text>
				</view>
				<view class="stat-item">
					<text class="num">{{ info.gift_balance || 0 }}</text>
					<text class="label">赠送余额</text>
				</view>
				<view class="stat-item">
					<text class="num">{{ info.orderCount || 0 }}</text>
					<text class="label">消费次数</text>
				</view>
				<view class="stat-item">
					<text class="num">{{ info.score || 0 }}</text>
					<text class="label">积分</text>
				</view>
			</view>
		</view>
		
		<!-- 详细信息 -->
		<view class="card">
			<view class="info-row"><text class="label">注册时间</text><text>{{ info.create_time || '-' }}</text></view>
			<view class="info-row"><text class="label">最近消费</text><text>{{ info.lastOrderTime || '-' }}</text></view>
			<view class="info-row"><text class="label">用户类型</text><text>{{ getUserTypeName(info.user_type) }}</text></view>
		</view>
		
		<!-- 操作按钮 -->
		<view class="card">
			<button class="action-btn" @tap="goOrderList">查看订单</button>
		</view>
	</view>
</template>

<script>
import { getUserTypeName } from '@/utils/format'

export default {
	data() {
		return {
			info: {}
		}
	},
	onLoad(options) {
		if (options.info) {
			try {
				this.info = JSON.parse(decodeURIComponent(options.info))
			} catch (e) {
			}
		}
	},
	methods: {
		getUserTypeName,
		goOrderList() {
			uni.navigateTo({
				url: '/pagesA/order/set?userId=' + this.info.id
			})
		}
	}
}
</script>

<style lang="scss" scoped>
.page { min-height: 100vh; background: #f5f5f5; padding: 20rpx; }
.card { background: #fff; border-radius: 16rpx; padding: 30rpx; margin-bottom: 20rpx; }
.user-header { display: flex; align-items: center; }
.avatar { width: 100rpx; height: 100rpx; border-radius: 50%; margin-right: 24rpx; }
.user-info { flex: 1; }
.nickname { font-size: 32rpx; font-weight: 600; }
.mobile { font-size: 26rpx; color: #666; margin-top: 8rpx; }
.vip-badge { background: linear-gradient(135deg, #FFD700, #FFA500); color: #fff; font-size: 24rpx; padding: 6rpx 20rpx; border-radius: 20rpx; }
.stats-grid { display: flex; margin-top: 30rpx; padding-top: 30rpx; border-top: 1rpx solid #f0f0f0; }
.stat-item { flex: 1; text-align: center; }
.stat-item .num { font-size: 32rpx; font-weight: 600; color: var(--main-color, #5AAB6E); display: block; }
.stat-item .label { font-size: 22rpx; color: #999; }
.info-row { display: flex; justify-content: space-between; padding: 16rpx 0; border-bottom: 1rpx solid #f8f8f8; font-size: 28rpx; }
.info-row .label { color: #999; }
.action-btn { background: var(--main-color, #5AAB6E); color: #fff; border-radius: 30rpx; font-size: 28rpx; }
</style>
