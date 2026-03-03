<template>
	<view class="container" :style="themeStyle">
		<view class="info">
			<view class="line">1. 打开手机蓝牙设置开关,并靠近门锁</view>
			<view class="line">2. 输入2.4G频段的wifi名称和密码</view>
			<view class="line">3. 智能锁装电池，触摸输密码面板唤醒锁</view>
			<view class="line">4. 配置WIFI 按钮进行门锁联网配置</view>
			<view class="line">5. 配置时间大概15秒，请耐心等待完成</view>
			<view class="line">6. 仅支持带wifi功能的门锁</view>
		</view>
		<view class="form">
			<view class="sn">
				<label class="label">WIFI名称： </label>
				<input class="input" v-model="wifiSSid" type="text" placeholder="请输入名称（禁止中文）" />
			</view>
			<view class="sn">
				<label class="label">WIFI密码： </label>
				<input class="input" v-model="wifiPwd" type="text" placeholder="请输入8位以上密码" />
			</view>
			<view class="btn" @tap="configLock">配置WIFI</view>
		</view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			lock_data: '',
			wifiSSid: '',
			wifiPwd: ''
		}
	},
	onLoad(options) {
		this.lock_data = options.lock_data || ''
	},
	methods: {
		configLock() {
			if (!this.wifiSSid || !this.wifiPwd || this.wifiPwd.length < 8) {
				uni.showToast({ title: 'WIFI信息不正确', icon: 'error' })
				return
			}
			if (!this.lock_data) {
				uni.showToast({ title: '门锁数据为空', icon: 'none' })
				return
			}
			// #ifdef MP-WEIXIN
			try {
				const lock = require('@/utils/lock.js')
				lock.configLockWifi(this.lock_data, this.wifiSSid, this.wifiPwd)
			} catch (e) {
				uni.showToast({ title: '配置失败：' + e.message, icon: 'none' })
			}
			// #endif
			// #ifndef MP-WEIXIN
			uni.showToast({ title: '门锁WiFi配置仅支持微信小程序', icon: 'none' })
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
.form .sn { display: flex; justify-content: center; align-items: center; font-size: 36rpx; font-weight: 600; margin-top: 20rpx; }
.form .sn .label { margin-right: 20rpx; }
.form .sn .input { border: rgb(67, 160, 236) 0.1rpx solid; background-color: #fff; padding: 5rpx; width: 400rpx; }
.form .btn { align-items: center; width: 300rpx; background-color: #108ee9; color: #fff; font-size: 32rpx; height: 50rpx; line-height: 50rpx; border-radius: 10rpx; margin: 20rpx auto; padding: 10rpx; text-align: center; font-weight: 600; }
</style>
