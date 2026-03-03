<template>
	<view class="page" :style="themeStyle">
		<form @submit="onSubmit">
			<view class="form-section">
				<!-- 适用门店 -->
				<view class="form-item">
					<view class="label-container">
						<text class="label">适用门店</text>
						<text class="required">*</text>
					</view>
					<view class="value">{{storeName}}</view>
				</view>
				
				<!-- 套餐名称 -->
				<view class="form-item">
					<view class="label-container">
						<text class="label">套餐名称</text>
						<text class="required">*</text>
					</view>
					<input name="pkg_name" class="input" placeholder="请输入" v-model="item.pkg_name" />
				</view>
				
				<!-- 套餐时长 -->
				<view class="form-item">
					<view class="label-container">
						<text class="label">套餐时长</text>
						<text class="required">*</text>
					</view>
					<view class="stepper">
						<button type="default" class="btn" @click.prevent="decreaseDuration">-</button>
						<input type="number" class="count-display" :value="hours" disabled />
						<button type="default" class="btn" @click.prevent="increaseDuration">+</button>
						<text>小时</text>
					</view>
				</view>
				
				<!-- 美团团购商品id -->
				<view class="form-item">
					<view class="label-container">
						<text class="label">美团团购商品id</text>
					</view>
					<input name="mtId" class="input lt" placeholder="美团团购商品id" type="number" v-model="item.mtId" />
				</view>
				
				<!-- 抖音团购商品id -->
				<view class="form-item">
					<view class="label-container">
						<text class="label">抖音团购商品id</text>
					</view>
					<input name="dyId" class="input lt" placeholder="抖音团购商品id" type="number" v-model="item.dyId" />
				</view>
				
				<!-- 包间限制 -->
				<view class="form-item">
					<view class="label-container">
						<text class="label">包间限制：</text>
					</view>
					<view class="top-tabs-container">
						<view class="top-tabs">
							<view v-for="(tab, idx) in roomLimit" :key="idx" 
								:class="['tab', tabIndex === tab.value ? 'active' : '']"
								@click="tabChange(tab.value)">
								{{tab.text}}
							</view>
						</view>
						<view class="checkbox-group">
							<!-- 按大小设置 -->
							<view v-if="tabIndex === 0" class="checkbox-list">
								<label v-for="(room, idx) in storesRoomList" :key="idx" class="checkbox-item">
									<checkbox :value="room.key" :checked="roomTypeCheckd[idx]" @click="chackRoomType(idx)" />
									<text>{{room.value}}</text>
								</label>
							</view>
							<!-- 按包厢设置 -->
							<view v-if="tabIndex === 1" class="checkbox-list">
								<label v-for="(room, idx) in doorList" :key="idx" class="checkbox-item">
									<checkbox :value="String(room.room_id)" :checked="enableRoomCheck[idx]" @click="chackEnableRoom(idx)" />
									<text>{{room.room_name}}</text>
								</label>
							</view>
						</view>
					</view>
				</view>
				
				<!-- 可用时间 -->
				<view class="form-item column">
					<view class="label">可用时间</view>
					<view class="checkbox-time-group">
						<view v-for="(time, idx) in times" :key="idx" class="checkbox-time-item" @click="toggleCheck(idx)">
							<checkbox :checked="time.checked" />
							<text>{{time.index}}</text>
						</view>
					</view>
					<view class="choose">
						<text @click="selectAllHours">全选</text>
						<text @click="invertSelection">反选</text>
					</view>
				</view>
				
				<!-- 可用星期 -->
				<view class="form-item column">
					<view class="label">可用星期</view>
					<view class="checkbox-week-group">
						<view v-for="(day, idx) in weekDays" :key="idx" class="checkbox-week-item" @click="chackWeek(idx)">
							<checkbox :checked="checkedStates[idx]" />
							<text>{{day}}</text>
						</view>
					</view>
					<view class="choose">
						<text @click="selectAll">全选</text>
						<text @click="selectInverse">反选</text>
					</view>
				</view>
				
				<!-- 可余额支付 -->
				<view class="form-item">
					<view class="label">可余额支付</view>
					<view class="switch-container">
						<switch :checked="balanceBuy" @change="switch2Change" />
					</view>
				</view>
				
				<!-- 销售价格 -->
				<view class="form-item">
					<view class="label-container">
						<text class="label">销售价格</text>
						<text class="required">*</text>
					</view>
					<input name="price" class="input" placeholder="请输入" type="digit" v-model="item.price" />
				</view>
				
				<!-- 过期天数 -->
				<view class="form-item">
					<view class="label-container">
						<text class="label">过期天数</text>
					</view>
					<input name="expireDay" class="input lt" placeholder="0为不过期" type="number" v-model="item.expire_day" />
				</view>
				
				<!-- 单用户最大购买数量 -->
				<view class="form-item">
					<view class="label label_width">单用户最大购买数量</view>
					<input name="maxNum" class="input" placeholder="0 为不限" type="number" v-model="item.max_num" />
				</view>
				
				<!-- 排序号 -->
				<view class="form-item">
					<view class="label">排序号</view>
					<input name="sortId" class="input" placeholder="数字越小越靠前" type="number" v-model="item.sortId" style="margin-left: 13%;" />
				</view>
				
				<!-- 按钮组 -->
				<view class="button-group">
					<button @click="cancelSave">取消</button>
					<button type="primary" form-type="submit">保存</button>
				</view>
			</view>
		</form>
	</view>
</template>

<script>
import api from '@/api/index.js'

export default {
	data() {
		return {
			store_id: '',
			storeName: '',
			hours: 1,
			balance_buy: false,
			tabIndex: 0,
			times: new Array(24).fill(null).map((_, i) => ({ index: i, checked: false })),
			weekDays: ["周一", "周二", "周三", "周四", "周五", "周六", "周日"],
			weekNum: ["1", "2", "3", "4", "5", "6", "7"],
			roomTypeNum: ["1", "2", "3", "4", "5", "6", "7", "8"],
			checkedStates: Array(7).fill(false),
			roomTypeCheckd: Array(8).fill(false),
			enableRoomCheck: [],
			storesRoomList: [
				{ key: "1", value: "小包" },
				{ key: "2", value: "中包" },
				{ key: "3", value: "大包" },
				{ key: "4", value: "豪包" },
				{ key: "5", value: "商务包" },
				{ key: "6", value: "斯洛克" },
				{ key: "7", value: "中式黑八" },
				{ key: "8", value: "美式球桌" }
			],
			roomLimit: [
				{ text: '按大小设置', value: 0 },
				{ text: '按包厢设置', value: 1 }
			],
			doorList: [],
			item: {
				pkg_id: '',
				pkgName: '',
				price: '',
				expireDay: '',
				maxNum: '',
				sortId: '',
				mtId: '',
				dyId: '',
				ksId: ''
			}
		}
	},
	
	onLoad(options) {
		this.store_id = options.store_id || ''
		this.store_name = options.store_name || ''
		
		// 获取房间列表
		this.getDoorList()
		
		// 如果有传入item，则为编辑模式
		if (options.item) {
			try {
				const item = JSON.parse(decodeURIComponent(options.item))
				this.item = item
				this.hours = item.hours || 1
				this.balance_buy = item.balance_buy || false
				
				// 解析可用星期
				if (item.enable_week) {
					const weekDaysString = String(item.enable_week)
					const weekDaysSet = new Set(weekDaysString.split(',').map(day => day.trim()))
					this.checkedStates = this.weekNum.map(day => weekDaysSet.has(day))
				}
				
				// 解析房间类型
				if (item.room_type) {
					const roomTypeString = String(item.room_type)
					const roomTypeSet = new Set(roomTypeString.split(',').map(item => item.trim()))
					this.roomTypeCheckd = this.roomTypeNum.map(item => roomTypeSet.has(item))
				}
				
				// 解析可用时间
				if (item.enable_time && Array.isArray(item.enable_time)) {
					const timeIndices = new Set(item.enable_time.map(time => parseInt(time)))
					this.times = this.times.map((time, index) => ({
						index: index,
						checked: timeIndices.has(index)
					}))
				}
				
				// 如果有enableRoom，切换到按包厢设置
				if (item.enable_room) {
					this.tabIndex = 1
				}
			} catch (e) {
			}
		}
	},
	
	methods: {
		// 获取房间列表
		getDoorList() {
			api.post('/member/store/getRoomInfoList?store_id=' + this.store_id, {}).then(res => {
				if (res.code === 0) {
					this.doorList = res.data || []
					
					// 如果是编辑模式且有enableRoom，设置选中状态
					if (this.item.enable_room) {
						const enableRoomString = String(this.item.enable_room)
						const enableRoomSet = new Set(enableRoomString.split(',').map(item => item.trim()))
						this.enableRoomCheck = this.doorList.map(item => enableRoomSet.has(String(item.room_id)))
					} else {
						this.enableRoomCheck = Array(this.doorList.length).fill(false)
					}
				}
			})
		},
		
		// 切换tab
		tabChange(value) {
			this.tabIndex = value
		},
		
		// 房间类型选择
		chackRoomType(index) {
			this.roomTypeCheckd[index] = !this.roomTypeCheckd[index]
			this.roomTypeCheckd = [...this.roomTypeCheckd]
		},
		
		// 包厢选择
		chackEnableRoom(index) {
			this.enableRoomCheck[index] = !this.enableRoomCheck[index]
			this.enableRoomCheck = [...this.enableRoomCheck]
		},
		
		// 星期选择
		chackWeek(index) {
			this.checkedStates[index] = !this.checkedStates[index]
			this.checkedStates = [...this.checkedStates]
		},
		
		// 星期全选
		selectAll() {
			this.checkedStates = Array(7).fill(true)
		},
		
		// 星期反选
		selectInverse() {
			this.checkedStates = this.checkedStates.map(state => !state)
		},
		
		// 时间选择
		toggleCheck(index) {
			this.times[index].checked = !this.times[index].checked
			this.times = [...this.times]
		},
		
		// 时间全选
		selectAllHours() {
			this.times = this.times.map(item => ({ ...item, checked: true }))
		},
		
		// 时间反选
		invertSelection() {
			this.times = this.times.map(item => ({ ...item, checked: !item.checked }))
		},
		
		// 增加时长
		increaseDuration() {
			if (this.hours < 100) {
				this.hours++
			} else {
				uni.showToast({ title: '已达最大时长', icon: 'none' })
			}
		},
		
		// 减少时长
		decreaseDuration() {
			if (this.hours > 1) {
				this.hours--
			} else {
				uni.showToast({ title: '时长不能小于1小时', icon: 'none' })
			}
		},
		
		// 余额支付开关
		switch2Change(e) {
			this.balance_buy = e.detail.value
		},
		
		// 取消
		cancelSave() {
			uni.navigateBack()
		},
		
		// 提交
		onSubmit(e) {
			// 验证
			if (!this.item.pkg_name) {
				uni.showToast({ title: '请输入套餐名称', icon: 'none' })
				return
			}
			if (this.hours <= 0) {
				uni.showToast({ title: '套餐时长必须大于0', icon: 'none' })
				return
			}
			if (!this.item.price) {
				uni.showToast({ title: '请输入销售价格', icon: 'none' })
				return
			}
			
			// 获取选中的时间
			const enableTime = this.times.filter(item => item.checked).map(item => item.index)
			
			// 获取选中的星期
			const enableWeek = this.checkedStates.map((checked, idx) => checked ? idx + 1 : null).filter(v => v !== null)
			
			// 获取选中的房间类型
			const roomType = this.roomTypeCheckd.map((checked, idx) => checked ? this.roomTypeNum[idx] : null).filter(v => v !== null)
			
			// 获取选中的包厢
			const enableRoom = this.enableRoomCheck.map((checked, idx) => checked ? this.doorList[idx].room_id : null).filter(v => v !== null)
			
			const params = {
				pkg_id: this.item.pkg_id || '',
				pkgName: this.item.pkg_name,
				hours: this.hours,
				store_id: this.store_id,
				room_type: this.tabIndex === 0 ? roomType.join(',') : '',
				enableRoom: this.tabIndex === 1 ? enableRoom.join(',') : '',
				enableTime: enableTime.join(','),
				enableWeek: enableWeek.join(','),
				balanceBuy: this.balance_buy,
				price: this.item.price,
				expireDay: this.item.expire_day || 0,
				maxNum: this.item.max_num || 0,
				sortId: this.item.sortId || 0,
				mtId: this.item.mtId || '',
				dyId: this.item.dyId || '',
				ksId: this.item.ksId || ''
			}
			
			api.post('/member/pkg/admin/saveAdminPkg', params).then(res => {
				if (res.code === 0) {
					uni.showToast({ title: '保存成功', icon: 'success' })
					setTimeout(() => uni.navigateBack(), 1500)
				} else {
					uni.showToast({ title: res.msg || '保存失败', icon: 'none' })
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
}

.form-section {
	padding: 20rpx;
	background-color: #fff;
}

.form-item {
	display: flex;
	align-items: center;
	margin-bottom: 20rpx;
	
	&.column {
		flex-direction: column;
		align-items: flex-start;
	}
}

.label-container {
	display: flex;
	align-items: center;
	margin-right: 10rpx;
}

.label {
	font-size: 28rpx;
	color: #000;
}

.label_width {
	width: 220rpx;
}

.required {
	color: red;
	margin-left: 5rpx;
}

.value {
	padding-left: 30rpx;
	font-size: 28rpx;
	color: #666;
}

.input {
	width: 50%;
	height: 60rpx;
	line-height: 60rpx;
	padding: 0 20rpx;
	border: 1px solid #ccc;
	border-radius: 5rpx;
	font-size: 28rpx;
}

.lt {
	margin-left: 8%;
}

.stepper {
	display: flex;
	align-items: center;
	font-size: 24rpx;
	margin-left: 5%;
	
	.btn {
		width: 60rpx;
		height: 60rpx;
		font-size: 30rpx;
		border: 1rpx solid #ccc;
		display: flex;
		align-items: center;
		justify-content: center;
		background: #fff;
		padding: 0;
		margin: 0;
		line-height: 60rpx;
	}
	
	.count-display {
		width: 80rpx;
		text-align: center;
		font-size: 28rpx;
	}
	
	text {
		margin-left: 10rpx;
	}
}

.switch-container {
	transform: scale(0.8);
	margin-left: 6%;
}

.top-tabs-container {
	width: 74%;
	padding: 0 20rpx 20rpx;
	background-color: #fff;
}

.top-tabs {
	width: 100%;
	display: flex;
	
	.tab {
		width: 50%;
		height: 80rpx;
		font-size: 28rpx;
		line-height: 80rpx;
		text-align: center;
		background-color: #fff;
		border: 1rpx solid #ddd;
		
		&.active {
			background: var(--main-color, #1aad19);
			color: #fff;
			border-color: var(--main-color, #1aad19);
		}
	}
}

.checkbox-group {
	padding: 10rpx;
	border: 1px solid var(--main-color, #1aad19);
	margin-top: 10rpx;
}

.checkbox-list {
	display: flex;
	flex-wrap: wrap;
}

.checkbox-item {
	display: flex;
	align-items: center;
	width: 33%;
	margin-bottom: 10rpx;
	font-size: 24rpx;
}

.checkbox-time-group {
	width: 100%;
	display: flex;
	flex-wrap: wrap;
	margin-top: 10rpx;
}

.checkbox-time-item {
	display: flex;
	align-items: center;
	width: 18%;
	margin-bottom: 10rpx;
	font-size: 24rpx;
}

.checkbox-week-group {
	width: 100%;
	display: flex;
	flex-wrap: wrap;
	margin-top: 10rpx;
}

.checkbox-week-item {
	display: flex;
	align-items: center;
	width: 24%;
	margin-bottom: 10rpx;
	font-size: 24rpx;
}

.choose {
	display: flex;
	gap: 30rpx;
	margin-top: 10rpx;
	
	text {
		color: #007aff;
		text-decoration: underline;
		font-size: 26rpx;
	}
}

.button-group {
	display: flex;
	margin-top: 60rpx;
	
	button {
		width: 50%;
		border-radius: 10rpx;
		font-size: 28rpx;
		height: 80rpx;
		line-height: 80rpx;
		
		&:first-child {
			background-color: #ccc;
			color: #fff;
		}
		
		&[type="primary"] {
			background-color: var(--main-color, #1aad19);
			color: #fff;
		}
	}
}
</style>
