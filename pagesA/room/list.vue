<template>
	<view class="page" :style="themeStyle">
		<!-- 门店选择 -->
		<view class="store-bar">
			<picker v-if="!isCleaner" mode="selector" :range="storeNames" @change="storeChange">
				<view class="store-picker">{{ currentStoreName }} ▼</view>
			</picker>
			<view v-else class="store-picker">{{ currentStoreName }}</view>
			<view class="store-btns" v-if="!isCleaner">
				<button size="mini" class="btn-open" @tap="openStoreDoor">开大门</button>
			</view>
		</view>

		<!-- 房间列表 -->
		<view class="room-list">
			<view class="room-card" v-for="(item, index) in doorList" :key="index" @tap="roomOp" :data-room="item">
				<view class="room-left">
					<view class="room-name">{{ item.room_name }}</view>
					<!-- 数据库定义：0=停用, 1=空闲, 2=使用中, 3=维护中, 4=待清洁/已预约 -->
					<view class="room-status" :class="'s-' + item.status + (item.status == 4 && item.is_cleaning ? (item.cleaning_status == 2 ? '-cleaning-active' : '-cleaning') : '')">
						{{ item.status == 0 ? '禁用' : item.status == 1 ? '空闲' : item.status == 2 ? '使用中' : item.status == 3 ? '维护中' : (item.is_cleaning ? (item.cleaning_status == 2 ? '清洁中' : '待清洁') : '已预约') }}
					</view>
				</view>
				<view class="room-right">
					<text class="room-price">￥{{ item.price }}/时</text>
					<text class="room-type">
						{{ item.type == 0 ? '特价包' : item.type == 1 ? '小包' : item.type == 2 ? '中包' : item.type == 3 ? '大包' : item.type == 4 ? '豪包' : item.type == 5 ? '商务包' : '其他' }}
					</text>
				</view>
			</view>
			<view class="empty" v-if="doorList.length === 0">暂无房间数据</view>
		</view>

		<!-- 房间操作弹窗 -->
		<u-popup :show="showRoomOp" mode="bottom" round="12" @close="closeRoomOp">
			<view class="op-content">
				<view class="op-title">{{ roomItem.room_name }} - 操作</view>
				<!-- 数据库定义：0=停用, 1=空闲, 2=使用中, 3=维护中, 4=待清洁/已预约 -->
				<view class="op-status">
					状态：{{ roomItem.status == 0 ? '禁用' : roomItem.status == 1 ? '空闲' : roomItem.status == 2 ? '使用中' : roomItem.status == 3 ? '维护中' : (roomItem.is_cleaning ? '待清洁' : '已预约') }}
				</view>
				<view class="op-grid">
					<view class="op-item" @tap="openDoor">
						<image src="/static/icon/open.png" style="width:50rpx;height:50rpx;" />
						<text>开门开电</text>
					</view>
					<view class="op-item" @tap="closeDoor">
						<text style="font-size:50rpx;color:#f44336;">🔒</text>
						<text>关门关电</text>
					</view>
					<view class="op-item" @tap="disableRoom">
						<text style="font-size:50rpx;color:#ff9800;">⊘</text>
						<text>{{ roomItem.status == 0 ? '启用' : '禁用' }}</text>
					</view>
					<!-- 数据库定义：2=使用中, 4=待清洁 -->
					<view class="op-item" @tap="clearAndFinish" v-if="roomItem.status == 4 && roomItem.is_cleaning">
						<text style="font-size:50rpx;color:#2196F3;">✓</text>
						<text>清洁结单</text>
					</view>
					<view class="op-item" @tap="finishOrder" v-if="roomItem.status == 2">
						<text style="font-size:50rpx;color:#f44336;">✕</text>
						<text>强制结单</text>
					</view>
					<view class="op-item" @tap="toPlaceOrder">
						<text style="font-size:50rpx;color:#9C27B0;">✎</text>
						<text>代下单</text>
					</view>
				</view>
				
				<!-- 单独控制 -->
				<view class="device-control">
					<view class="device-title">单独控制</view>
					<view class="device-grid">
						<view class="device-btn" @tap="controlDevice('open_lock')">
							<text>🔑</text><text>开门锁</text>
						</view>
						<view class="device-btn" @tap="controlDevice('light_on')">
							<text>💡</text><text>开灯</text>
						</view>
						<view class="device-btn" @tap="controlDevice('light_off')">
							<text>🌑</text><text>关灯</text>
						</view>
						<view class="device-btn" @tap="controlDevice('ac_on')">
							<text>❄️</text><text>开空调</text>
						</view>
						<view class="device-btn" @tap="controlDevice('ac_off')">
							<text>🌡️</text><text>关空调</text>
						</view>
						<view class="device-btn" @tap="controlDevice('mahjong_on')">
							<text>🀄</text><text>开麻将机</text>
						</view>
					</view>
				</view>
				<button class="close-btn" @tap="closeRoomOp">关闭</button>
			</view>
		</u-popup>
	</view>
</template>

<script>
import http from '@/utils/http'
import { ROOM_STATUS_MAP, ROOM_TYPE_MAP } from '@/utils/constants'
import { getRoomStatusName, getRoomTypeName } from '@/utils/format'

export default {
	data() {
		return {
			stores: [],
			storeNames: ['全部门店'],
			store_id: '',
			currentStoreName: '全部门店',
			doorList: [],
			showRoomOp: false,
			roomItem: {},
			userType: 11,
			isCleaner: false
		}
	},
	onLoad(options) {
		if (options.store_id) {
			this.store_id = options.store_id
		}
		this.getUserInfo()
	},
	onShow() {
		this.getDoorList()
	},
	methods: {
		getUserInfo() {
			http.get('/member/user/get').then(res => {
				if (res.code == 0) {
					const ut = res.data.user_type || 11
					this.userType = ut
					this.isCleaner = (ut == 14)
					if (this.isCleaner && res.data.store_id) {
						this.store_id = res.data.store_id
						// 获取门店名称
						http.get('/member/index/getStoreInfo/' + res.data.store_id).then(sr => {
							if (sr.code == 0 && sr.data) {
								this.currentStoreName = sr.data.name || sr.data.store_name || '门店'
							}
						})
					}
					if (!this.isCleaner) {
						this.getStoreList()
					}
				}
			})
		},
		getStoreList() {
			http.get('/member/store/getStoreListByAdmin').then(res => {
				if (res.code == 0) {
					this.stores = res.data
					this.storeNames = ['全部门店', ...res.data.map(it => it.key)]
				}
			})
		},
		storeChange(e) {
			const idx = e.detail.value
			if (idx == 0) {
				this.store_id = ''
				this.currentStoreName = '全部门店'
			} else {
				this.store_id = this.stores[idx - 1].value
				this.currentStoreName = this.stores[idx - 1].key
			}
			this.getDoorList()
		},
		getDoorList() {
			let url = '/member/store/getRoomInfoList'
			if (this.store_id) url += '/' + this.store_id
			http.get(url).then(res => {
				if (res.code == 0) {
					const list = Array.isArray(res.data) ? res.data : (res.data.list || [])
					this.doorList = list.map(item => ({
						...item,
						room_name: item.room_name || item.name || '',
						room_id: item.room_id || item.id
					}))
				}
			})
		},
		openStoreDoor() {
			if (!this.store_id) {
				uni.showModal({ content: '请先选择门店', showCancel: false })
				return
			}
			http.post('/member/store/openStoreDoor/' + this.store_id).then(res => {
				if (res.code == 0) {
					uni.showToast({ title: '开门成功', icon: 'success' })
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			})
		},
		roomOp(e) {
			this.roomItem = e.currentTarget.dataset.room
			this.showRoomOp = true
		},
		closeRoomOp() {
			this.showRoomOp = false
			this.roomItem = {}
		},
		openDoor() {
			uni.showModal({
				title: '提示', content: '确定打开门和电源吗？',
				success: (res) => {
					if (res.confirm) {
						http.post('/member/order/openRoomDoor', { room_id: this.roomItem.room_id || this.roomItem.id }).then(r => {
							if (r.code == 0) uni.showToast({ title: '开门成功', icon: 'success' })
							else uni.showModal({ content: r.msg, showCancel: false })
						})
					}
				}
			})
		},
		closeDoor() {
			uni.showModal({
				title: '提示', content: '确定关闭门和电源吗？',
				success: (res) => {
					if (res.confirm) {
						http.post('/member/order/controlKT', { room_id: this.roomItem.room_id || this.roomItem.id, cmd: 1 }).then(r => {
							if (r.code == 0) uni.showToast({ title: '关门成功', icon: 'success' })
							else uni.showModal({ content: r.msg, showCancel: false })
						})
					}
				}
			})
		},
		disableRoom() {
			const roomId = this.roomItem.room_id || this.roomItem.id
			const newStatus = this.roomItem.status == 0 ? 1 : 0
			uni.showModal({
				title: '提示', content: '确定修改房间状态吗？',
				success: (res) => {
					if (res.confirm) {
						http.post('/member/store/disableRoom/' + roomId, { status: newStatus == 0 ? 2 : 1 }).then(r => {
							if (r.code == 0) {
								uni.showToast({ title: '操作成功', icon: 'success' })
								this.closeRoomOp()
								this.getDoorList()
							} else {
								uni.showModal({ content: r.msg, showCancel: false })
							}
						})
					}
				}
			})
		},
		clearAndFinish() {
			const roomId = this.roomItem.room_id || this.roomItem.id
			uni.showModal({
				title: '注意', content: '房间状态将变为空闲并立即关电，如有进行中订单将被结束！',
				success: (res) => {
					if (res.confirm) {
						http.post('/member/store/disableRoom/' + roomId, { status: 1 }).then(r => {
							if (r.code == 0) {
								uni.showToast({ title: '操作成功', icon: 'success' })
								this.closeRoomOp()
								this.getDoorList()
							}
						})
					}
				}
			})
		},
		finishOrder() {
			const roomId = this.roomItem.room_id || this.roomItem.id
			uni.showModal({
				title: '注意', content: '进行中的订单将被结束并立即关电！请谨慎操作！',
				success: (res) => {
					if (res.confirm) {
						http.post('/member/order/closeOrder/' + roomId).then(r => {
							if (r.code == 0) {
								uni.showToast({ title: '操作成功', icon: 'success' })
								this.closeRoomOp()
								this.getDoorList()
							}
						})
					}
				}
			})
		},
		toPlaceOrder() {
			this.closeRoomOp()
			const roomId = this.roomItem.room_id || this.roomItem.id
			uni.navigateTo({
				url: `/pages/order/place?id=${roomId}&roomName=${this.roomItem.room_name}`
			})
		},
		controlDevice(cmd) {
			const cmdNames = {
				'open_lock': '开门锁',
				'light_on': '开灯',
				'light_off': '关灯',
				'ac_on': '开空调',
				'ac_off': '关空调',
				'mahjong_on': '开麻将机'
			}
			const roomId = this.roomItem.room_id || this.roomItem.id
			http.post('/member/order/controlDevice', { room_id: roomId, cmd: cmd }).then(r => {
				if (r.code == 0) {
					uni.showToast({ title: (cmdNames[cmd] || cmd) + '成功', icon: 'success' })
				} else {
					uni.showModal({ content: r.msg || '操作失败', showCancel: false })
				}
			})
		}
	}
}
</script>

<style lang="scss" scoped>
.page { min-height: 100vh; background: #f5f5f5; }
.store-bar { display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 20rpx 30rpx; position: sticky; top: 0; z-index: 10; }
.store-picker { font-size: 28rpx; color: #333; padding: 10rpx 20rpx; background: #f5f5f5; border-radius: 8rpx; }
.btn-open { background: var(--main-color, #5AAB6E); color: #fff; font-size: 24rpx; border-radius: 30rpx; }
.room-list { padding: 20rpx; }
.room-card { display: flex; justify-content: space-between; align-items: center; background: #fff; border-radius: 16rpx; padding: 24rpx; margin-bottom: 16rpx; }
.room-name { font-size: 30rpx; font-weight: 600; margin-bottom: 10rpx; }
.room-status { font-size: 24rpx; padding: 4rpx 16rpx; border-radius: 20rpx; display: inline-block; color: #fff; }
/* 数据库定义：0=停用, 1=空闲, 2=使用中, 3=维护中, 4=待清洁/已预约 */
.s-0 { background: #999; }
.s-1 { background: var(--main-color, #5AAB6E); }
.s-2 { background: #f44336; }
.s-3 { background: #999; }
.s-4 { background: #ff9800; }
.s-4-cleaning { background: #58b92b; }
.room-right { text-align: right; }
.room-price { font-size: 28rpx; color: #f44336; font-weight: 600; display: block; }
.room-type { font-size: 24rpx; color: #999; }
.empty { text-align: center; padding: 100rpx 0; color: #999; }
.op-content { padding: 40rpx; }
.op-title { font-size: 32rpx; font-weight: 600; text-align: center; margin-bottom: 10rpx; }
.op-status { text-align: center; font-size: 26rpx; color: #666; margin-bottom: 30rpx; }
.op-grid { display: flex; flex-wrap: wrap; }
.op-item { width: 33.33%; display: flex; flex-direction: column; align-items: center; padding: 20rpx 0; }
.op-item text { font-size: 24rpx; margin-top: 10rpx; color: #333; }
.close-btn { margin-top: 30rpx; background: #f5f5f5; color: #666; border-radius: 30rpx; }
.device-control { margin-top: 30rpx; padding-top: 20rpx; border-top: 1px solid #eee; }
.device-title { font-size: 26rpx; color: #666; margin-bottom: 20rpx; }
.device-grid { display: flex; flex-wrap: wrap; gap: 16rpx; }
.device-btn { width: calc(33.33% - 12rpx); display: flex; flex-direction: column; align-items: center; padding: 16rpx 0; background: #f8f8f8; border-radius: 12rpx; }
.device-btn text:first-child { font-size: 36rpx; }
.device-btn text:last-child { font-size: 22rpx; color: #666; margin-top: 6rpx; }
</style>
