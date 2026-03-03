<template>
	<view class="container" :style="themeStyle">
		<view class="form">
			<!-- 是否允许已使用退款 -->
			<view class="section">
				<view class="title">是否允许已使用退款</view>
				<view class="radio-group">
					<view class="radio-item" @tap="irregularRefundChange(false)" :class="{ active: !irregularRefund }">
						<view class="radio-dot" :class="{ checked: !irregularRefund }"></view>
						<text>不允许</text>
					</view>
					<view class="radio-item" @tap="irregularRefundChange(true)" :class="{ active: irregularRefund }">
						<view class="radio-dot" :class="{ checked: irregularRefund }"></view>
						<text>允许</text>
					</view>
				</view>
			</view>
			<!-- 取消订单 -->
			<view class="section">
				<view class="title">取消订单</view>
				<view class="radio-group">
					<view class="radio-item" @tap="refundable = 0; showRefund = true" :class="{ active: refundable === 0 }">
						<view class="radio-dot" :class="{ checked: refundable === 0 }"></view>
						<text>允许取消</text>
					</view>
					<view class="radio-item" @tap="refundable = 1; showRefund = false" :class="{ active: refundable === 1 }">
						<view class="radio-dot" :class="{ checked: refundable === 1 }"></view>
						<text>不可取消</text>
					</view>
				</view>
			</view>
			<!-- 可退款时间 -->
			<view class="section" v-if="showRefund">
				<view class="title">可退款时间</view>
				<view class="input-row">
					<text>允许订单开始前</text>
					<view class="number-input">
						<view class="btn" @tap="refundTime = Math.max(0, refundTime - 1)">-</view>
						<input type="number" v-model="refundTime" />
						<view class="btn" @tap="refundTime++">+</view>
						<text class="unit">分钟退款</text>
					</view>
				</view>
			</view>
			<!-- 提前预定时间 -->
			<view class="section">
				<view class="title">提前预定时间</view>
				<view class="input-row">
					<text>顾客需提前</text>
					<view class="number-input">
						<view class="btn" @tap="preOrderTime = Math.max(0, preOrderTime - 1)">-</view>
						<input type="number" v-model="preOrderTime" />
						<view class="btn" @tap="preOrderTime++">+</view>
						<text class="unit">分钟预定</text>
					</view>
				</view>
			</view>
			<!-- 最晚延迟时间点 -->
			<view class="section">
				<view class="title">最晚延迟时间点</view>
				<picker mode="date" @change="onDateChange">
					<view class="picker-value">{{ latestPeriodRulePoint || '请选择时间' }}</view>
				</picker>
			</view>
			<!-- 联系电话 -->
			<view class="section">
				<view class="title">联系电话</view>
				<input type="number" maxlength="11" placeholder="请输入11位手机号" v-model="notifyPhone" class="phone-input" />
			</view>
			<button class="save-btn" @tap="saveSettings">保存</button>
		</view>
	</view>
</template>

<script>
import http from '@/utils/http'

export default {
	data() {
		return {
			store_id: '',
			refundable: 0,
			refundTime: 0,
			preOrderTime: 0,
			intervalTime: 5,
			notifyPhone: '',
			irregularRefund: false,
			showRefund: true,
			latestPeriodRulePoint: ''
		}
	},
	onLoad(options) {
		if (options.store_id) this.store_id = options.store_id
		this.loadConfig()
	},
	methods: {
		loadConfig() {
			if (!this.store_id) return
			http.get('/member/reserve/push/rule/' + this.store_id).then(res => {
				if (res.code == 0 && res.data) {
					const d = res.data
					this.refundable = d.refundable || 0
					this.refundTime = d.refund_before || 0
					this.preOrderTime = d.add_order_before || 0
					this.notifyPhone = d.notify_phone || ''
					this.irregularRefund = !!d.irregular_refund
					this.showRefund = this.refundable === 0
					this.latestPeriodRulePoint = d.latest_period_rule_point || ''
				}
			}).catch(() => {})
		},
		irregularRefundChange(val) {
			this.irregularRefund = val
		},
		onDateChange(e) {
			this.latestPeriodRulePoint = e.detail.value + ' 00:00:00'
		},
		saveSettings() {
			if (this.refundable === 0 && this.refundTime > 120) {
				uni.showModal({ title: '提示', content: '退款时间最大为120分钟，请输入。', showCancel: false })
				return
			}
			http.post('/member/reserve/push/rule', {
				store_id: this.store_id,
				refundable: this.refundable,
				addOrderBefore: this.preOrderTime,
				refundBefore: this.refundTime,
				notifyPhone: this.notifyPhone,
				irregularRefund: this.irregularRefund,
				latestPeriodRulePoint: this.latestPeriodRulePoint
			}).then(res => {
				if (res.code == 0) {
					uni.showToast({ title: '保存成功' })
				}
			})
		}
	}
}
</script>

<style lang="scss" scoped>
.container { min-height: 100vh; background: #f5f5f5; }
.form { padding: 20rpx; }
.section { background: #fff; border-radius: 16rpx; padding: 24rpx; margin-bottom: 20rpx; }
.title { font-size: 30rpx; font-weight: 600; margin-bottom: 16rpx; }
.radio-group { display: flex; gap: 30rpx; }
.radio-item { display: flex; align-items: center; font-size: 28rpx; }
.radio-dot { width: 36rpx; height: 36rpx; border-radius: 50%; border: 2rpx solid #ccc; margin-right: 12rpx; }
.radio-dot.checked { border-color: var(--main-color, #5AAB6E); background: var(--main-color, #5AAB6E); }
.input-row { display: flex; align-items: center; font-size: 28rpx; flex-wrap: wrap; }
.number-input { display: flex; align-items: center; margin-left: 10rpx; }
.number-input .btn { width: 56rpx; height: 56rpx; line-height: 56rpx; text-align: center; background: #f0f0f0; border-radius: 8rpx; font-size: 32rpx; }
.number-input input { width: 80rpx; text-align: center; font-size: 28rpx; }
.unit { margin-left: 10rpx; font-size: 26rpx; color: #666; }
.picker-value { background: #f5f5f5; padding: 16rpx 24rpx; border-radius: 8rpx; font-size: 28rpx; }
.phone-input { background: #f5f5f5; padding: 16rpx 24rpx; border-radius: 8rpx; font-size: 28rpx; }
.save-btn { background: var(--main-color, #5AAB6E); color: #fff; border-radius: 30rpx; margin-top: 30rpx; font-size: 30rpx; }
</style>
