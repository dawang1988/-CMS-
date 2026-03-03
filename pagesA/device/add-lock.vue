<template>
	<view class="container" :style="themeStyle">
		<view class="tips">
			无编码的锁、网关打不开的锁，可在此处进行初始化配置，初始化前确保门锁处于配置模式
		</view>
		<view class="info">
			<view class="line">1. 打开手机蓝牙设置开关,并靠近门锁</view>
			<view class="line">2. 输入从物联网平台获得的智能锁编号</view>
			<view class="line">3. 智能锁装电池，触摸输密码面板唤醒锁</view>
			<view class="line">4. 听到锁发出"请添加蓝牙管理员"</view>
			<view class="line">5. 点击扫描附近锁，选择锁进行初始化</view>
			<view class="line">6. 初始化时间大概10秒，请耐心等待完成</view>
		</view>
		<view class="form">
			<view class="sn">
				<text class="label">智能锁编号：T T </text>
				<input class="input" v-model="deviceSn" :disabled="checkSuccess" type="number" placeholder="请输入8位数字" maxlength="8" />
			</view>
			<view class="btn scan-btn" @tap="scanLock">扫描附近锁</view>
		</view>

		<view class="list" v-if="checkSuccess">
			<view class="item" v-for="(item, index) in list" :key="index">
				<view class="lock" :class="item.isSettingMode ? 'enable' : 'disable'">
					<view>
						{{ item.device_name }}
						<text v-if="!item.isSettingMode">(锁已注册)</text>
					</view>
					<view class="btn init-btn" v-if="item.isSettingMode" @tap="initLock(index)">初始化</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
import api from '@/api/index.js'

export default {
	data() {
		return {
			lockList: [],
			list: [],
			deviceSn: '',
			checkSuccess: false,
			successList: []
		}
	},

	onLoad(options) {},

	methods: {
		// 扫描附近锁
		scanLock() {
			if (!this.deviceSn || this.deviceSn.length < 8) {
				uni.showToast({ title: '输入8位锁编号', icon: 'error' })
				return
			}
			// 先查编号是否可用
			api.request({
				url: '/member/store/addLock',
				method: 'POST',
				data: {
					lock_data: 'query',
					deviceSn: 'TT' + this.deviceSn
				}
			}).then(res => {
				if (res.code == 0) {
					this.checkSuccess = true
					uni.showToast({ title: '请唤醒智能锁' })
				} else {
					uni.showModal({
						content: '编码校验失败:' + res.msg,
						showCancel: false
					})
				}
			})

			// 蓝牙扫描门锁
			// 注意：TTLock蓝牙插件在uni-app中需要使用对应的uni插件
			// 微信小程序中使用 requirePlugin("myPlugin")
			// #ifdef MP-WEIXIN
			try {
				const plugin = requirePlugin("myPlugin")
				plugin.startScanBleDevice(
					(lockDevice, lockList) => {
						let list = []
						lockList.forEach(item => {
							list.push({
								deviceId: item.deviceId,
								device_name: item.device_name,
								isSettingMode: item.isSettingMode,
								electricQuantity: item.electricQuantity,
								rssi: item.rssi
							})
						})
						this.lockList = lockList
						this.list = list
					},
					(err) => {
						uni.showToast({ title: '蓝牙扫描失败', icon: 'error' })
					}
				)
			} catch (e) {
				uni.showToast({ title: '蓝牙插件未安装', icon: 'none' })
			}
			// #endif
		},

		// 初始化门锁
		initLock(index) {
			if (!this.deviceSn || this.deviceSn.length < 8) {
				uni.showModal({ content: '请先输入智能锁编码', showCancel: false })
				return
			}
			if (!this.lockList || !this.lockList.length) return

			let deviceFromScan = this.lockList[index]
			if (!deviceFromScan.isSettingMode) {
				uni.showToast({ title: '此锁不可添加', icon: 'error' })
				this.checkSuccess = false
				return
			}

			// #ifdef MP-WEIXIN
			try {
				const plugin = requirePlugin("myPlugin")
				uni.showLoading({ title: '请靠近智能锁' })
				plugin.initLock({ deviceFromScan }).then(result => {
					if (result.errorCode == 0) {
						let lockData = result.lock_data
						this.postLockData(lockData, '')
					} else {
						this.handleResetLock(plugin, result.lock_data)
						uni.hideLoading()
						uni.showToast({ title: '初始化失败', icon: 'error' })
						this.checkSuccess = false
					}
				})
			} catch (e) {
				uni.hideLoading()
				uni.showToast({ title: '蓝牙插件未安装', icon: 'none' })
			}
			// #endif
		},

		// 上传门锁数据
		postLockData(lockData, upData) {
			api.request({
				url: '/member/store/addLock',
				method: 'POST',
				data: {
					lock_data: lockData,
					upData: upData,
					deviceSn: 'TT' + this.deviceSn
				}
			}).then(res => {
				this.checkSuccess = false
				uni.hideLoading()
				if (res.code == 0) {
					uni.showToast({ title: '初始化成功' })
				} else {
					// #ifdef MP-WEIXIN
					try {
						const plugin = requirePlugin("myPlugin")
						this.handleResetLock(plugin, lockData)
					} catch (e) {}
					// #endif
					uni.showModal({
						content: '初始化失败:' + res.msg,
						showCancel: false
					})
				}
			}).catch(() => {
				// #ifdef MP-WEIXIN
				try {
					const plugin = requirePlugin("myPlugin")
					this.handleResetLock(plugin, lockData)
				} catch (e) {}
				// #endif
				uni.hideLoading()
				uni.showToast({ title: '初始化失败', icon: 'error' })
				this.checkSuccess = false
			})
		},

		// 重置门锁
		handleResetLock(plugin, lockData) {
			setTimeout(() => {
				plugin.resetLock({ lockData }).then(res => {
					uni.hideLoading()
					if (res.errorCode == 0) {
						uni.showToast({ title: '智能锁已重置', icon: 'success' })
					}
				})
			}, 3000)
		}
	}
}
</script>

<style lang="scss" scoped>
.container {
	min-height: 100vh;
	background: #f5f5f5;
}

.tips {
	font-size: 36rpx;
	text-align: center;
	font-weight: bold;
	padding: 20rpx;
}

.info {
	font-size: 32rpx;
	background-image: radial-gradient(circle 248px at center, #16d9e3 0%, #30c7ec 47%, #46aef7 100%);
	margin: 10rpx 20rpx;
	border-radius: 10rpx;
	padding: 10rpx;
	color: #fff;
	font-weight: 600;
}

.info .line {
	margin-bottom: 10rpx;
}

.form {
	margin-top: 50rpx;
	align-items: center;
}

.form .sn {
	display: flex;
	justify-content: center;
	align-items: center;
	font-size: 36rpx;
	font-weight: 600;
}

.form .sn .label {
	margin-right: 20rpx;
}

.form .sn .input {
	border: 1rpx solid rgb(67, 160, 236);
	background-color: #fff;
	padding: 5rpx;
	width: 280rpx;
}

.scan-btn {
	align-items: center;
	width: 300rpx;
	background-color: #108ee9;
	color: #fff;
	font-size: 32rpx;
	height: 50rpx;
	line-height: 50rpx;
	border-radius: 10rpx;
	margin: 20rpx auto;
	padding: 10rpx;
	text-align: center;
	font-weight: 600;
}

.list {
	overflow-y: auto;
	max-height: 330rpx;
	padding: 10rpx 30rpx;
	margin: 20rpx;
	border-radius: 20rpx;
	background-image: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.item {
	margin: 30rpx 10rpx;
	font-size: 30rpx;
}

.lock {
	display: flex;
	justify-content: space-between;
	align-items: center;
	font-size: 28rpx;
	font-weight: 600;
	line-height: 32rpx;
	margin: 20rpx;
	height: 32rpx;
	text-align: center;
}

.disable {
	color: rgb(122, 122, 122);
}

.enable {
	color: #2088d3;
}

.init-btn {
	background-color: #2088d3;
	color: #fff;
	padding: 10rpx 30rpx;
	border-radius: 10rpx;
}
</style>
