/**
 * 门店相关混入 - 从首页拆分的门店操作逻辑
 */
import http from '@/utils/http.js'

export default {
	data() {
		return {
			storeId: '',
			storeInfo: {},
			roomList: [],
			bannerImages: [],
			roomClass: [],
			tabIndex: 0,
			lat: '',
			lon: ''
		}
	},
	
	methods: {
		/**
		 * 获取定位
		 */
		getLocation() {
			return new Promise((resolve, reject) => {
				uni.getLocation({
					type: 'gcj02',
					success: res => {
						this.lat = res.latitude
						this.lon = res.longitude
						resolve(res)
					},
					fail: err => reject(err)
				})
			})
		},
		
		/**
		 * 获取门店信息
		 */
		async getStoreInfo(storeId) {
			if (!storeId) return
			
			try {
				const res = await http.get(`/member/index/getStoreInfo/${storeId}`, {
					lat: this.lat,
					lon: this.lon
				})
				
				if (res.code === 0 && res.data) {
					this.storeInfo = this.formatStoreInfo(res.data)
					this.parseBannerImages(res.data)
					this.parseRoomClass(res.data)
				}
				return res
			} catch (e) {
				console.error('获取门店信息失败', e)
			}
		},
		
		/**
		 * 格式化门店信息（使用数据库原始字段名）
		 */
		formatStoreInfo(data) {
			return {
				...data,
				store_id: data.id,
				store_name: data.store_name || data.name
			}
		},
		
		/**
		 * 解析轮播图
		 */
		parseBannerImages(data) {
			const bannerSrc = data.banner_img || data.head_img
			if (bannerSrc && bannerSrc.length > 0) {
				this.bannerImages = typeof bannerSrc === 'string' 
					? bannerSrc.split(',') 
					: Array.isArray(bannerSrc) ? bannerSrc : []
			} else if (data.images) {
				if (typeof data.images === 'object') {
					this.bannerImages = Object.values(data.images)
				} else if (typeof data.images === 'string') {
					this.bannerImages = data.images.split(',')
				}
			}
		},
		
		/**
		 * 解析房间类别
		 */
		parseRoomClass(data) {
			if (data.room_class_list && data.room_class_list.length > 0) {
				const classMap = { 0: '棋牌', 1: '台球', 2: 'KTV' }
				this.roomClass = data.room_class_list
					.filter(e => classMap[e] !== undefined)
					.map(e => ({ text: classMap[e], value: e }))
				
				if (this.roomClass.length > 0) {
					this.tabIndex = this.roomClass[0].value
				}
			}
		},
		
		/**
		 * 获取房间列表
		 */
		async getRoomList(storeId, roomClass) {
			if (!storeId) return
			
			try {
				const res = await http.post('/member/index/getRoomInfoList', {
					store_id: storeId,
					room_class: roomClass
				})
				
				if (res.code === 0) {
					this.roomList = res.data.map(room => this.formatRoomInfo(room))
				}
				return res
			} catch (e) {
				console.error('获取房间列表失败', e)
			}
		},
		
		/**
		 * 格式化房间信息
		 */
		formatRoomInfo(room) {
			// 时间显示
			room.timeText = this.formatTimeRange(room.start_time, room.end_time)
			
			// 预约时间列表
			if (room.order_time_list) {
				room.order_time_list = room.order_time_list.map(item => 
					this.formatTimeRange(item.start_time, item.end_time)
				)
			}
			
			// 等待时间
			if (room.end_time) {
				const diffMs = new Date(room.end_time) - new Date()
				const diffMin = Math.floor(diffMs / 60000)
				const h = Math.floor(diffMin / 60)
				const m = diffMin % 60
				room.waitTime = {
					hours: h < 10 ? `0${h}` : `${h}`,
					minutes: m < 10 ? `0${m}` : `${m}`
				}
			}
			
			// KTV座位颜色
			if (room.room_class == 2) {
				const fillMap = { 0: '#e5e5e5', 1: '#fff', 2: '#7FDBFF', 3: '#DC143C', 4: '#F0E68C' }
				room.fill = fillMap[room.status] || '#fff'
			}
			
			return room
		},
		
		/**
		 * 格式化时间范围
		 */
		formatTimeRange(startTime, endTime) {
			if (startTime && !endTime) {
				return this.formatDateTime(startTime, 'MM月DD日HH:mm')
			}
			if (startTime && endTime) {
				return `${this.formatDateTime(startTime, 'MM月DD日HH:mm')}-${this.formatDateTime(endTime, 'HH:mm')}`
			}
			return ''
		},
		
		/**
		 * 格式化日期时间
		 */
		formatDateTime(time, format) {
			const d = new Date(time)
			const pad = n => n < 10 ? '0' + n : n
			return format
				.replace('YYYY', d.getFullYear())
				.replace('MM', pad(d.getMonth() + 1))
				.replace('DD', pad(d.getDate()))
				.replace('HH', pad(d.getHours()))
				.replace('mm', pad(d.getMinutes()))
		},
		
		/**
		 * 打开地图导航
		 */
		openNavigation() {
			const s = this.storeInfo
			if (!s.latitude || !s.longitude) {
				uni.showToast({ title: '门店位置信息不完整', icon: 'none' })
				return
			}
			uni.openLocation({
				latitude: parseFloat(s.latitude),
				longitude: parseFloat(s.longitude),
				name: s.store_name || s.name,
				address: s.address,
				scale: 28
			})
		},
		
		/**
		 * 拨打客服电话
		 */
		callService() {
			const phone = this.storeInfo.phone
			if (!phone) {
				uni.showToast({ title: '暂无客服电话', icon: 'none' })
				return
			}
			
			if (phone.length === 11) {
				uni.makePhoneCall({ phoneNumber: phone })
			} else {
				uni.showModal({
					title: '客服信息',
					content: `客服微信号：${phone}`,
					confirmText: '复制',
					success: res => {
						if (res.confirm) {
							uni.setClipboardData({
								data: phone,
								success: () => uni.showToast({ title: '已复制' })
							})
						}
					}
				})
			}
		},
		
		/**
		 * 显示WiFi信息
		 */
		showWifiInfo() {
			const wifiName = this.storeInfo.wifi_name || ''
			const wifiPwd = this.storeInfo.wifi_password || ''
			
			if (!wifiName && !wifiPwd) {
				uni.showToast({ title: '该门店暂未配置WiFi信息', icon: 'none' })
				return
			}
			
			uni.showModal({
				title: 'WiFi信息',
				content: `名称：${wifiName}\n密码：${wifiPwd}`,
				confirmText: '复制密码',
				success: res => {
					if (res.confirm && wifiPwd) {
						uni.setClipboardData({
							data: wifiPwd,
							success: () => uni.showToast({ title: '密码已复制' })
						})
					}
				}
			})
		}
	}
}
