<template>
	<view class="page-root" :style="themeStyle">
	<!-- 预约页面 - 简洁模式式?-->
	<view class="containerInfo">
		<scroll-view scroll-y class="scroll-view">
			<view class="simple">
				<!-- 门店信息卡片 -->
				<view class="info-card">
					<view class="top">
						<view class="left">
							<image src="/static/icon/stoer.png" style="width:36rpx;height:36rpx;" />
							<view class="info">
								<text class="name">{{doorinfodata.store_name}}</text>
							</view>
						</view>
						<view class="change-btn" @tap="goIndexPage">
							<text>切换门店</text>
						</view>
					</view>
					<view class="bottom">
						<text class="location">{{doorinfodata.address}}</text>
						<view class="item" @tap="onClickShow" data-index="0">
							<image src="/static/icon/navigation.png" />
							<text>导航</text>
						</view>
						<view class="line"></view>
						<view class="item" @tap="onClickShow" data-index="1">
							<image src="/static/icon/phone.png" />
							<text>电话</text>
						</view>
					</view>
					<view class="distant">
						<view @tap="goTencentMap" class="color-primary"></view>
						<text style="color: var(--main-color);" @tap="goTencentMap">距我{{doorinfodata.distance}}km</text>
					</view>
				</view>
				
				<!-- 房间分类Tab -->
				<view class="top-tabs-container" v-if="roomClass && roomClass.length > 1">
					<view class="top-tabs" :class="{ active: tabIndex === item.value }" v-for="item in roomClass" :key="item.value" :data-index="item.value" @tap="tabChange">
						<text v-if="item.value == 0" style="font-size:35rpx;">🀄</text>
						<text v-if="item.value == 1" style="font-size:35rpx;">🎱</text>
						<text v-if="item.value == 2" style="font-size:35rpx;">🎤</text>
						<view class="tab" :data-index="item.value" @tap="tabChange">{{item.text}}</view>
					</view>
				</view>
				
				<!-- 棋牌、KTV 房间列表 -->
				<view class="lists" v-if="tabIndex == 0 || tabIndex == 2" v-for="(item, index) in doorlistArr" :key="item.id" id="roomList">
					<view class="item">
						<view class="top">
							<view class="left">
								<image class="img" v-if="item.imageUrls && item.imageUrls.length > 0" :src="item.imageUrls.split(',')[0]" @tap="imgYu" :data-index="index" mode="scaleToFill"></image>
								<image class="img" v-else src="/static/logo.png" mode="aspectFit"></image>
								<view class="flag disabled" v-if="item.status == 0">禁用</view>
								<view class="flag undo" v-else-if="item.status == 1">空闲中</view>
								<view class="flag doing" v-else-if="item.status == 2">使用中</view>
								<view class="flag disabled" v-else-if="item.status == 3">维护中</view>
								<view class="flag qingjieing" v-else-if="item.status == 4 && item.is_cleaning && item.cleaning_status == 2">清洁中</view>
								<view class="flag daiqingjie" v-else-if="item.status == 4 && item.is_cleaning">待清洁</view>
								<view class="flag bukeyong" v-else>已预约</view>
							</view>
							<view class="right" @tap="goOrder" :data-status="item.status" :data-index="index" :data-info="item.id">
								<view class="info">
									<view class="name">
										<view class="type">
											<text v-if="item.type == 0">特价房</text>
											<text v-else-if="item.type == 1">小包</text>
											<text v-else-if="item.type == 2">中包</text>
											<text v-else-if="item.type == 3">大包</text>
											<text v-else-if="item.type == 4">豪包</text>
											<text v-else-if="item.type == 5">商务房</text>
											<text v-else-if="item.type == 6">斯诺克</text>
											<text v-else-if="item.type == 7">中式黑八</text>
											<text v-else>美式球桌</text>
										</view>
										{{item.name}}
									</view>
									<view class="tags">
										<view class="tag" v-for="(labelitem, labelindex) in (item.label ? item.label.split(',') : [])" :key="labelindex">{{labelitem}}</view>
									</view>
								</view>
								<view class="line2">
									<view class="priceLabel">
										<view class="price">
											<label class="color-attention">￥{{item.price}}</label>
											/小时										</view>
									</view>
									<view class="priceLabel" v-if="item.vip_price_list && item.vip_price_list.length > 0">
										<view class="price">
											<text style="color: rgb(255, 51, 0);font-size:28rpx;">会员</text>
											<label class="color-attention">￥{{item.vip_price_list[item.vip_price_list.length-1].price}}</label>
											/时起
										</view>
									</view>
								</view>
								<view class="pkgInfo" v-for="(pkgItem, pkgIndex) in item.pkg_list" :key="pkgIndex">
									{{pkgItem.pkg_name}} ￥{{pkgItem.price}}
								</view>
								<view class="line2 bt">
									<view class="bottom">
										<view class="btn disabled" v-if="item.status == 0">禁用</view>
										<view class="btn bg-primary" v-else>预定</view>
									</view>
								</view>
							</view>
						</view>
						<view class="timeIndexPrice">
							<view class="index" v-if="item.morning_price">
								上午：
								<text class="price"> ￥{{item.morning_price}}</text>
							</view>
							<view class="index" v-if="item.afternoon_price">
								下午：
								<text class="price"> ￥{{item.afternoon_price}}</text>
							</view>
							<view class="index" v-if="item.night_price">
								夜间：
								<text class="price"> ￥{{item.night_price}}</text>
							</view>
							<view class="index" v-if="item.tx_price">
								通宵：
								<text class="price"> ￥{{item.tx_price}}</text>
							</view>
						</view>
						<view class="foot">
							<view class="foot-top">
								<view class="labels">
									<view class="label disabled">不可用</view>
									<view class="label">可预约</view>
								</view>
								<view class="line3">
									<view class="time-line">
										<image src="/static/icon/order-time.png" v-if="item.timeText" />
										<text v-if="item.timeText">{{item.timeText}}被预约</text>
									</view>
									<view class="more" @tap="onShowReserve" :data-list="item.order_time_list" v-if="item.order_time_list && item.order_time_list.length > 1">
										更多
									</view>
								</view>
							</view>
							<view class="times">
								<view class="time" :class="{ disabled: houritem2.disable }" v-for="(houritem2, hourindex) in timeHourAllArr[index]" :key="hourindex">{{houritem2.hour}}</view>
							</view>
						</view>
					</view>
				</view>
				
				<!-- 台球房间列表 -->
				<view class="tab-container" v-if="tabIndex == 1" id="roomList">
					<view class="tab-item" v-for="(item, index) in doorlistArr" :key="item.id">
						<view class="tab-left">
							<view class="tab-left-top">
								<!-- 无订单立即开门 -->
								<view class="tab-img-box-begin" v-if="item.status == 1 || item.status == 2 || item.status == 4" @tap="goOrder" :data-status="item.status" :data-index="index" :data-info="item.id">
									<view class="tab-info">
										<text class="tab-roomName">{{item.name}}</text>
										<view class="price">
											<label class="color-attention">￥{{item.price}}</label>
											/小时
										</view>
									</view>
									<view class="tab-button">
										<button class="begin-button">开门</button>
									</view>
								</view>
								<!-- 有订单，显示等待时间 -->
								<view class="tab-img-box-wait" v-if="item.status == 3 && item.end_time" @tap="goOrder" :data-status="item.status" :data-index="index" :data-info="item.id">
									<view class="tab-wait">
										<text class="tab-wait-roomName">{{item.name}}</text>
										<view></view>
										<text style="font-size: 26rpx;color: rgb(83, 83, 83);">即将空闲中</text>
									</view>
									<view class="tab-wait-time">
										<view class="time-number">{{item.waitTime ? item.waitTime.hours : '00'}}</view>
										<view style="width: 10rpx;"></view>
										<view class="time-date">时</view>
										<view style="width: 10rpx;"></view>
										<view class="time-number">{{item.waitTime ? item.waitTime.minutes : '00'}}</view>
										<view style="width: 10rpx;"></view>
										<view class="time-date">时</view>
									</view>
								</view>
								<!-- 禁用状态 -->
								<view class="tab-img-box-wait" v-if="item.status == 0">
									<view class="tab-wait">
										<text class="tab-wait-roomName">{{item.name}}</text>
										<view></view>
									</view>
									<view class="tab-wait-time">
										<view class="time-disable">禁用</view>
									</view>
								</view>
							</view>
						</view>
					</view>
				</view>
				
				<!-- 温馨提示 -->
				<view class="notes">
					<image src="/static/icon/more.png" />
					到底了，没有更多啦~
				</view>
			</view>
		</scroll-view>
	</view>
	
	<!-- 导航提示框 -->
	<u-popup :show="show" mode="center" @close="show = false">
		<view class="popup navigation" v-if="popupIndex === 0">
			<view class="title">导航到店</view>
			<view class="sub-title">可选择您所需要的服务</view>
			<view class="btn" @tap="goTencentMap">
				<image src="/static/icon/nav.png" />
				地图导航
			</view>
			<view class="btn" @tap="goGuide">
				<image src="/static/icon/guide.png" />
				位置指引
			</view>
		</view>
		<view class="popup service" v-if="popupIndex === 1">
			<view class="title">联系客服</view>
			<view class="sub-title">可选择您所需要的服务</view>
			<view class="btn" @tap="call">
				<image src="/static/icon/phone-call.png" />
				{{ doorinfodata.phone }}
			</view>
		</view>
	</u-popup>
	
	<!-- 门店公告 -->
	<notice-popup 
		:show="popshow" 
		:notice="doorinfodata.notice" 
		:store-id="doorinfodata.id"
		frequency="once-per-day"
		@close="popshow = false"
	/>
	
	<!-- WiFi信息 -->
	<u-popup :show="wifiShow" mode="center" @close="wifiShow = false">
		<view class="wifiDialog">
			<view class="dialog">
				<view class="item">
					<label>WiFi名称: </label>
					<text>{{doorinfodata.wifi_name}}</text>
				</view>
				<view class="item">
					<label>Wifi密码: </label>
					<text>{{doorinfodata.wifi_password}}</text>
				</view>
				<view class="btn">
					<button class="copy" @tap="copyWifi" :data-ssid="doorinfodata.wifi_name" :data-pwd="doorinfodata.wifi_password">
						复制密码
					</button>
					<button class="connect" @tap="connectWifi" :data-ssid="doorinfodata.wifi_name" :data-pwd="doorinfodata.wifi_password">
						一键连接
					</button>
				</view>
				<view class="info">
					部分机型不支持一键连接，请复制密码自行连接
				</view>
			</view>
		</view>
	</u-popup>
	
	<!-- 房间预定时间列表 -->
	<u-popup :show="showReserve" mode="center" @close="showReserve = false">
		<view class="reserve-box">
			<view class="title">预定时间</view>
			<view class="time-line" v-for="(item, index) in orderTimeList" :key="index">
				<view class="dot"></view>
				<view class="time-tag"><text>{{item}} </text><text>已预约</text></view>
			</view>
			<button @tap="onHideReserve">知道了</button>
		</view>
	</u-popup>
	</view>
</template>

<script>
import http from '@/utils/http.js'
import { formatNumber } from '@/utils/util.js'
import NoticePopup from '@/components/notice-popup/notice-popup.vue'

export default {
	components: {
		NoticePopup
	},
	data() {
		return {
			store_id: '',
			doorinfodata: {},
			roomClass: [],
			timeselectindex: 0,
			timeDayArr: [],
			timeWeekArr: [],
			doorlistArr: [],
			timeHourAllArr: [],
			isLogin: false,
			popshow: false,
			wifiShow: false,
			simpleModel: '',
			tabIndex: '',
			show: false,
			popupIndex: 0,
			lat: '',
			lon: '',
			showReserve: false,
			orderTimeList: []
		}
	},
	
	onLoad(options) {
		// onLoad
		const app = getApp()
		this.isLogin = app.globalData.isLogin
		
		let storeId = ''
		if (options.store_id) {
			storeId = options.store_id
			uni.setStorageSync('global_store_id', storeId)
		}
		if (!storeId) {
			storeId = uni.getStorageSync('global_store_id')
		}
		this.store_id = storeId
	},
	
	onShow() {
		// onShow
		const app = getApp()
		this.isLogin = app.globalData.isLogin
		this.doorlistArr = []
		
		if (this.store_id) {
			this.loadingtime()
			this.getLocation().then(() => {
				this.getStoreInfo()
			}).catch(() => {
				this.getStoreInfo()
			})
			this.getDoorList()
		}
	},
	
	methods: {
		
		// 加载时间选择
		loadingtime() {
			const date = new Date()
			const year = date.getFullYear()
			const month = date.getMonth() + 1
			const day = date.getDate()
			const dateStr = `${year}-${formatNumber(month)}-${formatNumber(day)}`
			
			const dates = this.getDates(5, dateStr)
			const dayArr = []
			const weekArr = []
			
			dates.forEach(item => {
				dayArr.push(`${item.month}.${item.day}`)
				weekArr.push(item.week)
			})
			
			this.timeDayArr = dayArr
			this.timeWeekArr = weekArr
		},
		
		// 获取未来几天的日期
		getDates(days, todate) {
			const dateArry = []
			for (let i = 0; i < days; i++) {
				dateArry.push(this.dateLater(todate, i))
			}
			return dateArry
		},
		
		// 计算未来日期
		dateLater(dates, later) {
			const show_day = ['周日', '周一', '周二', '周三', '周四', '周五', '周六']
			const date = new Date(dates)
			date.setDate(date.getDate() + later)
			const day = date.getDay()
			
			return {
				year: date.getFullYear(),
				month: formatNumber(date.getMonth() + 1),
				day: formatNumber(date.getDate()),
				week: show_day[day]
			}
		},
		
		// 获取门店信息
		getStoreInfo() {
			http.get(`/member/index/getStoreInfo/${this.store_id}`, { lat: this.lat, lon: this.lon })
				.then(res => {
					if (res.code === 0 && res.data) {
						const data = res.data
						this.doorinfodata = data
						this.simple_model = data.simple_model
						
						// 应用主题色
						this.applyThemeColor(data.template_key)
						
						// 如果有公告内容，显示公告弹窗
						if (data.notice && data.notice.trim()) {
							this.popshow = true
						}
						
						// 房间类别筛选					
						if (data.room_class_list && data.room_class_list.length > 0) {
							const classArr = []
							data.room_class_list.forEach(e => {
								if (e === 0) {
									classArr.push({ text: '棋牌', value: 0 })
								} else if (e === 1) {
									classArr.push({ text: '台球', value: 1 })
								} else if (e === 2) {
									classArr.push({ text: 'KTV', value: 2 })
								}
							})
							this.roomClass = classArr
							if (!this.tabIndex && this.tabIndex !== 0) {
								this.tabIndex = classArr[0].value
							}
						}
					} else {
						uni.navigateTo({ url: '/pages/door/list' })
					}
				})
				.catch(() => {
					uni.navigateTo({ url: '/pages/door/list' })
				})
		},
		
		// 获取房间列表
		getDoorList() {
			if (!this.store_id) return
			
			http.post('/member/index/getRoomInfoList', {
				store_id: this.store_id,
				room_class: this.tabIndex
			}).then(res => {
				if (res.code !== 0 || !res.data) return
				this.doorlistArr = res.data.map(el => {
					el.timeText = this.timeFilter(el.start_time, el.end_time)
					if (el.order_time_list) {
						el.order_time_list = el.order_time_list.map(item =>
							this.timeFilter(item.start_time, item.end_time)
						)
					}
					
					// 计算等待时间
					if (el.end_time) {
						const currentTime = new Date()
						const endDateTime = new Date(el.end_time)
						const diffInMilliseconds = endDateTime - currentTime
						const diffInMinutes = Math.floor(diffInMilliseconds / (1000 * 60))
						const hours = Math.floor(diffInMinutes / 60)
						const minutes = diffInMinutes % 60
						const timeNumber1 = hours < 10 ? `0${hours}` : `${hours}`
						const timeNumber2 = minutes < 10 ? `0${minutes}` : `${minutes}`
						el.waitTime = {
							hours: timeNumber1,
							minutes: timeNumber2
						}
					}
					
					return el
				})
				this.setroomlistHour(0)
			})
		},
		
		// 设置列表禁用时间段	
		setroomlistHour(aindex) {
			const atemplist = []
			for (let i = 0; i < this.doorlistArr.length; i++) {
				const atemp = this.doorlistArr[i].time_slot ? this.doorlistArr[i].time_slot.slice(aindex * 24, aindex * 24 + 24) : []
				atemplist.push(atemp)
			}
			this.timeHourAllArr = atemplist
		},
		
		// 时间过滤
		timeFilter(startTime, endTime) {
			if (startTime && !endTime) {
				return this.formatTime(startTime, 'MM月DD日HH:mm')
			} else if (startTime && endTime) {
				const start = this.formatTime(startTime, 'MM月DD日HH:mm')
				const end = this.formatTime(endTime, 'HH:mm')
				return `${start}-${end}`
			} else {
				return ''
			}
		},
		
		// 格式化时间	
		formatTime(time, format) {
			const date = new Date(time)
			const o = {
				'M+': date.getMonth() + 1,
				'D+': date.getDate(),
				'H+': date.getHours(),
				'm+': date.getMinutes(),
				's+': date.getSeconds()
			}
			for (let k in o) {
				if (new RegExp('(' + k + ')').test(format)) {
					format = format.replace(RegExp.$1, o[k] < 10 ? '0' + o[k] : o[k])
				}
			}
			return format
		},
		
		// Tab切换
		tabChange(e) {
			const index = e.currentTarget.dataset.index
			this.tabIndex = Number(index)
			this.doorlistArr = []
			this.getDoorList()
		},
		
		// 下单
		goOrder(e) {
			const status = e.currentTarget.dataset.status
			const index = e.currentTarget.dataset.index
			if (status == 0 || status == 3) return  // 禁用或维护中
			
			const aroomid = e.currentTarget.dataset.info
			const room = this.doorlistArr[index]
			
			// 状态2：使用中 - 直接进入预约页面，用户可以预约其他时段
			if (status == 2) {
				uni.navigateTo({
					url: `/pages/order/submit?roomId=${aroomid}&goPage=1&storeId=${this.store_id}&timeselectindex=${this.timeselectindex}`
				})
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
							success: (res) => {
								if (res.confirm) {
									uni.navigateTo({
										url: `/pages/order/submit?roomId=${aroomid}&goPage=1&storeId=${this.store_id}&timeselectindex=${this.timeselectindex}`
									})
								}
							}
						})
					} else {
						uni.showModal({
							title: '提示',
							content: '场地暂未清洁，禁止预订！',
							showCancel: false
						})
					}
				} else {
					// 已预约状态 - 允许用户预约其他时间段
					uni.showModal({
						title: '提示',
						content: '该房间部分时段已被预约，您可以选择其他空闲时段预订',
						confirmText: '查看详情',
						success: (res) => {
							if (res.confirm) {
								uni.navigateTo({
									url: `/pages/order/submit?roomId=${aroomid}&goPage=1&storeId=${this.store_id}&timeselectindex=${this.timeselectindex}`
								})
							}
						}
					})
				}
			} else {
				uni.navigateTo({
					url: `/pages/order/submit?roomId=${aroomid}&goPage=1&storeId=${this.store_id}&timeselectindex=${this.timeselectindex}`
				})
			}
		},
		
		// 切换门店
		goIndexPage() {
			uni.navigateTo({ url: `/pages/door/list?storeId=${this.store_id}` })
		},
		
		// 打开地图
		goTencentMap() {
			const store = this.doorinfodata
			uni.openLocation({
				latitude: store.latitude,
				longitude: store.longitude,
				name: store.store_name,
				address: store.address,
				scale: 28
			})
		},
		
		// 位置指引
		goGuide() {
			uni.navigateTo({
				url: `/pagesA/guide/index?storeId=${this.store_id}`
			})
		},
		
		// 打电话	
		call() {
			const phone = this.doorinfodata.phone || ''
			const phoneLength = phone.length
			if (phoneLength > 0) {
				if (phoneLength == 11) {
					uni.makePhoneCall({
						phoneNumber: phone
					})
				} else {
					uni.showModal({
						title: '提示',
						content: `客服上班时间10:00~23:00\r\n如您遇到问题，建议先查看"使用中帮助"！\r\n本店客服微信号：${phone}`,
						confirmText: '复制',
						success: (res) => {
							if (res.confirm) {
								uni.setClipboardData({
									data: phone,
									success: () => {
										uni.showToast({ title: '微信号已复制到剪贴板' })
									}
								})
							}
						}
					})
				}
			}
		},
		
		// 复制微信
		copy() {
			// No wechat field in database - method kept for compatibility
		},
		
		// 显示弹窗
		onClickShow(e) {
			const index = e.currentTarget.dataset.index
			this.show = true
			this.popupIndex = +index
		},
		
		// 隐藏弹窗
		onClickHide() {
			this.show = false
		},
		
		// 图片预览
		imgYu(e) {
			const aindex = e.currentTarget.dataset.index
			const alistimage = this.doorlistArr[aindex]
			const aimagearr = alistimage.imageUrls
			
			if (aimagearr && aimagearr.length > 0) {
				const anewimagearr = aimagearr.split(',')
				const src = anewimagearr[0]
				uni.previewImage({
					current: src,
					urls: anewimagearr
				})
			} else {
				uni.showToast({ title: '该房间暂无图片介绍', icon: 'none' })
			}
		},
		
		// 复制WiFi密码
		copyWifi(e) {
			const pwd = e.currentTarget.dataset.pwd
			uni.setClipboardData({
				data: pwd,
				success: () => {
					uni.showToast({ title: '已复制到剪贴板！' })
					this.wifiShow = false
				}
			})
		},
		
		// 连接WiFi
		connectWifi(e) {
			const ssid = e.currentTarget.dataset.ssid
			const pwd = e.currentTarget.dataset.pwd
			if (!ssid) {
				uni.showToast({ title: '未获取到WiFi名称', icon: 'none' })
				return
			}
			// #ifdef MP-WEIXIN
			uni.startWifi({
				success: () => {
					uni.connectWifi({
						SSID: ssid,
						password: pwd,
						success: () => {
							uni.showToast({ title: 'WiFi连接成功', icon: 'success' })
							this.wifiShow = false
						},
						fail: () => {
							uni.setClipboardData({
								data: pwd,
								success: () => uni.showToast({ title: '自动连接失败，密码已复制', icon: 'none' })
							})
						}
					})
				},
				fail: () => {
					uni.setClipboardData({
						data: pwd,
						success: () => uni.showToast({ title: '设备不支持，密码已复制', icon: 'none' })
					})
				}
			})
			// #endif
			// #ifndef MP-WEIXIN
			uni.setClipboardData({
				data: pwd,
				success: () => uni.showToast({ title: '密码已复制，请手动连接WiFi', icon: 'none' })
			})
			// #endif
		},
		
		// 显示预约时间
		onShowReserve(e) {
			const list = e.currentTarget.dataset.list
			this.orderTimeList = list
			this.showReserve = true
		},
		
		// 隐藏预约时间
		onHideReserve() {
			this.showReserve = false
		},
		
		// 获取位置
		getLocation() {
			return new Promise((resolve, reject) => {
				uni.getLocation({
					type: 'gcj02',
					success: (res) => {
						this.lat = res.latitude
						this.lon = res.longitude
						resolve()
					},
					fail: () => {
						reject()
					}
				})
			})
		}
	}
}
</script>

<style lang="scss" scoped>
.containerInfo {
	height: 100vh;
	background-color: #f3f2f2;
}

.scroll-view {
	height: 100%;
}

.simple {
	.info-card {
		background: #fff;
		padding: 20rpx 34rpx;
		border-radius: 17rpx;
		display: flex;
		flex-direction: column;
		justify-content: space-between;
		margin: 20rpx;
		
		.top {
			display: flex;
			justify-content: space-between;
			
			.left {
				display: flex;
				align-items: center;
				
				.info {
					margin-left: 10rpx;
					
					.name {
						font-size: 32rpx;
						font-weight: 600;
						color: #1d1d1d;
					}
				}
			}
		}
		
		.change-btn {
			width: 169rpx;
			height: 47rpx;
			background: var(--main-color, #5AAB6E);
			border-radius: 52rpx;
			display: flex;
			justify-content: center;
			align-items: center;
			font-size: 21rpx;
			color: #fff;
		}
		
		.location {
			width: 450rpx;
			font-weight: 300;
			font-size: 22rpx;
			color: #817F7F;
			line-height: 32rpx;
			margin-top: 10rpx;
		}
		
		.bottom {
			display: flex;
			justify-content: space-around;
			align-items: center;
			margin-top: 10rpx;
			
			.line {
				width: 0rpx;
				height: 34rpx;
				border: 0.5rpx solid #BCBCBC;
			}
			
			image {
				width: 39rpx;
				height: 39rpx;
			}
			
			.item {
				display: flex;
				flex-direction: column;
				align-items: center;
				font-weight: 400;
				font-size: 21rpx;
				color: #3E3E3E;
			}
		}
		
		.distant {
			display: flex;
			justify-content: flex-start;
			align-items: center;
			margin-top: 10rpx;
			font-size: 22rpx;
		}
	}
	
	.top-tabs-container {
		padding: 0 20rpx 20rpx;
		background-color: #fff;
		display: flex;
		justify-content: space-between;
		margin: 20rpx;
		border-radius: 17rpx;
	}
	
	.top-tabs {
		width: 49.6%;
		height: 80rpx;
		font-size: 32rpx;
		line-height: 60rpx;
		text-align: center;
		align-items: center;
		background-color: #f5f5f5;
		border-radius: 10rpx;
		padding: 10rpx;
		display: flex;
		justify-content: center;
		
		.tab {
			margin-left: 10rpx;
		}
		
		&.active {
			color: var(--main-color);
			font-weight: bold;
			background-image: linear-gradient(181.2deg, #fff, var(--main-color) 150.8%);
		}
	}
	
	.lists {
		.item {
			padding: 27rpx 34rpx;
			border-radius: 17rpx;
			margin: 20rpx;
			background: #fff;
			
			.top {
				display: flex;
				justify-content: space-between;
				
				.left {
					width: 200rpx;
					height: 260rpx;
					border-radius: 10rpx;
					position: relative;
					overflow: hidden;
					margin-right: 30rpx;
					
					.img {
						width: 200rpx;
						height: 250rpx;
					}
					
					.flag {
						position: absolute;
						bottom: 0;
						left: 0;
						width: 100%;
						height: 50rpx;
						line-height: 50rpx;
						text-align: center;
						font-size: 30rpx;
						color: #fff;
						
						&.doing {
							background: var(--main-red);
						}
						
						&.undo {
							background: var(--main-color);
						}
						
						&.disabled {
							background: #817F7F;
						}
						
						&.daiqingjie {
							background: #58b92b;
						}
						
						&.bukeyong {
							background: #FFA313;
						}
					}
				}
				
				.right {
					flex: 1;
					display: flex;
					flex-direction: column;
					justify-content: space-between;
					
					.name {
						font-weight: 700;
						font-size: 30rpx;
						display: flex;
						align-items: center;
						
						.type {
							display: flex;
							justify-content: center;
							align-items: center;
							background: var(--main-color, #5AAB6E);
							border-radius: 35rpx;
							font-size: 26rpx;
							color: #fff;
							padding: 4rpx 16rpx;
							margin-right: 8rpx;
							white-space: nowrap;
						}
					}
					
					.tags {
						display: flex;
						flex-wrap: wrap;
						margin-top: 20rpx;
						
						.tag {
							margin: 0 10rpx 10rpx 0;
							border: 1px solid var(--main-color);
							border-radius: 8rpx;
							padding: 0 10rpx;
							color: var(--main-color);
							font-size: 22rpx;
							line-height: 28rpx;
						}
					}
					
					.line2 {
						display: flex;
						justify-content: space-between;
						
						&.bt {
							display: flex;
							justify-content: flex-end;
							height: 50rpx;
							line-height: 50rpx;
						}
					}
					
					.priceLabel {
						display: flex;
						align-items: center;
						flex-wrap: wrap;
						
						.price {
							font-weight: 700;
							font-size: 17rpx;
							line-height: 32rpx;
							
							.color-attention {
								font-weight: 700;
								font-size: 31rpx;
								line-height: 32rpx;
								color: var(--main-color);
							}
						}
					}
					
					.pkgInfo {
						margin-top: 10rpx;
						font-size: 26rpx;
						font-weight: 600;
						color: #fd4a33;
					}
					
					.bottom {
						display: flex;
						justify-content: right;
						align-items: center;
						
						.btn {
							width: 125rpx;
							height: 50rpx;
							text-align: center;
							line-height: 50rpx;
							border-radius: 30rpx;
							margin: 10rpx;
							font-size: 26rpx;
							
							&.disabled {
								background: #bebebe;
								color: #fff;
							}
						}
					}
				}
			}
			
			.timeIndexPrice {
				display: flex;
				justify-content: space-around;
				margin: 10rpx 0rpx;
				
				.index {
					font-size: 22rpx;
					border-radius: 5rpx;
					padding: 5rpx 10rpx;
					background-color: #2fe75d31;
					
					.price {
						color: #fd4a33;
					}
				}
			}
			
			.foot {
				margin-top: 10rpx;
				
				.foot-top {
					display: flex;
					justify-content: space-between;
					font-size: 22rpx;
					
					.labels {
						display: flex;
						
						.label {
							margin-right: 20rpx;
							
							&::before {
								content: "";
								display: inline-block;
								margin-right: 6rpx;
								width: 20rpx;
								height: 20rpx;
								background: #e8e8e8;
							}
							
							&.disabled::before {
								background: var(--main-color);
							}
						}
					}
					
					.line3 {
						display: flex;
						justify-content: space-between;
						align-items: center;
						font-size: 22rpx;
						color: #FFA313;
						
						.time-line {
							display: flex;
							justify-content: center;
							align-items: center;
							
							image {
								width: 24rpx;
								height: 24rpx;
								margin-right: 12rpx;
							}
						}
						
						.more {
							color: var(--main-color);
							font-size: 21rpx;
							display: flex;
							align-items: center;
							margin-left: 10rpx;
						}
					}
				}
				
				.times {
					margin-top: 10rpx;
					display: flex;
					justify-content: space-between;
					
					.time {
						font-size: 20rpx;
						text-align: center;
						color: #aaa;
						border-top: 10rpx solid #e8e8e8;
						width: 24rpx;
						line-height: 30rpx;
						
						&.disabled {
							border-color: var(--main-color);
						}
					}
				}
			}
		}
	}
	
	/* 台球房间列表样式 */
	.tab-container {
		display: flex;
		flex-wrap: wrap;
		padding: 0 20rpx;
		
		.tab-item {
			width: 48%;
			margin: 10rpx 1%;
			
			.tab-left {
				width: 100%;
				
				.tab-left-top {
					.tab-img-box-begin,
					.tab-img-box-wait {
						width: 100%;
						height: 200rpx;
						border-radius: 10rpx;
						overflow: hidden;
						display: flex;
						flex-direction: column;
						justify-content: center;
						align-items: center;
						background-size: contain;
						background-repeat: no-repeat;
						background-position: center;
						background-color: #f5f5f5;
						
						.tab-info,
						.tab-wait {
							text-align: center;
							
							.tab-roomName,
							.tab-wait-roomName {
								font-size: 28rpx;
								font-weight: bold;
								color: #333;
							}
							
							.price {
								font-size: 24rpx;
								margin-top: 10rpx;
								
								.color-attention {
									color: var(--main-color);
									font-size: 32rpx;
									font-weight: bold;
								}
							}
						}
						
						.tab-button {
							margin-top: 10rpx;
							
							.begin-button {
								width: 150rpx;
								height: 50rpx;
								border-radius: 40rpx;
								background-color: rgb(2, 185, 97);
								color: #fff;
								font-size: 28rpx;
								font-weight: bold;
								line-height: 50rpx;
								border: none;
							}
						}
						
						.tab-wait-time {
							display: flex;
							align-items: center;
							margin-top: 10rpx;
							
							.time-number {
								color: #fff;
								background-color: rgb(245, 158, 61);
								width: 46rpx;
								height: 46rpx;
								line-height: 46rpx;
								text-align: center;
								border-radius: 8rpx;
								font-size: 24rpx;
							}
							
							.time-date {
								color: rgb(245, 158, 61);
								margin: 0 5rpx;
								font-size: 24rpx;
							}
							
							.time-disable {
								color: red;
								font-size: 28rpx;
							}
						}
					}
				}
			}
		}
	}
	
	.notes {
		font-size: 21rpx;
		color: var(--main-color, #5AAB6E);
		text-align: center;
		display: flex;
		justify-content: center;
		align-items: center;
		margin: 40rpx 0;
		
		image {
			width: 30rpx;
			height: 30rpx;
			margin-right: 12rpx;
		}
	}
}

/* 弹窗样式 */
.popup {
	width: 539rpx;
	background: #FFFFFF;
	border-radius: 17rpx;
	background: linear-gradient(180deg, #C9FFD7 0%, #FFFFFF 100%);
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 33rpx 0 27rpx;
	
	.title {
		font-weight: 600;
		font-size: 34rpx;
		color: #000000;
		text-align: center;
	}
	
	.sub-title {
		font-weight: 400;
		font-size: 17rpx;
		color: #817F7F;
		text-align: center;
		margin: 13rpx 0 20rpx;
	}
	
	.btn {
		width: 399rpx;
		height: 82rpx;
		background: var(--main-color);
		border-radius: 52rpx;
		font-weight: 400;
		font-size: 31rpx;
		color: #FFFFFF;
		display: flex;
		align-items: center;
		margin-bottom: 20rpx;
		
		image {
			width: 44rpx;
			height: 44rpx;
			margin-left: 63rpx;
			margin-right: 34rpx;
		}
	}
}

.wifiDialog {
	.dialog {
		padding: 20rpx 50rpx;
		text-align: center;
		
		.item {
			font-size: 32rpx;
			line-height: 60rpx;
			
			label {
				color: #666;
			}
		}
		
		.btn {
			display: flex;
			justify-content: space-between;
			margin-top: 20rpx;
			
			button {
				width: 200rpx;
				height: 60rpx;
				line-height: 60rpx;
				font-size: 26rpx;
				border-radius: 8rpx;
			}
			
			.copy {
				background-color: rgb(204, 204, 204);
			}
			
			.connect {
				background-color: #108ee9;
				color: #fff;
			}
		}
		
		.info {
			margin-top: 10rpx;
			font-size: 22rpx;
			color: #999;
		}
	}
}

.reserve-box {
	width: 539rpx;
	background: linear-gradient(180deg, #FFECC7 0%, #FFFFFF 16%, #FFFFFF 100%);
	border-radius: 17rpx;
	padding: 34rpx 40rpx;
	
	.title {
		font-weight: 500;
		font-size: 37rpx;
		color: #000000;
		text-align: center;
		margin-bottom: 58rpx;
	}
	
	.time-line {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 34rpx;
		
		.dot {
			width: 12rpx;
			height: 12rpx;
			background: #FFA313;
			border-radius: 50%;
			position: relative;
			z-index: 9;
			
			&::before {
				content: '';
				height: 60px;
				width: 2rpx;
				background: #FFA313;
				left: calc(50% - 1rpx);
				top: -50rpx;
				position: absolute;
				z-index: -1;
			}
		}
		
		.time-tag {
			color: #fff;
			background-color: #FFA313;
			width: 414rpx;
			height: 46rpx;
			border-radius: 17rpx;
			line-height: 46rpx;
			display: flex;
			justify-content: space-between;
			font-weight: 400;
			font-size: 24rpx;
			padding: 9rpx 22rpx;
			position: relative;
			
			&::before {
				content: '';
				width: 0;
				height: 0;
				border-bottom: 15rpx solid transparent;
				border-top: 15rpx solid transparent;
				border-right: 15rpx solid #FFA313;
				border-left: 15rpx solid transparent;
				left: -29rpx;
				top: calc(50% - 15rpx);
				position: absolute;
				z-index: 3;
			}
		}
	}
	
	button {
		width: 208rpx;
		height: 59rpx;
		line-height: 59rpx;
		background: var(--main-color, #5AAB6E);
		border-radius: 52rpx;
		font-weight: 400;
		font-size: 26rpx;
		color: #FFFFFF;
		margin: 22rpx auto 0;
		display: block;
	}
}
</style>
