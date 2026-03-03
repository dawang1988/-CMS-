<template>
	<view class="container" :style="themeStyle">
		<view v-if="list.length > 0" class="list">
			<view class="item" v-for="(item, index) in list" :key="index">
				<view class="top">
					<view class="order-no">订单编号：{{ item.order_no }}</view>
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
		<view class="nodata-list" v-else>暂无退款记录</view>
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
			list: []
		}
	},
	onLoad(options) {
		this.store_id = options.store_id || ''
	},
	onShow() {
		this.getList('refresh')
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
				refundOnly: true
			}).then(res => {
				if (res.code == 0) {
					const refundList = (res.data.list || []).filter(item => item.refund_price > 0)
					if (refundList.length === 0 && res.data.list.length === 0) {
						this.canLoadMore = false
					} else {
						this.list = this.list.concat(refundList)
						this.pageNo++
						this.canLoadMore = this.list.length < res.data.total
					}
				}
			})
		}
	}
}
</script>

<style lang="scss" scoped>
.container { min-height: 100vh; background: #f5f5f5; padding: 16rpx; }
.list { display: flex; flex-direction: column; gap: 16rpx; }
.item { background: #fff; border-radius: 12rpx; padding: 20rpx; }
.top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12rpx; }
.order-no { font-size: 28rpx; font-weight: 600; }
.info-line { font-size: 26rpx; color: #4a5568; line-height: 1.5; margin-bottom: 8rpx; }
.price-row { display: flex; background: linear-gradient(135deg, #f8fafc 0%, #e8f4fd 100%); border-radius: 10rpx; border: 1rpx solid #e1f0ff; margin: 12rpx 0; }
.price-item { flex: 1; display: flex; flex-direction: column; align-items: center; padding: 16rpx; }
.amount-label { font-size: 22rpx; color: #666; margin-bottom: 6rpx; }
.amount-value { font-size: 28rpx; font-weight: 700; }
.green { color: #27ae60; }
.red { color: #e74c3c; }
.price-divider { width: 1rpx; background: #d1d8e0; margin: 10rpx 0; }
.desc { background: #f8f9fa; padding: 16rpx; border-radius: 8rpx; font-size: 24rpx; color: #666; border-left: 3rpx solid #5AAB6E; }

</style>
