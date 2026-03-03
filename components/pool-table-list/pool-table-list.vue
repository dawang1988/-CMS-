<template>
	<!-- 台球房间列表 -->
	<view class="tab-container">
		<view class="tab-item" v-for="(item, index) in rooms" :key="item.id">
			<view class="tab-left">
				<view class="tab-left-top">
					<!-- 可开台 -->
					<view 
						class="tab-img-box-begin" 
						v-if="item.status == 1 || item.status == 2 || item.status == 4" 
						@tap="$emit('go-order', { status: item.status, index, id: item.id })"
					>
						<view class="tab-info">
							<text class="tab-roomName">{{ item.name }}</text>
							<view class="price">
								<label class="color-attention">￥{{ item.price }}</label>
								元/小时
							</view>
						</view>
						<view class="tab-button">
							<button class="begin-button">开台</button>
						</view>
					</view>
					
					<!-- 使用中 -->
					<view class="tab-img-box-wait" v-if="item.status == 3">
						<view class="tab-wait">
							<text class="tab-wait-roomName">{{ item.name }}</text>
							<view></view>
							<text style="font-size: 30rpx;">预计等待：</text>
						</view>
						<view class="tab-wait-time" v-if="item.waitTime">
							<view class="time-number">{{ item.waitTime.hours }}</view>
							<view style="width: 10rpx;"></view>
							<view class="time-date">时</view>
							<view style="width: 10rpx;"></view>
							<view class="time-number">{{ item.waitTime.minutes }}</view>
							<view style="width: 10rpx;"></view>
							<view class="time-date">分</view>
						</view>
					</view>
					
					<!-- 禁用 -->
					<view class="tab-img-box-wait" v-if="item.status == 0">
						<view class="tab-wait">
							<text class="tab-wait-roomName">{{ item.name }}</text>
							<view></view>
						</view>
						<view class="tab-wait-time">
							<view class="time-disable">禁用</view>
						</view>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	name: 'PoolTableList',
	options: {
		styleIsolation: 'shared'
	},
	props: {
		rooms: { type: Array, default: () => [] }
	}
}
</script>
