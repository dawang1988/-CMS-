<template>
	<view class="page" :style="themeStyle">
		<!-- 顶部筛选 -->
		<view class="tabs">
			<picker @change="statusDropdown" :value="statusIndex" :range="option1" range-key="text">
				<view class="dropdown-btn">
					<text>{{option1[statusIndex].text}}</text>
					<text style="font-size:20rpx;margin-left:8rpx;">▼</text>
				</view>
			</picker>
		</view>
		
		<view class="container">
			<block v-if="pkgList.length > 0">
				<view class="item" v-for="(item, index) in pkgList" :key="index">
					<view class="item-top">
						<view class="info">
							<text class="title">{{item.name}}</text>
						</view>
						<view class="price">
							<text style="color:#be1818;">￥<text style="font-size: 32rpx;font-weight: bold;">{{item.price}}</text></text>
						</view>
					</view>
					<view class="item-bottom">
						<view class="line2">
							<view>包间限制：
								<text v-if="item.roomQuantum && item.roomQuantum.length">{{item.roomQuantum}}</text>
								<text v-else>{{item.enableRoomQuantum}}</text>
							</view>
						</view>
						<view>
							<view>套餐时长：{{item.hours}}小时</view>
						</view>
						<view class="can-time">
							可用日期：
							<text v-if="!item.enable_week || (Array.isArray(item.enable_week) && item.enable_week.length >= 7)">不限制</text>
							<text v-else>限{{ item.weekQuantum }}可用</text>
						</view>
						<view class="can-time">
							可用时间段：
							<text v-if="!item.enable_time || item.enable_time == '[]' || (typeof item.enable_time === 'string' && item.enable_time.indexOf('00:00') >= 0 && item.enable_time.indexOf('23:59') >= 0)">不限制</text>
							<text v-else>限{{ item.timeQuantum || '' }}时可用</text>
						</view>
						<view class="line2">
							<view>
								可余额支付：
								<text v-if="item.balance_buy">是</text>
								<text v-else>否</text>
							</view>
						</view>
						<view class="line3">
							<view>
								<text style="font-size: 40rpx;" :style="{color: item.enable ? 'var(--main-color, #1aad19)' : 'red'}">
									{{item.enable ? '正常' : '禁用'}}
								</text>
							</view>
							<view class="delete-icon" @click="deletePkg(item.id)">
								<text style="font-size:40rpx;color:#666;">🗑</text>
							</view>
							<button class="enableBtn" @click="changeStatus(item)">
								<text v-if="item.enable">禁用</text>
								<text v-else>启用</text>
							</button>
							<button class="editBtn" @click="editPkg(item)">编辑</button>
						</view>
					</view>
				</view>
			</block>
			<block v-else>
				<view class="nodata-list">暂无套餐，请新增</view>
			</block>
		</view>
		
		<!-- 底部按钮 -->
		<button class="bottom bg-primary" @click="goToAddPage">新增套餐</button>
	</view>
</template>

<script>
import api from '@/api/index.js'

export default {
	data() {
		return {
			pkgList: [],
			store_id: '',
			storeName: '',
			option1: [
				{ text: '全部状态', value: -1 },
				{ text: '禁用', value: 0 },
				{ text: '启用', value: 1 }
			],
			statusIndex: 0,
			status: '',
			pageNo: 1,
			pageSize: 10,
			canLoadMore: true
		}
	},
	
	onLoad(options) {
		this.store_id = options.store_id || ''
		this.store_name = options.store_name || ''
	},
	
	onShow() {
		this.getPkgList('refresh')
	},
	
	onPullDownRefresh() {
		this.pkgList = []
		this.canLoadMore = true
		this.pageNo = 1
		this.getPkgList('refresh')
		uni.stopPullDownRefresh()
	},
	
	onReachBottom() {
		if (this.canLoadMore) {
			this.getPkgList('')
		} else {
			uni.showToast({ title: '我是有底线的...', icon: 'none' })
		}
	},
	
	methods: {
		getPkgList(e) {
			let message = ''
			if (e === 'refresh') {
				message = '正在加载'
				this.pkgList = []
				this.canLoadMore = true
				this.pageNo = 1
			}
			if (message) uni.showLoading({ title: message })
			
			api.post('/member/pkg/admin/getAdminPkgPage', {
				pageNo: this.pageNo,
				pageSize: this.pageSize,
				enable: this.status,
				store_id: this.store_id
			}).then(res => {
				uni.hideLoading()
				if (res.code === 0) {
					if (res.data.list.length === 0) {
						this.canLoadMore = false
					} else {
						const newList = res.data.list.map(meal => ({
							...meal,
							weekQuantum: this.convertWeekday(meal.enable_week),
							timeQuantum: this.convertTime(meal.enable_time),
							roomQuantum: this.convertRoomType(meal.room_type),
							enableRoomQuantum: this.convertEnableRoomType(meal.room_list, meal.enable_room)
						}))
						this.pkgList = this.pkgList.concat(newList)
						this.pageNo++
						this.canLoadMore = this.pkgList.length < res.data.total
					}
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			}).catch(() => uni.hideLoading())
		},
		
		statusDropdown(e) {
			this.statusIndex = e.detail.value
			const item = this.option1[this.statusIndex]
			this.status = item.value === -1 ? '' : item.value
			this.getPkgList('refresh')
		},
		
		goToAddPage() {
			uni.navigateTo({
				url: '/pagesA/package/edit?store_id=' + this.store_id + '&store_name=' + this.store_name
			})
		},
		
		convertWeekday(numbers) {
			if (!numbers || !numbers.length) return ''
			const weekDays = ['周日', '周一', '周二', '周三', '周四', '周五', '周六']
			return numbers.map(num => weekDays[num % 7]).join(' | ')
		},
		
		convertRoomType(numbers) {
			if (!numbers || numbers.length === 0) return ''
			const types = ['不限', '小包', '中包', '大包', '豪包', '商务包', '斯洛克', '中式黑八', '美式球桌']
			return numbers.map(num => types[num] || '').join('、')
		},
		
		convertEnableRoomType(doorRoomList, enableRoom) {
			if (!doorRoomList || !enableRoom) return ''
			const enabledRoomIds = enableRoom.filter(roomId => doorRoomList.some(room => room.id === roomId))
			return enabledRoomIds.map(roomId => {
				const room = doorRoomList.find(room => room.id === roomId)
				return room ? room.name : ''
			}).join('、')
		},
		
		convertTime(numbers) {
			if (!numbers || numbers.length === 0) return ''
			let result = []
			let start = numbers[0]
			let end = numbers[0]
			for (let i = 1; i < numbers.length; i++) {
				if (numbers[i] === end + 1) {
					end = numbers[i]
				} else {
					result.push(start + '~' + end)
					start = numbers[i]
					end = numbers[i]
				}
			}
			result.push(start + '~' + end)
			return result.join(', ')
		},
		
		changeStatus(item) {
			api.post('/member/pkg/admin/enable/' + item.id, {
				pkg_id: item.id
			}).then(res => {
				if (res.code === 0) {
					uni.showToast({ title: '操作成功', icon: 'none' })
					this.getPkgList('refresh')
				} else {
					uni.showToast({ title: res.msg, icon: 'none' })
				}
			})
		},
		
		editPkg(item) {
			const params = encodeURIComponent(JSON.stringify(item))
			uni.navigateTo({
				url: '/pagesA/package/edit?item=' + params + '&store_id=' + this.store_id + '&store_name=' + this.store_name
			})
		},
		
		deletePkg(id) {
			uni.showModal({
				title: '温馨提示',
				content: '您是否确认删除此套餐？',
				success: (res) => {
					if (res.confirm) {
						api.post('/member/pkg/admin/delete/' + id, {}).then(res => {
							if (res.code === 0) {
								uni.showToast({ title: '操作成功', icon: 'success' })
								this.getPkgList('refresh')
							}
						})
					}
				}
			})
		}
	}
}
</script>

<style lang="scss" scoped>
.page { min-height: 100vh; background: #f5f5f5; }
.tabs { position: fixed; z-index: 9; width: 100%; height: 90rpx; background: #fff; border-bottom: 1rpx solid #ddd; display: flex; align-items: center; padding: 0 30rpx; }
.dropdown-btn { display: flex; align-items: center; font-size: 28rpx; }
.dropdown-btn text { margin-right: 10rpx; }
.container { padding: 110rpx 30rpx 140rpx; }
.bottom { width: 530rpx; height: 90rpx; text-align: center; line-height: 90rpx; position: fixed; bottom: 30rpx; left: 50%; transform: translateX(-50%); border-radius: 50rpx; font-size: 36rpx; color: #fff; background: var(--main-color, #1aad19); }
.container .item { margin: 30rpx 0; }
.container .item .item-top { background-color: var(--main-color, #1aad19); color: #fff; border-top-left-radius: 15rpx; border-top-right-radius: 15rpx; padding: 10rpx 20rpx; font-weight: 600; display: flex; flex-wrap: wrap; justify-content: space-between; }
.container .item .item-top .price { display: flex; flex-direction: column; align-items: center; font-size: 26rpx; }
.container .item .item-top .info { display: flex; flex-direction: column; font-size: 26rpx; }
.container .item .item-top .info .title { font-size: 32rpx; margin-bottom: 10rpx; width: 450rpx; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.container .item .item-bottom { background-color: #fff; border-bottom-left-radius: 15rpx; border-bottom-right-radius: 15rpx; padding: 25rpx; overflow: hidden; font-size: 26rpx; }
.container .item .item-bottom .line2 { display: flex; justify-content: space-between; margin-bottom: 10rpx; }
.container .item .item-bottom .line3 { display: flex; justify-content: space-between; margin-top: 15rpx; align-items: center; }
.container .item .item-bottom .can-time { margin-bottom: 10rpx; }
.enableBtn { width: 160rpx; height: 60rpx; line-height: 60rpx; font-size: 28rpx; background-color: #da635f; color: #fff; border-radius: 10rpx; padding: 0; }
.editBtn { width: 160rpx; height: 60rpx; line-height: 60rpx; font-size: 28rpx; background-color: #45cf40; color: #fff; border-radius: 10rpx; padding: 0; }
.delete-icon { padding: 10rpx; }

</style>
