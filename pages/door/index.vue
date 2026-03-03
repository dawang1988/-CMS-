<template>
	<view class="page" :style="themeStyle">
		<view class="top">
			<view class="top-search">
				<view class="left">
					<view class="label" @tap="goLocation">
						<image src="/static/icon/city.png" mode="scaleToFill" />
						<text>{{cityName}}</text>
						<view class="icon"></view>
					</view>
					<input class="input" type="text" placeholder="输入关键字搜索门店" v-model="name" @input="onInputChange" />
				</view>
				<view v-if="isMap" class="right" @tap="goListSeach">
					<text>列表</text>
				</view>
				<view v-else class="right" @tap="goMapSeach">
					<image src="/static/icon/location.png" style="width:36rpx;height:36rpx;" />
				</view>
			</view>
			<view class="banner" v-if="bannershowlist.length > 0">
				<swiper autoplay indicator-dots>
					<swiper-item v-for="(item, index) in bannershowlist" :key="index">
						<image :src="item.imgUrl" mode="scaleToFill"></image>
					</swiper-item>
				</swiper>
			</view>
		</view>

		<view class="container">
			<!-- 列表 -->
			<view class="lists">
				<view class="list">
					<block v-if="MainStorelist.length > 0">
						<view v-for="(item, index) in MainStorelist" :key="index">
							<view class="store-card" @tap="goStore" :data-storeid="item.store_id">
								<view class="image-container">
					
									<view v-if="item.head_img">
										<image class="store-card__image" :src="item.head_img" mode="aspectFill" lazy-load></image>
									</view>
									<view v-else>
										<image class="store-card__image" src="/static/logo.png" mode="aspectFill"></image>
									</view>
								</view>
								<view class="text-container">
									<view class="standard">
										空闲:
										<text style="color:#e0260d">{{item.freeRoomNum}}</text>
									</view>
									<view class="name">{{item.store_name}}</view>
									<view class="address">{{item.address}}</view>
									<view class="score-row" v-if="item.avg_score > 0">
										<text class="score-star">★</text>
										<text class="score-num">{{item.avg_score}}</text>
										<text class="review-count">{{item.review_count || 0}}条评价</text>
									</view>
									<view class="item-buttom">
										<view class="info">
											<view class="position" @tap.stop="goTencentMap" :data-info="item" v-if="item.distance">
												<text class="distance">{{item.distance}}</text>km
											</view>
										</view>
										<view class="door-button" hover-class="door-button-hover">立即预定</view>
									</view>
								</view>
							</view>
						</view>
					</block>
					<block v-else>
						<view>
							<view class="noStoreInfo">
								<view>
									<image class="noStore-image" src="/static/img/no-store.png" mode="scaleToFill" />
								</view>
								<text>暂无门店</text>
							</view>
						</view>
					</block>
				</view>
			</view>
		</view>

		<view style="margin-bottom: 80rpx;"></view>
	</view>
</template>

<script>
import http from '@/utils/http.js'
import listMixin from '@/mixins/listMixin'

export default {
	mixins: [listMixin],
	data() {
		return {
			appName: '',
			isMap: false,
			bannershowlist: [],
			MainStorelist: [],
			cityName: '选择城市',
			name: '',
			lat: '',
			lon: '',
			isLogin: false
		}
	},
	onLoad(options) {
		this.appName = getApp().globalData.appName
		this.getBannerdata()
		if (options.store_id) {
			this.store_id = options.store_id
		}
	},
	onShow() {
		// 动态设置导航栏标题（从后台配置读取）
		const appName = getApp().globalData.appName
		if (appName) {
			uni.setNavigationBarTitle({ title: appName })
		}
		this.getLocation()
		this.isLogin = getApp().globalData.isLogin
	},
	onPullDownRefresh() {
		this.pageNo = 1
		this.canLoadMore = true
		this.MainStorelist = []
		this.getMainListdata('refresh')
		uni.stopPullDownRefresh()
	},
	onReachBottom() {
		if (this.canLoadMore) {
			this.getMainListdata()
		} else {
			uni.showToast({
				title: '没有更多了...',
				icon: 'none'
			})
		}
	},
	methods: {
		goStore(e) {
			const storeId = e.currentTarget.dataset.storeid
			if (!storeId) {
				uni.showToast({
					title: '门店信息错误',
					icon: 'none'
				})
				return
			}
			// 设置全局门店ID并跳转到门店首页
			uni.setStorageSync('global_store_id', storeId)
			uni.navigateTo({
				url: '/pages/index/index?store_id=' + storeId
			})
		},
		goLocation() {
			uni.navigateTo({
				url: '/pages/location/index',
				events: {
					pageDataList: (data) => {
						this.cityName = data
						uni.setStorageSync('cityName', data)
						this.getBannerdata()
					}
				}
			})
		},
		goMapSeach() {
			uni.navigateTo({
				url: '/pages/map/index'
			})
		},
		goListSeach() {
			this.isMap = false
			this.name = ''
			this.getMainListdata('refresh')
		},
		goTencentMap(e) {
			const store = e.currentTarget.dataset.info
			uni.openLocation({
				latitude: store.latitude,
				longitude: store.longitude,
				name: store.store_name,
				address: store.address,
				scale: 28
			})
		},
		getMainListdata(e) {
			let message = ''
			if (e === 'refresh') {
				this.MainStorelist = []
				this.canLoadMore = true
				this.pageNo = 1
				message = '获取中...'
			}

			http.post('/member/index/getStoreList', {
				pageNo: this.pageNo,
				pageSize: 10,
				cityName: this.cityName,
				lat: this.lat,
				lon: this.lon,
				name: this.name
			}).then(info => {
				if (info.code === 0) {
					const list = info.data.list || []
					if (list.length === 0) {
						this.canLoadMore = false
					} else {
						if (this.MainStorelist.length > 0) {
							this.MainStorelist = this.MainStorelist.concat(list)
						} else {
							this.MainStorelist = list
						}
						this.canLoadMore = this.MainStorelist.length < info.data.total
						this.pageNo++
					}
				} else {
					uni.showModal({
						content: info.msg,
						showCancel: false
					})
				}
			}).catch(() => {})
		},
		getBannerdata() {
			http.get('/member/index/getBannerList').then(info => {
				if (info.code === 0) {
					this.bannershowlist = info.data
				}
			}).catch(() => {})
		},
		getLocation() {
			uni.getLocation({
				type: 'gcj02',
				success: (res) => {
					this.lat = res.latitude
					this.lon = res.longitude
					this.getMainListdata('refresh')
				},
				fail: (err) => {
					// 定位失败时显示友好提示
					uni.showModal({
						title: '定位提示',
						content: '无法获取您的位置，您可以手动选择城市查看门店',
						confirmText: '选择城市',
						cancelText: '继续浏览',
						success: (res) => {
							if (res.confirm) {
								this.goLocation()
							}
						}
					})
					this.getMainListdata('refresh')
				}
			})
		},
		onInputChange(e) {
			this.name = e.detail.value
			this.getMainListdata('refresh')
		}
	}
}
</script>

<style lang="scss">
page {
	width: 100%;
	height: 100%;
}
</style>
<style lang="scss" scoped>

.container {
	margin-top: 10rpx;
}

.top {
	width: 100%;
	background-color: var(--main-color, #6da773fd);
}

.top .banner {
	height: 206rpx;
	background: linear-gradient(180deg, var(--main-color, #6da773fd) 0%, #FFFFFF 100%);
}

.top-search {
	display: flex;
	justify-content: center;
	padding-bottom: 20rpx;
}

.top-search .left {
	margin-top: 20rpx;
	background: #fff;
	width: 600rpx;
	margin-right: 20rpx;
	display: flex;
	height: 60rpx;
	padding: 10rpx 0;
	box-sizing: border-box;
	border-radius: 25rpx;
}

.top-search .label {
	width: 200rpx;
	display: flex;
	justify-content: center;
	align-items: center;
	flex-wrap: nowrap;
	border-right: 1rpx solid #ddd;
}

.top-search .label image {
	width: 25rpx;
	height: 25rpx;
	margin-right: 6rpx;
}

.top-search .label text {
	font-size: 26rpx;
	display: block;
	max-width: 240rpx;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	margin-right: 6rpx;
}

.top-search .label .icon {
	width: 0;
	height: 0;
	border-top: 10rpx solid var(--main-color);
	border-right: 10rpx solid transparent;
	border-left: 10rpx solid transparent;
}

.top-search .input {
	padding: 0 20rpx;
}

.top-search .right {
	margin-top: 20rpx;
	color: #000;
	text-align: center;
	width: 90rpx;
}

.top-search .right text {
	display: block;
	font-size: 24rpx;
}

.banner {
	height: 400rpx;
	width: 100%;
	overflow: hidden;
	margin-bottom: 10rpx;
	text-align: center;
	background: #fff;
}

.banner swiper {
	height: 100%;
	width: 100%;
}

.banner image {
	width: 100%;
	height: 100%;
}

.store-card {
	height: 300rpx;
	background-color: #fff;
	border-radius: 20rpx;
	margin: 0 15rpx;
	display: flex;
	flex-direction: row;
	margin-bottom: 20rpx;
}

.store-card__image {
	width: 200rpx;
	height: 260rpx;
	margin: 20rpx;
	border-radius: 15rpx;
}

.text-container {
	width: 100%;
	position: relative;
}

.text-container .standard {
	font-size: 24rpx;
	line-height: 40rpx;
	text-align: center;
	position: absolute;
	top: 15rpx;
	width: 120rpx;
	height: 42rpx;
	right: 10rpx;
	background-color: #FFFFFF;
	color: var(--main-color, #5AAB6E);
	border: 1rpx var(--main-color, #5AAB6E) solid;
	border-radius: 20rpx;
}

.text-container .name {
	width: 320rpx;
	height: 120rpx;
	margin-top: 15rpx;
	font-size: 32rpx;
	font-weight: bold;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	overflow: hidden;
	text-overflow: ellipsis;
	max-height: 3.0em;
	line-height: 1.5em;
}

.text-container .address {
	font-size: 23rpx;
	margin-top: 8rpx;
	color: #686464;
	height: 50rpx;
	margin-right: 4rpx;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	overflow: hidden;
	text-overflow: ellipsis;
}

.text-container .score-row {
	display: flex;
	align-items: center;
	margin-top: 8rpx;
}

.text-container .score-row .score-star {
	color: #FFB800;
	font-size: 24rpx;
}

.text-container .score-row .score-num {
	color: #FFB800;
	font-size: 24rpx;
	font-weight: 600;
	margin-left: 4rpx;
}

.text-container .score-row .review-count {
	color: #999;
	font-size: 22rpx;
	margin-left: 12rpx;
}

.text-container .item-buttom {
	display: flex;
	justify-content: space-between;
}

.text-container .item-buttom .info {
	display: flex;
}

.text-container .item-buttom .info .position {
	font-size: 22rpx;
	line-height: 40rpx;
	display: flex;
	text-align: center;
	margin: auto;
	padding: 0rpx 10rpx;
	border-radius: 10rpx;
	width: fit-content;
	color: var(--main-color, #5AAB6E);
	border: 1px solid var(--main-color, #5AAB6E);
}

.text-container .item-buttom .door-button {
	height: 58rpx;
	line-height: 58rpx;
	padding: 0rpx 20rpx;
	font-size: 28rpx;
	margin-right: 20rpx;
	color: #fff;
	border-radius: 5px;
	background-color: var(--main-color);
	transition: all 0.2s;
}

.door-button-hover {
	opacity: 0.8;
	transform: scale(0.98);
}

.noStoreInfo {
	font-size: 30rpx;
	text-align: center;
	margin: 100rpx;
	color: #BCBCBC;
}

.noStoreInfo .noStore-image {
	height: 220rpx;
	width: 220rpx;
}
</style>
