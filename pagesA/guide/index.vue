<template>
	<view class="container" :style="themeStyle">
		<view class="item" v-if="list.length > 0" v-for="(item, index) in list" :key="index">
			<image class="img" :src="item" mode="widthFix" />
		</view>
		<view class="no-guide" v-if="list.length <= 0">
			<text class="no">暂无门店指引</text>
		</view>
	</view>
</template>

<script>
import http from '@/utils/http'

export default {
	data() {
		return {
			store_id: '',
			list: []
		}
	},

	onLoad(options) {
		const storeId = options && (options.storeId || options.store_id)
		if (storeId) {
			this.store_id = storeId
			this.getStoreInfo(storeId)
		}
	},

	methods: {
		getStoreInfo(storeId) {
			uni.showLoading({ title: '获取中...' })
			http.get('/member/index/getStoreInfo/' + storeId).then(res => {
				uni.hideLoading()
				if (res.code == 0) {
					// 字段名为 env_images（admin后台保存的字段）
					const envImg = res.data && (res.data.env_images || res.data.store_env_img)
					if (envImg && envImg.length > 0) {
						this.list = envImg.split(',').filter(url => url)
					}
				} else {
					uni.showModal({
						content: '请求服务异常，请稍后重试',
						showCancel: false
					})
				}
			}).catch(() => {
				uni.hideLoading()
			})
		}
	}
}
</script>

<style lang="scss" scoped>
.container {
	padding-bottom: 100rpx;
}

.img {
	width: 100%;
}

.no-guide {
	width: 100%;
	height: 60vh;
	display: flex;
	justify-content: center;
	align-items: center;
}

.no {
	font-size: 32rpx;
	color: #999;
}
</style>
