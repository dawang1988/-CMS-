<template>
	<view class="page" :style="themeStyle">
		<view class="container">
			<view class="form">
				<view class="line">
					<label>适用门店：</label>
					<view class="right"><text>{{storeName}}</text></view>
				</view>
				<view class="line">
					<label>优惠券名称：</label>
					<view class="right">
						<input v-model="name" type="text" placeholder="必填" />
					</view>
				</view>
				<view class="line">
					<label>优惠券类型：</label>
					<view class="right">
						<picker @change="bindTypeChange" :range="types" range-key="name">
							<view class="picker-box">
								<input type="text" disabled placeholder="请选择类型" :value="typeText" />
							</view>
						</picker>
					</view>
				</view>
				<view class="line">
					<label>包间限制：</label>
					<view class="right">
						<picker @change="bindDoorChange" :value="doorIndex" :range="rooms" range-key="name">
							<view class="picker-box">
								<input type="text" disabled placeholder="请选择类型" :value="doorText" />
							</view>
						</picker>
					</view>
				</view>
				<view class="line">
					<label>使用门槛：</label>
					<!-- 抵扣券/加时券 -->
					<view class="right" v-if="type==1||type==3">
						<picker @change="bindRuleChange" :value="ruleIndex" :range="rules">
							<view class="picker-box">
								<input type="text" disabled placeholder="请选择" :value="ruleText" />
							</view>
						</picker>
					</view>
					<!-- 满减券 -->
					<view class="right step-row" v-if="type==2">
						<input type="digit" v-model="min_amount" placeholder="0" class="step-input" />
						<text>元</text>
					</view>
				</view>
				<view class="line">
					<label>抵扣额度：</label>
					<view class="right" v-if="type==1||type==3">
						<picker @change="bindLimitChange" :value="limitIndex" :range="limits">
							<view class="picker-box">
								<input type="text" disabled placeholder="请选择" :value="limitText" />
							</view>
						</picker>
					</view>
					<view class="right step-row" v-if="type==2">
						<input type="digit" v-model="amount" placeholder="0" class="step-input" />
						<text>元</text>
					</view>
				</view>
				<view class="line">
					<label>过期时间：</label>
					<view class="right">
						<picker mode="date" :value="end_time" @change="selectDateChange">
							<view class="picker-box">
								<input type="text" disabled placeholder="请选择时间" :value="end_time" />
							</view>
						</picker>
					</view>
				</view>
			</view>
			<view class="tip">1. 抵扣券：满X小时，抵扣X小时。如：想0元续费订单，就设置使用门槛1小时，抵扣额度1小时。</view>
			<view class="tip">2. 满减券：满X元，减免X元。如：满100元减20元。</view>
			<view class="tip">3. 加时券：在X小时付费基础上，免费额外增加X小时。不支持0元加时续费或0元加时下单。</view>
			<!-- 底部按钮 -->
			<view class="submit-bar">
				<button class="btn-cancel" @tap="cancel">取消</button>
				<button class="btn-save" @tap="submit">保存</button>
			</view>
		</view>
	</view>
</template>

<script>
import http from '@/utils/http.js'

export default {
	data() {
		return {
			coupon_id: '',
			name: '',
			type: '',
			store_id: '',
			storeName: '',
			room_class: '',
			doorIndex: 0,
			ruleIndex: '',
			limitIndex: '',
			min_amount: '',
			amount: '',
			end_time: '',
			types: [{ id: 1, name: '抵扣券' }, { id: 2, name: '满减券' }, { id: 3, name: '加时券' }],
			rooms: [
				{ id: null, name: '不限制' }, { id: 1, name: '小包' }, { id: 2, name: '中包' },
				{ id: 3, name: '大包' }, { id: 4, name: '豪包' }, { id: 5, name: '商务包' },
				{ id: 6, name: '斯洛克' }, { id: 7, name: '中式黑八' }, { id: 8, name: '美式球桌' }
			],
			rules: ['0小时','1小时','2小时','3小时','4小时','5小时','6小时','7小时','8小时','9小时','10小时','11小时','12小时','13小时','14小时','15小时','16小时','17小时','18小时','19小时','20小时','21小时','22小时','23小时','24小时'],
			limits: ['0.5小时','1小时','1.5小时','2小时','2.5小时','3小时','3.5小时','4小时','4.5小时','5小时','5.5小时','6小时','6.5小时','7小时','7.5小时','8小时','8.5小时','9小时','9.5小时','10小时','10.5小时','11小时','11.5小时','12小时']
		}
	},
	computed: {
		typeText() {
			if (!this.type) return ''
			const found = this.types.find(t => t.id === this.type)
			return found ? found.name : ''
		},
		doorText() {
			return this.rooms[this.doorIndex] ? this.rooms[this.doorIndex].name : ''
		},
		ruleText() {
			return this.ruleIndex !== '' ? this.rules[this.ruleIndex] : ''
		},
		limitText() {
			return this.limitIndex !== '' ? this.limits[this.limitIndex] : ''
		}
	},
	onLoad(options) {
		this.store_id = options.store_id || ''
		this.store_name = options.store_name || ''
		if (options.coupon_id) {
			this.coupon_id = Number(options.coupon_id)
		}
	},
	onShow() {
		if (this.coupon_id) this.getData()
	},
	methods: {
		getData() {
			http.get('/member/manager/getCouponDetail/' + this.coupon_id).then(res => {
				if (res.code === 0) {
					const d = res.data
					this.coupon_id = d.id
					this.name = d.name
					this.type = d.type
					this.store_id = d.store_id
					this.room_class = d.room_class
					// 回显doorIndex
					this.rooms.forEach((r, i) => { if (r.id === d.room_class) this.doorIndex = i })
					// 回显ruleIndex
					this.rules.forEach((r, i) => {
						const num = r.match(/\d+(\.\d+)?/)
						if (num && Number(num[0]) == d.min_amount) this.ruleIndex = i
					})
					this.min_amount = d.min_amount
					// 回显limitIndex
					this.limits.forEach((r, i) => {
						const num = r.match(/\d+(\.\d+)?/)
						if (num && Number(num[0]) == d.amount) this.limitIndex = i
					})
					this.amount = d.amount
					this.end_time = d.end_time || ''
				}
			})
		},
		bindTypeChange(e) { this.type = this.types[e.detail.value].id },
		bindDoorChange(e) {
			this.doorIndex = e.detail.value
			this.room_class = this.rooms[e.detail.value].id
		},
		bindRuleChange(e) {
			this.ruleIndex = e.detail.value
			const num = this.rules[e.detail.value].match(/\d+(\.\d+)?/)
			this.min_amount = num ? num[0] : 0
		},
		bindLimitChange(e) {
			this.limitIndex = e.detail.value
			const num = this.limits[e.detail.value].match(/\d+(\.\d+)?/)
			this.amount = num ? num[0] : 0
		},
		selectDateChange(e) { this.end_time = e.detail.value },
		cancel() { uni.navigateBack() },
		submit() {
			if (!this.name || !this.type || !this.store_id || !this.end_time) {
				return uni.showToast({ title: '请填写完整', icon: 'none' })
			}
			http.post('/member/manager/saveCouponDetail', {
				id: this.coupon_id || '',
				name: this.name,
				end_time: this.end_time,
				min_amount: this.min_amount,
				amount: this.amount,
				store_id: this.store_id,
				room_class: this.room_class,
				type: this.type
			}).then(res => {
				if (res.code === 0) {
					uni.showToast({ title: '设置成功', icon: 'success' })
					setTimeout(() => uni.navigateBack(), 1000)
				} else {
					uni.showToast({ title: res.msg || '保存失败', icon: 'none' })
				}
			}).catch(() => {
				uni.showToast({ title: '请求失败', icon: 'none' })
			})
		}
	}
}
</script>

<style lang="scss" scoped>
.page { min-height: 100vh; background: #f5f5f5; }
.container { padding: 20rpx 20rpx 200rpx; }
.form { background: #fff; border-radius: 12rpx; padding: 20rpx; }
.line { display: flex; align-items: center; padding: 20rpx 0; border-bottom: 1rpx solid #f0f0f0; }
.line label { width: 200rpx; font-size: 28rpx; color: #333; flex-shrink: 0; }
.line .right { flex: 1; }
.line .right input { width: 100%; font-size: 28rpx; height: 60rpx; }
.picker-box { width: 100%; }
.picker-box input { width: 100%; font-size: 28rpx; color: #333; }
.step-row { display: flex; align-items: center; }
.step-input { width: 200rpx; height: 60rpx; border: 1rpx solid #ccc; border-radius: 8rpx; padding: 0 16rpx; font-size: 28rpx; margin-right: 10rpx; }
.tip { font-size: 24rpx; color: #999; padding: 10rpx 0; line-height: 1.6; }
.submit-bar { position: fixed; bottom: 0; left: 0; right: 0; display: flex; padding: 20rpx 30rpx; padding-bottom: calc(20rpx + env(safe-area-inset-bottom)); background: #fff; box-shadow: 0 -2rpx 10rpx rgba(0,0,0,0.05); }
.btn-cancel { flex: 1; height: 80rpx; line-height: 80rpx; text-align: center; background: #f5f5f5; color: #666; border-radius: 40rpx; font-size: 30rpx; margin-right: 20rpx; }
.btn-save { flex: 1; height: 80rpx; line-height: 80rpx; text-align: center; background: #007aff; color: #fff; border-radius: 40rpx; font-size: 30rpx; }
</style>