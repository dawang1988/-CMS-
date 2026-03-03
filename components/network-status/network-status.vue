<template>
	<view class="network-status" v-if="!isConnected">
		<view class="network-offline">
			<image src="/static/icon/no-network.png" mode="aspectFit" class="offline-icon" />
			<text class="offline-text">网络连接已断开</text>
			<text class="offline-tip">请检查您的网络设置</text>
			<button class="retry-btn" @tap="checkNetwork">重新连接</button>
		</view>
	</view>
</template>

<script>
export default {
	name: 'NetworkStatus',
	data() {
		return {
			isConnected: true,
			networkType: 'unknown'
		}
	},
	mounted() {
		this.checkNetwork()
		this.listenNetworkChange()
	},
	methods: {
		// 检查网络状态
		checkNetwork() {
			uni.getNetworkType({
				success: (res) => {
					this.networkType = res.networkType
					this.isConnected = res.networkType !== 'none'
					if (this.isConnected) {
						this.$emit('online')
					} else {
						this.$emit('offline')
					}
				}
			})
		},
		// 监听网络变化
		listenNetworkChange() {
			uni.onNetworkStatusChange((res) => {
				this.isConnected = res.isConnected
				this.networkType = res.networkType
				if (res.isConnected) {
					uni.showToast({ title: '网络已恢复', icon: 'none' })
					this.$emit('online')
				} else {
					this.$emit('offline')
				}
			})
		}
	}
}
</script>

<style lang="scss" scoped>
.network-status {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: #fff;
	z-index: 9999;
	display: flex;
	align-items: center;
	justify-content: center;
}
.network-offline {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 40rpx;
}
.offline-icon {
	width: 200rpx;
	height: 200rpx;
	margin-bottom: 30rpx;
}
.offline-text {
	font-size: 32rpx;
	color: #333;
	margin-bottom: 16rpx;
}
.offline-tip {
	font-size: 26rpx;
	color: #999;
	margin-bottom: 40rpx;
}
.retry-btn {
	width: 240rpx;
	height: 80rpx;
	line-height: 80rpx;
	background: var(--main-color, #5AAB6E);
	color: #fff;
	border-radius: 40rpx;
	font-size: 28rpx;
}
</style>
