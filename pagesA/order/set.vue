<template>
	<view class="page" :style="themeStyle">
		<!-- 筛选栏 -->
		<view class="filter-bar">
			<picker mode="selector" :range="storeNames" @change="storeChange">
				<view class="filter-item">{{ store_id ? currentStoreName : '全部门店' }} ▼</view>
			</picker>
			<picker mode="selector" :range="statusNames" @change="statusChange">
				<view class="filter-item">{{ statusNames[statusIndex] }} ▼</view>
			</picker>
			<picker mode="selector" :range="sortNames" @change="sortChange">
				<view class="filter-item">{{ sortNames[sortIndex] }} ▼</view>
			</picker>
		</view>

		<!-- 订单列表 -->
		<view class="order-list">
			<view class="order-card" v-for="(item, index) in orderlist" :key="index">
				<view class="order-top">
					<text class="order-no" @tap="copyText" :data-text="item.order_no">{{ item.order_no }}</text>
					<text class="status" :class="'status-' + item.status">
						{{ item.status == 0 ? '未开始' : item.status == 1 ? '进行中' : item.status == 2 ? '已完成' : '已取消' }}
					</text>
				</view>
				<view class="order-info">
					<view class="info-row"><text class="label">门店：</text><text>{{ item.store_name }}</text></view>
					<view class="info-row"><text class="label">房间：</text><text>{{ item.room_name }}</text></view>
					<view class="info-row"><text class="label">用户：</text><text>{{ item.nickname || item.phone }}</text></view>
					<view class="info-row"><text class="label">开始：</text><text>{{ item.start_time }}</text></view>
					<view class="info-row"><text class="label">结束：</text><text>{{ item.end_time }}</text></view>
					<view class="info-row"><text class="label">金额：</text><text class="price">￥{{ item.total_amount || item.price || 0 }}</text></view>
				</view>
				<view class="order-btns">
					<view class="btn" @tap="goChangeRoom" :data-info="item" v-if="item.status == 0 || item.status == 1">换房</view>
					<view class="btn" @tap="renewClick" :data-info="item" v-if="item.status == 0 || item.status == 1">续费</view>
					<view class="btn" @tap="cancelOrder" :data-info="item" v-if="item.status == 0 || item.status == 1">取消</view>
					<view class="btn warn" @tap="finishOrder" :data-info="item" v-if="item.status == 1">结单</view>
					<view class="btn" @tap="callMobile" :data-mobile="item.phone" v-if="item.phone">电话</view>
				</view>
			</view>
			<view class="empty" v-if="orderlist.length === 0">暂无订单</view>
		</view>

		<!-- 续费弹窗 -->
		<u-popup :show="renewShow" mode="center" round="12" @close="renewCancel">
			<view class="popup-content">
				<view class="popup-title">续费</view>
				<view class="popup-row">
					<text>房间：{{ orderInfo.room_name }}</text>
				</view>
				<view class="popup-row">
					<text>当前结束：{{ orderInfo.end_time }}</text>
				</view>
				<view class="popup-row">
					<text>增加小时：</text>
					<view class="number-box">
						<text class="nb-btn" @click="addTimeH > 0 && (addTimeH--, onChangeH())">-</text>
						<text class="nb-val">{{ addTimeH }}</text>
						<text class="nb-btn" @click="addTimeH < 24 && (addTimeH++, onChangeH())">+</text>
					</view>
				</view>
				<view class="popup-row">
					<text>增加分钟：</text>
					<view class="number-box">
						<text class="nb-btn" @click="addTimeM >= 10 && (addTimeM -= 10, onChangeM())">-</text>
						<text class="nb-val">{{ addTimeM }}</text>
						<text class="nb-btn" @click="addTimeM <= 49 && (addTimeM += 10, onChangeM())">+</text>
					</view>
				</view>
				<view class="popup-row" v-if="newTime">
					<text>续费后结束：{{ newTime }}</text>
				</view>
				<view class="popup-row" v-if="totalPay > 0">
					<text>预计费用：</text><text class="price">￥{{ totalPay }}</text>
				</view>
				<view class="popup-btns">
					<button class="cancel-btn" @tap="renewCancel">取消</button>
					<button class="confirm-btn" @tap="renewConfirm">确认续费</button>
				</view>
			</view>
		</u-popup>
	</view>
</template>

<script>
import http from '@/utils/http'
import { ORDER_STATUS_LIST, ORDER_SORT_LIST } from '@/utils/constants'
import { getOrderStatusName, formatDateTime } from '@/utils/format'
import listMixin from '@/mixins/listMixin'

export default {
	mixins: [listMixin],
	data() {
		return {
			stores: [],
			storeNames: ['全部门店'],
			store_id: '',
			currentStoreName: '全部门店',
			statusIndex: 0,
			statusNames: ['全部状态', '未开始', '进行中', '已完成', '已取消'],
			statusValues: ['', 0, 1, 2, 3],
			sortIndex: 0,
			sortNames: ['默认排序', '下单时间', '预约时间'],
			sortValues: ['', 'createTime', 'startTime'],
			status: '',
			orderColumn: '',
			orderlist: [],
			renewShow: false,
			orderInfo: {},
			addTimeH: 0,
			addTimeM: 0,
			newTime: '',
			totalPay: 0,
			user_id: ''
		}
	},
	onLoad(options) {
		if (options.user_id) this.user_id = options.user_id
		this.getStoreList()
		this.getOrderList('refresh')
	},
	onPullDownRefresh() {
		this.getOrderList('refresh')
		uni.stopPullDownRefresh()
	},
	onReachBottom() {
		if (this.canLoadMore) {
			this.getOrderList()
		}
	},
	methods: {
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
			this.getOrderList('refresh')
		},
		statusChange(e) {
			this.statusIndex = e.detail.value
			this.status = this.statusValues[e.detail.value]
			this.getOrderList('refresh')
		},
		sortChange(e) {
			this.sortIndex = e.detail.value
			this.orderColumn = this.sortValues[e.detail.value]
			this.getOrderList('refresh')
		},
		getOrderList(e) {
			if (e === 'refresh') {
				this.orderlist = []
				this.canLoadMore = true
				this.pageNo = 1
			}
			http.post('/member/order/getOrderPage', {
				pageNo: this.pageNo,
				pageSize: this.pageSize,
				status: this.status,
				store_id: this.store_id,
				user_id: this.user_id,
				orderColumn: this.orderColumn
			}).then(res => {
				if (res.code == 0) {
					if (res.data.list.length === 0) {
						this.canLoadMore = false
					} else {
						this.orderlist = this.orderlist.concat(res.data.list)
						this.pageNo++
						this.canLoadMore = this.orderlist.length < res.data.total
					}
				}
			})
		},
		goChangeRoom(e) {
			var info = e.currentTarget.dataset.info
			var orderInfo = {
				"order_id": info.id || info.order_id,
				"store_id": info.store_id,
				"store_name": info.store_name || '',
				"room_name": info.room_name || '',
				"start_time": info.start_time || '',
				"end_time": info.end_time || '',
				"room_type": info.room_type || 0,
				"room_id": info.room_id,
				"room_class": info.room_class || 0,
				"phone": info.phone || ''
			}
			uni.navigateTo({
				url: '/pages/room/change?orderInfo=' + encodeURIComponent(JSON.stringify(orderInfo))
			})
		},
		cancelOrder(e) {
			const info = e.currentTarget.dataset.info
			uni.showModal({
				title: '确认取消',
				content: '确定取消该订单吗？',
				success: (res) => {
					if (res.confirm) {
						http.post('/member/order/cancelOrder/' + info.id).then(r => {
							if (r.code == 0) {
								uni.showToast({ title: '取消成功' })
								this.getOrderList('refresh')
							} else {
								uni.showModal({ content: r.msg, showCancel: false })
							}
						})
					}
				}
			})
		},
		finishOrder(e) {
			const info = e.currentTarget.dataset.info
			uni.showModal({
				title: '注意',
				content: '进行中的订单将被结束并立即关电，确认操作？',
				success: (res) => {
					if (res.confirm) {
						http.post('/member/order/closeOrder/' + info.id).then(r => {
							if (r.code == 0) {
								uni.showToast({ title: '操作成功' })
								this.getOrderList('refresh')
							} else {
								uni.showModal({ content: r.msg, showCancel: false })
							}
						})
					}
				}
			})
		},
		renewClick(e) {
			const info = e.currentTarget.dataset.info
			if (info.status == 2 || info.status == 3) {
				uni.showModal({ title: '提示', content: '订单不允许续费，请重新下单', showCancel: false })
				return
			}
			this.orderInfo = info
			this.addTimeH = 0
			this.addTimeM = 0
			this.newTime = ''
			this.totalPay = 0
			this.renewShow = true
		},
		onChangeH() {
			this.calcRenew()
		},
		onChangeM() {
			this.calcRenew()
		},
		calcRenew() {
			const minute = this.addTimeH * 60 + this.addTimeM
			if (minute > 0 && this.orderInfo.end_time) {
				const end = new Date(this.orderInfo.end_time.replace(/-/g, '/'))
				end.setMinutes(end.getMinutes() + minute)
				const pad = n => n < 10 ? '0' + n : n
				this.newTime = `${end.getFullYear()}-${pad(end.getMonth()+1)}-${pad(end.getDate())} ${pad(end.getHours())}:${pad(end.getMinutes())}:00`
				this.totalPay = (minute * (this.orderInfo.price || 0) / 60).toFixed(2)
			}
		},
		renewConfirm() {
			if (!this.newTime) {
				uni.showToast({ title: '请选择增加时间', icon: 'none' })
				return
			}
			http.post('/member/order/renew', {
				id: this.orderInfo.id,
				end_time: this.newTime,
				order_no: this.orderInfo.order_no
			}).then(res => {
				if (res.code == 0) {
					uni.showToast({ title: '续时成功' })
					this.renewCancel()
					this.getOrderList('refresh')
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			})
		},
		renewCancel() {
			this.renewShow = false
		},
		callMobile(e) {
			uni.makePhoneCall({ phoneNumber: e.currentTarget.dataset.mobile })
		},
		copyText(e) {
			uni.setClipboardData({
				data: e.currentTarget.dataset.text,
				success: () => uni.showToast({ title: '复制成功' })
			})
		}
	}
}
</script>

<style lang="scss" scoped>
.page { min-height: 100vh; background: #f5f5f5; }
.filter-bar { display: flex; background: #fff; padding: 20rpx; position: sticky; top: 0; z-index: 10; }
.filter-item { flex: 1; text-align: center; font-size: 26rpx; color: #333; padding: 10rpx 0; }
.order-list { padding: 20rpx; }
.order-card { background: #fff; border-radius: 16rpx; padding: 24rpx; margin-bottom: 20rpx; }
.order-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16rpx; border-bottom: 1rpx solid #f0f0f0; padding-bottom: 16rpx; }
.order-no { font-size: 24rpx; color: #666; }
.status { font-size: 26rpx; font-weight: 600; }
.status-0 { color: #ff9800; }
.status-1 { color: var(--main-color, #5AAB6E); }
.status-2 { color: #999; }
.status-3 { color: #f44336; }
.info-row { display: flex; font-size: 26rpx; line-height: 48rpx; }
.info-row .label { color: #999; width: 100rpx; }
.price { color: #f44336; font-weight: 600; }
.order-btns { display: flex; justify-content: flex-end; margin-top: 16rpx; padding-top: 16rpx; border-top: 1rpx solid #f0f0f0; }
.order-btns .btn { padding: 8rpx 24rpx; border: 1rpx solid var(--main-color, #5AAB6E); color: var(--main-color, #5AAB6E); border-radius: 30rpx; font-size: 24rpx; margin-left: 16rpx; }
.order-btns .btn.warn { border-color: #f44336; color: #f44336; }
.empty { text-align: center; padding: 100rpx 0; color: #999; font-size: 28rpx; }
.popup-content { padding: 40rpx; width: 600rpx; }
.popup-title { font-size: 32rpx; font-weight: 600; text-align: center; margin-bottom: 30rpx; }
.popup-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20rpx; font-size: 28rpx; }
.popup-btns { display: flex; justify-content: space-between; margin-top: 30rpx; }
.popup-btns button { width: 45%; border-radius: 30rpx; font-size: 28rpx; }
.cancel-btn { background: #f5f5f5; color: #666; }
.confirm-btn { background: var(--main-color, #5AAB6E); color: #fff; }

.number-box { display: flex; align-items: center; }
.nb-btn { width: 56rpx; height: 56rpx; line-height: 56rpx; text-align: center; background: #f5f5f5; border-radius: 8rpx; font-size: 32rpx; color: #333; }
.nb-val { min-width: 80rpx; text-align: center; font-size: 28rpx; }
</style>
