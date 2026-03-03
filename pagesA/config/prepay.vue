<template>
	<view class="container" :style="themeStyle">
		<view class="form">
			<view class="line">
				<text class="label">预付费价格</text>
				<view class="right">
					<input type="digit" placeholder="请输入" v-model="prePrice" />
					<text class="unit">元</text>
				</view>
			</view>
			<view class="tips">注：设置顾客下单时需要支付的金额，用不完会自动退回</view>
			<view class="line">
				<text class="label">最低消费价格</text>
				<view class="right">
					<input type="digit" placeholder="请输入" v-model="minCharge" />
					<text class="unit">元</text>
				</view>
			</view>
			<view class="tips">注：消费门槛，订单至少会扣这么多费用。不填或输入0则表示没有消费门槛</view>
		</view>
		<view class="submit">
			<button class="cancel-btn" @tap="cancel">取消</button>
			<button class="save-btn" @tap="setPrePayConfig">保存</button>
		</view>
	</view>
</template>

<script>
import http from '@/utils/http'

export default {
	data() {
		return {
			room_id: '',
			prePrice: '',
			preUnit: '',
			minCharge: ''
		}
	},
	onLoad(options) {
		if (options && options.room_id) {
			this.room_id = options.room_id
		} else {
			uni.navigateBack()
		}
	},
	onShow() {
		this.getPrePayConfig()
	},
	methods: {
		getPrePayConfig() {
			http.post('/member/store/getPrePayConfig/' + this.room_id).then(res => {
				if (res.code == 0) {
					this.prePrice = res.data.prePrice || ''
					this.preUnit = res.data.preUnit || ''
					this.minCharge = res.data.minCharge || ''
				}
			})
		},
		setPrePayConfig() {
			http.post('/member/store/setPrePayConfig', {
				room_id: this.room_id,
				prePrice: this.prePrice,
				preUnit: this.preUnit,
				minCharge: this.minCharge
			}).then(res => {
				if (res.code == 0) {
					uni.showToast({ title: '操作成功' })
					setTimeout(() => uni.navigateBack(), 200)
				} else {
					uni.showModal({ title: '失败', content: res.msg, showCancel: false })
				}
			})
		},
		cancel() {
			setTimeout(() => uni.navigateBack(), 100)
		}
	}
}
</script>

<style lang="scss" scoped>
.container { min-height: 100vh; background: #f5f5f5; padding-bottom: 120rpx; }
.form { padding: 20rpx; }
.line { display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 24rpx; border-radius: 12rpx; margin-bottom: 4rpx; }
.label { font-size: 28rpx; font-weight: 600; }
.right { display: flex; align-items: center; }
.right input { width: 200rpx; text-align: right; font-size: 28rpx; border-bottom: 1rpx solid #e0e0e0; padding: 8rpx; }
.unit { font-size: 28rpx; margin-left: 8rpx; color: #666; }
.tips { font-size: 24rpx; color: #999; padding: 12rpx 24rpx 20rpx; }
.submit { position: fixed; bottom: 0; left: 0; right: 0; display: flex; padding: 20rpx; background: #fff; gap: 20rpx; }
.submit button { flex: 1; border-radius: 30rpx; font-size: 28rpx; }
.cancel-btn { background: #f5f5f5; color: var(--main-color, #5AAB6E); border: 1rpx solid var(--main-color, #5AAB6E); }
.save-btn { background: var(--main-color, #5AAB6E); color: #fff; }
</style>
