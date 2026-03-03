<template>
	<view class="banner-swiper">
		<swiper 
			:autoplay="autoplay" 
			:indicator-dots="indicatorDots"
			:interval="interval"
			:circular="circular"
		>
			<swiper-item v-for="(item, index) in images" :key="index" @tap="onTap(item, index)">
				<image 
					class="banner-image" 
					:src="item.url || item" 
					:mode="imageMode"
				/>
			</swiper-item>
		</swiper>
	</view>
</template>

<script>
/**
 * 轮播图组件 - 从首页拆分
 * @property {Array} images - 图片列表，支持字符串数组或对象数组 [{url, link}]
 * @property {Boolean} autoplay - 是否自动播放
 * @property {Boolean} indicatorDots - 是否显示指示点
 * @property {Number} interval - 切换间隔
 * @property {Boolean} circular - 是否循环
 * @property {String} imageMode - 图片裁剪模式
 * @event tap - 点击图片事件
 */
export default {
	name: 'BannerSwiper',
	props: {
		images: {
			type: Array,
			default: () => []
		},
		autoplay: {
			type: Boolean,
			default: true
		},
		indicatorDots: {
			type: Boolean,
			default: true
		},
		interval: {
			type: Number,
			default: 3000
		},
		circular: {
			type: Boolean,
			default: true
		},
		imageMode: {
			type: String,
			default: 'scaleToFill'
		},
		height: {
			type: String,
			default: '294rpx'
		}
	},
	methods: {
		onTap(item, index) {
			this.$emit('tap', { item, index })
			// 如果有链接则跳转
			if (item.link) {
				uni.navigateTo({ url: item.link })
			}
		}
	}
}
</script>

<style lang="scss" scoped>
.banner-swiper {
	width: 100%;
	
	swiper {
		width: 100%;
		height: v-bind(height);
	}
	
	.banner-image {
		width: 100%;
		height: 100%;
	}
}
</style>
