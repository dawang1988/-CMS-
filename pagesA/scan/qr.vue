<template>
	<view class="page" :style="themeStyle">
		<view class="banner">
			<image src="/static/img/tuangou.png" mode="widthFix" class="banner-img" />
		</view>
		
		<view class="card">
			<view class="title">团购验券</view>
			
			<!-- 门店选择 -->
			<view class="form-item">
				<text class="label">所属门店</text>
				<picker mode="selector" :range="storeNames" @change="bindStoreChange">
					<view class="picker-value">{{ storeIndex !== '' ? storeNames[storeIndex] : '请选择门店' }} ▼</view>
				</picker>
			</view>
			
			<!-- 券码输入 -->
			<view class="form-item">
				<text class="label">团购券码</text>
				<view class="input-row">
					<input class="input" placeholder="请输入团购券码" v-model="groupPayNo" />
					<view class="scan-icon" @tap="scanCode">
						<image src="/static/icon/scan.png" style="width:48rpx;height:48rpx;" />
					</view>
				</view>
			</view>
			
			<view class="tip">本功能为辅助门店核销团购券，并不产生实际订单</view>
			
			<!-- 提交按钮 -->
			<button class="submit-btn" @tap="submit" :disabled="!groupPayNo || !store_id">核销验券</button>
		</view>
		
		<!-- 核销成功 -->
		<view class="success-card" v-if="success">
			<text style="font-size:100rpx;color:var(--main-color, #5AAB6E);">✓</text>
			<text class="success-text">核销成功</text>
			<text class="success-info" v-if="verifyInfo">{{ verifyInfo }}</text>
		</view>
		
		<!-- 核销记录 -->
		<view class="card" v-if="logList.length > 0">
			<view class="title">最近核销记录</view>
			<view class="log-item" v-for="(item, index) in logList" :key="index">
				<view class="log-top">
					<text class="log-no">{{ item.group_pay_no }}</text>
					<text class="log-type" :class="item.verify_type == 1 ? 'auto' : 'manual'">
						{{ item.verify_type == 1 ? '下单核销' : '手动核销' }}
					</text>
				</view>
				<view class="log-info">
					<text>{{ item.title || '团购券' }}</text>
					<text class="log-time">{{ item.create_time }}</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
import http from '@/utils/http'

export default {
	data() {
		return {
			store_id: '',
			storeIndex: '',
			stores: [],
			storeNames: [],
			groupPayNo: '',
			success: false,
			verifyInfo: '',
			logList: []
		}
	},
	onLoad() {
		this.getStoreList()
	},
	onShow() {
		this.success = false
		if (this.store_id) {
			this.getVerifyLog()
		}
	},
	methods: {
		getStoreList() {
			http.get('/member/store/getStoreListByAdmin').then(res => {
				if (res.code == 0 && res.data) {
					this.stores = res.data.map(it => ({ name: it.key, id: it.value }))
					this.storeNames = res.data.map(it => it.key)
				}
			})
		},
		bindStoreChange(e) {
			this.storeIndex = e.detail.value
			this.store_id = this.stores[e.detail.value].id
			this.getVerifyLog()
		},
		scanCode() {
			uni.scanCode({
				success: (res) => {
					this.groupPayNo = res.result
					uni.showToast({ title: '扫码成功', icon: 'success' })
				},
				fail: () => {
					uni.showToast({ title: '扫码失败', icon: 'none' })
				}
			})
		},
		submit() {
			if (!this.groupPayNo || !this.store_id) {
				uni.showToast({ title: '请填写完整', icon: 'none' })
				return
			}
			if (this.groupPayNo.length < 6) {
				uni.showToast({ title: '请输入正确的团购券码', icon: 'none' })
				return
			}
			uni.showLoading({ title: '核销中...' })
			http.post('/admin/group/verify', {
				store_id: this.store_id,
				group_pay_no: this.groupPayNo
			}).then(res => {
				uni.hideLoading()
				if (res.code == 0) {
					this.success = true
					this.verifyInfo = '券码：' + this.groupPayNo
					this.groupPayNo = ''
					uni.showToast({ title: '核销成功', icon: 'success' })
					this.getVerifyLog()
					setTimeout(() => { this.success = false }, 3000)
				} else {
					uni.showModal({ title: '核销失败', content: res.msg || '验券失败', showCancel: false })
				}
			}).catch(() => {
				uni.hideLoading()
				uni.showToast({ title: '网络错误', icon: 'none' })
			})
		},
		getVerifyLog() {
			if (!this.store_id) return
			http.get('/admin/group/verify-log', {
				store_id: this.store_id
			}).then(res => {
				if (res.code == 0) {
					this.logList = res.data || []
				}
			})
		}
	}
}
</script>

<style lang="scss" scoped>
.page { min-height: 100vh; background: #f5f5f5; padding: 0 0 30rpx; }
.banner { text-align: center; padding: 40rpx 0 20rpx; background: linear-gradient(180deg, var(--main-color, #5AAB6E) 0%, #f5f5f5 100%); }
.banner-img { width: 50%; }
.card { background: #fff; border-radius: 16rpx; padding: 40rpx; margin: 20rpx 30rpx; }
.title { font-size: 32rpx; font-weight: 600; text-align: center; margin-bottom: 30rpx; }
.form-item { margin-bottom: 24rpx; }
.form-item .label { font-size: 28rpx; color: #333; margin-bottom: 12rpx; display: block; }
.picker-value { background: #f5f5f5; padding: 16rpx 24rpx; border-radius: 8rpx; font-size: 28rpx; color: #333; }
.input-row { display: flex; align-items: center; background: #f5f5f5; border-radius: 8rpx; padding-right: 16rpx; }
.input { flex: 1; padding: 16rpx 24rpx; font-size: 28rpx; }
.scan-icon { padding: 8rpx; }
.tip { font-size: 24rpx; color: #999; text-align: center; margin: 20rpx 0; }
.submit-btn { background: var(--main-color, #5AAB6E); color: #fff; border-radius: 44rpx; margin-top: 30rpx; font-size: 30rpx; height: 88rpx; line-height: 88rpx; }
.submit-btn[disabled] { background: #ccc; }
.success-card { background: #fff; border-radius: 16rpx; padding: 60rpx; margin: 20rpx 30rpx; display: flex; flex-direction: column; align-items: center; }
.success-text { font-size: 32rpx; color: var(--main-color, #5AAB6E); font-weight: 600; margin-top: 20rpx; }
.success-info { font-size: 24rpx; color: #999; margin-top: 12rpx; }
.log-item { padding: 20rpx 0; border-bottom: 1rpx solid #f5f5f5; }
.log-item:last-child { border-bottom: none; }
.log-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8rpx; }
.log-no { font-size: 26rpx; color: #333; font-weight: 500; }
.log-type { font-size: 22rpx; padding: 4rpx 12rpx; border-radius: 4rpx; }
.log-type.auto { background: #e6f7ff; color: #1890ff; }
.log-type.manual { background: #fff7e6; color: #fa8c16; }
.log-info { display: flex; justify-content: space-between; font-size: 24rpx; color: #999; }
.log-time { color: #ccc; }
</style>
