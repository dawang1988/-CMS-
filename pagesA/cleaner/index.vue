<template>
	<view class="page" :style="themeStyle">
		<view class="container">
			<block v-if="list.length">
				<view class="list">
					<view class="item" v-for="(item, idx) in list" :key="idx">
						<view class="top">
							<view class="left">
								<view class="avatar">
									<image :src="item.avatar || '/static/logo.png'" mode="aspectFill"></image>
								</view>
								<view class="nick">{{item.nickname || '未设置'}}</view>
							</view>
							<view class="right">
								<view class="info-box">
									<view class="info">门店：{{item.store_name}}</view>
									<view class="info">姓名：{{item.name}}</view>
									<view class="info">手机号：{{item.phone}}</view>
									<view class="info">总收入：{{item.total_money || 0}}元</view>
									<view class="info">已完成：{{item.finish_count || 0}}单，已结算：{{item.settlement_count || 0}}单</view>
								</view>
								<view class="btns">
									<view class="btn green" @tap="taskSettle(item)">任务结算</view>
									<view class="btn red" @tap="deleteCleaner(item)">删除</view>
								</view>
							</view>
						</view>
					</view>
				</view>
				<view v-if="canLoadMore" class="note-more">下拉刷新查看更多...</view>
			</block>
			<block v-else>
				<view class="nodata-list">暂无数据</view>
			</block>
		</view>
		<!-- 底部添加按钮 -->
		<view class="bottom-btn" @tap="showAddDialog">添加保洁员</view>
		<!-- 添加弹窗 -->
		<view class="dialog-mask" v-if="showDialog" @tap="showDialog = false">
			<view class="dialog-box" @tap.stop>
				<view class="dialog-title">添加保洁员</view>
				<view class="dialog-body">
					<view class="dialog-line">
						<text class="dialog-label">门店：</text>
						<text class="dialog-value">{{storeName}}</text>
					</view>
					<view class="dialog-line">
						<text class="dialog-label">姓名：</text>
						<input class="dialog-input" v-model="addName" placeholder="请输入" />
					</view>
					<view class="dialog-line">
						<text class="dialog-label">手机号：</text>
						<input class="dialog-input" v-model="addMobile" placeholder="请输入" type="number" />
					</view>
				</view>
				<view class="dialog-footer">
					<view class="dialog-btn cancel" @tap="cancelAdd">取消</view>
					<view class="dialog-btn confirm" @tap="submitAdd">确定</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
import http from '@/utils/http.js'

export default {
	data() {
		return {
			store_id: '',
			storeName: '',
			pageNo: 1,
			pageSize: 10,
			canLoadMore: true,
			list: [],
			showDialog: false,
			addName: '',
			addMobile: ''
		}
	},
	onLoad(options) {
		this.store_id = options.store_id || ''
		this.store_name = options.store_name || ''
	},
	onShow() {
		this.getList('refresh')
	},
	onPullDownRefresh() {
		this.getList('refresh')
		uni.stopPullDownRefresh()
	},
	onReachBottom() {
		if (this.canLoadMore) {
			this.getList()
		} else {
			uni.showToast({ title: '我是有底线的...', icon: 'none' })
		}
	},
	methods: {
		getList(type) {
			if (type === 'refresh') {
				this.list = []
				this.canLoadMore = true
				this.pageNo = 1
			}
			http.post('/member/manager/getClearUserPage', {
				pageNo: this.pageNo,
				pageSize: this.pageSize,
				store_id: this.store_id
			}).then(res => {
				if (res.code === 0) {
					const data = res.data
					if (!data.list || data.list.length === 0) {
						this.canLoadMore = false
					} else {
						this.list = this.list.concat(data.list)
						this.pageNo++
						this.canLoadMore = this.list.length < data.total
					}
				} else {
					uni.showToast({ title: res.msg || '加载失败', icon: 'none' })
				}
			})
		},
		deleteCleaner(item) {
			uni.showModal({
				title: '提示',
				content: '员工在' + item.store_name + '完成的任务需全部结算后才允许删除！删除后该员工将无法在此门店接单！请确认！',
				confirmText: '确认删除',
				success: (res) => {
					if (res.confirm) {
						http.post('/member/manager/deleteClearUser/' + item.store_id + '/' + item.user_id).then(res2 => {
							if (res2.code === 0) {
								uni.showToast({ title: '删除成功' })
								this.getList('refresh')
							} else {
								uni.showToast({ title: res2.msg || '删除失败', icon: 'none' })
							}
						})
					}
				}
			})
		},
		showAddDialog() { this.showDialog = true },
		cancelAdd() {
			this.addName = ''
			this.addMobile = ''
			this.showDialog = false
		},
		submitAdd() {
			if (!this.addName || !this.addMobile) {
				return uni.showToast({ title: '请输入完整', icon: 'none' })
			}
			http.post('/member/manager/saveClearUser', {
				store_id: this.store_id,
				name: this.addName,
				mobile: this.addMobile
			}).then(res => {
				if (res.code === 0) {
					uni.showToast({ title: '保存成功' })
					this.showDialog = false
					this.addName = ''
					this.addMobile = ''
					this.getList('refresh')
				} else {
					uni.showToast({ title: res.msg || '保存失败', icon: 'none' })
				}
			})
		},
		taskSettle(item) {
			uni.navigateTo({
				url: '/pagesA/task/settle?info=' + encodeURIComponent(JSON.stringify(item))
			})
		}
	}
}
</script>

<style lang="scss" scoped>
.page { min-height: 100vh; background: #f5f5f5; padding-bottom: 140rpx; }
.container { padding: 16rpx 16rpx 40rpx; }
.list { padding-top: 5rpx; }
.item { background: #fff; border-radius: 12rpx; padding: 20rpx; margin-bottom: 16rpx; }
.top { display: flex; }
.left { width: 150rpx; display: flex; flex-direction: column; align-items: center; }
.avatar { width: 100rpx; height: 100rpx; border-radius: 50%; overflow: hidden; }
.avatar image { width: 100%; height: 100%; }
.nick { font-size: 24rpx; color: #666; margin-top: 10rpx; text-align: center; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; width: 150rpx; }
.right { flex: 1; display: flex; justify-content: space-between; }
.info-box { flex: 1; }
.info { font-size: 26rpx; color: #333; line-height: 1.8; }
.btns { width: 160rpx; display: flex; flex-direction: column; justify-content: flex-end; }
.btn { width: 160rpx; height: 60rpx; line-height: 60rpx; text-align: center; border-radius: 30rpx; font-size: 26rpx; margin-top: 16rpx; }
.btn.green { background: #07c160; color: #fff; }
.btn.red { background: #ee0a24; color: #fff; }

.bottom-btn { position: fixed; bottom: 30rpx; left: 160rpx; width: 430rpx; height: 90rpx; line-height: 90rpx; text-align: center; background: #007aff; color: #fff; border-radius: 50rpx; font-size: 36rpx; }
.dialog-mask { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 999; }
.dialog-box { width: 600rpx; background: #fff; border-radius: 16rpx; overflow: hidden; }
.dialog-title { text-align: center; font-size: 32rpx; font-weight: bold; padding: 30rpx 0 20rpx; }
.dialog-body { padding: 0 30rpx 20rpx; }
.dialog-line { display: flex; align-items: center; padding: 16rpx 0; border-bottom: 1rpx solid #f0f0f0; }
.dialog-label { width: 140rpx; font-size: 28rpx; color: #333; }
.dialog-value { font-size: 28rpx; color: #666; }
.dialog-input { flex: 1; height: 60rpx; font-size: 28rpx; border: 1rpx solid #eee; border-radius: 8rpx; padding: 0 16rpx; }
.dialog-footer { display: flex; border-top: 1rpx solid #eee; }
.dialog-btn { flex: 1; height: 90rpx; line-height: 90rpx; text-align: center; font-size: 30rpx; }
.dialog-btn.cancel { color: #666; }
.dialog-btn.confirm { color: #007aff; }
</style>