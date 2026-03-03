<template>
	<view class="page" :style="themeStyle">
		<!-- 搜索栏 -->
		<view class="search">
			<view class="search-bar-inner">
				<input class="search-input" v-model="name" placeholder="请输入昵称、手机号" @confirm="search" />
				<view class="search-btn" @click="search">搜索</view>
			</view>
		</view>
		
		<view class="container">
			<block v-if="MainList.length">
				<!-- 列表 -->
				<view class="list">
					<view class="item" v-for="(item, index) in MainList" :key="index">
						<view class="top">
							<view class="left">
								<view class="img">
									<image :src="item.avatar || '/static/logo.png'" mode="widthFix"></image>
								</view>
								<view class="nick">{{item.nickname}}</view>
							</view>
							<view class="right">
								<view class="info">手机号：{{item.phone || '未绑定'}}</view>
								<view class="info">VIP等级：{{item.vip_name || '普通会员'}}</view>
								<view class="info">注册时间：{{item.create_time}}</view>
								<view class="info">累计积分：<text class="score">{{item.score}}</text></view>
							</view>
						</view>
						<view class="button-wrapper">
							<button class="btn-more" @click="edit(item)">修改</button>
							<view class="delete-icon" @click="deleteVip(item.id)">
								<text style="font-size:40rpx;color:#ff0000;">🗑</text>
							</view>
						</view>
					</view>
				</view>
				<view v-if="canLoadMore" class="noteMore">下拉刷新查看更多...</view>
			</block>
			<block v-else>
				<view class="nodata-list">暂无数据</view>
			</block>
		</view>
		
		<!-- 底部按钮 -->
		<view class="bottom bg-primary" @click="addVip">添加会员</view>
		
		<!-- 修改会员弹窗 -->
		<u-popup :show="showEdit" mode="center" :round="10" @close="cancelEdit">
			<view class="dialog">
				<view class="dialog-title">修改会员</view>
				<view class="dialog-content">
					<view class="field-item">
						<text class="label">用户昵称：</text>
						<text class="value">{{member.nickname}}</text>
					</view>
					<view class="field-item">
						<text class="label">手机号：</text>
						<text class="value">{{member.phone}}</text>
					</view>
					<view class="field-item">
						<text class="label">原等级：</text>
						<text class="value">{{member.vip_name}}</text>
					</view>
					<view class="field-item">
						<text class="label">新等级：</text>
						<picker @change="bindVipChange" :value="vipIndex" :range="vipList" range-key="text">
							<view class="picker">
								<input type="text" disabled placeholder="请选择新会员等级" :value="vipList[vipIndex] ? vipList[vipIndex].text : ''" />
								<text style="font-size:24rpx;">▼</text>
							</view>
						</picker>
					</view>
					<view class="tip">强制修改用户的会员等级</view>
				</view>
				<view class="dialog-footer">
					<button class="btn-cancel" @click="cancelEdit">取消</button>
					<button class="btn-confirm" @click="confirmEdit">确定</button>
				</view>
			</view>
		</u-popup>
		
		<!-- 添加会员弹窗 -->
		<u-popup :show="showAdd" mode="center" :round="10" @close="cancelAdd">
			<view class="dialog">
				<view class="dialog-title">添加会员</view>
				<view class="dialog-content">
					<view class="field-item">
						<text class="label">手机号：</text>
						<input type="number" v-model="mobile" placeholder="请输入手机号" maxlength="11" />
					</view>
					<view class="field-item">
						<text class="label">会员等级：</text>
						<picker @change="bindVipChange" :value="vipIndex" :range="vipList" range-key="text">
							<view class="picker">
								<input type="text" disabled placeholder="请选择会员等级" :value="vipList[vipIndex] ? vipList[vipIndex].text : ''" />
								<text style="font-size:24rpx;">▼</text>
							</view>
						</picker>
					</view>
					<view class="tip">强制修改用户的会员等级</view>
				</view>
				<view class="dialog-footer">
					<button class="btn-cancel" @click="cancelAdd">取消</button>
					<button class="btn-confirm" @click="confirmAdd">确定</button>
				</view>
			</view>
		</u-popup>
	</view>
</template>

<script>
import api from '@/api/index.js'
import listMixin from '@/mixins/listMixin'

export default {
	mixins: [listMixin],
	data() {
		return {
			MainList: [],
			name: '',
			showEdit: false,
			showAdd: false,
			store_id: '',
			member: {},
			mobile: '',
			vipList: [],
			vipIndex: -1
		}
	},
	
	onLoad(options) {
		// 优先从参数获取，否则从全局存储获取
		this.store_id = options.store_id || uni.getStorageSync('admin_store_id') || uni.getStorageSync('global_store_id') || ''
		if (this.store_id) {
			this.getListData('refresh')
			this.getVipList()
		} else {
			uni.showToast({ title: '请先选择门店', icon: 'none' })
		}
	},
	
	onPullDownRefresh() {
		this.MainList = []
		this.canLoadMore = true
		this.pageNo = 1
		this.getListData('refresh')
		uni.stopPullDownRefresh()
	},
	
	onReachBottom() {
		if (this.canLoadMore) {
			this.getListData('')
		} else {
			uni.showToast({ title: '我是有底线的...', icon: 'none' })
		}
	},
	
	methods: {
		getListData(e) {
			let message = ''
			if (e === 'refresh') {
				message = '正在加载'
				this.pageNo = 1
				this.MainList = []
			}
			if (message) uni.showLoading({ title: message })
			
			api.post('/member/manager/getVipPage', {
				store_id: this.store_id,
				pageNo: this.pageNo,
				pageSize: this.pageSize,
				name: this.name
			}).then(res => {
				uni.hideLoading()
				if (res.code === 0) {
					if (res.data.list.length === 0) {
						this.canLoadMore = false
					} else {
						this.MainList = this.MainList.concat(res.data.list)
						this.pageNo++
						this.canLoadMore = this.MainList.length < res.data.total
					}
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			}).catch(() => uni.hideLoading())
		},
		
		search() { this.getListData('refresh') },
		
		getVipList() {
			api.post('/member/store/getVipConfig/' + this.store_id, {}).then(res => {
				if (res.code === 0) {
					this.vipList = res.data.map(it => ({ text: it.vip_name, value: it.vip_level }))
				}
			})
		},
		
		edit(item) {
			this.member = item
			this.vipIndex = -1
			this.showEdit = true
		},
		
		addVip() {
			this.mobile = ''
			this.vipIndex = -1
			this.showAdd = true
		},
		
		confirmEdit() {
			if (this.vipIndex < 0) {
				uni.showToast({ title: '未选择等级', icon: 'none' })
				return
			}
			api.post('/member/store/editMemberVip', {
				user_id: this.member.id,
				store_id: this.store_id,
				vip_level: this.vipList[this.vipIndex].value
			}).then(res => {
				if (res.code === 0) {
					uni.showToast({ title: '操作成功' })
					this.showEdit = false
					this.getListData('refresh')
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			})
		},
		
		confirmAdd() {
			if (this.vipIndex < 0) {
				uni.showToast({ title: '未选择等级', icon: 'none' })
				return
			}
			api.post('/member/store/addMemberVip', {
				mobile: this.mobile,
				store_id: this.store_id,
				vip_level: this.vipList[this.vipIndex].value
			}).then(res => {
				if (res.code === 0) {
					uni.showToast({ title: '操作成功' })
					this.showAdd = false
					this.getListData('refresh')
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			})
		},
		
		cancelEdit() { this.member = {}; this.showEdit = false },
		cancelAdd() { this.mobile = ''; this.showAdd = false },
		bindVipChange(e) { this.vipIndex = e.detail.value },
		
		deleteVip(id) {
			uni.showModal({
				title: '提示',
				content: '您是否确认删除此会员信息？删除后将清空所有积分，不可恢复！',
				success: (res) => {
					if (res.confirm) {
						api.post('/member/manager/deleteVip', {
							user_id: id,
							store_id: this.store_id
						}).then(res => {
							if (res.code === 0) {
								uni.showToast({ title: '操作成功' })
								this.getListData('refresh')
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
.page { min-height: 100vh; background: #f5f5f5; padding-bottom: 140rpx; }
.search { background: #fff; width: 100%; position: fixed; z-index: 10; top: 0; left: 0; padding: 10rpx 20rpx; border-bottom: 1rpx solid #ddd; box-sizing: border-box; }
.container { padding: 100rpx 16rpx 20rpx; }
.list { padding-top: 30rpx; }
.list .item { margin-bottom: 20rpx; padding: 20rpx; background: #fff; border-radius: 12rpx; position: relative; }
.list .item .top { width: 100%; display: flex; justify-content: space-between; font-size: 26rpx; align-items: flex-start; }
.list .item .left { width: 200rpx; display: flex; flex-direction: column; align-items: center; }
.list .item .img { width: 160rpx; height: 160rpx; overflow: hidden; background: #eee; border-radius: 10rpx; }
.list .item .img image { width: 100%; height: 100%; }
.list .item .nick { text-align: center; margin-top: 20rpx; font-size: 26rpx; }
.list .item .right { flex: 1; padding-left: 20rpx; }
.list .item .info { line-height: 50rpx; font-size: 26rpx; color: #333; }
.list .item .info .score { color: #ff6600; font-weight: bold; }
.button-wrapper { position: absolute; bottom: 20rpx; right: 20rpx; display: flex; align-items: center; }
.btn-more { background: var(--main-color, #1aad19); height: 50rpx; line-height: 50rpx; font-size: 24rpx; color: #fff; padding: 0 20rpx; border-radius: 6rpx; margin-right: 20rpx; }
.delete-icon { padding: 10rpx; }
.bottom { width: 430rpx; height: 90rpx; text-align: center; line-height: 90rpx; position: fixed; bottom: 30rpx; left: 50%; transform: translateX(-50%); border-radius: 50rpx; font-size: 36rpx; color: #fff; background: var(--main-color, #1aad19); }

.dialog { width: 600rpx; padding: 30rpx; }
.dialog-title { text-align: center; font-size: 32rpx; font-weight: bold; margin-bottom: 30rpx; }
.dialog-content { padding: 20rpx 0; }
.field-item { display: flex; align-items: center; padding: 15rpx 0; border-bottom: 1rpx solid #eee; }
.field-item .label { width: 160rpx; font-size: 28rpx; color: #333; }
.field-item .value { flex: 1; font-size: 28rpx; color: #666; }
.field-item input { flex: 1; font-size: 28rpx; height: 60rpx; padding: 0 10rpx; }
.field-item .picker { flex: 1; display: flex; align-items: center; }
.field-item .picker input { flex: 1; }
.tip { font-size: 24rpx; color: #ff0000; text-align: center; margin-top: 20rpx; }
.dialog-footer { display: flex; justify-content: space-around; margin-top: 30rpx; }
.dialog-footer button { width: 200rpx; height: 70rpx; line-height: 70rpx; font-size: 28rpx; border-radius: 10rpx; }
.btn-cancel { background: #f5f5f5; color: #666; }
.btn-confirm { background: var(--main-color, #1aad19); color: #fff; }

.search-bar-inner { display: flex; align-items: center; background: #fff; padding: 16rpx 20rpx; }
.search-input { flex: 1; height: 64rpx; background: #f5f5f5; border-radius: 32rpx; padding: 0 24rpx; font-size: 26rpx; }
.search-btn { margin-left: 16rpx; padding: 0 24rpx; height: 64rpx; line-height: 64rpx; background: var(--main-color, #5AAB6E); color: #fff; border-radius: 32rpx; font-size: 26rpx; }
</style>
