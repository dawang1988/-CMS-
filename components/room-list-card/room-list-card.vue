<template>
	<!-- 棋牌/KTV 房间列表 -->
	<view class="lists">
		<view class="item" v-for="(item, index) in rooms" :key="item.id">
			<view class="top">
				<view class="left">
					<image 
						class="img" 
						:src="getFirstImage(item) || '/static/logo.png'" 
						@tap="$emit('preview-image', index)" 
						:mode="getFirstImage(item) ? 'scaleToFill' : 'aspectFit'"
					></image>
					
					<view v-if="item.status == 0" class="flag disabled">禁用</view>
					<view v-else-if="item.status == 1" class="flag undo">空闲中</view>
					<view v-else-if="item.status == 2" class="flag doing">使用中</view>
					<view v-else-if="item.status == 3" class="flag disabled">维护中</view>
					<view v-else-if="item.status == 4 && item.is_cleaning && item.cleaning_status == 2" class="flag qingjieing">清洁中</view>
					<view v-else-if="item.status == 4 && item.is_cleaning" class="flag daiqingjie">待清洁</view>
					<view v-else class="flag bukeyong">已预约</view>
				</view>
				
				<view class="right" @tap="$emit('go-order', { status: item.status, index, id: item.id })">
					<view class="info">
						<view class="name">
							<view class="type">{{ roomTypeText(item.type) }}</view>
							{{ item.name || item.room_name }}
						</view>
						<view class="tags">
							<view class="tag" v-for="(labelitem, labelindex) in splitString(item.label)" :key="labelindex">
								{{ labelitem }}
							</view>
						</view>
					</view>
					
					<view class="line2">
						<view class="priceLabel">
							<view class="price">
								<label class="color-attention">￥{{ item.price }}</label>
								元/时
							</view>
						</view>
						<view class="priceLabel" v-if="item.vip_price_list && item.vip_price_list.length > 0">
							<view class="price">
								<text style="color: rgb(255, 51, 0);font-size:28rpx;">会员</text>
								<label class="color-attention">￥{{ item.vip_price_list[item.vip_price_list.length-1].price }}</label>
								元/时起
							</view>
						</view>
					</view>
					
					<view class="pkgInfo" v-for="(pkgItem, pkgIndex) in (item.pkg_list || []).slice(0, 3)" :key="pkgIndex">
						{{ pkgItem.pkg_name }} ￥{{ pkgItem.price }}
					</view>
					
					<view class="line2 bt">
						<view class="bottom">
							<view v-if="item.status == 0" class="btn disabled">禁用</view>
							<view v-else class="btn bg-primary">预定</view>
						</view>
					</view>
				</view>
			</view>
			
			<!-- 时段价格 -->
			<view class="timeIndexPrice">
				<view class="index" v-if="item.morning_price">
					上午场 <text class="price"> ￥{{ item.morning_price }}</text>
				</view>
				<view class="index" v-if="item.afternoon_price">
					下午场 <text class="price"> ￥{{ item.afternoon_price }}</text>
				</view>
				<view class="index" v-if="item.night_price">
					夜间场 <text class="price"> ￥{{ item.night_price }}</text>
				</view>
				<view class="index" v-if="item.tx_price">
					通宵场 <text class="price"> ￥{{ item.tx_price }}</text>
				</view>
			</view>
			
			<!-- 时间轴 -->
			<view class="foot">
				<view class="foot-top">
					<view class="labels">
						<view class="label disabled">不可用</view>
						<view class="label">可预约</view>
					</view>
					<view class="line3">
						<view class="time-line" v-if="item.timeText">
							<image src="/static/icon/order-time.png" />
							<text>{{ item.timeText }}被预定</text>
						</view>
						<view class="more" v-if="item.order_time_list && item.order_time_list.length > 1" @tap="$emit('show-reserve', item.order_time_list)">
							更多
						</view>
					</view>
				</view>
				<view class="times">
					<view 
						class="time" 
						:class="{ disabled: houritem2.disable }" 
						v-for="(houritem2, hourindex) in timeHourAllArr[index]" 
						:key="hourindex"
					>
						{{ houritem2.hour }}
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
import magicMixin from '@/utils/magicMixin.js'

const ROOM_TYPE_MAP = {
	0: '特价包', 1: '小包', 2: '中包', 3: '大包', 4: '豪包',
	5: '商务包', 6: '斯洛克', 7: '中式黑八'
}

export default {
	name: 'RoomListCard',
	options: {
		styleIsolation: 'shared'
	},
	props: {
		rooms: { type: Array, default: () => [] },
		timeHourAllArr: { type: Array, default: () => [] }
	},
	methods: {
		splitString(str) {
			return magicMixin.split(str)
		},
		roomTypeText(type) {
			// 兼容数字和文字两种格式
			if (ROOM_TYPE_MAP[type] !== undefined) return ROOM_TYPE_MAP[type]
			// 如果是文字类型，直接返回
			if (type && typeof type === 'string' && isNaN(type)) return type
			return '美式球桌'
		},
		getFirstImage(item) {
			// 优先使用 imageUrls（逗号分隔的完整URL）
			if (item.imageUrls) {
				const arr = item.imageUrls.split(',')
				if (arr.length > 0 && arr[0]) return arr[0]
			}
			// 其次解析 images（可能是 JSON 字符串）
			const imgStr = item.images
			if (!imgStr) return ''
			if (typeof imgStr === 'string') {
				try {
					const parsed = JSON.parse(imgStr)
					if (Array.isArray(parsed) && parsed.length > 0) return parsed[0]
				} catch(e) {
					const arr = imgStr.split(',')
					if (arr.length > 0 && arr[0]) return arr[0]
				}
			}
			if (Array.isArray(imgStr) && imgStr.length > 0) return imgStr[0]
			return ''
		}
	}
}
</script>

<style lang="scss">
/* 房间列表 */
.lists .item { padding: 27rpx 34rpx; width: 629rpx; border-radius: 17rpx; margin: 26rpx auto; border-bottom: 20rpx solid #f5f5f5; background: #fff; }
.lists .item:last-child { border-bottom: 0; }
.lists .item .top { display: flex; justify-content: space-between; }
.lists .item .top .left { width: 200rpx; height: 260rpx; border-radius: 10rpx; position: relative; overflow: hidden; margin-right: 30rpx; flex-shrink: 0; }
.lists .item .top .left .img { width: 200rpx; height: 250rpx; }
.lists .item .top .left .flag { position: absolute; bottom: 0; left: 0; width: 100%; height: 50rpx; line-height: 50rpx; text-align: center; font-size: 30rpx; color: #fff; }
.lists .item .top .left .doing { background: var(--main-red); }
.lists .item .top .left .undo { background: var(--main-color); }
.lists .item .top .left .disabled { background: #817F7F; }
.lists .item .top .left .daiqingjie { background: #58b92b; }
.lists .item .top .left .qingjieing { background: #1E90FF; }
.lists .item .top .left .bukeyong { background: #FFA313; }
.lists .item .top .right { width: 470rpx; display: flex; flex-direction: column; justify-content: space-between; }
.lists .item .top .right .info { display: flex; flex-direction: column; }
.lists .item .top .right .name { font-weight: 700; font-size: 30rpx; display: flex; align-items: center; }
.lists .item .top .right .name .type { display: flex; justify-content: center; align-items: center; background: var(--main-color, #5AAB6E); border-radius: 35rpx; font-size: 26rpx; color: #fff; padding: 4rpx 16rpx; margin-right: 8rpx; white-space: nowrap; }
.lists .item .top .right .tags { display: flex; flex-wrap: wrap; margin-top: 20rpx; }
.lists .item .top .right .tags .tag { margin: 0 10rpx 10rpx 0; border: 1px solid var(--main-color); border-radius: 8rpx; padding: 0 10rpx; color: var(--main-color); font-size: 22rpx; line-height: 28rpx; }
.lists .item .top .right .line2 { display: flex; justify-content: space-between; }
.lists .item .top .right .line2.bt { display: flex; justify-content: flex-end; height: 50rpx; line-height: 50rpx; }
.priceLabel { display: flex; align-items: center; flex-wrap: wrap; }
.lists .item .top .right .priceLabel .price { font-weight: 700; font-size: 17rpx; line-height: 32rpx; }
.lists .item .top .right .priceLabel .price .color-attention { font-weight: 700; font-size: 31rpx; line-height: 32rpx; color: var(--main-color); }
.color-attention { color: #ea4e4e; }
.lists .item .top .right .bottom { display: flex; justify-content: right; align-items: center; }
.lists .item .top .right .bottom .btn { width: 125rpx; height: 50rpx; text-align: center; line-height: 50rpx; border-radius: 30rpx; margin: 10rpx; font-size: 26rpx; }
.lists .item .top .right .bottom .disabled { background: #bebebe; color: #fff; }
.bg-primary { background: var(--main-color, #5AAB6E) !important; color: #fff !important; }

/* 时间轴 */
.lists .item .foot { margin-top: 10rpx; }
.lists .item .foot .foot-top { display: flex; justify-content: space-between; font-size: 22rpx; }
.lists .item .foot .foot-top .labels { display: flex; }
.lists .item .foot .foot-top .label::before { content: ""; display: inline-block; margin-right: 6rpx; width: 20rpx; height: 20rpx; background: #e8e8e8; }
.lists .item .foot .foot-top .label { margin-right: 20rpx; }
.lists .item .foot .foot-top .disabled::before { background: var(--main-color); }
.lists .item .foot .times { margin-top: 10rpx; display: flex; justify-content: space-between; }
.lists .item .foot .times .time { font-size: 20rpx; text-align: center; color: #aaa; border-top: 10rpx solid #e8e8e8; width: 24rpx; line-height: 30rpx; }
.lists .item .foot .times .disabled { border-color: var(--main-color); }
.line3 { display: flex; justify-content: space-between; align-items: center; font-size: 22rpx; color: #FFA313; }
.line3 .time-line { display: flex; justify-content: center; align-items: center; }
.line3 .time-line image { width: 24rpx; height: 24rpx; margin-right: 12rpx; }
.line3 .more { color: var(--main-color); font-size: 21rpx; display: flex; align-items: center; margin-left: 10rpx; }

/* 时段价格 */
.lists .item .timeIndexPrice { display: flex; justify-content: space-around; margin: 10rpx 0rpx; }
.lists .item .timeIndexPrice .index { font-size: 22rpx; border-radius: 5rpx; padding: 5rpx 10rpx; background-color: #2fe75d31; }
.lists .item .timeIndexPrice .index .price { color: #fd4a33; }
.pkgInfo { margin-top: 10rpx; font-size: 26rpx; font-weight: 600; color: #fd4a33; }
</style>
