<template>
	<view class="page" :style="themeStyle">
		<view class="container">
			<!-- 商品封面 -->
			<view class="line">
				<label>*商品封面</label>
				<view class="right">
					<view class="uploader-wrap">
						<view class="uploader-item" v-for="(img, idx) in image" :key="idx">
							<image :src="img.url" mode="aspectFill" @click="previewImage(image, idx)"></image>
							<view class="delete-btn" @click="deleteImage('image', idx)">×</view>
						</view>
						<view class="uploader-add" v-if="image.length < 1" @click="chooseImage('image')">
							<text>+</text>
						</view>
					</view>
				</view>
			</view>
			
			<!-- 商品轮播图 -->
			<view class="line">
				<label>*商品轮播图</label>
				<view class="right">
					<view class="uploader-wrap">
						<view class="uploader-item" v-for="(img, idx) in sliderImage" :key="idx">
							<image :src="img.url" mode="aspectFill" @click="previewImage(sliderImage, idx)"></image>
							<view class="delete-btn" @click="deleteImage('sliderImage', idx)">×</view>
						</view>
						<view class="uploader-add" v-if="sliderImage.length < 3" @click="chooseImage('sliderImage')">
							<text>+</text>
						</view>
					</view>
				</view>
			</view>
			
			<!-- 商品名称 -->
			<view class="field-item">
				<text class="field-label required">商品名称</text>
				<input class="field-input" v-model="storeName" placeholder="请输入商品名称" />
			</view>
			
			<!-- 商品分类 -->
			<view class="field-item" @click="showPopup">
				<text class="field-label required">商品分类</text>
				<view class="field-value">
					<text>{{productKind || '请选择分类'}}</text>
					<text style="font-size:28rpx;color:#999;">›</text>
				</view>
			</view>
			
			<!-- 单位名称 -->
			<view class="field-item">
				<text class="field-label required">单位名称</text>
				<input class="field-input" v-model="unitName" placeholder="请输入单位名称" />
			</view>

			<!-- 商品规格 -->
			<view class="section-title">*商品规格</view>
			<view class="spec-list" v-if="productSpecificationList.length > 0">
				<view class="spec-item" v-for="(spec, idx) in productSpecificationList" :key="idx">
					<view class="spec-name">
						<text>{{spec.value}}</text>
						<text class="close-btn" @click="removeSpec(idx)">×</text>
					</view>
					<view class="spec-values">
						<view class="spec-value-item" v-for="(val, vidx) in spec.detail" :key="vidx">
							<text>{{val}}</text>
							<text class="close-btn" @click="removeSpecValue(idx, vidx)">×</text>
						</view>
						<button class="add-value-btn" size="mini" @click="openAddValue(idx)">添加</button>
					</view>
				</view>
			</view>
			<view class="spec-btns">
				<button size="mini" type="primary" @click="showAddForm">添加新规格</button>
				<button size="mini" class="generate-btn" @click="generateProperty">生成属性</button>
			</view>
			
			<!-- 商品属性 -->
			<view class="section-title">*商品属性</view>
			<view class="property-list" v-if="propertyList.length > 0">
				<view class="property-item" v-for="(prop, idx) in propertyList" :key="idx">
					<view class="property-values">
						<text>{{prop.value1}}</text>
						<text v-if="prop.value2" style="margin-left: 20rpx;">{{prop.value2}}</text>
					</view>
					<view class="property-image">
						<view class="uploader-wrap small">
							<view class="uploader-item" v-if="prop.pic && prop.pic.length > 0">
								<image :src="prop.pic[0].url" mode="aspectFill"></image>
								<view class="delete-btn" @click="deletePropertyImage(idx)">×</view>
							</view>
							<view class="uploader-add" v-else @click="choosePropertyImage(idx)">
								<text>+</text>
							</view>
						</view>
					</view>
					<view class="property-info">
						<text>价格: {{prop.price}}</text>
						<text class="edit-icon" @click="openUpdatePrice(idx)">✎</text>
						<text style="margin-left: 30rpx;">库存: {{prop.stock}}</text>
						<text class="edit-icon" @click="openUpdateStock(idx)">✎</text>
					</view>
				</view>
			</view>
			
			<!-- 底部保存按钮 -->
			<view class="submit-bar">
				<button class="submit-btn" @click="submit">保存</button>
			</view>
		</view>
		
		<!-- 分类选择弹窗 -->
		<u-popup :show="show" mode="bottom" @close="onClose">
			<view class="picker-header">
				<text @click="cancelPicker">取消</text>
				<text class="picker-title">选择分类</text>
				<text @click="confirmPicker">确定</text>
			</view>
			<picker-view :value="[categoryIndex]" @change="onPickerChange" class="picker-view">
				<picker-view-column>
					<view class="picker-item" v-for="(item, idx) in kindList" :key="idx">{{item}}</view>
				</picker-view-column>
			</picker-view>
		</u-popup>

		<!-- 添加规格弹窗 -->
		<u-popup :show="showAddSpecification" mode="center" :round="10" @close="showAddSpecification = false">
			<view class="dialog">
				<view class="dialog-title">添加规格</view>
				<view class="dialog-content">
					<view class="dialog-item">
						<label>规格：</label>
						<input v-model="specificationName" placeholder="请输入规格(例如：口味)" />
					</view>
					<view class="dialog-item">
						<label>规格值：</label>
						<input v-model="specificationValue" placeholder="请输入规格值(例如:微辣/特辣)" />
					</view>
				</view>
				<view class="dialog-footer">
					<button class="btn-cancel" @click="showAddSpecification = false">取消</button>
					<button class="btn-confirm" @click="addSpecification">确认</button>
				</view>
			</view>
		</u-popup>
		
		<!-- 添加规格值弹窗 -->
		<u-popup :show="addValue" mode="center" :round="10" @close="addValue = false">
			<view class="dialog">
				<view class="dialog-title">添加规格值</view>
				<view class="dialog-content">
					<view class="dialog-item">
						<label>规格值：</label>
						<input v-model="addSpecificationValue" placeholder="请输入规格值(例如:微辣/特辣..)" />
					</view>
				</view>
				<view class="dialog-footer">
					<button class="btn-cancel" @click="addValue = false">取消</button>
					<button class="btn-confirm" @click="confirmAddValue">确认</button>
				</view>
			</view>
		</u-popup>
		
		<!-- 修改价格弹窗 -->
		<u-popup :show="showUpdatePrice" mode="center" :round="10" @close="showUpdatePrice = false">
			<view class="dialog">
				<view class="dialog-title">修改价格</view>
				<view class="dialog-content">
					<view class="dialog-item">
						<label>价格：</label>
						<input type="digit" v-model="updatePrice" placeholder="请输入价格" />
					</view>
				</view>
				<view class="dialog-footer">
					<button class="btn-cancel" @click="showUpdatePrice = false">取消</button>
					<button class="btn-confirm" @click="confirmUpdatePrice">确认</button>
				</view>
			</view>
		</u-popup>
		
		<!-- 修改库存弹窗 -->
		<u-popup :show="showUpdateStock" mode="center" :round="10" @close="showUpdateStock = false">
			<view class="dialog">
				<view class="dialog-title">修改库存</view>
				<view class="dialog-content">
					<view class="dialog-item">
						<label>库存：</label>
						<input type="number" v-model="updateStock" placeholder="请输入库存" />
					</view>
				</view>
				<view class="dialog-footer">
					<button class="btn-cancel" @click="showUpdateStock = false">取消</button>
					<button class="btn-confirm" @click="confirmUpdateStock">确认</button>
				</view>
			</view>
		</u-popup>
	</view>
</template>

<script>
import api from '@/api/index.js'
import http from '@/utils/http.js'
import config from '@/config/index.js'

export default {
	data() {
		return {
			product_id: '',
			store_id: '',
			image: [],
			sliderImage: [],
			storeName: '',
			productKind: '',
			productKindId: '',
			unitName: '',
			kindList: [],
			kindListAllInfo: [],
			show: false,
			categoryIndex: 0,
			productSpecificationList: [],
			showAddSpecification: false,
			specificationName: '',
			specificationValue: '',
			addValue: false,
			valueIndex: '',
			addSpecificationValue: '',
			propertyList: [],
			propertyIndex: 0,
			showUpdatePrice: false,
			updatePrice: 0,
			showUpdateStock: false,
			updateStock: 0
		}
	},
	
	onLoad(options) {
		this.store_id = options.store_id || ''
		this.getKindlist()
		
		if (options.product_id) {
			this.product_id = options.product_id
			this.getProductData()
		}
	},
	
	methods: {
		// 获取商品分类列表
		getKindlist() {
			api.get('/product/category/list', { shopId: this.store_id }).then(res => {
				if (res.code === 0) {
					this.kindListAllInfo = res.data || []
					this.kindList = this.kindListAllInfo.map(item => item.name)
				}
			})
		},
		
		// 获取商品详情
		getProductData() {
			api.get('/product/store-product/info/' + this.product_id, {}).then(res => {
				if (res.code === 0 && res.data && res.data.productInfo) {
					const info = res.data.productInfo
					this.image = info.image ? [{ url: info.image }] : []
					this.sliderImage = (info.slider_image || []).map(url => ({ url }))
					this.storeName = info.store_name || ''
					this.unitName = info.unit_name || ''
					this.productKindId = info.cate_id || ''
					this.productSpecificationList = info.items || []
					
					// 查找分类名称
					const kind = this.kindListAllInfo.find(k => k.id == info.cate_id)
					this.productKind = kind ? kind.name : ''
					
					// 处理属性
					if (info.attrs && info.attrs.length > 0) {
						this.propertyList = info.attrs.map(item => ({
							...item,
							value1: item.sku ? item.sku.split(',')[0] : '',
							value2: item.sku ? item.sku.split(',')[1] : '',
							pic: item.pic ? [{ url: item.pic }] : []
						}))
					}
				}
			})
		},
		
		// 选择图片
		chooseImage(type) {
			uni.chooseImage({
				count: type === 'image' ? 1 : 3 - this.sliderImage.length,
				success: (res) => {
					res.tempFilePaths.forEach(path => {
						this.uploadImage(path, type)
					})
				}
			})
		},
		
		// 上传图片
		uploadImage(filePath, type) {
			http.upload(filePath).then(url => {
				if (type === 'image') {
					this.image.push({ url })
				} else {
					this.sliderImage.push({ url })
				}
			}).catch(() => {
				uni.showToast({ title: '上传失败', icon: 'none' })
			})
		},
		
		// 删除图片
		deleteImage(type, index) {
			if (type === 'image') {
				this.image.splice(index, 1)
			} else {
				this.sliderImage.splice(index, 1)
			}
		},
		
		// 预览图片
		previewImage(images, index) {
			uni.previewImage({
				urls: images.map(img => img.url),
				current: index
			})
		},
		
		// 显示分类选择
		showPopup() {
			this.show = true
		},
		
		onClose() {
			this.show = false
		},
		
		cancelPicker() {
			this.show = false
		},
		
		onPickerChange(e) {
			this.categoryIndex = e.detail.value[0]
		},
		
		confirmPicker() {
			if (this.kindListAllInfo.length > 0) {
				this.productKind = this.kindList[this.categoryIndex]
				this.productKindId = this.kindListAllInfo[this.categoryIndex].id
			}
			this.show = false
		},

		// 显示添加规格弹窗
		showAddForm() {
			if (this.productSpecificationList.length >= 2) {
				uni.showToast({ title: '规格最多只能2个~', icon: 'none' })
				return
			}
			this.specificationName = ''
			this.specificationValue = ''
			this.showAddSpecification = true
		},
		
		// 添加规格
		addSpecification() {
			if (!this.specificationName || !this.specificationValue) {
				uni.showToast({ title: '请填写完整', icon: 'none' })
				return
			}
			this.productSpecificationList.push({
				value: this.specificationName,
				detail: [this.specificationValue]
			})
			this.showAddSpecification = false
		},
		
		// 删除规格
		removeSpec(index) {
			this.productSpecificationList.splice(index, 1)
		},
		
		// 打开添加规格值弹窗
		openAddValue(index) {
			this.valueIndex = index
			this.addSpecificationValue = ''
			this.addValue = true
		},
		
		// 确认添加规格值
		confirmAddValue() {
			if (!this.addSpecificationValue) {
				uni.showToast({ title: '请填写完整', icon: 'none' })
				return
			}
			this.productSpecificationList[this.valueIndex].detail.push(this.addSpecificationValue)
			this.addValue = false
		},
		
		// 删除规格值
		removeSpecValue(specIdx, valIdx) {
			this.productSpecificationList[specIdx].detail.splice(valIdx, 1)
		},
		
		// 生成属性
		generateProperty() {
			if (this.productSpecificationList.length === 0) {
				uni.showToast({ title: '请先添加规格', icon: 'error' })
				return
			}
			
			let attributes = []
			const specs = this.productSpecificationList
			
			const generateCombinations = (index, currentCombination, currentDetail) => {
				if (index === specs.length) {
					attributes.push({
						value1: currentCombination[0] || '',
						value2: currentCombination[1] || '',
						detail: currentDetail,
						pic: this.image.length > 0 ? [...this.image] : [],
						price: 0,
						stock: 0
					})
					return
				}
				
				const spec = specs[index]
				if (spec.detail && spec.detail.length > 0) {
					for (const detail of spec.detail) {
						let newDetail = { ...currentDetail }
						newDetail[spec.value] = detail
						generateCombinations(index + 1, [...currentCombination, detail], newDetail)
					}
				}
			}
			
			generateCombinations(0, [], {})
			this.propertyList = attributes
		},
		
		// 选择属性图片
		choosePropertyImage(index) {
			uni.chooseImage({
				count: 1,
				success: (res) => {
					this.uploadPropertyImage(res.tempFilePaths[0], index)
				}
			})
		},
		
		// 上传属性图片
		uploadPropertyImage(filePath, index) {
			http.upload(filePath).then(url => {
				this.propertyList[index].pic = [{ url }]
			}).catch(() => {
				uni.showToast({ title: '上传失败', icon: 'none' })
			})
		},
		
		// 删除属性图片
		deletePropertyImage(index) {
			this.propertyList[index].pic = []
		},
		
		// 打开修改价格弹窗
		openUpdatePrice(index) {
			this.propertyIndex = index
			this.updatePrice = this.propertyList[index].price
			this.showUpdatePrice = true
		},
		
		// 确认修改价格
		confirmUpdatePrice() {
			if (this.updatePrice < 0) {
				uni.showToast({ title: '请填写正确的价格', icon: 'none' })
				return
			}
			this.propertyList[this.propertyIndex].price = this.updatePrice
			this.showUpdatePrice = false
		},
		
		// 打开修改库存弹窗
		openUpdateStock(index) {
			this.propertyIndex = index
			this.updateStock = this.propertyList[index].stock
			this.showUpdateStock = true
		},
		
		// 确认修改库存
		confirmUpdateStock() {
			this.propertyList[this.propertyIndex].stock = this.updateStock
			this.showUpdateStock = false
		},

		// 保存商品
		submit() {
			if (!this.storeName || this.image.length === 0 || this.sliderImage.length === 0 || !this.productKindId || !this.unitName) {
				uni.showToast({ title: '商品信息不完整', icon: 'error' })
				return
			}
			if (this.productSpecificationList.length === 0) {
				uni.showToast({ title: '请添加规格', icon: 'error' })
				return
			}
			if (this.propertyList.length === 0) {
				uni.showToast({ title: '请生成属性', icon: 'error' })
				return
			}
			
			// 处理属性数据
			const attrs = this.propertyList.map(item => ({
				...item,
				pic: item.pic && item.pic.length > 0 ? item.pic[0].url : ''
			}))
			
			const images = this.sliderImage.map(item => item.url)
			
			const params = {
				shopId: this.store_id,
				shopName: this.store_id,
				image: this.image[0].url,
				slider_image: images,
				store_name: this.storeName,
				store_info: ' ',
				cate_id: this.productKindId,
				price: attrs[0] ? attrs[0].price : 0,
				unit_name: this.unitName,
				items: this.productSpecificationList,
				attrs: attrs,
				spec_type: '1'
			}
			
			if (this.product_id) {
				params.id = this.product_id
			}
			
			api.post('/product/store-product/create', params).then(res => {
				if (res.code === 0) {
					uni.showToast({ title: '保存成功', icon: 'success' })
					setTimeout(() => uni.navigateBack(), 1500)
				} else {
					uni.showToast({ title: res.msg || '保存失败', icon: 'none' })
				}
			})
		}
	}
}
</script>

<style lang="scss" scoped>
.page {
	min-height: 100vh;
	background: #f5f5f5;
	padding-bottom: 120rpx;
}

.container {
	padding: 16rpx;
}

.line {
	display: flex;
	margin-bottom: 16rpx;
	padding: 30rpx;
	background-color: #fff;
	border-radius: 10rpx;
	
	label {
		width: 180rpx;
		line-height: 60rpx;
		color: #646566;
		font-size: 28rpx;
	}
	
	.right {
		flex: 1;
	}
}

.uploader-wrap {
	display: flex;
	flex-wrap: wrap;
	gap: 16rpx;
	
	&.small {
		.uploader-item, .uploader-add {
			width: 120rpx;
			height: 120rpx;
		}
	}
}

.uploader-item {
	width: 160rpx;
	height: 160rpx;
	position: relative;
	
	image {
		width: 100%;
		height: 100%;
		border-radius: 8rpx;
	}
	
	.delete-btn {
		position: absolute;
		top: -10rpx;
		right: -10rpx;
		width: 36rpx;
		height: 36rpx;
		background: #ff4d4f;
		color: #fff;
		border-radius: 50%;
		text-align: center;
		line-height: 36rpx;
		font-size: 24rpx;
	}
}

.uploader-add {
	width: 160rpx;
	height: 160rpx;
	border: 2rpx dashed #ddd;
	border-radius: 8rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	
	text {
		font-size: 60rpx;
		color: #999;
	}
}

.field-item {
	display: flex;
	align-items: center;
	padding: 20rpx 30rpx;
	background: #fff;
	border-bottom: 1rpx solid #eee;
	
	.field-label {
		width: 180rpx;
		font-size: 28rpx;
		color: #646566;
		
		&.required::before {
			content: '*';
			color: #ff4d4f;
			margin-right: 4rpx;
		}
	}
	
	.field-input {
		flex: 1;
		font-size: 28rpx;
		height: 60rpx;
	}
	
	.field-value {
		flex: 1;
		display: flex;
		justify-content: space-between;
		align-items: center;
		font-size: 28rpx;
		color: #999;
	}
}

.section-title {
	padding: 20rpx 16rpx;
	font-size: 28rpx;
	color: #333;
}

.spec-list {
	background: #fff;
	padding: 20rpx;
	margin-bottom: 16rpx;
	border-radius: 10rpx;
}

.spec-item {
	margin-bottom: 20rpx;
}

.spec-name {
	display: flex;
	align-items: center;
	font-size: 28rpx;
	margin-bottom: 10rpx;
	
	.close-btn {
		margin-left: 10rpx;
		color: #ff4d4f;
		font-size: 32rpx;
	}
}

.spec-values {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: 10rpx;
}

.spec-value-item {
	display: flex;
	align-items: center;
	padding: 6rpx 16rpx;
	background: #ecf5ff;
	border-radius: 6rpx;
	font-size: 24rpx;
	
	.close-btn {
		margin-left: 6rpx;
		color: #ff4d4f;
		font-size: 24rpx;
	}
}

.add-value-btn {
	font-size: 24rpx;
	padding: 0 20rpx;
	height: 50rpx;
	line-height: 50rpx;
}

.spec-btns {
	display: flex;
	gap: 20rpx;
	padding: 0 16rpx;
	margin-bottom: 20rpx;
	
	button {
		font-size: 26rpx;
	}
	
	.generate-btn {
		background-color: #fff8e6;
		color: #ffba00;
	}
}

.property-list {
	background: #fff;
	padding: 20rpx;
	margin-bottom: 16rpx;
	border-radius: 10rpx;
}

.property-item {
	display: flex;
	align-items: center;
	padding: 16rpx 0;
	border-bottom: 1rpx solid #eee;
	
	&:last-child {
		border-bottom: none;
	}
}

.property-values {
	width: 150rpx;
	font-size: 26rpx;
}

.property-image {
	margin: 0 20rpx;
}

.property-info {
	flex: 1;
	display: flex;
	align-items: center;
	font-size: 26rpx;
	
	.edit-icon {
		margin-left: 10rpx;
		color: var(--main-color, #1aad19);
		font-size: 28rpx;
	}
}

.submit-bar {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	padding: 20rpx 30rpx;
	background: #fff;
	box-shadow: 0 -2rpx 10rpx rgba(0,0,0,0.05);
}

.submit-btn {
	width: 100%;
	height: 90rpx;
	line-height: 90rpx;
	background: var(--main-color, #1aad19);
	color: #fff;
	font-size: 32rpx;
	border-radius: 45rpx;
}

.picker-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 20rpx 30rpx;
	border-bottom: 1rpx solid #eee;
	
	text {
		font-size: 28rpx;
		color: var(--main-color, #1aad19);
	}
	
	.picker-title {
		color: #333;
		font-weight: bold;
	}
}

.picker-view {
	height: 400rpx;
}

.picker-item {
	line-height: 80rpx;
	text-align: center;
	font-size: 28rpx;
}

.dialog {
	width: 600rpx;
	padding: 30rpx;
}

.dialog-title {
	text-align: center;
	font-size: 32rpx;
	font-weight: bold;
	margin-bottom: 30rpx;
}

.dialog-content {
	padding: 20rpx 0;
}

.dialog-item {
	display: flex;
	align-items: center;
	margin-bottom: 20rpx;
	
	label {
		width: 120rpx;
		font-size: 28rpx;
	}
	
	input {
		flex: 1;
		height: 60rpx;
		border: 1rpx solid #ddd;
		border-radius: 6rpx;
		padding: 0 16rpx;
		font-size: 28rpx;
	}
}

.dialog-footer {
	display: flex;
	justify-content: space-around;
	margin-top: 30rpx;
	
	button {
		width: 200rpx;
		height: 70rpx;
		line-height: 70rpx;
		font-size: 28rpx;
		border-radius: 10rpx;
	}
	
	.btn-cancel {
		background: #f5f5f5;
		color: #666;
	}
	
	.btn-confirm {
		background: var(--main-color, #1aad19);
		color: #fff;
	}
}
</style>
