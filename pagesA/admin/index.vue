<template>
  <view class="page" :style="themeStyle">
    <view class="header">
      <view class="title">门店管理</view>
      <!-- 门店选择器 -->
      <view class="store-selector" @tap="showStorePicker = true" v-if="stores.length > 1">
        <text class="store-name">{{ storeInfo.store_name }}</text>
        <text class="arrow">▼</text>
      </view>
      <view class="store-name" v-else>{{ storeInfo.store_name }}</view>
    </view>

    <view class="menu-grid">
      <view class="menu-item" @tap="goPage('/pagesA/door/manage')">
        <image src="/static/icon/room-c.png" style="width:50rpx;height:50rpx;" />
        <text>房间管理</text>
      </view>
      <view class="menu-item" @tap="goPage('/pagesA/order/set')">
        <image src="/static/icon/order-c.png" style="width:50rpx;height:50rpx;" />
        <text>订单管理</text>
      </view>
      <view class="menu-item" @tap="goPage('/pagesA/statics/index')">
        <image src="/static/icon/data-c.png" style="width:50rpx;height:50rpx;" />
        <text>数据统计</text>
      </view>
      <view class="menu-item" @tap="goPage('/pagesA/store/set')">
        <image src="/static/icon/stores-c.png" style="width:50rpx;height:50rpx;" />
        <text>门店设置</text>
      </view>
      <view class="menu-item" @tap="goPage('/pagesA/product/manage')">
        <image src="/static/icon/pack.png" style="width:50rpx;height:50rpx;" />
        <text>商品管理</text>
      </view>
      <view class="menu-item" @tap="goPage('/pagesA/coupon/set')">
        <image src="/static/icon/coupon-c.png" style="width:50rpx;height:50rpx;" />
        <text>优惠券</text>
      </view>
      <view class="menu-item" @tap="goPage('/pagesA/vip/list')">
        <image src="/static/icon/balance-user.png" style="width:50rpx;height:50rpx;" />
        <text>会员管理</text>
      </view>
      <view class="menu-item" @tap="goPage('/pagesA/device/list')">
        <image src="/static/icon/device-c.png" style="width:50rpx;height:50rpx;" />
        <text>设备管理</text>
      </view>
      <view class="menu-item" @tap="goPage('/pagesA/admin/permission')">
        <image src="/static/icon/employee-c.png" style="width:50rpx;height:50rpx;" />
        <text>权限管理</text>
      </view>
    </view>

    <!-- 门店选择弹窗 -->
    <u-popup :show="showStorePicker" mode="bottom" @close="showStorePicker = false">
      <view class="store-picker">
        <view class="picker-header">
          <text class="picker-title">选择门店</text>
          <text class="picker-close" @tap="showStorePicker = false">✕</text>
        </view>
        <scroll-view scroll-y class="picker-list">
          <view 
            class="picker-item" 
            :class="{ active: storeInfo.id === item.value }"
            v-for="item in stores" 
            :key="item.value"
            @tap="selectStore(item)"
          >
            <text>{{ item.key }}</text>
            <text class="check" v-if="storeInfo.id === item.value">✓</text>
          </view>
        </scroll-view>
      </view>
    </u-popup>
  </view>
</template>

<script>
import http from '@/utils/http'

export default {
  data() {
    return {
      storeInfo: {},
      stores: [],
      showStorePicker: false
    }
  },

  onShow() {
    this.getStoreList()
  },

  methods: {
    async getStoreList() {
      try {
        const res = await http.get('/member/store/getStoreListByAdmin')
        if (res.code === 0 && res.data && res.data.length > 0) {
          this.stores = res.data
          // 优先从缓存读取上次选择的门店
          const cachedStoreId = uni.getStorageSync('admin_store_id')
          const cachedStore = cachedStoreId ? res.data.find(s => s.value == cachedStoreId) : null
          const store = cachedStore || res.data[0]
          this.storeInfo = {
            id: store.value,
            store_name: store.key
          }
          // 缓存当前选择
          uni.setStorageSync('admin_store_id', store.value)
        }
      } catch (e) {
      }
    },

    selectStore(item) {
      this.storeInfo = {
        id: item.value,
        store_name: item.key
      }
      // 缓存选择
      uni.setStorageSync('admin_store_id', item.value)
      this.showStorePicker = false
    },

    goPage(url) {
      const storeId = this.storeInfo.id || ''
      const storeName = encodeURIComponent(this.storeInfo.store_name || '')
      uni.navigateTo({ url: `${url}?store_id=${storeId}&store_name=${storeName}` })
    }
  }
}
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background: #f5f5f5;
}

.header {
  background: linear-gradient(135deg, #6da773fd 0%, #5AAB6E 100%);
  padding: 60rpx 40rpx;
  color: #fff;
}

.title {
  font-size: 40rpx;
  font-weight: bold;
  margin-bottom: 10rpx;
}

.store-name {
  font-size: 28rpx;
  opacity: 0.9;
}

.store-selector {
  display: inline-flex;
  align-items: center;
  background: rgba(255, 255, 255, 0.2);
  padding: 10rpx 20rpx;
  border-radius: 30rpx;
  
  .store-name {
    opacity: 1;
    margin-right: 10rpx;
  }
  
  .arrow {
    font-size: 20rpx;
    opacity: 0.8;
  }
}

.menu-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20rpx;
  padding: 20rpx;
}

.menu-item {
  background: #fff;
  border-radius: 20rpx;
  padding: 40rpx 20rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20rpx;
}

.menu-item text {
  font-size: 26rpx;
  color: #333;
}

// 门店选择弹窗
.store-picker {
  background: #fff;
  border-radius: 24rpx 24rpx 0 0;
  max-height: 70vh;
}

.picker-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 30rpx;
  border-bottom: 1rpx solid #f0f0f0;
}

.picker-title {
  font-size: 32rpx;
  font-weight: bold;
  color: #333;
}

.picker-close {
  font-size: 36rpx;
  color: #999;
  padding: 10rpx;
}

.picker-list {
  max-height: 500rpx;
}

.picker-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 30rpx;
  border-bottom: 1rpx solid #f5f5f5;
  font-size: 30rpx;
  color: #333;
  
  &.active {
    color: var(--main-color, #5AAB6E);
    font-weight: bold;
  }
  
  .check {
    color: var(--main-color, #5AAB6E);
    font-size: 36rpx;
  }
}
</style>
