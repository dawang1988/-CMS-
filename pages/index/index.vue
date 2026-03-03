<template>
	<view class="page" :style="themeStyle">
		<!-- 简洁模式 -->
		<view class="simple" v-if="simpleModel">
			<!-- 顶部Logo和快捷操作 -->
			<view class="logo-top" style="padding-top:80rpx">
				<view class="logo-name">
					<text class="name">{{ doorinfodata.store_name }}</text>
				</view>
				<view class="controller">
					<view class="item" @tap="openDoor">
						<image src="/static/icon/open.png" />
						<text>开门</text>
					</view>
					<view class="item" @tap="roomRenew">
						<image src="/static/icon/reorder.png" />
						<text>续单</text>
					</view>
					<view class="item" @tap="toShop">
						<image src="/static/icon/discount.png" />
						<text>商品</text>
					</view>
					<view class="item" @tap="gototuangou">
						<image src="/static/icon/coupon-check.png" />
						<text>团购</text>
					</view>
					<view class="item" @tap="onClickShow" data-index="1">
						<image src="/static/icon/service.png" />
						<text>客服</text>
					</view>
				</view>
			</view>
			
			<!-- 轮播图 -->
			<view class="banner">
				<swiper autoplay indicator-dots>
					<swiper-item v-for="(item, index) in bannerImg" :key="index">
						<image style="height: 100%;width: 100%;" class="banner" :src="item" mode="scaleToFill"></image>
					</swiper-item>
				</swiper>
			</view>
			
			<!-- 门店信息卡片 -->
			<store-info-card 
				:store="doorinfodata" 
				@change-store="goIndexPage" 
				@navigate="goTencentMap" 
				@guide="goGuide"
				@call="call" 
			/>
			
			<!-- 房间类型选择 -->
			<room-type-tabs :roomClass="roomClass" :tabIndex="tabIndex" @change="onTabChange" />
			
			<!-- 棋牌、KTV 房间列表 -->
			<room-list-card 
				v-if="tabIndex == 0 || tabIndex == 2" 
				:rooms="doorlistArr" 
				:timeHourAllArr="timeHourAllArr"
				@go-order="onGoOrder"
				@preview-image="imgYu"
				@show-reserve="onShowReserveFromChild"
			/>
			
			<!-- 台球房间列表 -->
			<pool-table-list v-if="tabIndex == 1" :rooms="doorlistArr" @go-order="onGoOrder" />
			
			<!-- 用户评价已移至门店详情页 -->
			
			<!-- KTV SVG座位图 -->
			<view v-if="tabIndex == 2 && doorinfodata.svg">
				<view class="color-mean">
					<view class="out-common">
						<view class="color-common" style="background-color: #e5e5e5;"></view>
						<view>禁用</view>
					</view>
					<view class="out-common">
						<view class="color-common" style="background-color: #7FDBFF;"></view>
						<view>待清洁</view>
					</view>
					<view class="out-common">
						<view class="color-common" style="background-color: #F0E68C;"></view>
						<view>已预约</view>
					</view>
					<view class="out-common">
						<view class="color-common" style="background-color: #DC143C;"></view>
						<view>使用中</view>
					</view>
					<view class="out-common">
						<view class="color-common" style="background-color: green;"></view>
						<view>选中</view>
					</view>
				</view>
				
				<view class="svg-style">
					<movable-area :style="{ width: '100%', height: (svgHeight + 50) + 'px' }">
						<movable-view 
							:style="{ width: svgWidth + 'px', height: svgHeight + 'px' }" 
							:scale="true" :scale-min="0.5" :x="40" :y="25" :scale-max="2" direction="all"
						>
							<image :src="doorinfodata.svg" mode="scaleToFill" @load="imageLoad" 
								:style="{ width: svgWidth + 'px', height: svgHeight + 'px', position: 'absolute' }" />
							<view style="position: absolute; z-index: 1;">
								<view v-for="(item, index) in doorlistArr" :key="index" 
									:style="{ position: 'absolute', left: item.svgX + 'px', top: item.svgY + 'px' }" 
									@tap="handleClick" :data-item="item">
									<view :style="{ width: '30px', height: '30px', border: '1rpx solid #5a5a5a60', borderRadius: '8px', zIndex: 10000, backgroundColor: item.fill }"></view>
									<text :style="{ position: 'absolute', left: '9px', top: '8px', fontSize: '12px' }">{{ item.name }}</text>
								</view>
							</view>
						</movable-view>
					</movable-area>
				</view>
				
				<view class="fixed-button-wrapper">
					<button size="mini" class="to-comfrim" @tap="goStudy">确认选座</button>
				</view>
			</view>
			
			<!-- 温馨提示 -->
			<view class="notes" v-if="tabIndex != 2">
				<image src="/static/icon/more.png" />
				到底了，没有更多啦~
			</view>
		</view>
		
		<!-- custom 模板 -->
		<view class="custom" v-else-if="templateKey == 'custom'">
			<view class="banner">
				<swiper autoplay indicator-dots>
					<swiper-item v-for="(item, index) in bannerImg" :key="index">
						<image :src="item" mode="scaleToFill"></image>
					</swiper-item>
				</swiper>
			</view>
			<view class="intro">
				<view class="address" @tap="goTencentMap">
					<iconfont name="weibiaoti-3" size="15"></iconfont>
					<view class="text">{{ doorinfodata.address }}</view>
				</view>
			</view>
			<view class="toolbar">
				<view class="line1">
					<view class="yuyue" @tap="goYuyue" :style="{ backgroundImage: 'url(' + doorinfodata.btn_img + ')' }"></view>
					<view class="right-box">
						<view class="storename sub-item" @tap="goIndexPage" :style="{ backgroundImage: 'url(' + doorinfodata.qh_img + ')' }"></view>
						<view class="group-buying sub-item" @tap="gototuangou" :style="{ backgroundImage: 'url(' + doorinfodata.tg_img + ')' }"></view>
					</view>
				</view>
				<view class="line2">
					<view class="slim sub-item margin-t-26" @tap="toShop" :style="{ backgroundImage: 'url(' + doorinfodata.cz_img + ')' }"></view>
					<view class="sub-item margin-t-26 slim" @tap="openDoor" :style="{ backgroundImage: 'url(' + doorinfodata.open_img + ')' }"></view>
					<view class="sub-item margin-t-26 slim" @tap="showWifi" :style="{ backgroundImage: 'url(' + doorinfodata.wifi_img + ')' }"></view>
					<view class="sub-item margin-t-26 slim" @tap="call" :style="{ backgroundImage: 'url(' + doorinfodata.kf_img + ')' }"></view>
				</view>
			</view>
			<view class="section" v-if="storeEnvImg.length > 0">
				<view class="title">门店介绍</view>
				<view class="content">
					<image v-for="(item, index) in storeEnvImg" :key="index" :src="item" mode="widthFix"></image>
				</view>
			</view>
		</view>
		
		<!-- taiqiu-tiezi 模板 -->
		<view class="tiezi" v-else-if="templateKey == 'taiqiu-tiezi'">
			<view class="banner">
				<swiper autoplay indicator-dots>
					<swiper-item v-for="(item, index) in bannerImg" :key="index">
						<image :src="item" mode="scaleToFill"></image>
					</swiper-item>
				</swiper>
			</view>
			<view class="content">
				<view class="store">
					<view class="top">
						<view class="info">
							<iconfont name="iov-store" size="26" color="#fff"></iconfont>
							<text class="name"> {{ doorinfodata.store_name }}</text>
						</view>
						<view class="distant" @tap="goTencentMap">
							<iconfont name="weibiaoti-3" color="#fff" size="15"></iconfont>
							<text style="color: #fff;">{{ doorinfodata.distance }}km</text>
						</view>
					</view>
					<view class="bottom">
						<text class="location">{{ doorinfodata.address }}</text>
					</view>
					<view class="btns">
						<view class="btn" @tap="goTencentMap">
							<iconfont name="daohang1" color="rgb(168, 168, 168)" size="15"></iconfont>
							门店导航
						</view>
						<view class="btn" @tap="goGuide">
							<iconfont name="daohang1" color="rgb(168, 168, 168)" size="15"></iconfont>
							位置指引
						</view>
						<view class="btn" @tap="call">
							<iconfont name="dianhua" color="rgb(168, 168, 168)" size="15"></iconfont>
							联系客服
						</view>
					</view>
				</view>
				<view class="info" v-if="doorinfodata.imgList && doorinfodata.imgList.length >= 6">
					<view class="top">
						<view class="left">
							<view class="btn1" :style="{ backgroundImage: 'url(' + doorinfodata.imgList[0] + ')' }" @tap="goIndexPage"></view>
						</view>
						<view class="right">
							<view class="btn2" :style="{ backgroundImage: 'url(' + doorinfodata.imgList[1] + ')' }" @tap="scanQr"></view>
							<view class="btn3" :style="{ backgroundImage: 'url(' + doorinfodata.imgList[2] + ')' }" @tap="gototuangou"></view>
						</view>
					</view>
					<view class="buttom">
						<view class="btn4" :style="{ backgroundImage: 'url(' + doorinfodata.imgList[3] + ')' }" @tap="goVideo"></view>
						<view class="btn5" :style="{ backgroundImage: 'url(' + doorinfodata.imgList[4] + ')' }" @tap="openDoor"></view>
					</view>
					<view class="shop">
						<view class="btn6" :style="{ backgroundImage: 'url(' + doorinfodata.imgList[5] + ')' }" @tap="toShop"></view>
					</view>
				</view>
				
				<room-type-tabs :roomClass="roomClass" :tabIndex="tabIndex" @change="onTabChange" />
				
				<room-list-card 
					v-if="tabIndex == 0 || tabIndex == 2" 
					:rooms="doorlistArr" 
					:timeHourAllArr="timeHourAllArr"
					@go-order="onGoOrder"
					@preview-image="imgYu"
					@show-reserve="onShowReserveFromChild"
				/>
				
				<pool-table-list v-if="tabIndex == 1" :rooms="doorlistArr" @go-order="onGoOrder" />
			</view>
		</view>
		
		<!-- 门店公告弹窗 -->
		<notice-popup 
			:show="noticeShow" 
			:notice="doorinfodata.notice" 
			:store-id="doorinfodata.id"
			frequency="once-per-day"
			@close="noticeShow = false"
		/>
		
		<!-- 预约时间列表弹窗 -->
		<u-popup :show="showReserve" mode="bottom" :round="20" :closeable="true" @close="showReserve = false">
			<view class="reserve-popup">
				<view class="reserve-title">预约时间</view>
				<view class="reserve-list">
					<view class="reserve-item" v-for="(item, index) in orderTimeList" :key="index">
						<text class="reserve-time">{{ item }}</text>
					</view>
					<view v-if="!orderTimeList || orderTimeList.length === 0" class="reserve-empty">暂无预约</view>
				</view>
			</view>
		</u-popup>
	</view>
</template>


<script>
import http from '@/utils/http.js'
import magicMixin from '@/utils/magicMixin.js'
import Iconfont from '@/components/iconfont/iconfont.vue'
import StoreInfoCard from '@/components/store-info-card/store-info-card.vue'
import RoomTypeTabs from '@/components/room-type-tabs/room-type-tabs.vue'
import RoomListCard from '@/components/room-list-card/room-list-card.vue'
import PoolTableList from '@/components/pool-table-list/pool-table-list.vue'
import StoreReviews from '@/components/store-reviews/store-reviews.vue'
import NoticePopup from '@/components/notice-popup/notice-popup.vue'

export default {
	components: {
		Iconfont,
		StoreInfoCard,
		NoticePopup,
		RoomTypeTabs,
		RoomListCard,
		PoolTableList,
		StoreReviews
	},
	data() {
		return {
			store_id: '',
			storeEnvImg: [],
			bannerImg: [],
			doorinfodata: { storeName: '', imgList: [] },
			roomClass: [],
			timeselectindex: 0,
			timeDayArr: [],
			timeWeekArr: [],
			doorlistArr: [],
			timeHourArr: [],
			timeHourAllArr: [],
			simpleModel: true,
			templateKey: '',
			tabIndex: 0,
			lat: '',
			lon: '',
			showReserve: false,
			orderTimeList: [],
			selectedRoomId: null,
			svgWidth: 0,
			svgHeight: 0,
			noticeShow: false  // 公告弹窗显示状态
		}
	},
	
	onLoad(options) {
		let storeId = options.store_id || ''
		
		// 处理小程序码扫码进入的 scene 参数
		// scene 格式: sid=4 或 sid=4&rid=5
		if (options.scene) {
			const scene = decodeURIComponent(options.scene)
			const params = {}
			scene.split('&').forEach(item => {
				const [key, val] = item.split('=')
				if (key && val) params[key] = val
			})
			if (params.sid) {
				storeId = params.sid
			}
		}
		
		if (storeId) {
			this.store_id = storeId
			uni.setStorageSync('global_store_id', storeId)
		}
		this.loadingtime()
	},
	
	onShow() {
		this.roomClass = []
		this.tabIndex = ''
		
		const storeId_1 = uni.getStorageSync('global_store_id')
		if (storeId_1) {
			this.store_id = storeId_1
		} else {
			uni.switchTab({ url: '/pages/door/index' })
			return
		}
		
		uni.setStorageSync('global_store_id', this.store_id)
		
		this.getLocation().then(() => {
			this.getStoreInfodata()
			this.getDoorListdata()
		}).catch(() => {
			this.getStoreInfodata()
			this.getDoorListdata()
		})
	},
	
	methods: {
		splitString(str) {
			return magicMixin.split(str)
		},
		
		loadingtime() {
			const date = new Date()
			const atimestring1 = `${date.getFullYear()}-${date.getMonth() + 1}-${date.getDate()}`
			const atimelist = this.getDates(5, atimestring1)
			this.timeDayArr = atimelist.map(d => d.month + '.' + d.day)
			this.timeWeekArr = atimelist.map(d => d.week)
		},
		
		getDates(days, todate) {
			const dateArry = []
			for (let i = 0; i < days; i++) {
				dateArry.push(this.dateLater(todate, i))
			}
			return dateArry
		},
		
		dateLater(dates, later) {
			const show_day = ['周日', '周一', '周二', '周三', '周四', '周五', '周六']
			const date = new Date(dates)
			date.setDate(date.getDate() + later)
			const pad = n => n < 10 ? '0' + n : '' + n
			return {
				year: date.getFullYear(),
				month: pad(date.getMonth() + 1),
				day: pad(date.getDate()),
				week: show_day[date.getDay()]
			}
		},
		
		getLocation() {
			return new Promise((resolve, reject) => {
				uni.getLocation({
					type: 'gcj02',
					success: res => { this.lat = res.latitude; this.lon = res.longitude; resolve() },
					fail: () => reject()
				})
			})
		},
		
		getStoreInfodata() {
			if (!this.store_id) {
				uni.switchTab({ url: '/pages/door/index' })
				return
			}
			
			http.get(`/member/index/getStoreInfo/${this.store_id}`, {
				lat: this.lat, lon: this.lon
			}).then(res => {
				if (res.code === 0 && res.data) {
					const data = res.data
					this.doorinfodata = {
						...data,
						store_id: data.id,
						store_name: data.store_name || data.name
					}
					
					this.simpleModel = data.simple_model !== false && data.simple_model != 0
					this.templateKey = data.template_key || ''
					
					// 应用主题色
					this.applyThemeColor(data.template_key)
					
					// 门店环境图片
					if (data.env_images && data.env_images.length > 0) {
						this.storeEnvImg = typeof data.env_images === 'string' 
							? data.env_images.split(',') 
							: Array.isArray(data.env_images) ? data.env_images : []
					}
					
					// Banner图片
					const bannerSrc = data.banner_img || data.head_img
					if (bannerSrc && bannerSrc.length > 0) {
						this.bannerImg = typeof bannerSrc === 'string' 
							? bannerSrc.split(',') 
							: Array.isArray(bannerSrc) ? bannerSrc : []
					} else if (data.images) {
						if (typeof data.images === 'object') {
							this.bannerImg = Object.values(data.images)
						} else if (typeof data.images === 'string' && data.images.length > 0) {
							this.bannerImg = data.images.split(',')
						}
					}
					
					// 房间类别
					if (data.room_class_list && data.room_class_list.length > 0) {
						const classMap = { 0: '棋牌', 1: '台球', 2: 'KTV' }
						this.roomClass = data.room_class_list
							.filter(e => classMap[e] !== undefined)
							.map(e => ({ text: classMap[e], value: e }))
						
						if (this.tabIndex === '' && this.roomClass.length > 0) {
							this.tabIndex = this.roomClass[0].value
						}
					}
					
					// 如果有公告内容，显示公告弹窗
					if (data.notice && data.notice.trim()) {
						this.noticeShow = true
					}
				} else {
					uni.showModal({
						content: res.msg || '获取门店信息失败',
						showCancel: false,
						success: () => uni.switchTab({ url: '/pages/door/index' })
					})
				}
			}).catch(() => {})
		},
		
		getDoorListdata() {
			if (!this.store_id) return
			
			http.post('/member/index/getRoomInfoList', {
				store_id: this.store_id,
				room_class: this.tabIndex
			}).then(res => {
				if (res.code === 0) {
					this.doorlistArr = res.data.map(el => {
						// 优先使用后端返回的 timeText（从订单数据计算），没有时才自己算
						if (!el.timeText) {
							el.timeText = this.timeFilter(el.start_time, el.end_time)
						}
						if (el.order_time_list) {
							el.order_time_list = el.order_time_list.map(item => {
								if (typeof item === 'string') return item
								return this.timeFilter(item.start_time, item.end_time)
							})
						}
						if (el.end_time) {
							const diffMs = new Date(el.end_time) - new Date()
							const diffMin = Math.floor(diffMs / 60000)
							const h = Math.floor(diffMin / 60), m = diffMin % 60
							el.waitTime = {
								hours: h < 10 ? `0${h}` : `${h}`,
								minutes: m < 10 ? `0${m}` : `${m}`
							}
						}
						if (el.room_class == 2) {
							const fillMap = { 0: '#e5e5e5', 1: '#fff', 2: '#7FDBFF', 3: '#DC143C', 4: '#F0E68C' }
							el.fill = fillMap[el.status] || '#fff'
						}
						return el
					})
					this.setroomlistHour(0)
				} else {
					uni.showModal({ content: res.msg || '获取房间列表失败', showCancel: false })
				}
			}).catch(() => {})
		},
		
		setroomlistHour(aindex) {
			this.timeHourAllArr = this.doorlistArr.map(room => {
				if (room.time_slot && Array.isArray(room.time_slot)) {
					return room.time_slot.slice(aindex * 24, aindex * 24 + 24)
				}
				return Array.from({ length: 24 }, (_, j) => ({
					hour: j < 10 ? '0' + j : '' + j,
					disable: false
				}))
			})
		},
		
		timeFilter(startTime, endTime) {
			if (startTime && !endTime) return this.formatTime(startTime, 'MM月DD日HH:mm')
			if (startTime && endTime) return `${this.formatTime(startTime, 'MM月DD日HH:mm')}-${this.formatTime(endTime, 'HH:mm')}`
			return ''
		},
		
		formatTime(time, format) {
			const d = new Date(time)
			const pad = n => n < 10 ? '0' + n : n
			return format
				.replace('YYYY', d.getFullYear())
				.replace('MM', pad(d.getMonth() + 1))
				.replace('DD', pad(d.getDate()))
				.replace('HH', pad(d.getHours()))
				.replace('mm', pad(d.getMinutes()))
		},
		
		// 子组件事件适配
		onTabChange(index) {
			this.tabIndex = Number(index)
			this.doorlistArr = []
			this.getDoorListdata()
		},
		
		onGoOrder(payload) {
			const { status, index, id } = payload
			if (status == 0 || status == 3) return  // 禁用或维护中
			const storeId = this.store_id
			const room = this.doorlistArr[index]
			
			const doNavigate = () => {
				uni.navigateTo({
					url: `/pages/order/submit?roomId=${id}&goPage=1&storeId=${storeId}&timeselectindex=${this.timeselectindex}`
				})
			}
			
			// 状态2：使用中 - 直接进入预约页面，用户可以预约其他时段
			if (status == 2) {
				doNavigate()
				return
			}
			
			// 状态4：判断是待清洁还是已预约
			if (status == 4) {
				if (room && room.is_cleaning) {
					// 待清洁状态
					if (this.doorinfodata.clear_open) {
						uni.showModal({
							title: '提示',
							content: '您选择的此场地暂未清洁，介意请勿预订！',
							confirmText: '继续预定',
							success: res => { if (res.confirm) doNavigate() }
						})
					} else {
						uni.showModal({ title: '提示', content: '房间暂未清洁，禁止预订！', showCancel: false })
					}
				} else {
					// 已预约状态 - 允许用户预约其他时间段
					uni.showModal({
						title: '提示',
						content: '该房间部分时段已被预约，您可以选择其他空闲时段预订',
						confirmText: '查看详情',
						success: res => { if (res.confirm) doNavigate() }
					})
				}
			} else {
				doNavigate()
			}
		},
		
		onShowReserveFromChild(list) {
			this.orderTimeList = list
			this.showReserve = true
		},
		
		// 原有事件处理（保持不变）
		tabChange(e) {
			this.onTabChange(e.currentTarget.dataset.index)
		},
		
		goOrder(e) {
			this.onGoOrder({
				status: e.currentTarget.dataset.status,
				index: e.currentTarget.dataset.index,
				id: e.currentTarget.dataset.info
			})
		},
		
		handleClick(e) {
			const room = e.currentTarget.dataset.item
			if (room.status === 0 || room.status === 3) {
				uni.showToast({ title: '该房间不可使用', icon: 'none' })
				return
			}
			// 状态2（使用中）或状态4（已预约）都允许进入预约页面选择其他时段
			// 状态4：判断是待清洁还是已预约
			if (room.status === 4 && !room.is_cleaning) {
				// 已预约状态 - 允许用户预约其他时间段
				uni.showModal({
					title: '提示',
					content: '该房间部分时段已被预约，您可以选择其他空闲时段预订',
					confirmText: '查看详情',
					success: res => {
						if (res.confirm) {
							uni.navigateTo({
								url: `/pages/order/submit?roomId=${room.id}&goPage=1&storeId=${this.store_id}&timeselectindex=${this.timeselectindex}`
							})
						}
					}
				})
				return
			}
			// 空闲(1)或待清洁(4且is_cleaning=true)可以选择
			if (room.status === 1 || (room.status === 4 && room.is_cleaning)) {
				this.doorlistArr = this.doorlistArr.map(item => {
					if (this.selectedRoomId && this.selectedRoomId !== room.id && item.id === this.selectedRoomId) {
						return { ...item, fill: item.status === 1 ? '#fff' : '#7FDBFF' }
					}
					if (item.id === room.id) return { ...item, fill: 'green' }
					return item
				})
				this.selectedRoomId = room.id
			} else {
				uni.showToast({ title: '房间不可用', icon: 'none' })
			}
		},
		
		goStudy() {
			if (!this.selectedRoomId) {
				uni.showToast({ title: '请先选择座位' })
				return
			}
			const selectedRoom = this.doorlistArr.find(item => item.id === this.selectedRoomId)
			let atime = this.timeselectindex >= 0 ? this.timeDayArr[this.timeselectindex] : ''
			const storeId = this.store_id
			
			const doNav = () => {
				uni.navigateTo({
					url: `/pages/order/submit?roomId=${selectedRoom.id}&daytime=${atime}&storeId=${storeId}&timeselectindex=${this.timeselectindex}`
				})
			}
			
			// 状态4且is_cleaning=true：待清洁
			if (selectedRoom.status == 4 && selectedRoom.is_cleaning) {
				if (this.doorinfodata.clear_open) {
					uni.showModal({
						title: '提示', content: '您选择的此场地暂未清洁，介意请勿预订！', confirmText: '继续预定',
						success: res => { if (res.confirm) doNav() }
					})
				} else {
					uni.showModal({ title: '提示', content: '房间暂未清洁，禁止预订！', showCancel: false })
				}
			} else {
				doNav()
			}
		},
		
		imageLoad(e) { this.svgWidth = e.detail.width; this.svgHeight = e.detail.height },
		goIndexPage() { uni.switchTab({ url: '/pages/door/index' }) },
		
		goTencentMap() {
			const s = this.doorinfodata
			uni.openLocation({ latitude: s.latitude, longitude: s.longitude, name: s.store_name, address: s.address, scale: 28 })
		},
		
		goGuide() {
			uni.navigateTo({
				url: `/pagesA/guide/index?storeId=${this.store_id}`
			})
		},
		
		openDoor() {
			http.get('/member/order/getOrderInfo').then(res => {
				if (res.code === 0 && res.data) {
					if (res.data.status == 0 && new Date(res.data.start_time) > Date.now()) {
						uni.showModal({
							title: '温馨提示', content: '当前还未到预约时间，是否提前开始消费？',
							success: r => { if (r.confirm) this.openRoomDoor(res.data) }
						})
					} else {
						this.openRoomDoor(res.data)
					}
				} else {
					uni.showModal({ title: '温馨提示', content: '当前无有效订单，请先下单！', showCancel: false })
				}
			}).catch(() => {})
		},
		
		openRoomDoor(data) {
			http.post(`/member/order/openRoomDoor?orderKey=${data.orderKey}`).then(res => {
				if (res.code == 0) {
					uni.showToast({ title: '操作成功', icon: 'success' })
				} else {
					uni.showModal({ title: '提示', content: res.msg || '操作失败', showCancel: false })
				}
			}).catch(() => {})
		},
		
		roomRenew() {
			http.get('/member/order/getOrderInfo').then(res => {
				if (res.code === 0 && res.data) {
					uni.navigateTo({ url: `/pages/room/renew?storeId=${res.data.store_id}&roomId=${res.data.room_id}` })
				} else {
					uni.showToast({ title: res.msg || '当前无进行中的订单', icon: 'none' })
				}
			}).catch(err => {
				uni.showToast({ title: (err && err.msg) || '当前无进行中的订单', icon: 'none' })
			})
		},
		
		toShop() { uni.navigateTo({ url: `/pages/shop/index?storeId=${this.doorinfodata.store_id}` }) },
		gototuangou() { uni.navigateTo({ url: `/pages/tuangou/index?storeId=${this.doorinfodata.store_id}` }) },
		goYuyue() { uni.navigateTo({ url: `/pages/booking/index?storeId=${this.store_id}` }) },
		showWifi() {
			const wifiName = this.doorinfodata.wifi_name || ''
			const wifiPwd = this.doorinfodata.wifi_password || ''
			if (!wifiName && !wifiPwd) {
				uni.showToast({ title: '该门店暂未配置WiFi信息', icon: 'none' })
				return
			}
			uni.showModal({
				title: 'WiFi信息',
				content: `名称：${wifiName}\n密码：${wifiPwd}`,
				confirmText: '复制密码',
				success: (res) => {
					if (res.confirm && wifiPwd) {
						uni.setClipboardData({
							data: wifiPwd,
							success: () => uni.showToast({ title: '密码已复制' })
						})
					}
				}
			})
		},
		
		call() {
			const phone = this.doorinfodata.phone
			if (!phone) return
			if (phone.length == 11) {
				uni.makePhoneCall({ phoneNumber: phone })
			} else {
				uni.showModal({
					title: '提示',
					content: `客服上班时间10：00~23：00\r\n如您遇到问题，建议先查看"使用帮助"！\r\n本店客服微信号：${phone}`,
					confirmText: '复制',
					success: res => {
						if (res.confirm) {
							uni.setClipboardData({ data: phone, success: () => uni.showToast({ title: '微信号已复制到剪贴板！' }) })
						}
					}
				})
			}
		},
		
		onClickShow(e) {
			const index = e.currentTarget.dataset.index
			if (index == 0) this.goTencentMap()
			else if (index == 1) this.call()
		},
		
		imgYu(indexOrEvent) {
			const aindex = typeof indexOrEvent === 'number' ? indexOrEvent : indexOrEvent.currentTarget.dataset.index
			const room = this.doorlistArr[aindex]
			let imgs = []
			// 优先用 imageUrls（逗号分隔的完整URL）
			if (room.imageUrls) {
				imgs = room.imageUrls.split(',').filter(i => i)
			} else if (room.images) {
				try {
					const parsed = typeof room.images === 'string' ? JSON.parse(room.images) : room.images
					if (Array.isArray(parsed)) imgs = parsed.filter(i => i)
				} catch(e) {
					imgs = room.images.split(',').filter(i => i)
				}
			}
			if (imgs.length > 0) {
				uni.previewImage({ current: imgs[0], urls: imgs })
			} else {
				uni.showToast({ title: '该房间暂无图片介绍', icon: 'none' })
			}
		},
		
		onShowReserve(e) {
			this.orderTimeList = e.currentTarget.dataset.list
			this.showReserve = true
		},
		
		scanQr() {
			uni.scanCode({
				success: res => { if (res.path) uni.navigateTo({ url: '/' + res.path }) },
				fail: () => uni.showToast({ title: '扫码失败', icon: 'none', duration: 1000 })
			})
		},
		
		goVideo() { uni.showToast({ title: '功能暂未开放', icon: 'none' }) }
	}
}
</script>


<style lang="scss">
page { height: 100%; width: 100%; background-color: #f3f2f2; }
.page { width: 100%; min-height: 100vh; background-color: #f3f2f2; }

/* 顶部Logo区域 */
.logo-top { background-color: var(--main-color, #5AAB6E); z-index: 1; }
.logo-top .name { color: #FFFFFF; font-weight: 600; font-size: 40rpx; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* 简洁模式控制栏 */
.simple .controller { background: var(--main-color, #5AAB6E); height: 200rpx; display: flex; justify-content: space-around; align-items: center; font-size: 26rpx; color: #fff; }
.simple .controller .item { display: flex; flex-direction: column; align-items: center; width: 20%; height: 130rpx; justify-content: space-around; }
.simple .controller .item:active { opacity: 0.8; }
.simple .controller .item image { width: 68rpx; height: 68rpx; }

/* 门店信息卡片 - 样式由 store-info-card 组件提供 */

/* Banner */
.simple .banner swiper { width: 100%; height: 294rpx; }
.simple .banner image { width: 100%; height: 100%; }

/* 房间类型选择 - 样式由 room-type-tabs 组件提供 */

/* 房间列表 - 样式由 room-list-card 组件提供 */

/* 底部提示 */
.notes { font-weight: 500; font-size: 21rpx; color: var(--main-color, #5AAB6E); text-align: center; display: flex; justify-content: center; align-items: center; margin-top: 20rpx; padding-bottom: 30rpx; }
.notes image { width: 30rpx; height: 30rpx; margin-right: 12rpx; }

/* 台球样式 */
.tab-container { display: flex; flex-wrap: wrap; }
.tab-item { width: 44%; display: flex; flex-direction: column; margin: auto 20rpx; }
.tab-left { width: 100%; height: 226rpx; border-radius: 10rpx; padding: 0; box-sizing: border-box; }
.tab-left-top { display: flex; align-items: center; justify-content: center; }
.tab-img-box-begin { width: 100%; height: 200rpx; border-radius: 10rpx; overflow: hidden; display: flex; flex-direction: column; color: #fff; background-color: var(--main-color, #5AAB6E); }
.tab-img-box-wait { width: 100%; height: 200rpx; border-radius: 10rpx; overflow: hidden; display: flex; flex-direction: column; background-color: #f5f5f5; }
.tab-info { width: 100%; height: 80rpx; margin-top: 30rpx; justify-content: center; text-align: center; }
.tab-info .price { font-size: 26rpx; }
.tab-wait { width: 100%; height: fit-content; margin-top: 30rpx; justify-content: center; text-align: center; }
.tab-button { width: 100%; justify-content: center; text-align: center; margin-top: 10rpx; }
.tab-wait-time { margin-top: 10rpx; justify-content: center; text-align: center; display: flex; }
.tab-roomName { color: #fff; font-size: 34rpx; }
.tab-wait-roomName { color: var(--main-color, #5AAB6E); font-size: 34rpx; }
.begin-button { width: 150rpx; height: 50rpx; border-radius: 40rpx; background-color: rgb(2, 185, 97); color: #fff; font-size: 30rpx; font-weight: bold; line-height: 50rpx; }
.time-number { color: #fff; background-color: rgb(245, 158, 61); width: 46rpx; border-radius: 8rpx; }
.time-date { color: rgb(245, 158, 61); }
.time-disable { color: red; }

/* tiezi模板 */
.tiezi { background-color: #20211c !important; }
.tiezi .banner swiper { width: 100%; height: 600rpx; overflow: hidden; }
.tiezi .banner image { width: 100%; height: 100%; }
.tiezi .content { text-align: center; align-items: center; position: relative; top: -80rpx; }
.tiezi .store { width: 93%; height: 205rpx; margin: 0rpx auto; background-color: #35362e8f; border-radius: 15rpx; }
.tiezi .store .top { display: flex; justify-content: space-between; }
.tiezi .store .top .info { display: flex; justify-content: space-between; padding: 15rpx 20rpx; font-size: 34rpx; color: #fff; font-weight: bold; }
.tiezi .store .top .info .name { margin-left: 10rpx; }
.tiezi .store .top .distant { display: flex; justify-content: space-between; align-items: center; width: fit-content; height: 40rpx; background: #35362e8f; border-radius: 9rpx; border: 1rpx solid #fff; font-size: 22rpx; padding: 0rpx 10rpx; margin: 15rpx 20rpx; }
.tiezi .store .bottom { padding: 0rpx 20rpx; }
.tiezi .store .bottom .location { font-size: 24rpx; color: #a8a8a8; }
.tiezi .store .btns { display: flex; justify-content: space-around; margin-top: 20rpx; }
.tiezi .store .btns .btn { display: flex; align-items: center; font-size: 24rpx; color: #a8a8a8; padding: 10rpx 30rpx; background: #35362e; border-radius: 30rpx; }
.tiezi .info { margin-top: 20rpx; }
.tiezi .info .top { display: flex; justify-content: space-between; padding: 0 20rpx; }
.tiezi .info .top .left { width: 48%; }
.tiezi .info .top .left .btn1 { width: 100%; height: 300rpx; background-size: cover; background-repeat: no-repeat; border-radius: 10rpx; }
.tiezi .info .top .right { width: 48%; display: flex; flex-direction: column; justify-content: space-between; }
.tiezi .info .top .right .btn2, .tiezi .info .top .right .btn3 { width: 100%; height: 145rpx; background-size: cover; background-repeat: no-repeat; border-radius: 10rpx; }
.tiezi .info .buttom { display: flex; justify-content: space-between; padding: 20rpx 20rpx 0; }
.tiezi .info .buttom .btn4, .tiezi .info .buttom .btn5 { width: 48%; height: 145rpx; background-size: cover; background-repeat: no-repeat; border-radius: 10rpx; }
.tiezi .info .shop { padding: 20rpx; }
.tiezi .info .shop .btn6 { width: 100%; height: 145rpx; background-size: cover; background-repeat: no-repeat; border-radius: 10rpx; }
.tiezi .top-tabs-container { padding: 20rpx; background-color: #20211c; display: flex; justify-content: space-between; }
.tiezi .top-tabs { width: 49.6%; height: 80rpx; font-size: 32rpx; line-height: 60rpx; text-align: center; display: flex; align-items: center; background-color: #35362e; border-radius: 10rpx; padding: 10rpx; margin: 0rpx 10rpx; color: #fff; }
.tiezi .top-tabs.active { color: var(--main-color, #5AAB6E); font-weight: bold; background-image: linear-gradient(181.2deg, #50504f, #20211c 150.8%); }
.tiezi .lists .item { background: #35362e; }
.tiezi .lists .item .top .right .name { color: #fff; }
.tiezi .lists .item .top .right .tags .tag { border-color: var(--main-color, #5AAB6E); color: var(--main-color, #5AAB6E); }

/* custom模板 */
.custom .banner swiper { width: 100%; height: 600rpx; overflow: hidden; }
.custom .banner image { width: 100%; height: 100%; }
.custom .intro { border-radius: 10rpx; display: flex; justify-content: space-between; align-items: center; height: 46rpx; width: 96%; margin-left: 2%; }
.custom .intro .address { display: flex; justify-content: space-between; align-items: center; }
.custom .intro .address .text { font-size: 22rpx; color: #666; line-height: 30rpx; margin-left: 10rpx; word-break: break-all; text-overflow: ellipsis; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 1; overflow: hidden; }
.custom .toolbar { height: 666rpx; width: 750rpx; }
.custom .toolbar .line1 { display: flex; justify-content: space-between; padding: 0 2%; }
.custom .toolbar .line1 .right-box { display: flex; flex-direction: column; justify-content: space-between; }
.custom .toolbar .sub-item { width: 345rpx; height: 193rpx; background-size: cover; background-repeat: no-repeat; border-radius: 10rpx; }
.custom .toolbar .sub-item.slim { width: 345rpx; height: 145rpx; }
.custom .toolbar .line1 .yuyue { width: 345rpx; height: 410rpx; background-size: cover; background-repeat: no-repeat; border-radius: 10rpx; }
.custom .toolbar .line2 { display: flex; justify-content: space-between; flex-wrap: wrap; padding: 0 2% 30rpx 2%; }
.custom .margin-t-26 { margin-top: 26rpx; }
.custom .section .title { margin-left: 5%; margin-top: 100rpx; margin-bottom: 20rpx; border-width: 0; border-left-width: 10rpx; border-style: solid; padding-left: 10rpx; line-height: 1; font-weight: 700; font-size: 36rpx; }
.custom .section .content image { width: 100%; overflow: hidden; }

/* KTV座位图 */
.svg-style { background-color: #fff; border: 0.5px solid black; }
.color-mean { display: flex; background-color: #fff; justify-content: space-between; }
.out-common { display: flex; align-items: center; margin: auto; }
.color-common { height: 20px; width: 20px; margin: 5px; border-radius: 4px; }
.fixed-button-wrapper { position: fixed; bottom: 10px; left: 50%; transform: translateX(-50%); z-index: 10; }
.to-comfrim { background-color: #fa9b01; color: #fff; height: 30px; }

/* 预约时间弹窗 */
.reserve-popup { padding: 30rpx; }
.reserve-title { font-size: 32rpx; font-weight: 600; text-align: center; margin-bottom: 30rpx; }
.reserve-list { max-height: 60vh; overflow-y: auto; }
.reserve-item { padding: 20rpx 24rpx; margin-bottom: 16rpx; background: #f5f5f5; border-radius: 12rpx; display: flex; align-items: center; }
.reserve-item::before { content: "🕐"; margin-right: 16rpx; }
.reserve-time { font-size: 28rpx; color: #333; }
.reserve-empty { text-align: center; color: #999; padding: 40rpx 0; font-size: 28rpx; }
</style>
