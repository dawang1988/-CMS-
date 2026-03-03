<template>
	<view class="container" :style="themeStyle">
		<!-- 搜索区域 -->
		<view class="search-area">
			<input class="search-input" placeholder="请输入订单编号或支付单号" v-model="orderNo" @confirm="onSearch" />
			<view class="search-btn" @tap="onSearch">搜索</view>
		</view>
		<view v-if="list.length > 0" class="list">
			<view class="item" v-for="(item, index) in list" :key="index">
				<view class="top">
					<view class="order-no">订单编号：{{ item.order_no }}</view>
					<view class="btn-refund" v-if="item.refund_price < item.price" @tap="refund(item)">退款</view>
				</view>
				<view class="info-line">支付单号：{{ item.pay_order_no }}</view>
				<view class="info-line">付款时间：{{ item.pay_time }}</view>
				<view class="price-row">
					<view class="price-item">
						<text class="amount-label">支付金额</text>
						<text class="amount-value green">¥{{ (item.price / 100.0).toFixed(2) }}</text>
					</view>
					<view class="price-divider"></view>
					<view class="price-item">
						<text class="amount-label">已退款金额</text>
						<text class="amount-value red">¥{{ (item.refund_price / 100.0).toFixed(2) }}</text>
					</view>
				</view>
				<view class="desc" v-if="item.goods_name">订单内容：{{ item.goods_name }}</view>
			</view>
			<view class="note-more" v-if="canLoadMore">下拉刷新查看更多...</view>
		</view>
		<view class="nodata-list" v-else>暂无数据</view>

		<!-- 退款弹框 -->
		<u-popup :show="showRefundModal" mode="center" round="12" @close="hideRefundModal">
			<view class="modal-content">
				<view class="modal-title">手动退款</view>
				<view class="refund-info">
					<text>最大可退款金额：</text>
					<text class="refund-max">¥{{ maxRefundAmount }}</text>
				</view>
				<view class="input-group">
					<text class="input-label">退款金额(可修改)</text>
					<view class="input-wrapper">
						<text>¥</text>
						<input type="digit" v-model="refundAmount" placeholder="请输入退款金额" />
					</view>
				</view>
				<view class="quick-options">
					<view class="option-btn" :class="{ active: selectedOption === 'full' }" @tap="selectOption('full')">全退</view>
					<view class="option-btn" :class="{ active: selectedOption === 'half' }" @tap="selectOption('half')">退一半</view>
					<view class="option-btn" :class="{ active: selectedOption === 'quarter' }" @tap="selectOption('quarter')">退25%</view>
				</view>
				<view class="tip">提示：此处为直接退款，并不会对订单产生任何影响</view>
				<view class="modal-btns">
					<button class="cancel-btn" @tap="hideRefundModal">取消</button>
					<button class="confirm-btn" @tap="confirmRefund">确定</button>
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
			store_id: '',
			pageNo: 1,
			pageSize: 10,
			canLoadMore: true,
			list: [],
			order_no: '',
			showRefundModal: false,
			maxRefundAmount: 0,
			refundAmount: '',
			selectedOption: 'full',
			currentOrder: null
		}
	},
	onLoad(options) {
		this.store_id = options.store_id || ''
	},
	onShow() {
		this.getList('refresh')
	},
	onPullDownRefresh() {
		this.getList('refresh')
		uni.stopPullDownRefresh()
	},
	onReachBottom() {
		if (this.canLoadMore) this.getList()
	},
	methods: {
		getList(e) {
			if (e === 'refresh') {
				this.list = []
				this.canLoadMore = true
				this.pageNo = 1
			}
			http.post('/member/manager/getPayOrderPage', {
				pageNo: this.pageNo,
				pageSize: this.pageSize,
				store_id: this.store_id,
				order_no: this.order_no
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
		onSearch() {
			this.getList('refresh')
		},
		refund(item) {
			const maxRefund = (item.price - item.refund_price) / 100.0
			this.maxRefundAmount = maxRefund.toFixed(2)
			this.refundAmount = maxRefund.toFixed(2)
			this.selectedOption = 'full'
			this.currentOrder = item
			this.showRefundModal = true
		},
		hideRefundModal() {
			this.showRefundModal = false
			this.currentOrder = null
		},
		selectOption(option) {
			const maxAmount = parseFloat(this.maxRefundAmount)
			let amount = 0
			if (option === 'full') amount = maxAmount
			else if (option === 'half') amount = maxAmount * 0.5
			else if (option === 'quarter') amount = maxAmount * 0.25
			this.selectedOption = option
			this.refundAmount = amount.toFixed(2)
		},
		confirmRefund() {
			const amount = parseFloat(this.refundAmount)
			const maxAmount = parseFloat(this.maxRefundAmount)
			if (!amount || amount <= 0) {
				uni.showToast({ title: '请输入有效退款金额', icon: 'none' })
				return
			}
			if (amount > maxAmount) {
				uni.showToast({ title: '退款金额不能超过最大可退款金额', icon: 'none' })
				return
			}
			http.post('/member/manager/refundPayOrder', {
				id: this.currentOrder.id,
				price: amount * 100
			}).then(res => {
				if (res.code == 0) {
					uni.showToast({ title: '操作成功', icon: 'success' })
					this.hideRefundModal()
					this.getList('refresh')
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			})
		}
	}
}
</script>

<style lang="scss" scoped>
.container { min-height: 100vh; background: #f5f5f5; padding: 16rpx; }
.search-area { display: flex; align-items: center; gap: 16rpx; margin-bottom: 20rpx; background: #fff; padding: 20rpx; border-radius: 12rpx; }
.search-input { flex: 1; height: 64rpx; padding: 0 20rpx; background: #f8f9fa; border: 1rpx solid #e1e5e9; border-radius: 32rpx; font-size: 26rpx; }
.search-btn { background: var(--main-color, #5AAB6E); color: #fff; padding: 16rpx 24rpx; border-radius: 32rpx; font-size: 26rpx; white-space: nowrap; }
.list { display: flex; flex-direction: column; gap: 16rpx; }
.item { background: #fff; border-radius: 12rpx; padding: 20rpx; }
.top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12rpx; }
.order-no { font-size: 28rpx; font-weight: 600; }
.btn-refund { background: #ff6b35; color: #fff; padding: 10rpx 30rpx; border-radius: 20rpx; font-size: 22rpx; }
.info-line { font-size: 26rpx; color: #4a5568; line-height: 1.5; margin-bottom: 8rpx; }
.price-row { display: flex; background: linear-gradient(135deg, #f8fafc 0%, #e8f4fd 100%); border-radius: 10rpx; border: 1rpx solid #e1f0ff; margin: 12rpx 0; }
.price-item { flex: 1; display: flex; flex-direction: column; align-items: center; padding: 16rpx; }
.amount-label { font-size: 22rpx; color: #666; margin-bottom: 6rpx; }
.amount-value { font-size: 28rpx; font-weight: 700; }
.green { color: #27ae60; }
.red { color: #e74c3c; }
.price-divider { width: 1rpx; background: #d1d8e0; margin: 10rpx 0; }
.desc { background: #f8f9fa; padding: 16rpx; border-radius: 8rpx; font-size: 24rpx; color: #666; border-left: 3rpx solid var(--main-color, #5AAB6E); }

.modal-content { padding: 40rpx; width: 600rpx; }
.modal-title { font-size: 32rpx; font-weight: 600; text-align: center; margin-bottom: 30rpx; }
.refund-info { display: flex; justify-content: space-between; padding: 20rpx; background: #f8f9fa; border-radius: 10rpx; margin-bottom: 20rpx; font-size: 26rpx; }
.refund-max { color: #e74c3c; font-weight: 600; }
.input-group { margin-bottom: 20rpx; }
.input-label { font-size: 26rpx; margin-bottom: 12rpx; display: block; }
.input-wrapper { display: flex; align-items: center; border: 1rpx solid #e1e5e9; border-radius: 10rpx; padding: 0 20rpx; }
.input-wrapper input { flex: 1; height: 80rpx; font-size: 28rpx; }
.quick-options { display: flex; gap: 16rpx; margin-bottom: 20rpx; }
.option-btn { flex: 1; padding: 16rpx; text-align: center; background: #f8f9fa; border: 1rpx solid #e1e5e9; border-radius: 8rpx; font-size: 24rpx; color: #666; }
.option-btn.active { background: var(--main-color, #5AAB6E); color: #fff; border-color: var(--main-color, #5AAB6E); }
.tip { font-size: 24rpx; color: #f44336; margin-bottom: 20rpx; }
.modal-btns { display: flex; gap: 20rpx; }
.modal-btns button { flex: 1; border-radius: 30rpx; font-size: 28rpx; }
.cancel-btn { background: #f5f5f5; color: #666; }
.confirm-btn { background: var(--main-color, #5AAB6E); color: #fff; }
</style>
