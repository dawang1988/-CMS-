<template>
	<view class="page" :style="themeStyle">
		<!-- 状态筛选 -->
		<view class="tab-bar">
			<view class="tab" :class="{ active: status === '' }" @tap="changeStatus" data-status="">全部</view>
			<view class="tab" :class="{ active: status === 0 }" @tap="changeStatus" data-status="0">待接单</view>
			<view class="tab" :class="{ active: status === 1 }" @tap="changeStatus" data-status="1">已接单</view>
			<view class="tab" :class="{ active: status === 2 }" @tap="changeStatus" data-status="2">清洁中</view>
			<view class="tab" :class="{ active: status === 3 }" @tap="changeStatus" data-status="3">已完成</view>
		</view>

		<!-- 任务列表 -->
		<view class="task-list">
			<view class="task-card" v-for="(item, index) in taskList" :key="index" @tap="goDetail" :data-id="item.id">
				<view class="task-top">
					<text class="store-name">{{ item.store_name }}</text>
					<text class="task-status" :class="'ts-' + item.status">
						{{ getStatusText(item.status) }}
					</text>
				</view>
				<view class="task-info">
					<view class="info-row">房间：{{ item.room_name }}</view>
					<view class="info-row">创建时间：{{ item.create_time }}</view>
					<view class="info-row" v-if="item.user_name">保洁员：{{ item.user_name || item.userName }}</view>
				</view>
				<!-- 待接单：接单按钮 -->
				<view class="task-btns" v-if="item.status == 0">
					<button class="btn-accept" @tap.stop="jiedan" :data-id="item.id">接单</button>
				</view>
				<!-- 已接单：开始清洁 + 开门 -->
				<view class="task-btns" v-if="item.status == 1">
					<button class="btn-start" @tap.stop="startTask" :data-id="item.id">开始清洁</button>
					<button class="btn-open" @tap.stop="openStoreDoor" :data-id="item.id">开大门</button>
					<button class="btn-open" @tap.stop="openRoomDoor" :data-id="item.id">开房间门</button>
				</view>
				<!-- 清洁中：完成清洁 + 开门 -->
				<view class="task-btns" v-if="item.status == 2">
					<button class="btn-finish" @tap.stop="finishTask" :data-id="item.id">完成清洁</button>
					<button class="btn-open" @tap.stop="openStoreDoor" :data-id="item.id">开大门</button>
					<button class="btn-open" @tap.stop="openRoomDoor" :data-id="item.id">开房间门</button>
				</view>
			</view>
			<view class="empty" v-if="taskList.length === 0">暂无任务</view>
		</view>
	</view>
</template>

<script>
import http from '@/utils/http'

export default {
	data() {
		return {
			status: '',
			taskList: [],
			pageNo: 1,
			canLoadMore: true,
			store_id: ''
		}
	},
	onLoad(options) {
		if (options.store_id) {
			this.store_id = options.store_id
		}
		this.getTaskList('refresh')
	},
	onShow() {
		this.getTaskList('refresh')
	},
	onPullDownRefresh() {
		this.getTaskList('refresh')
		uni.stopPullDownRefresh()
	},
	onReachBottom() {
		if (this.canLoadMore) this.getTaskList()
	},
	methods: {
		getStatusText(status) {
			const map = { 0: '待接单', 1: '已接单', 2: '清洁中', 3: '已完成', 4: '已取消', 5: '被驳回', 6: '已结算' }
			return map[status] || '未知'
		},
		changeStatus(e) {
			const s = e.currentTarget.dataset.status
			this.status = s === '' ? '' : Number(s)
			this.getTaskList('refresh')
		},
		getTaskList(e) {
			if (e === 'refresh') {
				this.taskList = []
				this.pageNo = 1
				this.canLoadMore = true
			}
			const params = {
				pageNo: this.pageNo,
				pageSize: 10,
				status: this.status
			}
			if (this.store_id) {
				params.store_id = this.store_id
			}
			http.post('/member/manager/getClearManagerPage', params).then(res => {
				if (res.code == 0) {
					if (res.data.list.length === 0) {
						this.canLoadMore = false
					} else {
						this.taskList = this.taskList.concat(res.data.list)
						this.pageNo++
						this.canLoadMore = this.taskList.length < res.data.total
					}
				}
			})
		},
		jiedan(e) {
			const id = e.currentTarget.dataset.id
			http.post('/member/clear/jiedan/' + id).then(res => {
				if (res.code == 0) {
					uni.showToast({ title: '接单成功' })
					this.getTaskList('refresh')
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			})
		},
		startTask(e) {
			const id = e.currentTarget.dataset.id
			http.post('/member/clear/start/' + id).then(res => {
				if (res.code == 0) {
					uni.showToast({ title: '开始清洁' })
					this.getTaskList('refresh')
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			})
		},
		finishTask(e) {
			const id = e.currentTarget.dataset.id
			uni.showModal({
				title: '确认',
				content: '确定已完成清洁？',
				success: (res) => {
					if (res.confirm) {
						http.post('/member/clear/finish/' + id).then(r => {
							if (r.code == 0) {
								uni.showToast({ title: '清洁完成', icon: 'success' })
								this.getTaskList('refresh')
							} else {
								uni.showModal({ content: r.msg, showCancel: false })
							}
						})
					}
				}
			})
		},
		openStoreDoor(e) {
			const id = e.currentTarget.dataset.id
			http.post('/member/clear/openStoreDoor/' + id).then(res => {
				if (res.code == 0) uni.showToast({ title: '开大门成功', icon: 'success' })
				else uni.showModal({ content: res.msg, showCancel: false })
			})
		},
		openRoomDoor(e) {
			const id = e.currentTarget.dataset.id
			http.post('/member/clear/openRoomDoor/' + id).then(res => {
				if (res.code == 0) uni.showToast({ title: '开房间门成功', icon: 'success' })
				else uni.showModal({ content: res.msg, showCancel: false })
			})
		},
		goDetail(e) {
			uni.navigateTo({ url: '/pagesA/task/detail?id=' + e.currentTarget.dataset.id })
		}
	}
}
</script>

<style lang="scss" scoped>
.page { min-height: 100vh; background: #f5f5f5; }
.tab-bar { display: flex; background: #fff; padding: 16rpx 20rpx; position: sticky; top: 0; z-index: 10; }
.tab { flex: 1; text-align: center; font-size: 26rpx; padding: 12rpx 0; color: #666; border-radius: 30rpx; }
.tab.active { background: var(--main-color, #5AAB6E); color: #fff; }
.task-list { padding: 20rpx; }
.task-card { background: #fff; border-radius: 16rpx; padding: 24rpx; margin-bottom: 16rpx; }
.task-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12rpx; }
.store-name { font-size: 30rpx; font-weight: 600; }
.task-status { font-size: 24rpx; padding: 4rpx 16rpx; border-radius: 20rpx; color: #fff; }
.ts-0 { background: #ff9800; }
.ts-1 { background: #2196F3; }
.ts-2 { background: var(--main-color, #5AAB6E); }
.ts-3 { background: #999; }
.ts-4 { background: #f44336; }
.info-row { font-size: 26rpx; color: #666; line-height: 44rpx; }
.task-btns { display: flex; justify-content: flex-end; margin-top: 16rpx; gap: 16rpx; flex-wrap: wrap; }
.btn-accept { background: #ff9800; color: #fff; font-size: 24rpx; border-radius: 30rpx; padding: 0 30rpx; height: 60rpx; line-height: 60rpx; }
.btn-start { background: var(--main-color, #5AAB6E); color: #fff; font-size: 24rpx; border-radius: 30rpx; padding: 0 30rpx; height: 60rpx; line-height: 60rpx; }
.btn-finish { background: #07c160; color: #fff; font-size: 24rpx; border-radius: 30rpx; padding: 0 30rpx; height: 60rpx; line-height: 60rpx; }
.btn-open { background: #2196F3; color: #fff; font-size: 24rpx; border-radius: 30rpx; padding: 0 30rpx; height: 60rpx; line-height: 60rpx; }
.empty { text-align: center; padding: 100rpx 0; color: #999; }
</style>
