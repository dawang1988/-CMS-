/**
 * 订单相关混入 - 从首页拆分的订单操作逻辑
 */
import http from '@/utils/http.js'

export default {
	methods: {
		/**
		 * 开房间门
		 */
		async openRoomDoor() {
			try {
				const res = await http.get('/member/order/getOrderInfo')
				
				if (res.code !== 0 || !res.data) {
					uni.showModal({
						title: '温馨提示',
						content: '当前无有效订单，请先下单！',
						showCancel: false
					})
					return
				}
				
				const order = res.data
				
				// 检查是否提前开门
				if (order.status == 0 && new Date(order.start_time) > Date.now()) {
					const confirmed = await this.showConfirmModal(
						'温馨提示',
						'当前还未到预约时间，是否提前开始消费？'
					)
					if (!confirmed) return
				}
				
				// 执行开门
				await this.doOpenDoor(order.orderKey)
				
			} catch (e) {
				console.error('开门失败', e)
			}
		},
		
		/**
		 * 执行开门操作
		 */
		async doOpenDoor(orderKey) {
			try {
				const res = await http.post(`/member/order/openRoomDoor?orderKey=${orderKey}`)
				
				if (res.code === 0) {
					uni.showToast({ title: '开门成功', icon: 'success' })
				} else {
					uni.showModal({
						title: '提示',
						content: res.msg || '开门失败',
						showCancel: false
					})
				}
			} catch (e) {
				uni.showToast({ title: '开门失败', icon: 'none' })
			}
		},
		
		/**
		 * 续费订单
		 */
		async goRenewOrder() {
			try {
				const res = await http.get('/member/order/getOrderInfo')
				
				if (res.code === 0 && res.data) {
					uni.navigateTo({
						url: `/pages/room/renew?storeId=${res.data.store_id}&roomId=${res.data.room_id}`
					})
				} else {
					uni.showToast({
						title: res.msg || '当前无进行中的订单',
						icon: 'none'
					})
				}
			} catch (e) {
				uni.showToast({
					title: (e && e.msg) || '当前无进行中的订单',
					icon: 'none'
				})
			}
		},
		
		/**
		 * 跳转下单页面
		 */
		goToOrder(room, storeId, options = {}) {
			const { timeselectindex = 0 } = options
			
			// 检查房间状态
			if (room.status == 0) {
				uni.showToast({ title: '该房间不可用', icon: 'none' })
				return
			}
			
			// 待清洁房间提示
			if (room.status == 2) {
				const clearOpen = options.clearOpen
				if (clearOpen) {
					uni.showModal({
						title: '提示',
						content: '您选择的此场地暂未清洁，介意请勿预订！',
						confirmText: '继续预定',
						success: res => {
							if (res.confirm) {
								this.navigateToSubmit(room.id, storeId, timeselectindex)
							}
						}
					})
				} else {
					uni.showModal({
						title: '提示',
						content: '房间暂未清洁，禁止预订！',
						showCancel: false
					})
				}
				return
			}
			
			this.navigateToSubmit(room.id, storeId, timeselectindex)
		},
		
		/**
		 * 跳转到提交订单页
		 */
		navigateToSubmit(roomId, storeId, timeselectindex) {
			uni.navigateTo({
				url: `/pages/order/submit?roomId=${roomId}&goPage=1&storeId=${storeId}&timeselectindex=${timeselectindex}`
			})
		},
		
		/**
		 * 显示确认弹窗
		 */
		showConfirmModal(title, content) {
			return new Promise(resolve => {
				uni.showModal({
					title,
					content,
					success: res => resolve(res.confirm)
				})
			})
		},
		
		/**
		 * 预览房间图片
		 */
		previewRoomImages(room) {
			let images = []
			
			if (room.imageUrls) {
				images = room.imageUrls.split(',').filter(i => i)
			} else if (room.images) {
				try {
					const parsed = typeof room.images === 'string' 
						? JSON.parse(room.images) 
						: room.images
					if (Array.isArray(parsed)) {
						images = parsed.filter(i => i)
					}
				} catch (e) {
					images = room.images.split(',').filter(i => i)
				}
			}
			
			if (images.length > 0) {
				uni.previewImage({ current: images[0], urls: images })
			} else {
				uni.showToast({ title: '该房间暂无图片介绍', icon: 'none' })
			}
		}
	}
}
