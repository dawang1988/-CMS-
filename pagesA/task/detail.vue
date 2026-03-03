<template>
	<view class="page" :style="themeStyle">
		<view class="card">
			<view class="title">任务详情</view>
			<view class="info-row"><text class="label">门店</text><text>{{ detail.store_name || '-' }}</text></view>
			<view class="info-row"><text class="label">房间</text><text>{{ detail.room_name || '-' }}</text></view>
			<view class="info-row"><text class="label">状态</text>
				<text :class="'ts-' + detail.status">{{ getStatusText(detail.status) }}</text>
			</view>
			<view class="info-row"><text class="label">创建时间</text><text>{{ detail.create_time || '-' }}</text></view>
			<view class="info-row"><text class="label">接单时间</text><text>{{ detail.take_time || '-' }}</text></view>
			<view class="info-row"><text class="label">开始时间</text><text>{{ detail.start_time || '-' }}</text></view>
			<view class="info-row"><text class="label">完成时间</text><text>{{ detail.end_time || '-' }}</text></view>
			<view class="info-row" v-if="detail.user_name"><text class="label">保洁员</text><text>{{ detail.user_name }}</text></view>
		</view>
		
		<!-- 待接单：接单 -->
		<view class="btns" v-if="detail.status == 0">
			<button class="btn primary" @tap="jiedan">接单</button>
		</view>
		<!-- 已接单：开始清洁 + 开大门 + 开房间门 + 取消接单 -->
		<view class="btns" v-if="detail.status == 1">
			<button class="btn primary" @tap="startTask">开始清洁</button>
			<button class="btn info" @tap="openStoreDoor">开大门</button>
			<button class="btn info" @tap="openRoomDoor">开房间门</button>
			<button class="btn warn" @tap="cancelTake">取消接单</button>
		</view>
		<!-- 清洁中：完成清洁 + 开大门 + 开房间门 -->
		<view class="btns" v-if="detail.status == 2">
			<button class="btn primary" @tap="finishTask">完成清洁</button>
			<button class="btn info" @tap="openStoreDoor">开大门</button>
			<button class="btn info" @tap="openRoomDoor">开房间门</button>
		</view>
	</view>
</template>

<script>
import http from '@/utils/http'

export default {
	data() {
		return { id: '', detail: {} }
	},
	onLoad(options) {
		this.id = options.id
		this.getDetail()
	},
	onShow() {
		this.getDetail()
	},
	methods: {
		getStatusText(status) {
			const map = { 0: '待接单', 1: '已接单', 2: '清洁中', 3: '已完成', 4: '已取消', 5: '被驳回', 6: '已结算' }
			return map[status] || '未知'
		},
		getDetail() {
			http.get('/member/clear/getDetail/' + this.id).then(res => {
				if (res.code == 0) this.detail = res.data || {}
			})
		},
		jiedan() {
			http.post('/member/clear/jiedan/' + this.id).then(res => {
				if (res.code == 0) { uni.showToast({ title: '接单成功' }); this.getDetail() }
				else uni.showModal({ content: res.msg, showCancel: false })
			})
		},
		startTask() {
			http.post('/member/clear/start/' + this.id).then(res => {
				if (res.code == 0) { uni.showToast({ title: '开始清洁' }); this.getDetail() }
				else uni.showModal({ content: res.msg, showCancel: false })
			})
		},
		finishTask() {
			uni.showModal({
				title: '确认', content: '确定已完成清洁？',
				success: (res) => {
					if (res.confirm) {
						http.post('/member/clear/finish/' + this.id).then(r => {
							if (r.code == 0) { uni.showToast({ title: '清洁完成', icon: 'success' }); this.getDetail() }
							else uni.showModal({ content: r.msg, showCancel: false })
						})
					}
				}
			})
		},
		openStoreDoor() {
			http.post('/member/clear/openStoreDoor/' + this.id).then(res => {
				if (res.code == 0) uni.showToast({ title: '开大门成功', icon: 'success' })
				else uni.showModal({ content: res.msg, showCancel: false })
			})
		},
		openRoomDoor() {
			http.post('/member/clear/openRoomDoor/' + this.id).then(res => {
				if (res.code == 0) uni.showToast({ title: '开房间门成功', icon: 'success' })
				else uni.showModal({ content: res.msg, showCancel: false })
			})
		},
		cancelTake() {
			uni.showModal({
				title: '确认', content: '确定取消接单？',
				success: (res) => {
					if (res.confirm) {
						http.post('/member/clear/cancel/' + this.id).then(r => {
							if (r.code == 0) { uni.showToast({ title: '已取消接单' }); this.getDetail() }
							else uni.showModal({ content: r.msg, showCancel: false })
						})
					}
				}
			})
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
.ts-0 { color: #ff9800; }
.ts-1 { color: #2196F3; }
.ts-2 { color: var(--main-color, #5AAB6E); }
.ts-3 { color: #999; }
.ts-4 { color: #f44336; }
.btns { display: flex; gap: 20rpx; margin-top: 20rpx; flex-wrap: wrap; }
.btn { flex: 1; border-radius: 30rpx; font-size: 28rpx; min-width: 180rpx; }
.btn.primary { background: var(--main-color, #5AAB6E); color: #fff; }
.btn.info { background: #2196F3; color: #fff; }
.btn.warn { background: #f44336; color: #fff; }
</style>
