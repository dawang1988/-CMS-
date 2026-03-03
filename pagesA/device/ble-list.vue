<template>
	<view class="container" :style="themeStyle">
		<view class="info">
			<view class="line">蓝牙门锁配对说明，请仔细阅读以下步骤：</view>
			<view class="line">1. 请确保手机蓝牙已开启并授权小程序使用</view>
			<view class="line">2. 请确保门锁设备已通电并处于可发现状态，建议靠近GPS</view>
			<view class="line">3. 点击开始扫描按钮，等待设备列表出现</view>
			<view class="line">4. 扫描到设备后，点击设备进行配对连接</view>
			<view class="line">5. 如遇到问题请检查蓝牙权限和设备电量</view>
		</view>
		<view class="form">
			<view class="btn" @tap="scanStart">开始扫描</view>
			<view class="btn" @tap="scanStop">停止扫描</view>
		</view>
		<view class="text" v-if="list.length > 0">已发现{{ list.length }}个设备</view>
		<view class="list" v-if="list.length > 0">
			<view class="item" v-for="(item, index) in list" :key="index">
				<view class="lock">
					<view>{{ item.name }}</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			isDiscovering: false,
			list: []
		}
	},
	onUnload() {
		// #ifdef MP-WEIXIN
		wx.closeBluetoothAdapter({
			success(res) {},
			fail(err) {}
		})
		// #endif
	},
	methods: {
		scanStart() {
			uni.showLoading({ title: '正在搜索...' })
			// #ifdef MP-WEIXIN
			wx.closeBluetoothAdapter({
				success(res) {},
				fail(err) {}
			})
			uni.hideLoading()
			wx.openBluetoothAdapter({
				success: () => {
					this.checkBluetoothState()
				},
				fail: (err) => {
					this.handleError(err, '蓝牙初始化失败，请检查蓝牙是否开启')
				}
			})
			// #endif
			// #ifndef MP-WEIXIN
			uni.hideLoading()
			uni.showToast({ title: '当前平台暂不支持蓝牙扫描功能', icon: 'none' })
			// #endif
		},
		scanStop() {
			// #ifdef MP-WEIXIN
			wx.closeBluetoothAdapter({
				success(res) {
					uni.showToast({ title: '已停止扫描' })
				}
			})
			// #endif
		},
		checkBluetoothState() {
			// #ifdef MP-WEIXIN
			wx.getBluetoothAdapterState({
				success: (res) => {
					if (res.available) {
						if (!res.discovering) {
							this.startScan()
						}
					} else {
						uni.showToast({ title: '蓝牙未开启', icon: 'none' })
					}
				},
				fail: (err) => {
					this.handleError(err, '获取蓝牙状态失败')
				}
			})
			// #endif
		},
		startScan() {
			// #ifdef MP-WEIXIN
			wx.startBluetoothDevicesDiscovery({
				services: [],
				allowDuplicatesKey: false,
				interval: 8000,
				success: () => {
					this.isDiscovering = true
					this.list = []
					this.onDeviceFound()
				},
				fail: (err) => {
					this.handleError(err, '启动蓝牙扫描失败')
				}
			})
			// #endif
		},
		onDeviceFound() {
			// #ifdef MP-WEIXIN
			wx.onBluetoothDeviceFound((res) => {
				const devices = res.devices || []
				const filtered = devices
					.filter(d => d.localName && (
						d.localName.startsWith('ck_100') ||
						d.localName.startsWith('S503') ||
						d.localName.startsWith('S8503')
					))
					.map(d => ({ name: d.localName, deviceId: d.deviceId }))
				if (filtered.length > 0) {
					this.list = [...this.list, ...filtered]
				}
			})
			// #endif
		},
		stopScan() {
			// #ifdef MP-WEIXIN
			wx.stopBluetoothDevicesDiscovery({
				success: () => {
					this.isDiscovering = false
				},
				fail: (err) => {
					this.handleError(err, '停止蓝牙扫描失败')
				}
			})
			// #endif
		},
		handleError(err, defaultMessage) {
			let message = defaultMessage
			const errMap = {
				10001: '当前设备不支持蓝牙功能，请在系统设置中开启蓝牙',
				10002: '蓝牙适配器不可用，请检查设备蓝牙是否开启并授权',
				10003: '未找到指定设备，请确认设备已开启',
				10004: '连接失败，请确认设备在范围内并重新连接',
				10005: '蓝牙设备未找到指定服务，请重新连接',
				10006: '蓝牙设备未找到指定特征值',
				10007: '蓝牙设备未找到指定特征值对应的描述',
				10008: '当前设备不支持该操作',
				10009: '系统版本过低，蓝牙功能需要更高版本系统支持'
			}
			if (err && err.errCode && errMap[err.errCode]) {
				message = errMap[err.errCode]
			}
			uni.showModal({ title: '提示', content: message, showCancel: false })
		}
	}
}
</script>

<style lang="scss" scoped>
.container { min-height: 100vh; background: #f5f5f5; }
.info { font-size: 32rpx; background-image: radial-gradient(circle 248px at center, #16d9e3 0%, #30c7ec 47%, #46aef7 100%); margin: 10rpx 20rpx; border-radius: 10rpx; padding: 10rpx; color: #fff; font-weight: 600; }
.info .line { margin-bottom: 10rpx; }
.form { margin-top: 50rpx; align-items: center; display: flex; }
.form .btn { align-items: center; width: 300rpx; background-color: #108ee9; color: #fff; font-size: 32rpx; height: 50rpx; line-height: 50rpx; border-radius: 10rpx; margin: 20rpx auto; padding: 10rpx; text-align: center; font-weight: 600; }
.text { display: flex; justify-content: center; align-items: center; font-size: 30rpx; text-align: center; font-weight: 600; }
.list { overflow-y: auto; max-height: 630rpx; padding: 10rpx 30rpx; margin: 20rpx; border-radius: 20rpx; background-image: linear-gradient(135deg, #c0d7fa 0%, #c3cfe2 100%); }
.item { margin: 20rpx 10rpx; font-size: 30rpx; }
.lock { display: flex; justify-content: space-between; align-items: center; font-size: 28rpx; font-weight: 600; line-height: 32rpx; margin: 20rpx 20rpx; height: 32rpx; text-align: center; }
</style>
