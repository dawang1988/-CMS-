<template>
	<view class="page" :style="themeStyle">
		<view class="container">
			<!-- 当前门店（只读） -->
			<view class="form-line">
				<label>所属门店：</label>
				<view class="right">
					<text class="tags">{{storeName}}</text>
				</view>
			</view>
			
			<!-- 优惠券名称 -->
			<view class="form-line">
				<label>优惠券名称：</label>
				<view class="right">
					<input type="text" v-model="name" placeholder="必填" />
				</view>
			</view>
			
			<!-- 优惠券类型 -->
			<view class="form-line">
				<label>优惠券类型：</label>
				<view class="right">
					<picker @change="bindTypeChange" :range="types" range-key="name">
						<view class="picker">
							<input type="text" disabled placeholder="请选择类型" :value="types[type-1] ? types[type-1].name : ''" />
							<text style="font-size:28rpx;color:#999;">›</text>
						</view>
					</picker>
				</view>
			</view>
			
			<!-- 包间限制 -->
			<view class="form-line">
				<label>包间限制：</label>
				<view class="right">
					<picker @change="bindDoorChange" :value="doorIndex" :range="rooms" range-key="name">
						<view class="picker">
							<input type="text" disabled placeholder="请选择类型" :value="rooms[doorIndex] ? rooms[doorIndex].name : ''" />
							<text style="font-size:28rpx;color:#999;">›</text>
						</view>
					</picker>
				</view>
			</view>

			<!-- 使用门槛 -->
			<view class="form-line">
				<label>使用门槛：</label>
				<!-- 抵扣券/加时券 -->
				<view class="right" v-if="type == 1 || type == 3">
					<picker @change="bindRuleChange" :value="ruleIndex" :range="rules">
						<view class="picker">
							<input type="text" disabled placeholder="请选择" :value="rules[ruleIndex]" />
							<text style="font-size:28rpx;color:#999;">›</text>
						</view>
					</picker>
				</view>
				<!-- 满减券 -->
				<view class="right step" v-if="type == 2">
					<view class="stepper">
						<button class="btn" @click="minAmountDecrease">-</button>
						<input type="number" v-model="min_amount" class="stepper-input" />
						<button class="btn" @click="minAmountIncrease">+</button>
					</view>
					<text>元</text>
				</view>
			</view>
			
			<!-- 抵扣额度 -->
			<view class="form-line">
				<label>抵扣额度：</label>
				<!-- 抵扣券/加时券 -->
				<view class="right" v-if="type == 1 || type == 3">
					<picker @change="bindLimitChange" :value="limitIndex" :range="limits">
						<view class="picker">
							<input type="text" disabled placeholder="请选择" :value="limits[limitIndex]" />
							<text style="font-size:28rpx;color:#999;">›</text>
						</view>
					</picker>
				</view>
				<!-- 满减券 -->
				<view class="right step" v-if="type == 2">
					<view class="stepper">
						<button class="btn" @click="amountDecrease">-</button>
						<input type="number" v-model="amount" class="stepper-input" />
						<button class="btn" @click="amountIncrease">+</button>
					</view>
					<text>元</text>
				</view>
			</view>
			
			<!-- 过期时间 -->
			<view class="form-line">
				<label>过期时间：</label>
				<view class="right">
					<picker mode="date" :value="end_time" @change="selectDateChange">
						<view class="picker">
							<input type="text" disabled placeholder="请选择时间" :value="end_time" />
							<text style="font-size:28rpx;color:#999;">›</text>
						</view>
					</picker>
				</view>
			</view>
			
			<!-- 发行总量 -->
			<view class="form-line">
				<label>发行总量：</label>
				<view class="right">
					<input type="number" v-model="total" placeholder="默认9999" />
				</view>
			</view>
		</view>
		
		<!-- 提示说明 -->
		<view class="tip">1. 抵扣券：满X小时，抵扣X小时。如：想0元续费订单，就设置使用门槛1小时，抵扣额度1小时。</view>
		<view class="tip">2. 满减券：满X元，减免X元。如：满100元减20元。</view>
		<view class="tip">3. 加时券：在X小时付费基础上，免费额外增加X小时。不支持0元加时续费或0元加时下单。</view>
		
		<!-- 底部按钮 -->
		<view class="submit-bar">
			<button class="btn-cancel" @click="cancel">取消</button>
			<button class="btn-submit" @click="submit">保存</button>
		</view>
	</view>
</template>

<script>
import api from '@/api/index.js'
import { COUPON_TYPE_LIST } from '@/utils/constants'

export default {
	data() {
		return {
			coupon_id: '',
			name: '',
			type: '',
			store_id: '',
			storeName: '',
			room_class: '',
			min_amount: 0,
			amount: 0,
			end_time: '',
			total: 9999,
			ruleIndex: 0,
			limitIndex: 0,
			doorIndex: 0,
			types: COUPON_TYPE_LIST,
			rooms: [
				{ id: null, name: '不限制' },
				{ id: 0, name: '棋牌' },
				{ id: 1, name: '台球' },
				{ id: 2, name: 'KTV' }
			],
			rules: ['0小时','1小时','2小时','3小时','4小时','5小时','6小时','7小时','8小时','9小时','10小时','11小时','12小时','13小时','14小时','15小时','16小时','17小时','18小时','19小时','20小时','21小时','22小时','23小时','24小时'],
			limits: ['0.5小时','1小时','1.5小时','2小时','2.5小时','3小时','3.5小时','4小时','4.5小时','5小时','5.5小时','6小时','6.5小时','7小时','7.5小时','8小时','8.5小时','9小时','9.5小时','10小时','10.5小时','11小时','11.5小时','12小时']
		}
	},
	
	onLoad(options) {
		this.store_id = options.store_id || ''
		this.storeName = options.store_name || ''
		
		if (options.coupon_id) {
			this.coupon_id = Number(options.coupon_id)
		}
	},
	
	onShow() {
		if (this.coupon_id) {
			this.getData()
		}
	},
	
	methods: {
		// 获取优惠券详情
		getData() {
			api.get('/member/manager/getCouponDetail/' + this.coupon_id, {
				coupon_id: this.coupon_id
			}).then(res => {
				if (res.code === 0 && res.data) {
					const data = res.data
					this.coupon_id = data.id
					this.name = data.name
					this.type = data.type
					this.store_id = data.store_id
					this.room_class = data.room_class
					this.min_amount = data.min_amount || 0
					this.amount = data.amount || 0
					this.end_time = data.end_time ? data.end_time.substring(0, 10) : ''
					this.total = data.total || 9999
					
					// 设置包间限制索引
					const doorIdx = this.rooms.findIndex(r => r.id == data.room_class)
					this.doorIndex = doorIdx >= 0 ? doorIdx : 0
					
					// 设置使用门槛索引
					const ruleIdx = this.rules.findIndex(r => {
						const match = r.match(/\d+(.\d+)?/)
						return match && parseFloat(match[0]) == data.min_amount
					})
					this.ruleIndex = ruleIdx >= 0 ? ruleIdx : 0
					
					// 设置抵扣额度索引
					const limitIdx = this.limits.findIndex(l => {
						const match = l.match(/\d+(.\d+)?/)
						return match && parseFloat(match[0]) == data.amount
					})
					this.limitIndex = limitIdx >= 0 ? limitIdx : 0
				} else {
					uni.showModal({ content: res.msg || '获取详情失败', showCancel: false })
				}
			})
		},
		
		// 选择优惠券类型
		bindTypeChange(e) {
			this.type = this.types[e.detail.value].id
		},
		
		// 选择包间限制
		bindDoorChange(e) {
			this.doorIndex = e.detail.value
			this.room_class = this.rooms[this.doorIndex].id
		},
		
		// 选择使用门槛
		bindRuleChange(e) {
			this.ruleIndex = e.detail.value
			const match = this.rules[this.ruleIndex].match(/\d+(.\d+)?/)
			this.min_amount = match ? parseFloat(match[0]) : 0
		},
		
		// 选择抵扣额度
		bindLimitChange(e) {
			this.limitIndex = e.detail.value
			const match = this.limits[this.limitIndex].match(/\d+(.\d+)?/)
			this.amount = match ? parseFloat(match[0]) : 0
		},
		
		// 选择过期时间
		selectDateChange(e) {
			this.end_time = e.detail.value
		},
		
		// 使用门槛增减（满减券）
		minAmountIncrease() {
			this.min_amount = Number(this.min_amount) + 1
		},
		
		minAmountDecrease() {
			if (this.min_amount > 0) {
				this.min_amount = Number(this.min_amount) - 1
			}
		},
		
		// 抵扣额度增减（满减券）
		amountIncrease() {
			this.amount = Number(this.amount) + 1
		},
		
		amountDecrease() {
			if (this.amount > 0) {
				this.amount = Number(this.amount) - 1
			}
		},
		
		// 取消
		cancel() {
			uni.navigateBack()
		},
		
		// 保存
		submit() {
			if (!this.name || !this.type || !this.store_id || !this.end_time) {
				uni.showToast({ title: '请填写完整', icon: 'none' })
				return
			}
			
			api.post('/member/manager/saveCouponDetail', {
				coupon_id: this.coupon_id || '',
				name: this.name,
				end_time: this.end_time,
				min_amount: this.min_amount,
				amount: this.amount,
				store_id: this.store_id,
				room_class: this.room_class,
				type: this.type,
				total: this.total || 9999
			}).then(res => {
				if (res.code === 0) {
					uni.showToast({ title: '设置成功', icon: 'success' })
					setTimeout(() => uni.navigateBack(), 1000)
				} else {
					uni.showModal({ content: res.msg || '保存失败', showCancel: false })
				}
			})
		}
	}
}
</script>

<style lang="scss" scoped>
.page {
	min-height: 100vh;
	background: #f5f5f5;
	padding-bottom: 150rpx;
}

.container {
	background: #fff;
	padding: 20rpx 0;
}

.form-line {
	display: flex;
	align-items: center;
	padding: 20rpx 30rpx;
	border-bottom: 1rpx solid #eee;
	
	label {
		width: 180rpx;
		font-size: 28rpx;
		color: #333;
		flex-shrink: 0;
	}
	
	.right {
		flex: 1;
		
		&.step {
			display: flex;
			align-items: center;
		}
		
		input {
			width: 100%;
			font-size: 28rpx;
			height: 60rpx;
		}
		
		.tags {
			font-size: 28rpx;
			color: #666;
		}
	}
	
	.picker {
		display: flex;
		align-items: center;
		justify-content: space-between;
		width: 100%;
		
		input {
			flex: 1;
		}
	}
}

.stepper {
	display: flex;
	align-items: center;
	
	.btn {
		width: 60rpx;
		height: 60rpx;
		font-size: 32rpx;
		line-height: 60rpx;
		text-align: center;
		background: #f5f5f5;
		border: 1rpx solid #ddd;
		padding: 0;
		margin: 0;
	}
	
	.stepper-input {
		width: 120rpx;
		height: 60rpx;
		text-align: center;
		border: 1rpx solid #ddd;
		border-left: none;
		border-right: none;
		font-size: 28rpx;
	}
}

.tip {
	padding: 16rpx 30rpx;
	font-size: 24rpx;
	color: #999;
	line-height: 1.6;
}

.submit-bar {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	display: flex;
	padding: 20rpx 30rpx;
	background: #fff;
	box-shadow: 0 -2rpx 10rpx rgba(0,0,0,0.05);
	
	button {
		flex: 1;
		height: 90rpx;
		line-height: 90rpx;
		font-size: 32rpx;
		border-radius: 45rpx;
		margin: 0 15rpx;
	}
	
	.btn-cancel {
		background: #f5f5f5;
		color: #666;
	}
	
	.btn-submit {
		background: var(--main-color, #1aad19);
		color: #fff;
	}
}
</style>
