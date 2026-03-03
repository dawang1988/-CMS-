<template>
	<view class="page" :style="themeStyle">
		<scroll-view scroll-y="true" class="scroll-container" @scrolltolower="onReachBottom">
			<view class="container">
				<view class="lists">
					<view class="list">
						<block v-if="vipBlacklist.length > 0">
							<view v-for="(item, index) in vipBlacklist" :key="index">
								<view class="store-card">
									<view class="item">
										<view class="image-container">
											<image class="store-card__image" :src="item.avatar || '/static/logo.png'" mode="aspectFill"></image>
										</view>
										<view class="user-info">
											<view class="top-info">
												{{item.nickname}}
												<text class="mobile">{{item.phone}}</text>
												<button class="remove" @click="showRemove(item.id)">移除</button>
											</view>
											<view class="vip-blacklist-item">
												<text>添加时间：{{item.addTimeFormatted}}</text>
											</view>
										</view>
									</view>
								</view>
							</view>
						</block>
						<block v-else>
							<view class="nodata-wrapper">
								<view class="noStoreInfo">
									<image class="noStore-image" src="/static/image/no-blackList.png" mode="scaleToFill" />
									<text>暂无黑名单</text>
								</view>
							</view>
						</block>
					</view>
				</view>
			</view>
		</scroll-view>
		
		<!-- 底部按钮 -->
		<button class="bottom bg-primary" @click="showAdd">添加黑名单</button>
		
		<!-- 添加黑名单弹窗 -->
		<u-popup :show="showAddBlack" mode="center" :round="10" @close="closeAddDialog">
			<view class="dialog">
				<view class="dialog-title">添加黑名单</view>
				<view class="dialog-content">
					<view class="dialog-item">
						<label>手机号：</label>
						<input v-model="userPhone" type="number" maxlength="11" placeholder="请输入" />
					</view>
				</view>
				<view class="dialog-footer">
					<button class="btn-cancel" @click="closeAddDialog">取消</button>
					<button class="btn-confirm" @click="addBlackList">确认</button>
				</view>
			</view>
		</u-popup>
		
		<!-- 移除确认弹窗 -->
		<u-popup :show="showremove" mode="center" :round="10" @close="closeRemoveDialog">
			<view class="dialog">
				<view class="dialog-title">提示</view>
				<view class="dialog-content">
					<view class="dialog-item center">
						<text>您确定要将该会员移出黑名单吗</text>
					</view>
				</view>
				<view class="dialog-footer">
					<button class="btn-cancel" @click="closeRemoveDialog">取消</button>
					<button class="btn-confirm" @click="remove">确认</button>
				</view>
			</view>
		</u-popup>
	</view>
</template>

<script>
import api from '@/api/index.js'

export default {
	data() {
		return {
			store_id: '',
			showAddBlack: false,
			showremove: false,
			userPhone: '',
			vipBlacklist: [],
			id: '',
			currentPage: 1,
			hasMore: false
		}
	},
	
	onLoad(options) {
		this.store_id = options.store_id || ''
		this.getVipBlacklist(true)
	},
	
	methods: {
		getVipBlacklist(refresh = false) {
			let currentPage = refresh ? 1 : this.currentPage + 1
			this.currentPage = currentPage
			
			api.post('/member/store/vip/blacklist', {
				store_id: this.store_id,
				pageSize: 10,
				pageNo: currentPage
			}).then(res => {
				if (res.code === 0) {
					const vipBlacklistFormatted = res.data.list.map(item => ({
						...item,
						addTimeFormatted: this.formatDate(item.add_time)
					}))
					
					if (!refresh) {
						this.vipBlacklist = this.vipBlacklist.concat(vipBlacklistFormatted)
						this.hasMore = this.currentPage * 10 < res.data.total
					} else {
						this.vipBlacklist = vipBlacklistFormatted
						this.hasMore = this.currentPage * 10 < res.data.total
					}
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			})
		},
		
		onReachBottom() {
			if (this.hasMore) {
				this.getVipBlacklist(false)
			} else {
				uni.showToast({ title: '没有更多了...', icon: 'none' })
			}
		},
		
		showAdd() {
			this.showAddBlack = true
		},
		
		showRemove(id) {
			this.id = id
			this.showremove = true
		},
		
		closeAddDialog() {
			this.showAddBlack = false
			this.userPhone = ''
		},
		
		closeRemoveDialog() {
			this.showremove = false
			this.id = ''
		},
		
		addBlackList() {
			if (!this.userPhone || this.userPhone.length < 11) {
				uni.showToast({ title: '手机号格式错误', icon: 'error' })
				return
			}
			
			api.post('/member/store/addBlackList', {
				phone: this.userPhone,
				store_id: this.store_id
			}).then(res => {
				if (res.code === 0) {
					uni.showToast({ title: '添加成功' })
					this.userPhone = ''
					this.showAddBlack = false
					this.getVipBlacklist(true)
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			})
		},
		
		remove() {
			api.post('/member/store/remove/' + this.id, {}).then(res => {
				if (res.code === 0) {
					uni.showToast({ title: '移除成功' })
					this.id = ''
					this.showremove = false
					this.getVipBlacklist(true)
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			})
		},
		
		formatDate(timestamp) {
			if (!timestamp) return ''
			const date = new Date(timestamp)
			const year = date.getFullYear()
			const month = String(date.getMonth() + 1).padStart(2, '0')
			const day = String(date.getDate()).padStart(2, '0')
			const hours = String(date.getHours()).padStart(2, '0')
			const minutes = String(date.getMinutes()).padStart(2, '0')
			const seconds = String(date.getSeconds()).padStart(2, '0')
			return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`
		}
	}
}
</script>

<style lang="scss" scoped>
.page { min-height: 100vh; background: #f5f5f5; padding-bottom: 140rpx; }
.scroll-container { height: calc(100vh - 140rpx); }
.container { padding: 20rpx; }
.store-card { background: #fff; border-radius: 15rpx; margin-bottom: 20rpx; padding: 20rpx; }
.store-card .item { display: flex; align-items: center; }
.image-container { width: 120rpx; height: 120rpx; margin-right: 20rpx; }
.store-card__image { width: 100%; height: 100%; border-radius: 10rpx; }
.user-info { flex: 1; }
.top-info { display: flex; align-items: center; font-size: 28rpx; font-weight: bold; margin-bottom: 10rpx; }
.top-info .mobile { margin-left: 20rpx; font-weight: normal; color: #666; }
.top-info .remove { margin-left: auto; background: #ff4d4f; color: #fff; font-size: 24rpx; padding: 5rpx 20rpx; border-radius: 8rpx; }
.vip-blacklist-item { font-size: 24rpx; color: #999; }
.nodata-wrapper { display: flex; height: 60vh; width: 100%; justify-content: center; align-items: center; }
.noStoreInfo { display: flex; flex-direction: column; align-items: center; }
.noStore-image { width: 200rpx; height: 200rpx; margin-bottom: 20rpx; }
.noStoreInfo text { font-size: 28rpx; color: #999; }
.bottom { width: 430rpx; height: 90rpx; text-align: center; line-height: 90rpx; position: fixed; bottom: 30rpx; left: 50%; transform: translateX(-50%); border-radius: 50rpx; font-size: 36rpx; color: #fff; background: var(--main-color, #1aad19); }
.dialog { width: 600rpx; padding: 30rpx; }
.dialog-title { text-align: center; font-size: 32rpx; font-weight: bold; margin-bottom: 30rpx; }
.dialog-content { padding: 20rpx 0; }
.dialog-item { display: flex; align-items: center; padding: 15rpx 0; }
.dialog-item.center { justify-content: center; }
.dialog-item label { width: 140rpx; font-size: 28rpx; }
.dialog-item input { flex: 1; border: 1rpx solid var(--main-color, #1aad19); border-radius: 8rpx; padding: 10rpx 15rpx; font-size: 28rpx; }
.dialog-footer { display: flex; justify-content: space-around; margin-top: 30rpx; }
.dialog-footer button { width: 200rpx; height: 70rpx; line-height: 70rpx; font-size: 28rpx; border-radius: 10rpx; }
.btn-cancel { background: #f5f5f5; color: #666; }
.btn-confirm { background: var(--main-color, #1aad19); color: #fff; }
</style>
