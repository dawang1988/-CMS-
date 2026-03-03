<template>
	<view class="page" :style="themeStyle">
		<view class="container">
			<!-- 当前模板信息 -->
			<view class="info">
				<view class="name">门店当前模板：{{ currentTemplateName }}</view>
			</view>

			<!-- 模板选择 -->
			<view class="section-title">模板选择</view>
			<view class="template-grid">
				<view
					class="template-card"
					:class="{ selected: selectedTemplate === item.key }"
					v-for="item in templates"
					:key="item.key"
					@click="selectTemplate(item.key)"
					:style="{ borderColor: selectedTemplate === item.key ? item.color : '#eee' }"
				>
					<view class="color-bar" :style="{ backgroundColor: item.color }"></view>
					<view class="card-name">{{ item.name }}</view>
					<view class="check-icon" v-if="selectedTemplate === item.key">✓ 已选择</view>
				</view>
			</view>

			<!-- 显示模式 -->
			<view class="section-title">显示模式</view>
			<view class="mode-switch">
				<view class="mode-item" :class="{ active: simpleMode === 1 }" @click="simpleMode = 1">
					简洁模式
				</view>
				<view class="mode-item" :class="{ active: simpleMode === 0 }" @click="simpleMode = 0">
					标准模式
				</view>
			</view>
			<view class="mode-tip">
				简洁模式：使用系统默认图标样式；标准模式：可自定义上传按钮图片
			</view>

			<!-- 自定义按钮图片（标准模式） -->
			<view v-if="simpleMode === 0" class="custom-section">
				<view class="section-title">自定义按钮图片</view>
				<view class="tip-box">请按照推荐尺寸上传图片，以获得最佳显示效果</view>
				<view class="upload-grid">
					<view class="upload-item" v-for="item in uploadFields" :key="item.field">
						<view class="upload-label">{{ item.label }}</view>
						<view class="upload-box" @click="chooseImage(item.field)">
							<image v-if="customImages[item.field]" :src="customImages[item.field]" mode="aspectFit" class="preview-img" />
							<view v-else class="upload-placeholder">
								<text class="plus">+</text>
								<text class="size">{{ item.size }}</text>
							</view>
						</view>
						<view v-if="customImages[item.field]" class="del-btn" @click="deleteImage(item.field)">删除</view>
					</view>
				</view>
			</view>

			<!-- 保存按钮 -->
			<view class="footer-btns">
				<view class="footer-btn" @click="cancel">取消</view>
				<view class="footer-btn primary" @click="save">保存设置</view>
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
			selectedTemplate: 'default',
			simpleMode: 1,
			customImages: {
				btn_img: '', qh_img: '', tg_img: '',
				cz_img: '', open_img: '', wifi_img: '', kf_img: ''
			},
			templates: [
				{ key: 'default', name: '默认模板', color: '#5AAB6E' },
				{ key: 'blue', name: '蓝色主题', color: '#1890ff' },
				{ key: 'orange', name: '橙色主题', color: '#fa8c16' },
				{ key: 'purple', name: '紫色主题', color: '#722ed1' }
			],
			uploadFields: [
				{ field: 'btn_img', label: '立即预约按钮图', size: '495×600' },
				{ field: 'qh_img', label: '切换门店按钮图', size: '495×282' },
				{ field: 'tg_img', label: '团购兑换按钮图', size: '495×282' },
				{ field: 'cz_img', label: '商品点单按钮图', size: '495×210' },
				{ field: 'open_img', label: '一键开门按钮图', size: '495×210' },
				{ field: 'wifi_img', label: 'WIFI信息按钮图', size: '495×210' },
				{ field: 'kf_img', label: '联系客服按钮图', size: '495×210' }
			]
		}
	},
	computed: {
		currentTemplateName() {
			const t = this.templates.find(t => t.key === this.selectedTemplate)
			return t ? t.name : this.selectedTemplate || '默认模板'
		}
	},
	onLoad(options) {
		if (options.store_id) {
			this.store_id = options.store_id
			this.loadStoreDetail()
		}
	},
	methods: {
		async loadStoreDetail() {
			try {
				const res = await http.get(`/member/store/getDetail/${this.store_id}`)
				if (res) {
					const data = res.data || res
					this.selectedTemplate = data.template_key || 'default'
					this.simpleMode = data.simple_model !== undefined ? Number(data.simple_model) : 1
					const fields = ['btn_img', 'qh_img', 'tg_img', 'cz_img', 'open_img', 'wifi_img', 'kf_img']
					fields.forEach(f => { this.customImages[f] = data[f] || '' })
				}
			} catch (e) {}
		},
		selectTemplate(key) {
			this.selectedTemplate = key
		},
		chooseImage(field) {
			uni.chooseImage({
				count: 1,
				success: (res) => {
					const tempPath = res.tempFilePaths[0]
					uni.showLoading({ title: '上传中...' })
					http.upload('/member/store/uploadImg', tempPath).then(uploadRes => {
						uni.hideLoading()
						if (uploadRes && uploadRes.url) {
							this.$set(this.customImages, field, uploadRes.url)
						}
					}).catch(() => {
						uni.hideLoading()
						uni.showToast({ title: '上传失败', icon: 'none' })
					})
				}
			})
		},
		deleteImage(field) {
			this.$set(this.customImages, field, '')
		},
		cancel() {
			uni.navigateBack()
		},
		async save() {
			const data = {
				store_id: this.store_id,
				templateKey: this.selectedTemplate,
				simple_model: this.simpleMode
			}
			// 标准模式时带上自定义图片
			if (this.simpleMode === 0) {
				Object.assign(data, this.customImages)
			}
			try {
				const res = await http.post('/member/store/setTemplate', data)
				if (res.code === 0 || res.errCode === 0) {
					uni.showToast({ title: '保存成功' })
					setTimeout(() => uni.navigateBack(), 300)
				} else {
					uni.showToast({ title: res.msg || '保存失败', icon: 'none' })
				}
			} catch (e) {
				uni.showToast({ title: '保存失败', icon: 'none' })
			}
		}
	}
}
</script>

<style lang="scss" scoped>
.page { min-height: 100vh; background: #f5f5f5; padding-bottom: 140rpx; }
.container { padding: 20rpx; }
.info { background: #fff; padding: 30rpx; text-align: center; border-radius: 12rpx; margin-bottom: 20rpx; }
.info .name { font-size: 32rpx; color: #333; }
.section-title { font-size: 30rpx; font-weight: bold; color: #333; padding: 20rpx 10rpx 16rpx; }
.template-grid { display: flex; flex-wrap: wrap; gap: 20rpx; }
.template-card {
	flex-basis: calc(50% - 10rpx); background: #fff; border-radius: 12rpx;
	border: 4rpx solid #eee; overflow: hidden; text-align: center; padding-bottom: 16rpx;
}
.template-card.selected { border-width: 4rpx; }
.color-bar { height: 120rpx; }
.card-name { font-size: 28rpx; font-weight: bold; padding: 16rpx 0 4rpx; }
.check-icon { font-size: 24rpx; color: #52c41a; }
.mode-switch { display: flex; gap: 20rpx; padding: 0 10rpx; }
.mode-item {
	flex: 1; text-align: center; padding: 20rpx; background: #fff;
	border-radius: 12rpx; font-size: 28rpx; color: #666; border: 4rpx solid #eee;
}
.mode-item.active { border-color: var(--main-color, #5AAB6E); color: var(--main-color, #5AAB6E); background: #f6ffed; }
.mode-tip { font-size: 24rpx; color: #999; padding: 12rpx 10rpx 0; }
.custom-section { margin-top: 10rpx; }
.tip-box { font-size: 24rpx; color: #fa8c16; background: #fff7e6; padding: 16rpx 20rpx; border-radius: 8rpx; margin-bottom: 16rpx; }
.upload-grid { display: flex; flex-wrap: wrap; gap: 20rpx; }
.upload-item { flex-basis: calc(50% - 10rpx); background: #fff; border-radius: 12rpx; padding: 16rpx; text-align: center; }
.upload-label { font-size: 26rpx; color: #333; margin-bottom: 12rpx; }
.upload-box {
	width: 100%; height: 200rpx; border: 2rpx dashed #ddd; border-radius: 8rpx;
	display: flex; align-items: center; justify-content: center; overflow: hidden;
}
.preview-img { width: 100%; height: 100%; }
.upload-placeholder { display: flex; flex-direction: column; align-items: center; }
.upload-placeholder .plus { font-size: 60rpx; color: #ccc; line-height: 1; }
.upload-placeholder .size { font-size: 22rpx; color: #bbb; margin-top: 8rpx; }
.del-btn { font-size: 24rpx; color: #ff4d4f; margin-top: 8rpx; }
.footer-btns {
	position: fixed; bottom: 0; left: 0; right: 0; display: flex; gap: 20rpx;
	padding: 20rpx; background: #fff; box-shadow: 0 -2rpx 10rpx rgba(0,0,0,0.05); z-index: 100;
}
.footer-btn {
	flex: 1; text-align: center; padding: 20rpx; border-radius: 10rpx;
	font-size: 28rpx; background: #f5f5f5; color: #666;
}
.footer-btn.primary { background: var(--main-color, #5AAB6E); color: #fff; }
</style>
