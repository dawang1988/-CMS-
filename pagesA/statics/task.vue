<template>
	<view class="container" :style="themeStyle">
		<!-- 今日统计 -->
		<view class="section">
			<view class="title"><view class="bar"></view>今日</view>
			<view class="box">
				<view class="item" @tap="golist" data-time="today" data-status="1">
					<view><text class="num">{{ info.today_jiedan || 0 }}</text>单</view>
					<view class="blue">已接单</view>
				</view>
				<view class="item" @tap="golist" data-time="today" data-status="2">
					<view><text class="num">{{ info.today_start || 0 }}</text>单</view>
					<view class="red">进行中</view>
				</view>
				<view class="item" @tap="golist" data-time="today" data-status="3">
					<view><text class="num">{{ info.today_finish || 0 }}</text>单</view>
					<view class="green">已完成</view>
				</view>
			</view>
		</view>
		<!-- 本月统计 -->
		<view class="section">
			<view class="title"><view class="bar"></view>本月</view>
			<view class="box">
				<view class="item" @tap="golist" data-time="month" data-status="6">
					<view><text class="num">{{ info.tomonth_jiesuan || 0 }}</text>单</view>
					<view class="blue">已结算</view>
				</view>
				<view class="item" @tap="golist" data-time="month" data-status="3">
					<view><text class="num">{{ info.tomonth_finish || 0 }}</text>单</view>
					<view class="green">已完成</view>
				</view>
				<view class="item" @tap="golist" data-time="month" data-status="5">
					<view><text class="num">{{ info.tomonth_bohui || 0 }}</text>单</view>
					<view class="yellow">已驳回</view>
				</view>
			</view>
		</view>
		<!-- 总收入统计 -->
		<view class="section">
			<view class="title"><view class="bar"></view>总收入统计</view>
			<view class="box">
				<view class="item" @tap="golist" data-time="" data-status="3">
					<view><text class="num">{{ info.total_finish || 0 }}</text>单</view>
					<view class="green">已完成</view>
				</view>
				<view class="item" @tap="golist" data-time="" data-status="6">
					<view><text class="num">{{ info.total_settlement || 0 }}</text>单</view>
					<view class="blue">已结算</view>
				</view>
				<view class="item">
					<view><text class="num">{{ info.total_money || 0 }}</text>元</view>
					<view class="green">总收入</view>
				</view>
			</view>
		</view>
		<!-- 结算记录 -->
		<view class="list-section">
			<view class="title"><view class="bar"></view>结算记录</view>
			<view v-if="list.length > 0">
				<view class="record-item" v-for="(item, index) in list" :key="index">
					<view class="line"><text class="label">结算时间：</text>{{ item.settle_time }}</view>
					<view class="line"><text class="label">房间：</text>{{ item.room_name || '-' }}</view>
					<view class="total">+{{ item.settle_amount || 0 }}元</view>
				</view>
				<view class="note-more">下拉查看更多...</view>
			</view>
			<view class="nodata-list" v-else>暂无数据</view>
		</view>
	</view>
</template>

<script>
import http from '@/utils/http'

export default {
	data() {
		return {
			info: {},
			pageNo: 1,
			pageSize: 10,
			canLoadMore: true,
			list: []
		}
	},
	onLoad() {
		this.getData()
		this.getMainListdata('refresh')
	},
	onPullDownRefresh() {
		this.list = []
		this.canLoadMore = true
		this.pageNo = 1
		this.getMainListdata('refresh')
		uni.stopPullDownRefresh()
	},
	onReachBottom() {
		if (this.canLoadMore) this.getMainListdata('')
	},
	methods: {
		getData() {
			http.get('/member/clear/getCleanerStats').then(res => {
				if (res.code == 0) this.info = res.data || {}
			})
		},
		getMainListdata(e) {
			if (e === 'refresh') {
				this.pageNo = 1
				this.list = []
				this.canLoadMore = true
			}
			http.post('/member/clear/getClearBillPage', {
				pageNo: this.pageNo,
				pageSize: this.pageSize
			}).then(res => {
				if (res.code == 0) {
					if (res.data.list.length === 0) {
						this.canLoadMore = false
					} else {
						this.list = this.list.concat(res.data.list)
						this.pageNo++
						this.canLoadMore = this.list.length < res.data.total
					}
				}
			})
		},
		golist(e) {
			const time = e.currentTarget.dataset.time
			const status = e.currentTarget.dataset.status
			let startTime = '', endTime = ''
			const now = new Date()
			const pad = n => n < 10 ? '0' + n : n
			const today = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}`
			if (time === 'today') {
				startTime = today
				endTime = today
			} else if (time === 'month') {
				startTime = `${now.getFullYear()}-${pad(now.getMonth()+1)}-01`
				endTime = today
			}
			uni.navigateTo({
				url: `/pagesA/task/index?status=${status}&startTime=${startTime}&endTime=${endTime}`
			})
		}
	}
}
</script>

<style lang="scss" scoped>
.container { min-height: 100vh; background: #f5f5f5; padding: 20rpx; }
.section { background: #fff; border-radius: 16rpx; padding: 24rpx; margin-bottom: 20rpx; }
.title { font-size: 30rpx; font-weight: 600; display: flex; align-items: center; margin-bottom: 20rpx; }
.title .bar { width: 6rpx; height: 30rpx; background: var(--main-color, #5AAB6E); border-radius: 3rpx; margin-right: 12rpx; }
.box { display: flex; }
.item { flex: 1; text-align: center; padding: 16rpx 0; font-size: 26rpx; }
.num { font-size: 40rpx; font-weight: 600; margin-right: 4rpx; }
.blue { color: #2196F3; font-size: 24rpx; margin-top: 8rpx; }
.red { color: #f44336; font-size: 24rpx; margin-top: 8rpx; }
.green { color: var(--main-color, #5AAB6E); font-size: 24rpx; margin-top: 8rpx; }
.yellow { color: #ff9800; font-size: 24rpx; margin-top: 8rpx; }
.list-section { background: #fff; border-radius: 16rpx; padding: 24rpx; }
.record-item { padding: 20rpx 0; border-bottom: 1rpx solid #f0f0f0; }
.line { font-size: 26rpx; line-height: 44rpx; }
.line .label { color: #999; }
.total { text-align: right; color: var(--main-color, #5AAB6E); font-size: 30rpx; font-weight: 600; }

</style>
