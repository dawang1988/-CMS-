<template>
	<view class="page" :style="themeStyle">
		<view class="card">
			<view class="title">任务结算</view>
			<view class="info-row"><text class="label">保洁员</text><text>{{ info.clearUserName || '-' }}</text></view>
			<view class="info-row"><text class="label">门店</text><text>{{ info.store_name || '-' }}</text></view>
			<view class="info-row"><text class="label">完成任务数</text><text>{{ info.taskCount || 0 }}</text></view>
			<view class="info-row"><text class="label">结算金额</text><text class="price">￥{{ info.total_amount || 0 }}</text></view>
			<view class="info-row"><text class="label">结算周期</text><text>{{ info.startDate }} ~ {{ info.endDate }}</text></view>
		</view>
		
		<view class="card" v-if="taskList.length > 0">
			<view class="title">任务明细</view>
			<view class="task-item" v-for="(item, index) in taskList" :key="index">
				<view class="task-room">{{ item.room_name }}</view>
				<view class="task-time">{{ item.finishTime }}</view>
				<view class="task-amount">￥{{ item.amount || 0 }}</view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			info: {},
			taskList: []
		}
	},
	onLoad(options) {
		if (options.info) {
			try { this.info = JSON.parse(decodeURIComponent(options.info)) } catch (e) {}
		}
	}
}
</script>

<style lang="scss" scoped>
.page { min-height: 100vh; background: #f5f5f5; padding: 20rpx; }
.card { background: #fff; border-radius: 16rpx; padding: 30rpx; margin-bottom: 20rpx; }
.title { font-size: 32rpx; font-weight: 600; margin-bottom: 20rpx; }
.info-row { display: flex; justify-content: space-between; padding: 14rpx 0; border-bottom: 1rpx solid #f8f8f8; font-size: 28rpx; }
.info-row .label { color: #999; }
.price { color: #f44336; font-weight: 600; }
.task-item { display: flex; justify-content: space-between; padding: 14rpx 0; border-bottom: 1rpx solid #f8f8f8; font-size: 26rpx; }
.task-room { flex: 1; }
.task-time { color: #999; }
.task-amount { color: #f44336; margin-left: 20rpx; }
</style>
