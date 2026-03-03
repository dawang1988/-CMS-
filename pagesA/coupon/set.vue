<template>
	<view class="page" :style="themeStyle">
		<!-- 顶部筛选 -->
		<view class="tabs">
			<picker @change="typeDropdown" :value="typeIndex" :range="types" range-key="text">
				<view class="dropdown-btn">
					<text>{{types[typeIndex].text}}</text>
					<text style="font-size:20rpx;margin-left:8rpx;">▼</text>
				</view>
			</picker>
			<view class="help-btn" @click="helpShow = true">
				<text style="font-size:24rpx;">❓</text>
				<text style="font-size:24rpx;margin-left:4rpx;">使用说明</text>
			</view>
		</view>
		
		<view class="container">
			<block v-if="list.length">
				<view class="list">
					<view class="item" v-for="(item, index) in list" :key="index">
						<!-- 渐变背景 -->
						<view class="gradient-bg" :class="[item.type == 1 ? 'gradient-red' : item.type == 2 ? 'gradient-yellow' : item.type == 3 ? 'gradient-blue' : 'gradient-red']"></view>
						
						<view class="info">
							<view class="left">
								<view class="name">{{item.name}}</view>
								<view class="text">包间限制：{{getRoomTypeText(item.room_class)}}</view>
								<view class="text">抵扣额度：
									<text v-if="item.type == 2">{{item.amount}}元</text>
									<text v-else>{{item.amount}}小时</text>
								</view>
								<view class="text">使用门槛：
									<text v-if="item.type == 2">满{{item.min_amount}}元可用</text>
									<text v-else>满{{item.min_amount}}小时可用</text>
								</view>
								<view class="text">发行/已领：{{item.received || 0}}/{{item.total || 0}}</view>
								<view class="text">过期时间：{{formatDate(item.end_time)}}</view>
								<view class="text">状态：<text :class="item.status == 1 ? 'normal' : 'expired'">{{getStatusText(item.status)}}</text></view>
							</view>
							<view class="right">
								<view class="btns" v-if="isSelect">
									<button class="btn bg-primary" @click="selectCoupon(item.id)">选择</button>
								</view>
								<view class="btns" v-else>
									<button class="btn primary" @click="openGiftDialog(item.id)">赠送用户</button>
									<navigator class="btn yellow" :url="'/pagesA/coupon/info?coupon_id=' + item.id + '&store_id=' + store_id + '&store_name=' + store_name">修改设置</navigator>
									<button class="btn" :class="item.status == 1 ? 'btn-stop' : 'btn-start'" @click="toggleStatus(item)">{{item.status == 1 ? '停用' : '启用'}}</button>
								</view>
							</view>
						</view>
						<view class="buttom">
							<button class="btn" @click="deleteCoupon(item.id)">删除</button>
							<button class="btn" @click="openActive(item.id, item.name)">发起活动</button>
							<button class="btn" @click="stopActive(item.id)">结束活动</button>
							<button class="btn" open-type="share" :data-id="item.id">分享领券</button>
						</view>
					</view>
				</view>
			</block>
			<block v-else>
				<view class="nodata-list">暂无数据</view>
			</block>
		</view>
		
		<!-- 底部按钮 -->
		<view class="bt" v-if="!isSelect">
			<navigator :url="'/pagesA/coupon/info?store_id=' + store_id + '&store_name=' + store_name" class="bottom bg-primary">添加优惠券</navigator>
		</view>
		
		<!-- 活动弹窗 -->
		<u-popup :show="activeShow" mode="center" :round="10" @close="cancelActive">
			<view class="dialog">
				<view class="dialog-title">发起活动</view>
				<view class="dialog-content">
					<view class="field-item">
						<text class="label">优惠券名称：</text>
						<text class="value">{{couponName}}</text>
					</view>
					<view class="field-item">
						<text class="label">活动名称：</text>
						<input type="text" v-model="activeName" placeholder="请输入一个活动的名称" />
					</view>
					<view class="field-item">
						<text class="label">总数量：</text>
						<input type="number" v-model="num" placeholder="请输入1-99999的数字" />
					</view>
					<view class="field-item" v-if="balanceNum">
						<text class="label">剩余数量：</text>
						<text class="value">{{balanceNum}}</text>
					</view>
					<view class="field-item">
						<text class="label">截止时间：</text>
						<picker mode="date" :value="endDate" @change="onDateChange">
							<view class="picker-value">{{endTime || '请选择时间'}}</view>
						</picker>
					</view>
				</view>
				<view class="dialog-footer">
					<button class="btn-cancel" @click="cancelActive">取消</button>
					<button class="btn-confirm" @click="saveActive">确定</button>
				</view>
			</view>
		</u-popup>
		<!-- 赠送用户弹窗 -->
		<view class="dialog-mask" v-if="giftShow" @click="giftShow = false"></view>
		<view class="gift-dialog" v-if="giftShow">
			<view class="dialog-title">赠送优惠券</view>
			<view class="dialog-content">
				<view class="search-bar">
					<input type="text" v-model="giftKeyword" placeholder="输入手机号搜索用户" @confirm="searchGiftUser" />
					<button class="search-btn" @click="searchGiftUser">搜索</button>
				</view>
				<scroll-view scroll-y class="user-list" v-if="giftUserList.length">
					<view class="user-item" v-for="u in giftUserList" :key="u.id" @click="confirmGift(u)">
						<view class="user-info">
							<text class="nickname">{{ u.nickname || '未设置昵称' }}</text>
							<text class="phone">{{ u.phone || '-' }}</text>
						</view>
						<text class="gift-btn-text">赠送</text>
					</view>
				</scroll-view>
				<view class="no-user" v-else-if="giftSearched">未找到用户</view>
			</view>
			<view class="dialog-footer-single">
				<button class="btn-cancel" @click="giftShow = false">关闭</button>
			</view>
		</view>
		
		<!-- 使用说明弹窗 -->
		<u-popup :show="helpShow" mode="center" :round="10" @close="helpShow = false" :closeable="true">
			<view class="help-dialog">
				<view class="help-title">💡 优惠券使用说明</view>
				<scroll-view scroll-y class="help-content">
					<!-- 抵扣券 -->
					<view class="help-section">
						<view class="help-type-title">
							<view class="type-badge badge-red">抵扣券</view>
							<text class="type-desc">减少支付金额</text>
						</view>
						<view class="help-item">
							<text class="help-label">作用：</text>
							<text class="help-text">抵扣X小时的费用，减少支付金额</text>
						</view>
						<view class="help-item">
							<text class="help-label">示例：</text>
							<text class="help-text">满1小时抵扣1小时 = 0元续费</text>
						</view>
						<view class="help-item">
							<text class="help-label">计算：</text>
							<text class="help-text">抵扣金额 = 抵扣小时 × 房间单价</text>
						</view>
						<view class="help-example">
							<text class="example-title">💰 实例：</text>
							<text class="example-text">房间10元/时，订单2小时，用"满2h抵1h"券</text>
							<text class="example-text">→ 抵扣10元，实付10元（相当于半价）</text>
						</view>
					</view>
					
					<!-- 满减券 -->
					<view class="help-section">
						<view class="help-type-title">
							<view class="type-badge badge-yellow">满减券</view>
							<text class="type-desc">按金额直接减免</text>
						</view>
						<view class="help-item">
							<text class="help-label">作用：</text>
							<text class="help-text">订单满X元，直接减Y元</text>
						</view>
						<view class="help-item">
							<text class="help-label">示例：</text>
							<text class="help-text">满10元减2元</text>
						</view>
						<view class="help-item">
							<text class="help-label">计算：</text>
							<text class="help-text">实付 = 订单金额 - 减免金额</text>
						</view>
						<view class="help-example">
							<text class="example-title">💰 实例：</text>
							<text class="example-text">订单15元，用"满10减2"券</text>
							<text class="example-text">→ 减2元，实付13元</text>
						</view>
					</view>
					
					<!-- 加时券 -->
					<view class="help-section">
						<view class="help-type-title">
							<view class="type-badge badge-blue">加时券</view>
							<text class="type-desc">延长使用时间</text>
						</view>
						<view class="help-item">
							<text class="help-label">作用：</text>
							<text class="help-text">免费增加X小时，不减钱</text>
						</view>
						<view class="help-item">
							<text class="help-label">示例：</text>
							<text class="help-text">满2小时加时1小时 = 付2h玩3h</text>
						</view>
						<view class="help-item">
							<text class="help-label">计算：</text>
							<text class="help-text">实际时长 = 订单时长 + 加时小时</text>
						</view>
						<view class="help-example">
							<text class="example-title">⏰ 实例：</text>
							<text class="example-text">订单2小时20元，用"满2h加1h"券</text>
							<text class="example-text">→ 支付20元，可玩3小时</text>
						</view>
					</view>
					
					<!-- 对比表格 -->
					<view class="help-section">
						<view class="help-subtitle">📊 三种券对比</view>
						<view class="compare-table">
							<view class="table-row table-header">
								<text class="col-1">类型</text>
								<text class="col-2">支付金额</text>
								<text class="col-3">使用时长</text>
							</view>
							<view class="table-row">
								<text class="col-1">抵扣券</text>
								<text class="col-2 highlight-green">减少 ✓</text>
								<text class="col-3">不变</text>
							</view>
							<view class="table-row">
								<text class="col-1">满减券</text>
								<text class="col-2 highlight-green">减少 ✓</text>
								<text class="col-3">不变</text>
							</view>
							<view class="table-row">
								<text class="col-1">加时券</text>
								<text class="col-2">不变</text>
								<text class="col-3 highlight-blue">增加 ✓</text>
							</view>
						</view>
					</view>
					
					<!-- 使用建议 -->
					<view class="help-section">
						<view class="help-subtitle">💡 创建建议</view>
						<view class="help-tips">
							<view class="tip-item">
								<text class="tip-icon">🎁</text>
								<text class="tip-text">新用户：抵扣券（满1h抵1h，0元体验）</text>
							</view>
							<view class="tip-item">
								<text class="tip-icon">💰</text>
								<text class="tip-text">促销：满减券（满10减2，简单直接）</text>
							</view>
							<view class="tip-item">
								<text class="tip-icon">⏰</text>
								<text class="tip-text">提高时长：加时券（满2h加1h）</text>
							</view>
							<view class="tip-item">
								<text class="tip-icon">⚠️</text>
								<text class="tip-text">包间限制选"不限制"最通用</text>
							</view>
						</view>
					</view>
				</scroll-view>
				<view class="help-footer">
					<button class="help-close-btn" @click="helpShow = false">知道了</button>
				</view>
			</view>
		</u-popup>
	</view>
</template>

<script>
import api from '@/api/index.js'
import { formatDate, getRoomClassName } from '@/utils/format'
import listMixin from '@/mixins/listMixin'

export default {
	mixins: [listMixin],
	data() {
		return {
			store_id: '',
			store_name: '',
			types: [
				{ text: '全部类型', value: '' },
				{ text: '抵扣券', value: 1 },
				{ text: '满减券', value: 2 },
				{ text: '加时券', value: 3 }
			],
			typeIndex: 0,
			type: '',
			isSelect: 0,
			list: [],
			user_id: '',
			activeShow: false,
			couponInfo: {},
			activeName: '',
			num: 0,
			balanceNum: '',
			couponName: '',
			coupon_id: '',
			end_time: '',
			end_date: '',
			giftShow: false,
			giftCouponId: '',
			giftKeyword: '',
			giftUserList: [],
			giftSearched: false,
			helpShow: false
		}
	},
	
	onLoad(options) {
		this.isSelect = options.isSelect ? parseInt(options.isSelect) : 0
		this.user_id = options.user_id ? parseInt(options.user_id) : ''
		this.store_id = options.store_id || uni.getStorageSync('admin_store_id') || ''
		this.store_name = options.store_name ? decodeURIComponent(options.store_name) : ''
		
		if (this.isSelect === 1) {
			uni.setNavigationBarTitle({ title: '赠送优惠券' })
		}
	},
	
	onShow() {
		this.getMainListdata('refresh')
	},
	
	onPullDownRefresh() {
		this.pageNo = 1
		this.canLoadMore = true
		this.list = []
		this.getMainListdata('refresh')
		uni.stopPullDownRefresh()
	},
	
	onReachBottom() {
		if (this.canLoadMore) {
			this.getMainListdata('')
		} else {
			uni.showToast({ title: '我是有底线的...', icon: 'none' })
		}
	},
	
	onShareAppMessage(res) {
		let couponId = res.target ? res.target.dataset.id : ''
		if (couponId) {
			return {
				title: '邀请您领取优惠券~',
				path: '/pages/coupon/active?couponId=' + couponId,
				imageUrl: '/static/image/share_coupon.jpg'
			}
		}
		return {}
	},
	
	methods: {
		typeDropdown(e) {
			this.typeIndex = e.detail.value
			this.type = this.types[this.typeIndex].value
			this.getMainListdata('refresh')
		},
		
		getMainListdata(e) {
			let message = ''
			if (e === 'refresh') {
				message = '正在加载'
				this.pageNo = 1
				this.list = []
			}
			if (message) uni.showLoading({ title: message })
			
			api.post('/member/manager/getCouponPage', {
				pageNo: this.pageNo,
				pageSize: this.pageSize,
				store_id: this.store_id,
				type: this.type,
				room_id: '',
				orderHour: ''
			}).then(res => {
				uni.hideLoading()
				if (res.code === 0) {
					if (res.data.list.length === 0) {
						this.canLoadMore = false
					} else {
						this.list = this.list.concat(res.data.list)
						this.pageNo++
						this.canLoadMore = this.list.length < res.data.total
					}
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			}).catch(() => uni.hideLoading())
		},
		
		getRoomTypeText: getRoomClassName,
		formatDate,
		getStatusText(status) {
			// 优惠券管理端状态：0=停用, 1=正常
			const map = { 0: '停用', 1: '正常' }
			return map[status] !== undefined ? map[status] : '未知'
		},
		
		selectCoupon(id) {
			uni.showModal({
				title: '提示',
				content: '是否确定赠送该优惠券',
				success: (res) => {
					if (res.confirm) {
						api.post('/member/manager/giftCoupon', {
							coupon_id: id,
							user_id: this.user_id
						}).then(res => {
							if (res.code === 0) {
								uni.showToast({ title: '赠送成功' })
								setTimeout(() => uni.navigateBack(), 200)
							} else {
								uni.showModal({ content: res.msg, showCancel: false })
							}
						})
					}
				}
			})
		},
		
		openActive(couponId, couponName) {
			this.couponName = couponName
			this.coupon_id = couponId
			
			api.post('/member/couponActive/getAdminByCouponId?couponId=' + couponId, {}).then(res => {
				if (res.code === 0 && res.data) {
					this.activeName = res.data.active_name
					this.num = res.data.num
					this.balanceNum = res.data.balance_num
					this.end_time = res.data.end_time
				}
				this.activeShow = true
			})
		},
		
		saveActive() {
			if (!this.activeName || !this.num || !this.end_time || this.end_time === '请选择时间') {
				uni.showToast({ title: '请输入完整', icon: 'none' })
				return
			}
			
			api.post('/member/couponActive/saveAdminByCouponId', {
				coupon_id: this.coupon_id,
				active_name: this.activeName,
				num: this.num,
				end_time: this.end_time
			}).then(res => {
				if (res.code === 0) {
					uni.showToast({ title: '操作成功' })
					this.activeShow = false
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			})
		},
		
		cancelActive() {
			this.activeName = ''
			this.num = 0
			this.balanceNum = ''
			this.end_time = ''
			this.activeShow = false
		},
		
		onDateChange(e) {
			this.endDate = e.detail.value
			this.end_time = e.detail.value + ' 23:59:59'
		},
		
		stopActive(couponId) {
			uni.showModal({
				title: '温馨提示',
				content: '结束后优惠券将无法被领取，已领取的仍然有效！您可以通过发起活动重新发起！是否确定结束？',
				success: (res) => {
					if (res.confirm) {
						api.post('/member/couponActive/stopActive?couponId=' + couponId, {}).then(res => {
							if (res.code === 0) {
								uni.showToast({ title: '操作成功', icon: 'success' })
							}
						})
					}
				}
			})
		},
		
		deleteCoupon(couponId) {
			uni.showModal({
				title: '温馨提示',
				content: '您是否确认删除此优惠券？',
				success: (res) => {
					if (res.confirm) {
						api.post('/member/manager/deleteCoupon?couponId=' + couponId, {}).then(res => {
							if (res.code === 0) {
								uni.showToast({ title: '操作成功', icon: 'success' })
								this.getMainListdata('refresh')
							}
						})
					}
				}
			})
		},
		
		toggleStatus(item) {
			const newStatus = item.status == 1 ? 0 : 1
			const text = newStatus === 1 ? '启用' : '停用'
			uni.showModal({
				title: '提示',
				content: `确定${text}「${item.name}」？`,
				success: (res) => {
					if (res.confirm) {
						api.post('/member/manager/saveCouponDetail', {
							coupon_id: item.id,
							status: newStatus
						}).then(res => {
							if (res.code === 0) {
								uni.showToast({ title: text + '成功', icon: 'success' })
								this.getMainListdata('refresh')
							} else {
								uni.showModal({ content: res.msg, showCancel: false })
							}
						})
					}
				}
			})
		},
		
		openGiftDialog(couponId) {
			this.giftCouponId = couponId
			this.giftKeyword = ''
			this.giftUserList = []
			this.giftSearched = false
			this.giftShow = true
		},
		
		searchGiftUser() {
			const keyword = this.giftKeyword.trim()
			if (!keyword || keyword.length < 4) {
				uni.showToast({ title: '请输入至少4位手机号', icon: 'none' })
				return
			}
			api.post('/member/manager/searchUserByPhone', { phone: keyword }).then(res => {
				this.giftSearched = true
				if (res.code === 0 && res.data) {
					this.giftUserList = [res.data]
				} else {
					this.giftUserList = []
					uni.showToast({ title: res.msg || '未找到用户', icon: 'none' })
				}
			}).catch(() => {
				this.giftSearched = true
				this.giftUserList = []
			})
		},
		
		confirmGift(user) {
			uni.showModal({
				title: '提示',
				content: `确定赠送优惠券给「${user.nickname || '用户' + user.id}」？`,
				success: (res) => {
					if (res.confirm) {
						api.post('/member/manager/giftCoupon', {
							coupon_id: this.giftCouponId,
							user_id: user.id
						}).then(res => {
							if (res.code === 0) {
								uni.showToast({ title: '赠送成功' })
								this.giftShow = false
							} else {
								uni.showModal({ content: res.msg, showCancel: false })
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
.container { padding: 100rpx 16rpx 140rpx; }
.list { padding: 16rpx 0; display: inline-block; width: 100%; }
.list .item { border-radius: 20rpx; margin-bottom: 16rpx; padding: 10rpx 30rpx; position: relative; overflow: hidden; }
.gradient-bg { position: absolute; width: 100%; height: 100%; left: 0; top: 0; z-index: 0; border-radius: 20rpx; }
.gradient-red { background: linear-gradient(135deg, #f04b59 0%, #f06573 50%, #ff8a9b 70%); }
.gradient-yellow { background: linear-gradient(135deg, #ff8c42 0%, #ffa726 30%, #ffcc02 100%); }
.gradient-blue { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #4facfe 100%); }
.list .item .info { display: flex; align-items: center; justify-content: space-between; color: #fff; font-size: 26rpx; line-height: 40rpx; min-height: 260rpx; padding: 20rpx 0 0 0; position: relative; z-index: 1; }
.list .item .info .name { font-size: 34rpx; font-weight: 600; margin-bottom: 10rpx; }
.list .item .info .text { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.list .item .info .expired { color: #b10808; }
.list .item .info .normal { color: #fff; }
.list .item .info .btns { display: flex; flex-direction: column; }
.list .item .info .btns .btn { margin-top: 20rpx; padding: 0 30rpx; text-align: center; height: 70rpx; line-height: 70rpx; font-size: 28rpx; border-radius: 10rpx; color: #fff; }
.list .item .info .btns .yellow { background-color: #f1ac2cd7; font-weight: 600; }
.list .item .info .btns .primary { background-color: #27c0fdf8; font-weight: 600; }
.list .item .info .btns .btn-stop { background-color: #ff4d4f; font-weight: 600; }
.list .item .info .btns .btn-start { background-color: #52c41a; font-weight: 600; }
.list .item .buttom { display: flex; justify-content: space-around; padding: 10rpx 0; position: relative; z-index: 1; }
.list .item .buttom .btn { background-color: var(--main-color, #1aad19); font-size: 24rpx; color: #fff; border-radius: 10rpx; padding: 0 20rpx; height: 60rpx; line-height: 60rpx; }
.bt { background-color: #fff; }
.bt .bottom { z-index: 2; width: 430rpx; height: 90rpx; text-align: center; line-height: 90rpx; position: fixed; bottom: 30rpx; left: 50%; transform: translateX(-50%); border-radius: 50rpx; font-size: 36rpx; color: #fff; background: var(--main-color, #1aad19); }

.dialog { width: 600rpx; padding: 30rpx; }
.dialog-title { text-align: center; font-size: 32rpx; font-weight: bold; margin-bottom: 30rpx; }
.dialog-content { padding: 20rpx 0; }
.field-item { display: flex; align-items: center; padding: 15rpx 0; border-bottom: 1rpx solid #eee; }
.field-item .label { width: 180rpx; font-size: 28rpx; color: #333; flex-shrink: 0; }
.field-item .value { flex: 1; font-size: 28rpx; color: #666; }
.field-item input { flex: 1; font-size: 28rpx; height: 60rpx; padding: 0 10rpx; }
.picker-value { font-size: 28rpx; color: #666; }
.dialog-footer { display: flex; justify-content: space-around; margin-top: 30rpx; }
.dialog-footer button { width: 200rpx; height: 70rpx; line-height: 70rpx; font-size: 28rpx; border-radius: 10rpx; }
.btn-cancel { background: #f5f5f5; color: #666; }
.btn-confirm { background: var(--main-color, #1aad19); color: #fff; }

.gift-dialog {
	position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
	width: 620rpx; background: #fff; border-radius: 16rpx; z-index: 101; overflow: hidden;
}
.gift-dialog .dialog-title { text-align: center; padding: 30rpx; font-size: 32rpx; font-weight: bold; border-bottom: 1rpx solid #eee; }
.gift-dialog .dialog-content { padding: 30rpx; }
.search-bar { display: flex; gap: 16rpx; margin-bottom: 20rpx; }
.search-bar input { flex: 1; height: 70rpx; padding: 0 20rpx; border: 1rpx solid #ddd; border-radius: 8rpx; font-size: 28rpx; }
.search-btn { width: 140rpx; height: 70rpx; line-height: 70rpx; text-align: center; background: var(--main-color, #1aad19); color: #fff; border-radius: 8rpx; font-size: 28rpx; }
.user-list { max-height: 400rpx; }
.user-item { display: flex; justify-content: space-between; align-items: center; padding: 20rpx 16rpx; border-bottom: 1rpx solid #f0f0f0; }
.user-info { display: flex; flex-direction: column; }
.user-info .nickname { font-size: 28rpx; color: #333; }
.user-info .phone { font-size: 24rpx; color: #999; margin-top: 4rpx; }
.gift-btn-text { font-size: 26rpx; color: var(--main-color, #1aad19); font-weight: 500; }
.no-user { text-align: center; color: #999; padding: 40rpx 0; font-size: 28rpx; }
.dialog-footer-single { border-top: 1rpx solid #eee; padding: 20rpx 30rpx; }
.dialog-footer-single .btn-cancel { width: 100%; height: 70rpx; line-height: 70rpx; text-align: center; background: #f5f5f5; color: #666; border-radius: 8rpx; font-size: 28rpx; }

/* 使用说明按钮 */
.help-btn {
	display: flex;
	align-items: center;
	padding: 10rpx 20rpx;
	background: #f0f9ff;
	border: 1rpx solid #3b82f6;
	border-radius: 30rpx;
	color: #3b82f6;
	margin-left: auto;
}

/* 使用说明弹窗 */
.help-dialog {
	width: 680rpx;
	max-height: 80vh;
	background: #fff;
	border-radius: 16rpx;
	overflow: hidden;
}

.help-title {
	text-align: center;
	font-size: 34rpx;
	font-weight: bold;
	padding: 30rpx;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: #fff;
}

.help-content {
	max-height: 60vh;
	padding: 20rpx 30rpx;
}

.help-section {
	margin-bottom: 30rpx;
	padding-bottom: 30rpx;
	border-bottom: 1rpx solid #f0f0f0;
}

.help-section:last-child {
	border-bottom: none;
}

.help-type-title {
	display: flex;
	align-items: center;
	margin-bottom: 20rpx;
}

.type-badge {
	padding: 8rpx 20rpx;
	border-radius: 30rpx;
	font-size: 24rpx;
	font-weight: bold;
	color: #fff;
	margin-right: 16rpx;
}

.badge-red {
	background: linear-gradient(135deg, #f04b59 0%, #ff8a9b 100%);
}

.badge-yellow {
	background: linear-gradient(135deg, #ff8c42 0%, #ffcc02 100%);
}

.badge-blue {
	background: linear-gradient(135deg, #1e3c72 0%, #4facfe 100%);
}

.type-desc {
	font-size: 26rpx;
	color: #666;
}

.help-item {
	display: flex;
	margin-bottom: 12rpx;
	line-height: 1.6;
}

.help-label {
	font-size: 26rpx;
	color: #333;
	font-weight: 500;
	min-width: 100rpx;
	flex-shrink: 0;
}

.help-text {
	font-size: 26rpx;
	color: #666;
	flex: 1;
}

.help-example {
	background: #f8f9fa;
	padding: 20rpx;
	border-radius: 12rpx;
	margin-top: 16rpx;
	border-left: 4rpx solid #3b82f6;
}

.example-title {
	display: block;
	font-size: 26rpx;
	font-weight: bold;
	color: #3b82f6;
	margin-bottom: 8rpx;
}

.example-text {
	display: block;
	font-size: 24rpx;
	color: #666;
	line-height: 1.8;
	margin-bottom: 4rpx;
}

.help-subtitle {
	font-size: 28rpx;
	font-weight: bold;
	color: #333;
	margin-bottom: 20rpx;
	padding-left: 16rpx;
	border-left: 4rpx solid #3b82f6;
}

/* 对比表格 */
.compare-table {
	background: #fff;
	border-radius: 12rpx;
	overflow: hidden;
	border: 1rpx solid #e5e7eb;
}

.table-row {
	display: flex;
	align-items: center;
	padding: 16rpx 20rpx;
	border-bottom: 1rpx solid #f0f0f0;
}

.table-row:last-child {
	border-bottom: none;
}

.table-header {
	background: #f8f9fa;
	font-weight: bold;
}

.col-1 {
	width: 30%;
	font-size: 24rpx;
	color: #333;
}

.col-2, .col-3 {
	width: 35%;
	font-size: 24rpx;
	color: #666;
	text-align: center;
}

.highlight-green {
	color: #10b981;
	font-weight: 500;
}

.highlight-blue {
	color: #3b82f6;
	font-weight: 500;
}

/* 使用建议 */
.help-tips {
	background: #fffbeb;
	padding: 20rpx;
	border-radius: 12rpx;
	border: 1rpx solid #fbbf24;
}

.tip-item {
	display: flex;
	align-items: flex-start;
	margin-bottom: 12rpx;
	line-height: 1.6;
}

.tip-item:last-child {
	margin-bottom: 0;
}

.tip-icon {
	font-size: 28rpx;
	margin-right: 12rpx;
	flex-shrink: 0;
}

.tip-text {
	font-size: 24rpx;
	color: #92400e;
	flex: 1;
}

.help-footer {
	padding: 20rpx 30rpx;
	border-top: 1rpx solid #f0f0f0;
}

.help-close-btn {
	width: 100%;
	height: 80rpx;
	line-height: 80rpx;
	text-align: center;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: #fff;
	border-radius: 40rpx;
	font-size: 30rpx;
	font-weight: 500;
}
</style>
