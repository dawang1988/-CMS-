<template>
	<view class="container" :style="themeStyle">
		<block v-if="list.length">
			<view class="list">
				<view class="item" v-for="(item, index) in list" :key="index">
					<view class="info">
						<view class="line"><label>订单编号：</label><text>{{ item.order_id }}</text></view>
						<view class="line"><label>开始时间：</label><text>{{ item.beginTime }}</text></view>
						<view class="line"><label>结束时间：</label><text>{{ item.end_time }}</text></view>
						<view class="line"><label>订单价格：</label><text>{{ item.amount / 100.0 }}</text></view>
						<view class="line"><label>预订时间：</label><text>{{ item.bookTime }}</text></view>
					</view>
					<view class="btns">
						<view class="btn red" @tap="setStatus(3, item)">拒绝</view>
						<view class="btn green" @tap="setStatus(2, item)">同意</view>
					</view>
				</view>
			</view>
			<view class="noteMore">没有更多了</view>
		</block>
		<block v-else>
			<view class="nodata-list">暂无数据</view>
		</block>
	</view>
</template>

<script>
import http from '@/utils/http.js'

export default {
	data() {
		return {
			store_id: '',
			list: []
		}
	},
	onLoad(options) {
		this.store_id = options.store_id || ''
		this.getMainListdata()
	},
	methods: {
		getMainListdata() {
			this.list = []
			const token = uni.getStorageSync('token')
			if (!token) return
			uni.showLoading({ title: '正在加载' })
			http.post('/member/manager/getYDCancelAuthList/' + this.store_id).then(res => {
				uni.hideLoading()
				if (res.code == 0) {
					this.list = res.data || []
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			}).catch(() => {
				uni.hideLoading()
			})
		},
		setStatus(status, item) {
			const token = uni.getStorageSync('token')
			if (!token) return
			http.post('/member/manager/auditYD', {
				audit_result: status,
				store_id: this.store_id,
				order_id: item.order_id
			}).then(res => {
				if (res.code == 0) {
					uni.showToast({ title: '操作成功' })
					setTimeout(() => {
						this.getMainListdata()
					}, 1000)
				} else {
					uni.showModal({ content: res.msg, showCancel: false })
				}
			})
		}
	}
}
</script>

<style lang="scss" scoped>
.container { min-height: 100vh; background: #f5f5f5; }
.list { padding-top: 10rpx; }
.list .item { margin: 20rpx; padding: 20rpx 30rpx; background: #fff; border-radius: 16rpx; display: flex; justify-content: space-between; align-items: center; }
.list .item .info .line { display: flex; margin-bottom: 10rpx; }
.list .item .info .line:last-child { margin-bottom: 0; }
.list .item .info .line label { flex-shrink: 0; font-size: 28rpx; }
.list .item .info .line text { font-size: 28rpx; }
.list .item .btns .btn { margin: 20rpx 0; padding: 0; width: 180rpx; text-align: center; height: 70rpx; line-height: 70rpx; font-size: 28rpx; border-radius: 16rpx; color: #fff; }
.list .item .btns .red { background: #e54d42; }
.list .item .btns .green { background: var(--main-color, #5AAB6E); }

</style>
