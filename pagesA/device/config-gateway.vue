<template>
	<view class="container" :style="themeStyle">
		<view class="info">
			<view class="line">1. 打开手机蓝牙设置开关,并靠近网关</view>
			<view class="line">2. 输入需要配置的Wifi名称和密码</view>
			<view class="line">3. Wifi仅支持2.4G频段，名称不能有中文</view>
			<view class="line">4. 将网关重新通电，灯光闪烁时点击"扫描"</view>
			<view class="line">5. 选择目标网关进行初始化</view>
		</view>
		<view class="form">
			<view class="sn">
				<label class="label">Wifi：</label>
				<input class="input" v-model="wifiSn" :disabled="checkSuccess" type="text" placeholder="请输入WIFI名称" maxlength="16" />
			</view>
			<view class="sn">
				<label class="label">密码：</label>
				<input class="input" v-model="wifiPwd" :disabled="checkSuccess" type="text" placeholder="请输入WIFI密码" />
			</view>
			<view class="btn" @tap="scanGateway">扫描网关</view>
		</view>
		<view class="list" v-if="checkSuccess">
			<view class="item" v-for="(item, index) in list" :key="index">
				<view class="lock enable">
					<view>{{ item.deviceId }}({{ item.device_name }})</view>
					<view class="btn" v-if="item.isSettingMode" @tap="initGateway(index)">初始化</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			gatewayList: [],
			list: [],
			wifiSn: '',
			wifiPwd: '',
			checkSuccess: false,
			successList: []
		}
	},
	methods: {
		scanGateway() {
			if (!this.wifiSn || !this.wifiPwd || this.wifiPwd.length < 8) {
				uni.showToast({ title: 'Wifi信息错误', icon: 'error' })
				return
			}
			this.checkSuccess = true
			uni.showToast({ title: '将网关重新通电', icon: 'none' })
			// #ifdef MP-WEIXIN
			try {
				const plugin = requirePlugin('myPlugin')
				plugin.startScanGateway((deviceFromScan, deviceFromScanList) => {
					let list = []
					deviceFromScanList.forEach((item, index) => {
						list.push({
							deviceId: item.deviceId,
							device_name: item.device_name,
							isSettingMode: item.isSettingMode,
							rssi: item.rssi,
							index: index
						})
					})
					this.gatewayList = deviceFromScanList
					this.list = list
				}, (err) => {
					uni.showToast({ title: '蓝牙扫描失败', icon: 'error' })
				})
			} catch (e) {
				uni.showToast({ title: '插件未加载，请在微信小程序中使用', icon: 'none' })
			}
			// #endif
			// #ifndef MP-WEIXIN
			uni.showToast({ title: '网关配置仅支持微信小程序', icon: 'none' })
			// #endif
		},
		initGateway(index) {
			const deviceFromScan = this.gatewayList[index]
			if (!deviceFromScan.isSettingMode) {
				uni.showModal({
					title: '提示',
					content: '网关当前不可添加，请重新通电后再试',
					showCancel: false
				})
				return
			}
			uni.showLoading({ title: '正在连接网关' })
			// #ifdef MP-WEIXIN
			try {
				const plugin = requirePlugin('myPlugin')
				plugin.connectGateway({ deviceFromScan }).then(res => {
					if (res.errorCode == 0) {
						plugin.initGateway({
							deviceFromScan,
							configuration: {
								type: 2,
								SSID: this.wifiSn,
								wifiPwd: this.wifiPwd,
								uid: 22700401,
								password: 'cbe3d534f8880e7581e62992c0ade209',
								companyId: 0,
								branchId: 0,
								plugName: deviceFromScan.device_name,
								server: 'plug.sciener.cn',
								port: 2999,
								useLocalIPAddress: false
							}
						}).then(initRes => {
							uni.hideLoading()
							if (initRes.errorCode === 0) {
								uni.showToast({ title: '初始化完成', icon: 'success' })
								this.list = []
								this.gatewayList = []
							} else {
								uni.showModal({
									title: '提示',
									content: `网关初始化失败：${initRes.errorMsg}`,
									showCancel: false
								})
							}
						})
					} else {
						uni.hideLoading()
						uni.showModal({
							title: '提示',
							content: `网关连接失败：${res.errorMsg}`,
							showCancel: false
						})
					}
				})
			} catch (e) {
				uni.hideLoading()
				uni.showToast({ title: '插件调用失败', icon: 'none' })
			}
			// #endif
		}
	}
}
</script>

<style lang="scss" scoped>
.container { min-height: 100vh; background: #f5f5f5; }
.info { font-size: 32rpx; background-image: radial-gradient(circle 248px at center, #16d9e3 0%, #30c7ec 47%, #46aef7 100%); margin: 10rpx 20rpx; border-radius: 10rpx; padding: 10rpx; color: #fff; font-weight: 600; }
.info .line { margin-bottom: 10rpx; }
.form { margin-top: 50rpx; align-items: center; }
.form .sn { margin-top: 20rpx; display: flex; justify-content: center; align-items: center; font-size: 36rpx; font-weight: 600; }
.form .sn .label { margin-right: 20rpx; }
.form .sn .input { border: rgb(67, 160, 236) 0.1rpx solid; background-color: #fff; padding: 5rpx; width: 280rpx; }
.form .btn { align-items: center; width: 300rpx; background-color: #108ee9; color: #fff; font-size: 32rpx; height: 50rpx; line-height: 50rpx; border-radius: 10rpx; margin: 20rpx auto; padding: 10rpx; text-align: center; font-weight: 600; }
.list { overflow-y: auto; max-height: 330rpx; padding: 10rpx 30rpx; margin: 20rpx; border-radius: 20rpx; background-image: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); }
.item { margin: 30rpx 10rpx; font-size: 30rpx; }
.lock { display: flex; justify-content: space-between; align-items: center; font-size: 28rpx; font-weight: 600; line-height: 32rpx; margin: 20rpx; height: 32rpx; text-align: center; }
.lock.enable { color: #2088d3; }
.lock .btn { background-color: #2088d3; color: #fff; padding: 10rpx 30rpx; border-radius: 10rpx; font-size: 26rpx; }
</style>
