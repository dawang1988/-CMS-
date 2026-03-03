<template>
  <view class="page" :style="themeStyle">
    <view class="search-bar">
      <input 
        class="search-input" 
        placeholder="搜索城市" 
        v-model="keyword"
        @input="searchCity"
      />
    </view>

    <view class="current-city" v-if="currentCity">
      <view class="label">当前城市</view>
      <view class="city-item" @tap="selectCity(currentCity)">
        {{ currentCity }}
      </view>
    </view>

    <view class="hot-cities">
      <view class="label">热门城市</view>
      <view class="city-list">
        <view 
          class="city-item" 
          v-for="(city, index) in hotCities" 
          :key="index"
          @tap="selectCity(city)"
        >
          {{ city }}
        </view>
      </view>
    </view>

    <view class="all-cities" v-if="filteredCities.length > 0">
      <view class="label">全部城市</view>
      <view class="city-list">
        <view 
          class="city-item" 
          v-for="(city, index) in filteredCities" 
          :key="index"
          @tap="selectCity(city)"
        >
          {{ city }}
        </view>
      </view>
    </view>
  </view>
</template>

<script>
export default {
  data() {
    return {
      keyword: '',
      currentCity: '',
      hotCities: ['北京', '上海', '广州', '深圳', '杭州', '成都', '重庆', '武汉'],
      allCities: [
        '北京', '上海', '广州', '深圳', '杭州', '成都', '重庆', '武汉',
        '南京', '天津', '苏州', '西安', '长沙', '沈阳', '青岛', '郑州',
        '大连', '东莞', '宁波', '厦门', '福州', '无锡', '合肥', '昆明',
        '哈尔滨', '济南', '佛山', '长春', '温州', '石家庄', '南宁', '常州'
      ],
      filteredCities: []
    }
  },

  onLoad() {
    this.getCurrentCity()
    this.filteredCities = this.allCities
  },

  methods: {
    getCurrentCity() {
      uni.getLocation({
        type: 'gcj02',
        success: (res) => {
          // 这里应该调用地理编码API获取城市名称
          // 简化处理，使用默认城市
          this.currentCity = '深圳'
        }
      })
    },

    searchCity() {
      if (this.keyword) {
        this.filteredCities = this.allCities.filter(city => 
          city.includes(this.keyword)
        )
      } else {
        this.filteredCities = this.allCities
      }
    },

    selectCity(city) {
      const eventChannel = this.getOpenerEventChannel()
      if (eventChannel) {
        eventChannel.emit('citySelected', city)
      }
      const pages = getCurrentPages()
      if (pages.length > 1) {
        uni.navigateBack()
      } else {
        uni.switchTab({ url: '/pages/door/index' })
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background: #f5f5f5;
}

.search-bar {
  background: #fff;
  padding: 20rpx;
}

.search-input {
  height: 70rpx;
  background: #f5f5f5;
  border-radius: 35rpx;
  padding: 0 30rpx;
  font-size: 28rpx;
}

.current-city, .hot-cities, .all-cities {
  background: #fff;
  margin-top: 20rpx;
  padding: 30rpx;
}

.label {
  font-size: 28rpx;
  color: #999;
  margin-bottom: 20rpx;
}

.city-list {
  display: flex;
  flex-wrap: wrap;
  gap: 20rpx;
}

.city-item {
  padding: 20rpx 40rpx;
  background: #f5f5f5;
  border-radius: 10rpx;
  font-size: 28rpx;
  color: #333;
}
</style>
