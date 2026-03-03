<template>
	<view class="svg-seat-map">
		<!-- 颜色说明 -->
		<view class="color-legend">
			<view class="legend-item" v-for="(item, index) in legend" :key="index">
				<view class="color-box" :style="{ backgroundColor: item.color }"></view>
				<text>{{ item.text }}</text>
			</view>
		</view>
		
		<!-- SVG座位图 -->
		<view class="svg-container">
			<movable-area :style="{ width: '100%', height: (svgHeight + 50) + 'px' }">
				<movable-view 
					:style="{ width: svgWidth + 'px', height: svgHeight + 'px' }" 
					:scale="true" 
					:scale-min="0.5" 
					:scale-max="2" 
					:x="40" 
					:y="25" 
					direction="all"
				>
					<image 
						:src="svgUrl" 
						mode="scaleToFill" 
						@load="onImageLoad" 
						:style="{ width: svgWidth + 'px', height: svgHeight + 'px', position: 'absolute' }" 
					/>
					<view class="seats-layer">
						<view 
							v-for="(seat, index) in seats" 
							:key="index" 
							class="seat-item"
							:style="{ left: seat.svgX + 'px', top: seat.svgY + 'px' }" 
							@tap="onSeatTap(seat)"
						>
							<view 
								class="seat-box" 
								:style="{ backgroundColor: getSeatColor(seat) }"
							></view>
							<text class="seat-name">{{ seat.name }}</text>
						</view>
					</view>
				</movable-view>
			</movable-area>
		</view>
		
		<!-- 确认按钮 -->
		<view class="confirm-wrapper" v-if="showConfirm">
			<button class="confirm-btn" @tap="onConfirm">确认选座</button>
		</view>
	</view>
</template>

<script>
/**
 * SVG座位图组件 - 从首页KTV模块拆分
 * @property {String} svgUrl - SVG背景图URL
 * @property {Array} seats - 座位列表 [{id, name, svgX, svgY, status}]
 * @property {Boolean} showConfirm - 是否显示确认按钮
 * @event select - 选择座位事件
 * @event confirm - 确认选座事件
 */
export default {
	name: 'SvgSeatMap',
	props: {
		svgUrl: {
			type: String,
			default: ''
		},
		seats: {
			type: Array,
			default: () => []
		},
		showConfirm: {
			type: Boolean,
			default: true
		}
	},
	data() {
		return {
			svgWidth: 300,
			svgHeight: 300,
			selectedSeatId: null,
			legend: [
				{ color: '#e5e5e5', text: '禁用' },
				{ color: '#DC143C', text: '使用中' },
				{ color: '#7FDBFF', text: '待清洁' },
				{ color: '#F0E68C', text: '已预约' },
				{ color: 'green', text: '选中' }
			]
		}
	},
	methods: {
		onImageLoad(e) {
			this.svgWidth = e.detail.width
			this.svgHeight = e.detail.height
		},
		
		getSeatColor(seat) {
			if (seat.id === this.selectedSeatId) {
				return 'green'
			}
			const colorMap = {
				0: '#e5e5e5',  // 禁用
				1: '#fff',     // 空闲
				2: '#DC143C',  // 使用中
				3: '#e5e5e5',  // 维护中
				4: seat.is_cleaning ? '#7FDBFF' : '#F0E68C'  // 待清洁/已预约
			}
			return colorMap[seat.status] || '#fff'
		},
		
		onSeatTap(seat) {
			if (seat.status === 0 || seat.status === 3) {
				uni.showToast({ title: '该座位不可使用', icon: 'none' })
				return
			}
			if (seat.status === 2) {
				uni.showToast({ title: '座位正在使用中', icon: 'none' })
				return
			}
			if (seat.status === 4 && !seat.is_cleaning) {
				uni.showToast({ title: '座位已被预约', icon: 'none' })
				return
			}
			
			this.selectedSeatId = seat.id
			this.$emit('select', seat)
		},
		
		onConfirm() {
			if (!this.selectedSeatId) {
				uni.showToast({ title: '请先选择座位', icon: 'none' })
				return
			}
			const seat = this.seats.find(s => s.id === this.selectedSeatId)
			this.$emit('confirm', seat)
		}
	}
}
</script>

<style lang="scss" scoped>
.svg-seat-map {
	.color-legend {
		display: flex;
		justify-content: space-around;
		padding: 20rpx;
		background: #fff;
		
		.legend-item {
			display: flex;
			align-items: center;
			font-size: 24rpx;
			
			.color-box {
				width: 30rpx;
				height: 30rpx;
				border-radius: 6rpx;
				margin-right: 8rpx;
				border: 1rpx solid #ddd;
			}
		}
	}
	
	.svg-container {
		padding: 20rpx;
	}
	
	.seats-layer {
		position: absolute;
		z-index: 1;
	}
	
	.seat-item {
		position: absolute;
		
		.seat-box {
			width: 60rpx;
			height: 60rpx;
			border: 1rpx solid rgba(90, 90, 90, 0.4);
			border-radius: 8rpx;
		}
		
		.seat-name {
			position: absolute;
			left: 18rpx;
			top: 16rpx;
			font-size: 24rpx;
		}
	}
	
	.confirm-wrapper {
		padding: 20rpx;
		
		.confirm-btn {
			background: var(--main-color, #5AAB6E);
			color: #fff;
			border-radius: 40rpx;
			font-size: 30rpx;
		}
	}
}
</style>
