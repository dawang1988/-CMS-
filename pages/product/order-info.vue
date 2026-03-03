<template>
	<scroll-view scroll-y class="page" :style="themeStyle">
		<view class="container">
			<view class="store-info">
				<text>{{ productOrderInfo.order_no }}</text>
				<view v-if="productOrderInfo.status == 0" class="status-css wait">待支付</view>
				<view v-if="productOrderInfo.status == 1" class="status-css send">待配送</view>
				<view v-if="productOrderInfo.status == 2" class="status-css end">已完成</view>
				<view v-if="productOrderInfo.status == 3" class="status-css cancel">已取消</view>
			</view>
			<view class="order-info">
				<view class="xian"></view>
				<view v-for="(item, idx) in displayList" :key="idx">
					<view class="productInfo">
						<view class="image">
							<image :src="item.image" style="height: 100rpx; width: 100rpx;" mode="aspectFill" />
						</view>
						<view class="attrs">
							<view><text>{{ item.name }}</text></view>
							<view><text>{{ item.valueStr }}</text></view>
						</view>
						<view class="price-num">
							<view style="display: flex;">
								<view class="product-price">￥{{ item.price }}</view>
								<view class="product-number">x{{ item.number }}</view>
							</view>
						</view>
					</view>
				</view>
				<view class="showmore" v-if="productOrderInfo.productInfoVoList && productOrderInfo.productInfoVoList.length > 3" @tap="showMore">
					<text>{{ isShowAll ? '收起' : '展示更多' }}</text>
				</view>
				<view class="xian"></view>
				<view class="info-line"><text>商品总数：</text><text>{{ productOrderInfo.productNum }}</text></view>
				<view class="info-line"><text>订单总金额：</text><text class="price-red">￥{{ productOrderInfo.pay_amount || productOrderInfo.totalPrice || 0 }}</text></view>
				<view class="info-line"><text>用户名：</text><text>{{ productOrderInfo.userName }}</text></view>
				<view class="info-line"><text>手机号：</text><text>{{ productOrderInfo.userPhone }}</text></view>
				<view class="info-line"><text>备注：</text><text>{{ productOrderInfo.mark }}</text></view>
				<view class="info-line"><text>下单时间：</text><text>{{ productOrderInfo.create_time }}</text></view>
				<view class="info-line"><text>支付方式：</text><text>微信支付</text></view>
			</view>
		</view>
	</scroll-view>
</template>

<script>
import http from '@/utils/http.js'

export default {
	data() {
		return {
			store_id: '',
			order_id: '',
			productOrderInfo: {},
			isShowAll: false
		}
	},
	computed: {
		displayList() {
			const list = this.productOrderInfo.productInfoVoList || []
			return this.isShowAll ? list : list.slice(0, 3)
		}
	},
	onLoad(options) {
		this.store_id = options.store_id || options.storeId || ''
		this.order_id = options.order_id || options.orderId || options.id || ''
	},
	onShow() {
		this.getOrderInfo()
	},
	methods: {
		getOrderInfo() {
			if (!this.order_id) return
			uni.showLoading({ title: '获取中...' })
			http.get('/product/order/info', {
				id: this.order_id
			}).then(res => {
				uni.hideLoading()
				if (res.code == 0) {
					const info = res.data
					let totalNumber = 0
					if (info.productInfoVoList) {
						info.productInfoVoList.forEach(p => {
							totalNumber += p.number
						})
					}
					info.productNum = totalNumber
					this.productOrderInfo = info
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			}).catch(() => {
				uni.hideLoading()
			})
		},
		showMore() {
			this.isShowAll = !this.isShowAll
		}
	}
}
</script>

<style lang="scss" scoped>
.page { min-height: 100vh; background: #f5f5f5; }
.container { padding: 20rpx; }
.store-info { display: flex; justify-content: space-between; align-items: center; padding: 20rpx 30rpx; background: #fff; border-radius: 16rpx; margin-bottom: 20rpx; font-size: 28rpx; }
.status-css { padding: 6rpx 16rpx; border-radius: 8rpx; font-size: 24rpx; color: #fff; }
.status-css.wait { background: #f0ad4e; }
.status-css.send { background: #5bc0de; }
.status-css.end { background: #5cb85c; }
.status-css.cancel { background: #999; }
.order-info { background: #fff; border-radius: 16rpx; padding: 20rpx 30rpx; }
.xian { height: 1rpx; background: #eee; margin: 20rpx 0; }
.productInfo { display: flex; align-items: center; padding: 16rpx 0; }
.productInfo .image { margin-right: 20rpx; flex-shrink: 0; }
.productInfo .image image { border-radius: 8rpx; }
.productInfo .attrs { flex: 1; font-size: 26rpx; color: #666; }
.productInfo .attrs view:first-child { font-size: 28rpx; color: #333; margin-bottom: 8rpx; }
.price-num { flex-shrink: 0; }
.product-price { font-size: 28rpx; color: #e54d42; margin-right: 10rpx; }
.product-number { font-size: 26rpx; color: #999; }
.showmore { text-align: center; padding: 16rpx 0; color: var(--main-color, #5AAB6E); font-size: 26rpx; }
.info-line { display: flex; justify-content: space-between; padding: 12rpx 0; font-size: 28rpx; color: #333; }
.price-red { color: rgb(245, 82, 82); }
</style>
