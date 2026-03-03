<template>
	<view class="page" :style="themeStyle">
		<!-- 顶部筛选 -->
		<view class="tabs" v-if="isLogin && gameEnabled">
			<view class="filter-bar">
				<view class="filter-item city-item">
					<text class="city-icon">📍</text>
					<text>{{ cityName || '定位中...' }}</text>
				</view>
				<view class="filter-item" @click="showStoreFilter = !showStoreFilter; showStatusFilter = false">
					<text>{{ currentStoreLabel }}</text>
					<text class="arrow">▼</text>
				</view>
				<view class="filter-item" @click="showStatusFilter = !showStatusFilter; showStoreFilter = false">
					<text>{{ currentStatusLabel }}</text>
					<text class="arrow">▼</text>
				</view>
			</view>
			<!-- 门店下拉 -->
			<view class="dropdown-mask" v-if="showStoreFilter" @click="showStoreFilter = false">
				<view class="dropdown-list" @click.stop>
					<view class="dropdown-item" :class="{ active: store_id === 0 }" @click="store_id = 0; showStoreFilter = false; storeDropdown()">全部门店</view>
					<view class="dropdown-item" :class="{ active: store_id === item.value }" v-for="item in stores" :key="item.value" @click="store_id = item.value; showStoreFilter = false; storeDropdown()">{{ item.label }}</view>
				</view>
			</view>
			<!-- 状态下拉 -->
			<view class="dropdown-mask" v-if="showStatusFilter" @click="showStatusFilter = false">
				<view class="dropdown-list" @click.stop>
					<view class="dropdown-item" :class="{ active: status === item.value }" v-for="item in statuslist" :key="item.value" @click="status = item.value; showStatusFilter = false; statusDropdown()">{{ item.label }}</view>
				</view>
			</view>
		</view>

		<view class="container" v-if="gameEnabled">
			<!-- 已登录状态 -->
			<block v-if="isLogin">
				<!-- 有数据 -->
				<block v-if="MainList.length">
					<view class="lists">
						<view class="item" v-for="(item, index) in MainList" :key="item.gameId">
							<!-- 状态标签 -->
							<view class="tag" :class="{
								'blue': item.status == 0,
								'yellow': item.status == 1,
								'green': item.status == 2,
								'gray': item.status == 3 || item.status == 4
							}">
								{{ item.status == 0 ? '组局中' : item.status == 1 ? '已组局' : item.status == 2 ? '已支付' : item.status == 3 ? '已失效' : '房主已解散' }}
							</view>

							<!-- 门店信息 -->
							<view class="door" @tap="goTencentMap" :data-info="item">
								<view class="name">门店：{{ item.store_name || '未知门店' }}</view>
								<image src="/static/icon/location.png" style="width:28rpx;height:28rpx;" />
							</view>

							<!-- 包间信息 -->
							<view class="info">
								<label>包间：</label>
								<text>{{ item.room_name || '未指定' }}</text>
							</view>

							<!-- 时间信息 -->
							<view class="info">
								<label>时间：</label>
								<view class="date">{{ item.start_time }}</view>
							</view>

							<!-- 规则信息 -->
							<view class="info">
								<label>规则：</label>
								<text>{{ item.ruleDesc }}</text>
							</view>

							<!-- 玩家信息 -->
							<view class="info userInfo">
								<label>玩家：</label>
								<view class="users">
									<view class="user" v-for="(user, userIndex) in item.playUserList" :key="user.user_id">
										<!-- 房主可以踢人 -->
										<view class="del-icon" v-if="userinfo.id == item.user_id && userinfo.id != user.user_id" 
											@tap="delUser" :data-gameid="item.gameId" :data-userid="user.user_id">
											<text style="font-size:24rpx;color:#ff0000;">✕</text>
										</view>
										<view class="photo">
											<image :src="user.avatar || '/static/img/default-avatar.png'" mode="aspectFill"
												@error="user.avatar = '/static/img/default-avatar.png'"></image>
										</view>
										<view class="name">{{ user.nickname || '玩家' }}</view>
									</view>
								</view>
							</view>

							<!-- 操作按钮 -->
							<view class="btns">
								<!-- 转发按钮 -->
								<button v-if="item.status != 3 && item.status != 4" class="btn share" open-type="share">
									<image src="/static/icon/send.png" style="width:24rpx;height:24rpx;margin-right:6rpx;" />
									转发好友
								</button>

								<!-- 聊天室（已加入的参与者可见） -->
								<button class="btn chat-btn" 
									v-if="item.status != 3 && item.status != 4 && item.playUserIds.includes(userinfo.id)"
									@tap="goChat" :data-info="item">
									💬 聊天
								</button>

								<!-- 立即加入 -->
								<button class="btn bg-primary" 
									v-if="item.status === 0 && !item.playUserIds.includes(userinfo.id)" 
									@tap="joinExitGame" :data-info="item">
									立即加入
								</button>

								<!-- 退出对局 -->
								<button class="btn exit" 
									v-if="item.status === 0 && item.playUserIds.includes(userinfo.id)" 
									@tap="joinExitGame" :data-info="item">
									退出对局
								</button>

								<!-- 对局已满（非参与者） -->
								<button class="btn full" 
									v-if="item.status === 1 && !item.playUserIds.includes(userinfo.id)">
									对局已满
								</button>

								<!-- 立即支付（仅房主） -->
								<button class="btn pay" 
									v-if="item.status === 1 && userinfo.id == item.user_id" 
									@tap="goOrder" :data-info="item">
									立即支付
								</button>

								<!-- 等待房主支付（非房主的参与者） -->
								<button class="btn full" 
									v-if="item.status === 1 && item.playUserIds.includes(userinfo.id) && userinfo.id != item.user_id">
									等待房主支付
								</button>

								<!-- 已支付（房主） -->
								<button class="btn payed" 
									v-if="item.status === 2 && userinfo.id == item.user_id" 
									@tap="goOrderDetail">
									已支付
								</button>

								<!-- 已支付（非房主的参与者） -->
								<button class="btn payed" 
									v-if="item.status === 2 && item.playUserIds.includes(userinfo.id) && userinfo.id != item.user_id">
									房主已支付
								</button>
							</view>
						</view>
					</view>
				</block>

				<!-- 暂无数据 -->
				<block v-else>
					<view class="nodata-list">暂无拼场信息</view>
				</block>
			</block>

			<!-- 未登录状态 -->
			<block v-else>
				<view class="containerlogin">
					<view class="photo">
						<view class="img">
							<image src="/static/logo.png" mode="widthFix" />
						</view>
						<view class="name">{{ appName }}</view>
					</view>
					<button class="loginBtn bg-primary" @tap="phone">登录</button>
				</view>
			</block>
		</view>

		<!-- 底部发起组局按钮 -->
		<view class="bottom bg-primary" v-if="isLogin && gameEnabled" @tap="addGame">发起组局</view>
	</view>
</template>

<script>
import http from '@/utils/http.js'
import { formatNumber } from '@/utils/util.js'

export default {
	data() {
		return {
			appName: '',
			status: -1,
			statuslist: [
				{ label: '全部状态', value: -1 },
				{ label: '组局中', value: 0 },
				{ label: '已组局', value: 1 },
				{ label: '已支付', value: 2 },
				{ label: '已失效', value: 3 },
				{ label: '已解散', value: 4 }
			],
			store_id: 0,
			stores: [],
			cityName: '',
			MainList: [],
			canLoadMore: true,
			pageNo: 1,
			pageSize: 10,
			userinfo: {},
			isLogin: false,
			from: '',
			mainColor: '#5AAB6E',
			showStoreFilter: false,
			showStatusFilter: false,
			gameEnabled: true
		}
	},
	computed: {
		currentStoreLabel() {
			if (this.store_id === 0) return '全部门店'
			const found = this.stores.find(s => s.value === this.store_id)
			return found ? found.label : '选择门店'
		},
		currentStatusLabel() {
			const found = this.statuslist.find(s => s.value === this.status)
			return found ? found.label : '状态筛选'
		}
	},

	onLoad(options) {
		const app = getApp()
		this.appName = app.globalData.appName || '自助棋牌'
		this.isLogin = app.globalData.isLogin
	},

	onShow() {
		const app = getApp()
		this.isLogin = app.globalData.isLogin

		// 检查拼场功能是否开启
		http.get('/system/config/get', { key: 'game_enabled' })
			.then(res => {
				if (res.code === 0 && res.data.value === '0') {
					this.gameEnabled = false
					uni.showToast({ title: '拼场功能暂未开放', icon: 'none', duration: 1500 })
					setTimeout(() => {
						uni.switchTab({ url: '/pages/door/index' })
					}, 500)
					return
				}
				this.gameEnabled = true
			})
			.catch(() => {
				this.gameEnabled = true
			})

		// 门店页面传过来的
		const door = uni.getStorageSync('door')
		if (door) {
			this.cityName = door.cityName
			this.store_id = door.store_id
			this.from = 1
		} else {
			this.store_id = 0
			this.from = ''
		}

		if (app.globalData.isLogin) {
			this.getuserinfo()
			if (!this.cityName) {
				this.getLocation()
			} else {
				this.getXiaLaListdata()
			}
		}
	},

	onHide() {
		// 清除缓存
	},

	onPullDownRefresh() {
		this.pageNo = 1
		this.canLoadMore = true
		this.MainList = []
		this.getListData('refresh')
		uni.stopPullDownRefresh()
	},

	onReachBottom() {
		if (this.canLoadMore) {
			this.getListData('')
		} else {
			uni.showToast({
				title: '我是有底线的...',
				icon: 'none'
			})
		}
	},

	onShareAppMessage() {
		return {
			title: this.appName,
			path: '/pages/door/index',
			imageUrl: '/static/logo.png',
			success: (res) => {
				uni.showToast({
					title: '分享成功',
					icon: 'success',
					duration: 2000
				})
			}
		}
	},

	methods: {
		// 处理头像URL
		getAvatar(avatar) {
			if (!avatar) return '/static/logo.png'
			if (avatar.startsWith('http')) return avatar
			const config = require('@/config/index.js').default || require('@/config/index.js')
			return config.imageBase + avatar
		},

		// 获取用户信息
		getuserinfo() {
			const app = getApp()
			if (!app.globalData.isLogin) return

			http.get('/member/user/get')
				.then(res => {
					if (res.code === 0) {
						this.userinfo = res.data
					}
				})
		},

		// 通过定位获取城市
		getLocation() {
			uni.getLocation({
				type: 'gcj02',
				success: (res) => {
					const lat = res.latitude
					const lng = res.longitude
					// 通过后端逆地理编码获取城市名
					http.get(`/member/index/getCityByLocation?lat=${lat}&lng=${lng}`)
						.then(apiRes => {
							if (apiRes.code === 0 && apiRes.data.city) {
								this.cityName = apiRes.data.city
							} else {
								this.cityName = ''
							}
							this.getXiaLaListdata()
						})
						.catch(() => {
							this.cityName = ''
							this.getXiaLaListdata()
						})
				},
				fail: () => {
					this.cityName = ''
					this.getXiaLaListdata()
				}
			})
		},

		// 获取门店列表数据
		getXiaLaListdata() {
			// 定位失败时不传cityName，查全部门店
			const validCity = this.cityName && this.cityName !== '未知城市' && this.cityName !== '定位失败'
			const cityParam = validCity ? `cityName=${this.cityName}&` : ''
			http.get(`/member/index/getStoreList?${cityParam}pageSize=100`)
				.then(res => {
					if (res.code === 0) {
						// 兼容两种返回格式：{list:[...]} 或 直接数组
						const rawList = Array.isArray(res.data) ? res.data : (res.data.list || [])
						const stores = rawList.map(it => ({
							label: it.key || it.name || it.store_name,
							value: it.value || it.id || it.store_id
						}))
						this.stores = stores

						// 如果城市名为空，从门店数据中获取
						if (!this.cityName && rawList.length > 0) {
							const city = rawList[0].city || ''
							this.cityName = city.replace(/市$/, '') || '全国'
						}

						if (!this.from && stores.length > 0) {
							this.store_id = stores[0].value
						}

						this.getListData('refresh')
					}
				})
				.catch(err => {
					uni.showModal({
						content: err.msg || '获取门店列表失败',
						showCancel: false
					})
				})
		},

		// 门店下拉菜单发生变化
		storeDropdown() {
			this.getListData('refresh')
		},

		// 状态下拉菜单发生变化
		statusDropdown() {
			this.getListData('refresh')
		},

		// 获取列表数据
		getListData(type) {
			const app = getApp()
			if (!app.globalData.isLogin) {
				uni.showModal({
					content: '请您先登录，再重试！',
					showCancel: false
				})
				return
			}

			const astatus = this.status == -1 ? '' : this.status

			if (type == 'refresh') {
				this.pageNo = 1
				this.MainList = []
			}

			http.post('/member/game/getGamePage', {
				pageNo: this.pageNo,
				pageSize: this.pageSize,
				store_id: this.store_id,
				status: astatus
			})
				.then(res => {
					if (res.code === 0) {
						const list = (res.data.list || []).map(item => ({
							...item,
							playUserIds: item.playUserIds || [],
							playUserList: item.playUserList || []
						}))
						if (list.length === 0) {
							this.canLoadMore = false
						} else {
							this.MainList = this.MainList.concat(list)
							this.pageNo = this.pageNo + 1
							this.canLoadMore = this.MainList.length < res.data.total
						}
					}
				})
				.catch(err => {
					uni.showModal({
						content: err.msg || '获取数据失败',
						showCancel: false
					})
				})
		},

		// 到腾讯地图
		goTencentMap(e) {
			const store = e.currentTarget.dataset.info
			this.goMap(store)
		},

		// 打开地图
		goMap(store) {
			uni.openLocation({
				latitude: store.latitude,
				longitude: store.longitude,
				name: store.store_name,
				address: store.address,
				scale: 28
			})
		},

		// 踢出对局
		delUser(e) {
			const gameId = e.currentTarget.dataset.gameid
			const userId = e.currentTarget.dataset.userid
			const app = getApp()

			if (!app.globalData.isLogin) return

			http.post(`/member/game/deleteUser/${gameId}/${userId}`, {
				gameId: gameId,
				user_id: userId
			})
				.then(() => {
					uni.showToast({
						title: '操作成功',
						icon: 'success'
					})
					this.getListData('refresh')
				})
		},

		// 发起组局
		addGame() {
			let storeId = this.store_id
			if (!storeId && this.stores.length > 0) {
				storeId = this.stores[0].value
			}
			if (!storeId) {
				uni.showToast({ title: '暂无可用门店', icon: 'none' })
				return
			}
			uni.navigateTo({
				url: `/pages/door/select?store_id=${storeId}&mode=game`
			})
		},

		// 进入聊天室
		goChat(e) {
			const info = e.currentTarget.dataset.info
			const gameInfo = encodeURIComponent(JSON.stringify({
				title: info.title || info.ruleDesc,
				store_name: info.store_name,
				playUserList: info.playUserList
			}))
			uni.navigateTo({
				url: `/pages/door/chat?gameId=${info.gameId}&gameInfo=${gameInfo}`
			})
		},

		// 加入或退出组局
		joinExitGame(e) {
			const info = e.currentTarget.dataset.info

			// 退出
			if (info.playUserIds.includes(this.userinfo.id)) {
				if (info.user_id == this.userinfo.id) {
					// 房主退出
					uni.showModal({
						title: '提示',
						content: '您确定退出该组局吗？房主退出后该对局会直接解散。',
						success: (res) => {
							if (res.confirm) {
								this.joinGame(info.gameId)
							}
						}
					})
				} else {
					// 用户退出
					uni.showModal({
						title: '提示',
						content: '您确定退出该组局吗？退出后对局将开放给其他玩家加入。',
						success: (res) => {
							if (res.confirm) {
								this.joinGame(info.gameId)
							}
						}
					})
				}
			} else {
				// 加入对局
				this.joinGame(info.gameId)
			}
		},

		// 加入或退出接口
		joinGame(gameId) {
			const app = getApp()
			if (!app.globalData.isLogin) return

			http.post(`/member/game/join/${gameId}`, {
				gameId: gameId
			})
				.then(() => {
					uni.showToast({
						title: '操作成功',
						icon: 'success'
					})
					setTimeout(() => {
						this.getListData('refresh')
					}, 500)
				})
				.catch(err => {
					uni.showModal({
						content: err.msg || '操作失败',
						showCancel: false
					})
				})
		},

		// 立即支付提交订单
		goOrder(e) {
			const info = e.currentTarget.dataset.info
			const sdays = info.start_time.split(' ')[0].split('-')
			const astartTime = sdays ? [sdays[1], sdays[2]].map(formatNumber).join('.') : ''

			uni.navigateTo({
				url: `/pages/order/submit?roomId=${info.room_id}&startTime=${info.start_time}&endTime=${info.end_time}&doorname=${info.store_name}&storeId=${info.store_id}&daytime=${astartTime}`
			})
		},

		// 已支付去订单详情
		goOrderDetail() {
			uni.navigateTo({
				url: '/pages/order/detail?toPage=true'
			})
		},

		// 去登录
		phone() {
			uni.navigateTo({
				url: '/pages/user/login'
			})
		}
	}
}
</script>

<style lang="scss" scoped>
.page {
	min-height: 100vh;
	background-color: #f5f5f5;
	padding-bottom: 120rpx;
}

.tabs {
	background: #fff;
	position: sticky;
	top: 0;
	z-index: 100;
}

.container {
	padding: 20rpx;
}

.lists {
	.item {
		background: #fff;
		border-radius: 16rpx;
		padding: 30rpx;
		margin-bottom: 20rpx;
		position: relative;
		box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.08);

		.tag {
			position: absolute;
			top: 0;
			right: 0;
			padding: 10rpx 30rpx;
			border-radius: 0 16rpx 0 16rpx;
			color: #fff;
			font-size: 24rpx;

			&.blue {
				background: #1989fa;
			}

			&.yellow {
				background: #ff976a;
			}

			&.green {
				background: #07c160;
			}

			&.gray {
				background: #969799;
			}
		}

		.door {
			display: flex;
			align-items: center;
			justify-content: space-between;
			margin-bottom: 20rpx;
			padding-top: 10rpx;

			.name {
				font-size: 32rpx;
				font-weight: 600;
				color: #323233;
			}
		}

		.info {
			display: flex;
			align-items: flex-start;
			margin-bottom: 16rpx;
			font-size: 28rpx;
			color: #646566;

			label {
				color: #969799;
				min-width: 100rpx;
			}

			text {
				flex: 1;
			}

			&.userInfo {
				.users {
					flex: 1;
					display: flex;
					flex-wrap: wrap;
					gap: 20rpx;

					.user {
						display: flex;
						flex-direction: column;
						align-items: center;
						position: relative;

						.del-icon {
							position: absolute;
							top: -8rpx;
							right: -8rpx;
							z-index: 10;
						}

						.photo {
							width: 100rpx;
							height: 100rpx;
							border-radius: 50%;
							overflow: hidden;
							border: 2rpx solid var(--main-color, #5AAB6E);

							image {
								width: 100%;
								height: 100%;
							}
						}

						.name {
							margin-top: 10rpx;
							font-size: 24rpx;
							color: #646566;
							max-width: 100rpx;
							overflow: hidden;
							text-overflow: ellipsis;
							white-space: nowrap;
						}
					}
				}
			}
		}

		.btns {
			display: flex;
			gap: 20rpx;
			margin-top: 30rpx;

			.btn {
				flex: 1;
				height: 72rpx;
				line-height: 72rpx;
				text-align: center;
				border-radius: 36rpx;
				font-size: 28rpx;
				border: none;

				&.share {
					background: #fff;
					border: 2rpx solid var(--main-color, #5AAB6E);
					color: var(--main-color, #5AAB6E);
				}

				&.chat-btn {
					background: #fff;
					border: 2rpx solid #1989fa;
					color: #1989fa;
				}

				&.bg-primary {
					background: var(--main-color, #5AAB6E);
					color: #fff;
				}

				&.exit {
					background: #ff976a;
					color: #fff;
				}

				&.full {
					background: #ebedf0;
					color: #c8c9cc;
				}

				&.pay {
					background: #ee0a24;
					color: #fff;
				}

				&.payed {
					background: #07c160;
					color: #fff;
				}
			}
		}
	}
}

.nodata-list {
	padding: 200rpx 0;
}

.containerlogin {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 200rpx 0;

	.photo {
		display: flex;
		flex-direction: column;
		align-items: center;

		.img {
			width: 200rpx;
			height: 200rpx;
			border-radius: 50%;
			overflow: hidden;
			margin-bottom: 30rpx;

			image {
				width: 100%;
				height: 100%;
			}
		}

		.name {
			font-size: 32rpx;
			color: #323233;
			margin-bottom: 60rpx;
		}
	}

	.loginBtn {
		width: 400rpx;
		height: 80rpx;
		line-height: 80rpx;
		border-radius: 40rpx;
		font-size: 32rpx;
		border: none;
	}
}

.bottom {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	height: 100rpx;
	line-height: 100rpx;
	text-align: center;
	color: #fff;
	font-size: 32rpx;
	font-weight: 600;
	z-index: 100;
	box-shadow: 0 -2rpx 12rpx rgba(0, 0, 0, 0.08);
}

.bg-primary {
	background: var(--main-color, #5AAB6E);
}

.filter-bar { display: flex; background: #fff; }
.filter-item { flex: 1; display: flex; align-items: center; justify-content: center; padding: 24rpx 0; font-size: 28rpx; color: #333; }
.filter-item .arrow { font-size: 20rpx; margin-left: 8rpx; color: #999; }
.city-item { color: var(--main-color, #5AAB6E); font-weight: bold; }
.city-icon { margin-right: 6rpx; font-size: 26rpx; }
.dropdown-mask { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.3); z-index: 999; }
.dropdown-list { background: #fff; max-height: 60vh; overflow-y: auto; }
.dropdown-item { padding: 24rpx 32rpx; font-size: 28rpx; color: #333; border-bottom: 1rpx solid #f5f5f5; }
.dropdown-item.active { color: var(--main-color, #5AAB6E); font-weight: bold; }
</style>
