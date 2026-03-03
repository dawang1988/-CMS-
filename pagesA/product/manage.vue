<template>
	<view class="page" :style="themeStyle">
		<!-- 顶部筛选 -->
		<view class="tabs">
			<picker @change="kindFilter" :value="kindIndex" :range="kindOption" range-key="text">
				<view class="dropdown-btn">
					<text>{{kindOption[kindIndex].text}}</text>
					<text style="font-size:20rpx;margin-left:8rpx;">▼</text>
				</view>
			</picker>
			<picker @change="productFilter" :value="sortIndex" :range="option2" range-key="text" style="margin-left: 30rpx;">
				<view class="dropdown-btn">
					<text>{{option2[sortIndex].text}}</text>
					<text style="font-size:20rpx;margin-left:8rpx;">▼</text>
				</view>
			</picker>
		</view>
		
		<scroll-view scroll-y="true" class="scroll-container" @scrolltolower="onReachBottom">
			<view class="container">
				<view class="lists">
					<view class="list">
						<block v-if="productList.length > 0">
							<view v-for="(item, index) in productList" :key="index">
								<view class="store-card">
									<view class="item">
										<view class="image-container">
											<image class="store-card__image" :src="item.image" mode="aspectFill"></image>
										</view>
										<view class="user-info">
											<view class="top-info">
												<text class="name">{{item.store_name}}</text>
												<view class="status-btn">
													<button size="mini" :class="item.isShow == 1 ? 'down' : 'up'" @click="changeShow(item, index)">
														{{item.isShow == 1 ? '下架' : '上架'}}
													</button>
												</view>
											</view>
											<view class="centerInfo">
												<text>分类：{{item.kindName}}</text>
												<text>销量：{{item.sales}}</text>
												<text>库存：{{item.stock}}</text>
											</view>
											<view class="vip-blacklist-item">
												<text class="productPrice">¥{{item.price}}</text>
												<view class="button-end">
													<button size="mini" class="proButton delete-btn" @click="deleteProduct(item.id)">删除</button>
													<button size="mini" class="proButton edit-btn" @click="goupdate(item.id)">修改</button>
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
									<text>暂无商品</text>
								</view>
							</view>
						</block>
					</view>
				</view>
			</view>
		</scroll-view>
		
		<!-- 底部按钮 -->
		<view class="bottom bg-primary" @click="gotoAdd">添加商品</view>
		
		<!-- 删除确认弹窗 -->
		<u-popup :show="showremove" mode="center" :round="10" @close="closeRemoveDialog">
			<view class="dialog">
				<view class="dialog-title">提示</view>
				<view class="dialog-content">
					<view class="dialog-item center">
						<text>您确定要删除该商品吗?</text>
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
import listMixin from '@/mixins/listMixin'

export default {
	mixins: [listMixin],
	data() {
		return {
			store_id: '',
			productList: [],
			kindList: [],
			currentPage: 1,
			hasMore: false,
			showremove: false,
			id: '',
			kind: -1,
			kindIndex: 0,
			sortIndex: 0,
			kindOption: [{ text: '全部分类', value: -1 }],
			option2: [{ text: '默认排序', value: '0' }]
		}
	},
	
	onLoad(options) {
		this.store_id = options.store_id || ''
	},
	
	onShow() {
		this.getKindlist()
	},
	
	methods: {
		getProductlist(refresh = false) {
			let currentPage = refresh ? 1 : this.currentPage + 1
			this.currentPage = currentPage
			
			api.post('/product/store-product/page', {
				store_id: this.store_id,
				cate_id: this.kind == -1 ? null : this.kind,
				pageSize: 10,
				pageNo: currentPage
			}).then(res => {
				if (res.code === 0) {
					const productList = res.data.list.map(item => ({
						...item,
						kindName: this.findKindName(item.cate_id)
					}))
					
					if (!refresh) {
						this.productList = this.productList.concat(productList)
						this.hasMore = this.currentPage * 10 < res.data.total
					} else {
						this.productList = productList
						this.hasMore = this.currentPage * 10 < res.data.total
					}
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			})
		},
		
		getKindlist() {
			api.get('/product/category/list', {
				shopId: this.store_id
			}).then(res => {
				if (res.code === 0) {
					this.kindList = res.data || []
					this.getProductlist(true)
					
					// 重置分类选项
					this.kindOption = [{ text: '全部分类', value: -1 }]
					this.kindList.forEach(item => {
						this.kindOption.push({ text: item.name, value: item.id })
					})
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			})
		},
		
		findKindName(cateId) {
			for (let i = 0; i < this.kindList.length; i++) {
				if (this.kindList[i].id == cateId) {
					return this.kindList[i].name
				}
			}
			return ''
		},
		
		onReachBottom() {
			if (this.hasMore) {
				this.getProductlist(false)
			}
		},
		
		deleteProduct(id) {
			this.id = id
			this.showremove = true
		},
		
		closeRemoveDialog() {
			this.showremove = false
			this.id = ''
		},
		
		remove() {
			api.post('/product/store-product/delete/' + this.id, {}).then(res => {
				if (res.code === 0) {
					uni.showToast({ title: '删除成功' })
					this.id = ''
					this.showremove = false
					this.getProductlist(true)
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			})
		},
		
		goupdate(id) {
			uni.navigateTo({
				url: '/pagesA/product/add?product_id=' + id + '&store_id=' + this.store_id
			})
		},
		
		changeShow(item, index) {
			const isShow = item.isShow == 1 ? 0 : 1
			
			api.post('/product/store-product/sale', {
				id: item.id,
				status: isShow
			}).then(res => {
				if (res.code === 0) {
					this.productList[index].isShow = isShow
				}
			})
		},
		
		gotoAdd() {
			uni.navigateTo({
				url: '/pagesA/product/add?store_id=' + this.store_id
			})
		},
		
		kindFilter(e) {
			this.kindIndex = e.detail.value
			this.kind = this.kindOption[this.kindIndex].value
			this.getProductlist(true)
		},
		
		productFilter(e) {
			this.sortIndex = e.detail.value
			// 可以根据需要添加排序逻辑
		}
	}
}
</script>

<style lang="scss" scoped>
.page { min-height: 100vh; background: #f5f5f5; }
.tabs { position: fixed; z-index: 9; width: 100%; height: 90rpx; background: #fff; border-bottom: 1rpx solid #ddd; display: flex; align-items: center; padding: 0 30rpx; }
.dropdown-btn { display: flex; align-items: center; font-size: 28rpx; }
.dropdown-btn text { margin-right: 10rpx; }
.scroll-container { height: calc(100vh - 230rpx); margin-top: 90rpx; }
.container { padding: 20rpx; padding-bottom: 140rpx; }
.store-card { background: #fff; border-radius: 15rpx; margin-bottom: 20rpx; padding: 20rpx; }
.store-card .item { display: flex; }
.image-container { width: 180rpx; height: 180rpx; margin-right: 20rpx; flex-shrink: 0; }
.store-card__image { width: 100%; height: 100%; border-radius: 10rpx; }
.user-info { flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
.top-info { display: flex; justify-content: space-between; align-items: center; }
.top-info .name { font-size: 28rpx; font-weight: bold; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.status-btn button { font-size: 24rpx; padding: 0 20rpx; height: 50rpx; line-height: 50rpx; border-radius: 8rpx; }
.status-btn .down { background: #ffeded; color: #ff9292; }
.status-btn .up { background: #e7faf0; color: #71e2a3; }
.centerInfo { display: flex; gap: 20rpx; font-size: 24rpx; color: #666; margin: 15rpx 0; }
.vip-blacklist-item { display: flex; justify-content: space-between; align-items: center; }
.productPrice { font-size: 32rpx; font-weight: bold; color: #ff4d4f; }
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
.dialog-footer { display: flex; justify-content: space-around; margin-top: 30rpx; }
.dialog-footer button { width: 200rpx; height: 70rpx; line-height: 70rpx; font-size: 28rpx; border-radius: 10rpx; }
.btn-cancel { background: #f5f5f5; color: #666; }
.btn-confirm { background: var(--main-color, #1aad19); color: #fff; }
</style>
