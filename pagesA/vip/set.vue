<template>
	<view class="page" :style="themeStyle">
		<!-- 搜索栏 -->
		<view class="search-bar">
			<input class="search-input" placeholder="搜索用户昵称/手机号" v-model="name" @confirm="search" />
			<button class="search-btn" @tap="search">搜索</button>
		</view>

		<!-- 排序栏 -->
		<view class="sort-bar">
			<view class="sort-item" :class="{ active: cloumnName == 'orderTime' }" @tap="sortBy" data-info="orderTime">
				最近消费 {{ cloumnName == 'orderTime' ? (sortRule == 'ASC' ? '↑' : '↓') : '' }}
			</view>
			<view class="sort-item" :class="{ active: cloumnName == 'createTime' }" @tap="sortBy" data-info="createTime">
				注册时间 {{ cloumnName == 'createTime' ? (sortRule == 'ASC' ? '↑' : '↓') : '' }}
			</view>
			<view class="sort-item" :class="{ active: cloumnName == 'orderCount' }" @tap="sortBy" data-info="orderCount">
				消费次数 {{ cloumnName == 'orderCount' ? (sortRule == 'ASC' ? '↑' : '↓') : '' }}
			</view>
		</view>

		<!-- 用户列表 -->
		<view class="user-list">
			<view class="user-card" v-for="(item, index) in MainList" :key="index">
				<view class="user-top" @tap="toDetail" :data-info="item">
					<image class="avatar" :src="item.avatar || '/static/logo.png'" mode="aspectFill"></image>
					<view class="user-info">
						<view class="nickname">{{ item.nickname || '未设置昵称' }}</view>
						<view class="mobile" @tap.stop="copy" :data-info="item.phone">{{ item.phone }} 📋</view>
					</view>
					<view class="vip-tag" v-if="item.vip_level > 0">VIP{{ item.vip_level }}</view>
				</view>
				<view class="user-stats">
					<view class="stat">
						<text class="label">消费次数</text>
						<text class="value">{{ item.orderCount || 0 }}</text>
					</view>
					<view class="stat">
						<text class="label">余额</text>
						<text class="value">{{ item.balance || 0 }}</text>
					</view>
					<view class="stat">
						<text class="label">最近消费</text>
						<text class="value">{{ item.lastOrderTime || '-' }}</text>
					</view>
				</view>
				<view class="user-btns" v-if="!isSelect">
					<view class="btn" @tap="recharge" :data-info="item">充值</view>
					<view class="btn" @tap="toDetail" :data-info="item">详情</view>
				</view>
				<view class="user-btns" v-else>
					<view class="btn primary" @tap="selectUser" :data-info="item">选择</view>
				</view>
			</view>
			<view class="empty" v-if="MainList.length === 0">暂无用户数据</view>
		</view>

		<!-- 充值弹窗 -->
		<u-popup :show="showRecharge" mode="center" round="12" @close="cancelRecharge">
			<view class="popup-content">
				<view class="popup-title">充值余额</view>
				<view class="popup-row">用户：{{ member.nickname || member.phone }}</view>
				<view class="popup-row">
					<text>门店：</text>
					<picker mode="selector" :range="storeNames" @change="bindStoreChange">
						<view class="store-select">{{ storeIndex !== '' ? storeNames[storeIndex] : '请选择门店' }} ▼</view>
					</picker>
				</view>
				<view class="popup-row">
					<text>金额：</text>
					<input class="money-input" type="digit" placeholder="输入充值金额" v-model="money" />
				</view>
				<view class="popup-btns">
					<button class="cancel-btn" @tap="cancelRecharge">取消</button>
					<button class="confirm-btn" @tap="confirmRecharge">确认充值</button>
				</view>
			</view>
		</u-popup>
	</view>
</template>

<script>
import http from '@/utils/http'

export default {
	data() {
		return {
			isSelect: 0,
			coupon_id: '',
			MainList: [],
			stores: [],
			storeNames: [],
			storeIndex: '',
			canLoadMore: true,
			pageNo: 1,
			pageSize: 10,
			name: '',
			cloumnName: 'orderTime',
			sortRule: 'DESC',
			showRecharge: false,
			member: {},
			money: '',
			rechargeStoreId: ''
		}
	},
	onLoad(options) {
		if (options.isSelect) this.isSelect = parseInt(options.isSelect)
		if (options.coupon_id) this.coupon_id = options.coupon_id
		if (this.isSelect === 1) uni.setNavigationBarTitle({ title: '选择会员' })
		this.getListData('refresh')
		this.getStoreList()
	},
	onPullDownRefresh() {
		this.getListData('refresh')
		uni.stopPullDownRefresh()
	},
	onReachBottom() {
		if (this.canLoadMore) this.getListData()
	},
	methods: {
		getStoreList() {
			http.get('/member/store/getStoreListByAdmin').then(res => {
				if (res.code == 0) {
					this.stores = res.data
					this.storeNames = res.data.map(it => it.key)
				}
			})
		},
		getListData(e) {
			if (e === 'refresh') {
				this.pageNo = 1
				this.MainList = []
				this.canLoadMore = true
			}
			http.post('/member/manager/getVipPage', {
				pageNo: this.pageNo,
				pageSize: this.pageSize,
				name: this.name,
				cloumnName: this.cloumnName,
				sortRule: this.sortRule
			}).then(res => {
				if (res.code == 0) {
					if (res.data.list.length === 0) {
						this.canLoadMore = false
					} else {
						this.MainList = this.MainList.concat(res.data.list)
						this.pageNo++
						this.canLoadMore = this.MainList.length < res.data.total
					}
				}
			})
		},
		search() {
			this.getListData('refresh')
		},
		sortBy(e) {
			const col = e.currentTarget.dataset.info
			if (this.cloumnName === col) {
				this.sortRule = this.sortRule === 'ASC' ? 'DESC' : 'ASC'
			} else {
				this.cloumnName = col
				this.sortRule = 'DESC'
			}
			this.getListData('refresh')
		},
		selectUser(e) {
			const info = e.currentTarget.dataset.info
			if (this.coupon_id) {
				uni.showModal({
					title: '提示', content: '确定赠送该会员优惠券？',
					success: (res) => {
						if (res.confirm) {
							http.post('/member/manager/giftCoupon', {
								coupon_id: this.coupon_id,
								user_id: info.id
							}).then(r => {
								if (r.code == 0) {
									uni.showToast({ title: '赠送成功' })
									setTimeout(() => uni.navigateBack(), 1000)
								} else {
									uni.showModal({ content: r.msg, showCancel: false })
								}
							})
						}
					}
				})
			}
		},
		copy(e) {
			uni.setClipboardData({
				data: e.currentTarget.dataset.info,
				success: () => uni.showToast({ title: '复制成功' })
			})
		},
		toDetail(e) {
			const info = e.currentTarget.dataset.info
			uni.navigateTo({ url: '/pagesA/vip/detail?info=' + encodeURIComponent(JSON.stringify(info)) })
		},
		recharge(e) {
			this.member = e.currentTarget.dataset.info
			this.money = ''
			this.rechargeStoreId = ''
			this.storeIndex = ''
			this.showRecharge = true
		},
		bindStoreChange(e) {
			this.storeIndex = e.detail.value
			this.rechargeStoreId = this.stores[e.detail.value].value
		},
		confirmRecharge() {
			if (!this.rechargeStoreId) {
				uni.showToast({ title: '请选择门店', icon: 'none' })
				return
			}
			if (!this.money || this.money <= 0) {
				uni.showToast({ title: '请输入金额', icon: 'none' })
				return
			}
			http.post('/member/user/rechargeBalance', {
				user_id: this.member.id,
				store_id: this.rechargeStoreId,
				money: this.money
			}).then(res => {
				if (res.code == 0) {
					uni.showToast({ title: '充值成功' })
					this.cancelRecharge()
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			})
		},
		cancelRecharge() {
			this.showRecharge = false
		}
	}
}
</script>

<style lang="scss" scoped>
.page { min-height: 100vh; background: #f5f5f5; }
.search-bar { display: flex; padding: 20rpx; background: #fff; }
.search-input { flex: 1; background: #f5f5f5; border-radius: 30rpx; padding: 10rpx 24rpx; font-size: 26rpx; }
.search-btn { background: var(--main-color, #5AAB6E); color: #fff; font-size: 26rpx; border-radius: 30rpx; margin-left: 16rpx; padding: 0 30rpx; line-height: 60rpx; height: 60rpx; }
.sort-bar { display: flex; background: #fff; padding: 16rpx 20rpx; border-top: 1rpx solid #f0f0f0; }
.sort-item { flex: 1; text-align: center; font-size: 24rpx; color: #666; padding: 8rpx 0; }
.sort-item.active { color: var(--main-color, #5AAB6E); font-weight: 600; }
.user-list { padding: 20rpx; }
.user-card { background: #fff; border-radius: 16rpx; padding: 24rpx; margin-bottom: 16rpx; }
.user-top { display: flex; align-items: center; }
.avatar { width: 80rpx; height: 80rpx; border-radius: 50%; margin-right: 20rpx; }
.user-info { flex: 1; }
.nickname { font-size: 28rpx; font-weight: 600; }
.mobile { font-size: 24rpx; color: #666; margin-top: 6rpx; }
.vip-tag { background: linear-gradient(135deg, #FFD700, #FFA500); color: #fff; font-size: 22rpx; padding: 4rpx 16rpx; border-radius: 20rpx; }
.user-stats { display: flex; margin-top: 16rpx; padding-top: 16rpx; border-top: 1rpx solid #f0f0f0; }
.stat { flex: 1; text-align: center; }
.stat .label { font-size: 22rpx; color: #999; display: block; }
.stat .value { font-size: 26rpx; font-weight: 600; color: #333; }
.user-btns { display: flex; justify-content: flex-end; margin-top: 16rpx; }
.user-btns .btn { padding: 8rpx 24rpx; border: 1rpx solid var(--main-color, #5AAB6E); color: var(--main-color, #5AAB6E); border-radius: 30rpx; font-size: 24rpx; margin-left: 16rpx; }
.user-btns .btn.primary { background: var(--main-color, #5AAB6E); color: #fff; }
.empty { text-align: center; padding: 100rpx 0; color: #999; }
.popup-content { padding: 40rpx; width: 600rpx; }
.popup-title { font-size: 32rpx; font-weight: 600; text-align: center; margin-bottom: 30rpx; }
.popup-row { margin-bottom: 20rpx; font-size: 28rpx; display: flex; align-items: center; }
.store-select { background: #f5f5f5; padding: 10rpx 20rpx; border-radius: 8rpx; }
.money-input { flex: 1; background: #f5f5f5; padding: 10rpx 20rpx; border-radius: 8rpx; }
.popup-btns { display: flex; justify-content: space-between; margin-top: 30rpx; }
.popup-btns button { width: 45%; border-radius: 30rpx; font-size: 28rpx; }
.cancel-btn { background: #f5f5f5; color: #666; }
.confirm-btn { background: var(--main-color, #5AAB6E); color: #fff; }
</style>
