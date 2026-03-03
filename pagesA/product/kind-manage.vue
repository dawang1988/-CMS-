<template>
	<view class="page" :style="themeStyle">
		<scroll-view scroll-y="true" class="scroll-container">
			<view class="container">
				<view class="lists">
					<view class="list">
						<block v-if="kindList.length > 0">
							<view v-for="(item, index) in kindList" :key="index">
								<view class="store-card">
									<view class="item">
										<view class="user-info">
											<view class="vip-blacklist-item">
												<view class="productPrice">
													<text>{{item.name}}</text>
												</view>
												<view class="button-end">
													<button size="mini" class="proButton delete-btn" @click="deleteKind(item.id)">删除</button>
													<button size="mini" class="proButton edit-btn" @click="goupdate(index)">修改</button>
												</view>
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
									<text>暂无商品分类</text>
								</view>
							</view>
						</block>
					</view>
				</view>
			</view>
		</scroll-view>
		
		<!-- 底部按钮 -->
		<view class="bottom bg-primary" @click="gotoAdd">添加分类</view>
		
		<!-- 删除确认弹窗 -->
		<u-popup :show="showremove" mode="center" :round="10" @close="closeRemoveDialog">
			<view class="dialog">
				<view class="dialog-title">提示</view>
				<view class="dialog-content">
					<view class="dialog-item center">
						<text>您确定要删除该分类吗?</text>
					</view>
				</view>
				<view class="dialog-footer">
					<button class="btn-cancel" @click="closeRemoveDialog">取消</button>
					<button class="btn-confirm" @click="remove">确认</button>
				</view>
			</view>
		</u-popup>
		
		<!-- 添加分类弹窗 -->
		<u-popup :show="showAddKind" mode="center" :round="10" @close="cancel">
			<view class="dialog">
				<view class="dialog-title">添加分类</view>
				<view class="dialog-content">
					<view class="dialog-item">
						<label>分类名称：</label>
						<input v-model="name" placeholder="请输入分类名称" />
					</view>
				</view>
				<view class="dialog-footer">
					<button class="btn-cancel" @click="cancel">取消</button>
					<button class="btn-confirm" @click="addKind">确认</button>
				</view>
			</view>
		</u-popup>
		
		<!-- 修改分类弹窗 -->
		<u-popup :show="showUpKind" mode="center" :round="10" @close="cancel">
			<view class="dialog">
				<view class="dialog-title">修改分类</view>
				<view class="dialog-content">
					<view class="dialog-item">
						<label>分类名称：</label>
						<input v-model="name" placeholder="请输入分类名称" />
					</view>
				</view>
				<view class="dialog-footer">
					<button class="btn-cancel" @click="cancel">取消</button>
					<button class="btn-confirm" @click="upKind">确认</button>
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
			kindList: [],
			name: '',
			status: 0,
			showremove: false,
			showAddKind: false,
			showUpKind: false,
			kindId: '',
			index: ''
		}
	},
	
	onLoad(options) {
		this.store_id = options.store_id || ''
		this.getKindlist()
	},
	
	methods: {
		getKindlist() {
			api.get('/product/category/list', {
				shopId: this.store_id
			}).then(res => {
				if (res.code === 0) {
					this.kindList = res.data || []
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			})
		},
		
		gotoAdd() {
			this.showAddKind = true
		},
		
		addKind() {
			if (!this.name) {
				uni.showToast({ title: '请输入分类名称', icon: 'none' })
				return
			}
			
			api.post('/product/category/create', {
				shopId: this.store_id,
				name: this.name,
				status: this.status
			}).then(res => {
				if (res.code === 0) {
					uni.showToast({ title: '添加成功' })
					this.name = ''
					this.showAddKind = false
					this.getKindlist()
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			})
		},
		
		deleteKind(id) {
			this.kindId = id
			this.showremove = true
		},
		
		closeRemoveDialog() {
			this.showremove = false
			this.kindId = ''
		},
		
		remove() {
			api.post('/product/category/delete/' + this.kindId, {}).then(res => {
				if (res.code === 0) {
					uni.showToast({ title: '删除成功' })
					this.kindId = ''
					this.showremove = false
					this.getKindlist()
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			})
		},
		
		goupdate(index) {
			const kind = this.kindList[index]
			this.index = index
			this.showUpKind = true
			this.name = kind.name
			this.kindId = kind.id
		},
		
		upKind() {
			if (!this.name) {
				uni.showToast({ title: '请输入分类名称', icon: 'none' })
				return
			}
			
			api.post('/product/category/update', {
				id: this.kindId,
				shopId: this.store_id,
				name: this.name,
				status: this.status
			}).then(res => {
				if (res.code === 0) {
					uni.showToast({ title: '修改成功' })
					this.name = ''
					this.kindId = ''
					this.showUpKind = false
					this.getKindlist()
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			})
		},
		
		cancel() {
			this.name = ''
			this.showAddKind = false
			this.showUpKind = false
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
.user-info { flex: 1; }
.vip-blacklist-item { display: flex; justify-content: space-between; align-items: center; }
.productPrice { font-size: 30rpx; font-weight: bold; }
.button-end { display: flex; gap: 15rpx; }
.proButton { font-size: 24rpx; padding: 0 20rpx; height: 50rpx; line-height: 50rpx; border-radius: 8rpx; }
.delete-btn { background-color: #ffeded; color: #ff9292; }
.edit-btn { background-color: #e7faf0; color: #71e2a3; }
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
.dialog-item label { width: 160rpx; font-size: 28rpx; }
.dialog-item input { flex: 1; border: 1rpx solid var(--main-color, #1aad19); border-radius: 8rpx; padding: 10rpx 15rpx; font-size: 28rpx; }
.dialog-footer { display: flex; justify-content: space-around; margin-top: 30rpx; }
.dialog-footer button { width: 200rpx; height: 70rpx; line-height: 70rpx; font-size: 28rpx; border-radius: 10rpx; }
.btn-cancel { background: #f5f5f5; color: #666; }
.btn-confirm { background: var(--main-color, #1aad19); color: #fff; }
</style>
