<template>
  <view class="store-card" @tap="handleTap">
    <view class="image-container">
      <image 
        class="store-card__image" 
        :src="store.head_img || '/static/logo.png'" 
        mode="aspectFill"
      />
    </view>
    <view class="text-container">
      <view class="standard">
        空闲: <text class="free-num">{{ store.freeRoomNum || 0 }}</text>
      </view>
      <view class="name">{{ store.store_name }}</view>
      <view class="address">{{ store.address }}</view>
      <view class="score-row" v-if="store.avg_score > 0">
        <text class="score-star">★</text>
        <text class="score-num">{{ store.avg_score }}</text>
        <text class="review-count">{{ store.review_count || 0 }}条评价</text>
      </view>
      <view class="item-bottom">
        <view class="info">
          <view 
            class="position" 
            @tap.stop="handleNavigation" 
            v-if="store.distance"
          >
            <text class="distance">{{ store.distance }}</text>km
          </view>
        </view>
        <view class="door-button">立即预定</view>
      </view>
    </view>
  </view>
</template>

<script>
export default {
  name: 'StoreCard',
  props: {
    store: {
      type: Object,
      required: true,
      default: () => ({})
    }
  },
  methods: {
    handleTap() {
      this.$emit('tap', this.store)
    },
    handleNavigation() {
      if (!this.store.latitude || !this.store.longitude) {
        uni.showToast({ title: '暂无位置信息', icon: 'none' })
        return
      }
      uni.openLocation({
        latitude: Number(this.store.latitude),
        longitude: Number(this.store.longitude),
        name: this.store.store_name || '',
        address: this.store.address || '',
        scale: 18
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.store-card {
  height: 300rpx;
  background-color: #fff;
  border-radius: 20rpx;
  margin: 0 15rpx 20rpx;
  display: flex;
  flex-direction: row;
}

.store-card__image {
  width: 200rpx;
  height: 260rpx;
  margin: 20rpx;
  border-radius: 15rpx;
}

.text-container {
  flex: 1;
  position: relative;
  padding: 15rpx 10rpx 15rpx 0;
}

.standard {
  font-size: 24rpx;
  line-height: 40rpx;
  text-align: center;
  position: absolute;
  top: 15rpx;
  right: 10rpx;
  width: 120rpx;
  height: 42rpx;
  background-color: #FFFFFF;
  color: var(--main-color, #5AAB6E);
  border: 1rpx solid var(--main-color, #5AAB6E);
  border-radius: 20rpx;
}

.free-num {
  color: #e0260d;
}

.name {
  width: 320rpx;
  margin-top: 15rpx;
  font-size: 32rpx;
  font-weight: bold;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.5em;
}

.address {
  font-size: 23rpx;
  margin-top: 8rpx;
  color: #686464;
  height: 50rpx;
  margin-right: 4rpx;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}

.score-row {
  display: flex;
  align-items: center;
  margin-top: 8rpx;
}

.score-star {
  color: #FFB800;
  font-size: 24rpx;
}

.score-num {
  color: #FFB800;
  font-size: 24rpx;
  font-weight: 600;
  margin-left: 4rpx;
}

.review-count {
  color: #999;
  font-size: 22rpx;
  margin-left: 12rpx;
}

.item-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 10rpx;
}

.position {
  font-size: 22rpx;
  line-height: 40rpx;
  padding: 0 10rpx;
  border-radius: 10rpx;
  color: var(--main-color, #5AAB6E);
  border: 1px solid var(--main-color, #5AAB6E);
}

.door-button {
  height: 58rpx;
  line-height: 58rpx;
  padding: 0 20rpx;
  font-size: 28rpx;
  margin-right: 20rpx;
  color: #fff;
  border-radius: 5px;
  background-color: var(--main-color, #5AAB6E);
}
</style>
