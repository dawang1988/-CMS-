<template>
  <view class="page" :style="themeStyle">
    <!-- 顶部搜索栏 -->
    <view class="top" :style="{ paddingTop: statusBarHeight + 'px' }">
      <view class="top-search">
        <view class="left">
          <view class="label" @tap="goLocation">
            <image src="/static/icon/city.png" style="width:30rpx;height:30rpx;" />
            <text>{{ cityName }}</text>
            <view class="arrow-down"></view>
          </view>
          <input 
            class="input" 
            type="text" 
            placeholder="输入关键字搜索门店" 
            v-model="name" 
            @input="onInputChange"
          />
        </view>
        <view class="right" @tap="goMapSearch">
          <image src="/static/icon/location.png" style="width:36rpx;height:36rpx;" />
        </view>
      </view>

      <!-- Banner轮播 -->
      <view class="banner" v-if="bannershowlist.length > 0">
        <swiper 
          :autoplay="true" 
          :indicator-dots="true" 
          indicator-color="rgba(255,255,255,0.5)"
          indicator-active-color="#fff"
          :circular="true"
        >
          <swiper-item v-for="(item, index) in bannershowlist" :key="index">
            <image :src="item.imgUrl" mode="aspectFill"></image>
          </swiper-item>
        </swiper>
      </view>
    </view>

    <!-- Tab切换 -->
    <view class="tab" @tap="handleTabChange">
      <view class="tab-left">
        <view :class="{ active: tabIndex === 0 }" data-index="0">附近门店</view>
      </view>
      <view class="tab-right">
        <view :class="{ active: tabIndex === 1 }" data-index="1">常用门店</view>
      </view>
    </view>

    <!-- 门店列表 -->
    <view class="container">
      <view class="lists">
        <view class="list">
          <block v-if="MainStorelist.length > 0">
            <view 
              v-for="(item, index) in MainStorelist" 
              :key="index"
              class="store-card" 
              @tap="goStore(item.store_id)"
            >
              <view class="image-container">
                <image 
                  class="store-card__image" 
                  :src="item.head_img || '/static/img/no-store.png'" 
                  mode="aspectFill"
                ></image>
              </view>
              <view class="text-container">
                <view class="standard">
                  空闲: <text style="color:#e0260d">{{ item.freeRoomNum }}</text>
                </view>
                <view class="name">{{ item.store_name }}</view>
                <view class="address">{{ item.address }}</view>
                <view class="item-bottom">
                  <view class="info">
                    <view 
                      class="position" 
                      @tap.stop="goTencentMap(item)" 
                      v-if="item.distance"
                    >
                      <text class="distance">{{ item.distance }}</text>km
                    </view>
                  </view>
                  <view class="door-button">立即预定</view>
                </view>
              </view>
            </view>
          </block>

          <!-- 空状态 -->
          <block v-else>
            <view class="noStoreInfo">
              <image class="noStore-image" src="/static/img/no-store.png" mode="aspectFit"></image>
              <text v-if="tabIndex === 1">暂无常用门店</text>
              <text v-else>暂无门店</text>
              <text v-if="tabIndex === 1">去附近门店看看吧</text>
              <button class="go-near" @tap="goNear" v-if="tabIndex === 1">
                查看附近门店
              </button>
            </view>
          </block>
        </view>
      </view>
    </view>

    <!-- 底部占位 -->
    <view style="margin-bottom: 80rpx;"></view>
  </view>
</template>

<script>
import { getDoorList, getBannerList } from '@/api/door'
import listMixin from '@/mixins/listMixin'

export default {
  mixins: [listMixin],
  data() {
    return {
      statusBarHeight: 0,
      bannershowlist: [],
      MainStorelist: [],
      cityName: '选择城市',
      name: '',
      lat: '',
      lon: '',
      tabIndex: 0,
      store_id: ''
    }
  },

  onLoad(options) {
    this.statusBarHeight = uni.getSystemInfoSync().statusBarHeight
    
    if (options.store_id) {
      this.store_id = options.store_id
    }

    // 获取缓存的城市名称
    const cityName = uni.getStorageSync('cityName')
    if (cityName) {
      this.cityName = cityName
    }

    this.getBannerdata()
  },

  onShow() {
    this.tabIndex = 0
    this.getLocation()
  },

  onPullDownRefresh() {
    this.pageNo = 1
    this.canLoadMore = true
    this.MainStorelist = []
    this.getMainListdata('refresh')
    setTimeout(() => {
      uni.stopPullDownRefresh()
    }, 500)
  },

  onReachBottom() {
    if (this.canLoadMore) {
      this.getMainListdata('', this.tabIndex === 1)
    } else {
      uni.showToast({
        title: '没有更多了...',
        icon: 'none'
      })
    }
  },

  methods: {
    // 获取位置
    getLocation() {
      uni.getLocation({
        type: 'gcj02',
        success: (res) => {
          this.lat = res.latitude
          this.lon = res.longitude
          this.getMainListdata('refresh')
        },
        fail: () => {
          this.getMainListdata('refresh')
        }
      })
    },

    // 获取门店列表
    async getMainListdata(type, often = false) {
      if (type === 'refresh') {
        this.MainStorelist = []
        this.canLoadMore = true
        this.pageNo = 1
      }

      try {
        const res = await getDoorList({
          pageNo: this.pageNo,
          pageSize: 10,
          cityName: this.cityName === '选择城市' ? '' : this.cityName,
          lat: this.lat,
          lon: this.lon,
          name: this.name,
          often: often
        })

        if (res.code === 0) {
          const list = res.data.list || []

          if (list.length === 0) {
            this.canLoadMore = false
          } else {
            this.MainStorelist = [...this.MainStorelist, ...list]
            this.pageNo++
            this.canLoadMore = this.MainStorelist.length < res.data.total
          }
        }
      } catch (e) {
        // error handled
      }
    },

    // 获取Banner
    async getBannerdata() {
      try {
        const res = await getBannerList()
        if (res.code === 0) {
          this.bannershowlist = res.data || []
        }
      } catch (e) {
        // error handled
      }
    },

    // 搜索输入
    onInputChange(e) {
      this.name = e.detail.value
      this.getMainListdata('refresh')
    },

    // 选择城市
    goLocation() {
      uni.navigateTo({
        url: '/pages/location/index',
        events: {
          citySelected: (data) => {
            this.cityName = data
            uni.setStorageSync('cityName', data)
            this.getBannerdata()
            this.getMainListdata('refresh')
          }
        }
      })
    },

    // 地图搜索
    goMapSearch() {
      uni.navigateTo({
        url: '/pages/map/index'
      })
    },

    // 进入门店
    goStore(storeId) {
      uni.setStorageSync('global_store_id', storeId)
      uni.switchTab({
        url: '/pages/door/index'
      })
    },

    // 打开地图导航
    goTencentMap(store) {
      uni.openLocation({
        latitude: store.latitude,
        longitude: store.longitude,
        name: store.store_name,
        address: store.address,
        scale: 18
      })
    },

    // Tab切换
    handleTabChange(e) {
      const index = e.target.dataset.index
      if (index === undefined) return

      // 常用门店需要登录
      if (index == 1) {
        const token = uni.getStorageSync('token')
        if (!token) {
          uni.showToast({
            title: '请先登录',
            icon: 'none'
          })
          setTimeout(() => {
            uni.navigateTo({
              url: '/pages/user/login'
            })
          }, 1000)
          return
        }
      }

      this.tabIndex = parseInt(index)
      this.MainStorelist = []
      this.getMainListdata('refresh', this.tabIndex === 1)
    },

    // 查看附近门店
    goNear() {
      this.tabIndex = 0
      this.getMainListdata('refresh')
    }
  }
}
</script>

<style lang="scss" scoped>
.page {
  width: 100%;
  min-height: 100vh;
  background: #f5f5f5;
}

.top {
  width: 100%;
  background: linear-gradient(180deg, var(--main-color, #6da773fd) 0%, #FFFFFF 100%);
}

.top-search {
  display: flex;
  justify-content: center;
  padding: 20rpx 20rpx;
}

.top-search .left {
  background: #fff;
  flex: 1;
  margin-right: 20rpx;
  display: flex;
  height: 60rpx;
  padding: 10rpx 0;
  box-sizing: border-box;
  border-radius: 25rpx;
}

.top-search .label {
  width: 200rpx;
  display: flex;
  justify-content: center;
  align-items: center;
  border-right: 1rpx solid #ddd;
}

.top-search .label text {
  font-size: 26rpx;
  margin: 0 6rpx;
  max-width: 120rpx;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.arrow-down {
  width: 0;
  height: 0;
  border-top: 10rpx solid var(--main-color, #6da773fd);
  border-right: 10rpx solid transparent;
  border-left: 10rpx solid transparent;
}

.top-search .input {
  flex: 1;
  padding: 0 20rpx;
  font-size: 26rpx;
}

.top-search .right {
  width: 60rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.banner {
  height: 300rpx;
  width: 100%;
  overflow: hidden;
}

.banner swiper {
  height: 100%;
  width: 100%;
}

.banner image {
  width: 100%;
  height: 100%;
}

.tab {
  height: 80rpx;
  width: 100%;
  display: flex;
  background: #fff;
}

.tab .tab-left,
.tab .tab-right {
  width: 50%;
}

.tab view view {
  display: flex;
  font-size: 31rpx;
  align-items: center;
  justify-content: center;
  height: 80rpx;
  font-weight: 500;
  color: #3E3E3E;
}

.tab view view.active {
  color: var(--main-color, #6da773fd);
  position: relative;
}

.tab view view.active::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 76rpx;
  height: 7rpx;
  background-color: var(--main-color, #6da773fd);
  border-radius: 17rpx;
}

.container {
  margin-top: 10rpx;
}

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

.text-container .standard {
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

.text-container .name {
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

.text-container .address {
  font-size: 23rpx;
  margin-top: 8rpx;
  color: #686464;
  height: 70rpx;
  margin-right: 4rpx;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}

.text-container .item-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 10rpx;
}

.text-container .item-bottom .position {
  font-size: 22rpx;
  line-height: 40rpx;
  padding: 0 10rpx;
  border-radius: 10rpx;
  color: var(--main-color, #5AAB6E);
  border: 1px solid var(--main-color, #5AAB6E);
}

.text-container .item-bottom .door-button {
  height: 58rpx;
  line-height: 58rpx;
  padding: 0 20rpx;
  font-size: 28rpx;
  margin-right: 20rpx;
  color: #fff;
  border-radius: 5px;
  background-color: var(--main-color, #6da773fd);
}

.noStoreInfo {
  font-size: 30rpx;
  text-align: center;
  margin: 100rpx;
  color: #BCBCBC;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.noStoreInfo .noStore-image {
  height: 220rpx;
  width: 220rpx;
  margin-bottom: 20rpx;
}

.noStoreInfo text {
  margin: 10rpx 0;
}

.noStoreInfo .go-near {
  font-size: 32rpx;
  width: 360rpx;
  height: 80rpx;
  color: #fff;
  border-radius: 25px;
  background-color: var(--main-color, #6da773fd);
  margin-top: 50rpx;
}
</style>
