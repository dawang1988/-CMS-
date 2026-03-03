<template>
  <view class="page" :style="themeStyle">
    <map 
      class="map" 
      :latitude="latitude" 
      :longitude="longitude"
      :markers="markers"
      :show-location="true"
      @markertap="onMarkerTap"
    ></map>

    <view class="store-card" v-if="selectedStore">
      <view class="store-info">
        <view class="store-name">{{ selectedStore.store_name }}</view>
        <view class="store-address">{{ selectedStore.address }}</view>
        <view class="store-distance" v-if="selectedStore.distance">
          距离{{ selectedStore.distance }}km
        </view>
      </view>
      <button class="btn" @tap="goStore">进入门店</button>
    </view>
  </view>
</template>

<script>
import { getDoorList } from '@/api/door'

export default {
  data() {
    return {
      latitude: 39.908823,
      longitude: 116.397470,
      markers: [],
      storeList: [],
      selectedStore: null
    }
  },

  onLoad() {
    this.getLocation()
  },

  methods: {
    getLocation() {
      uni.getLocation({
        type: 'gcj02',
        success: (res) => {
          this.latitude = res.latitude
          this.longitude = res.longitude
          this.getStoreList()
        }
      })
    },

    async getStoreList() {
      try {
        const res = await getDoorList({
          lat: this.latitude,
          lon: this.longitude,
          pageNo: 1,
          pageSize: 50
        })

        if (res.code === 0) {
          this.storeList = res.data.list || []
          this.markers = this.storeList.map((store, index) => ({
            id: store.store_id,
            latitude: store.latitude,
            longitude: store.longitude,
            title: store.store_name,
            iconPath: '/static/logo.png',
            width: 30,
            height: 30
          }))
        }
      } catch (e) {
        // error handled silently
      }
    },

    onMarkerTap(e) {
      const markerId = e.detail.markerId
      this.selectedStore = this.storeList.find(store => store.store_id === markerId)
    },

    goStore() {
      if (this.selectedStore) {
        uni.setStorageSync('global_store_id', this.selectedStore.store_id)
        uni.switchTab({
          url: '/pages/door/index'
        })
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.page {
  width: 100%;
  height: 100vh;
  position: relative;
}

.map {
  width: 100%;
  height: 100%;
}

.store-card {
  position: absolute;
  bottom: 40rpx;
  left: 20rpx;
  right: 20rpx;
  background: #fff;
  border-radius: 20rpx;
  padding: 30rpx;
  box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.store-info {
  flex: 1;
}

.store-name {
  font-size: 32rpx;
  font-weight: bold;
  margin-bottom: 10rpx;
}

.store-address {
  font-size: 24rpx;
  color: #666;
  margin-bottom: 8rpx;
}

.store-distance {
  font-size: 22rpx;
  color: #999;
}

.btn {
  padding: 20rpx 40rpx;
  background: var(--main-color, #6da773fd);
  color: #fff;
  border-radius: 10rpx;
  font-size: 28rpx;
}
</style>
