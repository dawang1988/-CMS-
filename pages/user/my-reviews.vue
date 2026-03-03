<template>
  <view class="my-reviews-page" :style="themeStyle">
    <view class="list" v-if="list.length > 0">
      <view class="review-item" v-for="(item, index) in list" :key="index">
        <view class="store-info">
          <text class="store-name">{{ item.store_name }}</text>
          <text class="room-name">{{ item.room_name }}</text>
        </view>
        
        <view class="review-content">
          <view class="score-row">
            <view class="stars">
              <text v-for="i in 5" :key="i" :class="['star', { active: i <= item.score }]">★</text>
            </view>
            <text class="time">{{ item.create_time }}</text>
          </view>
          
          <view class="content" v-if="item.content">{{ item.content }}</view>
          
          <view class="tags" v-if="item.tags">
            <text class="tag" v-for="(tag, idx) in parseTags(item.tags)" :key="idx">{{ tag }}</text>
          </view>
          
          <view class="images" v-if="item.images">
            <image 
              v-for="(img, idx) in parseImages(item.images)" 
              :key="idx" 
              :src="img" 
              mode="aspectFill"
              @tap="previewImage(item.images, idx)"
            />
          </view>
          
          <view class="reply" v-if="item.reply">
            <text class="label">商家回复：</text>
            <text class="text">{{ item.reply }}</text>
          </view>
        </view>
      </view>
    </view>
    
    <view class="loading" v-if="loading">加载中...</view>
    <view class="no-more" v-else-if="!canLoadMore && list.length > 0">没有更多了</view>
    <view class="empty" v-else-if="list.length === 0 && !loading">
      <image src="/static/img/no-store.png" mode="aspectFit" />
      <text>暂无评价记录</text>
      <text class="sub">完成订单后可以进行评价哦</text>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http.js'

export default {
  data() {
    return {
      list: [],
      pageNo: 1,
      pageSize: 10,
      loading: false,
      canLoadMore: true
    }
  },
  
  onLoad() {
    uni.setNavigationBarTitle({ title: '我的评价' })
    this.loadReviews()
  },
  
  onReachBottom() {
    if (this.canLoadMore && !this.loading) {
      this.loadReviews()
    }
  },
  
  onPullDownRefresh() {
    this.pageNo = 1
    this.list = []
    this.canLoadMore = true
    this.loadReviews().then(() => {
      uni.stopPullDownRefresh()
    })
  },
  
  methods: {
    async loadReviews() {
      if (this.loading) return
      this.loading = true
      
      try {
        const res = await http.get('/member/review/myList', {
          pageNo: this.pageNo,
          pageSize: this.pageSize
        })
        
        if (res.code === 0) {
          const newList = res.data.list || []
          if (this.pageNo === 1) {
            this.list = newList
          } else {
            this.list = [...this.list, ...newList]
          }
          
          this.canLoadMore = this.list.length < res.data.total
          if (newList.length > 0) {
            this.pageNo++
          }
        }
      } catch (e) {
        uni.showToast({ title: '加载失败', icon: 'none' })
      } finally {
        this.loading = false
      }
    },
    
    parseTags(tags) {
      if (!tags) return []
      try {
        return typeof tags === 'string' ? JSON.parse(tags) : tags
      } catch (e) {
        return []
      }
    },
    
    parseImages(images) {
      if (!images) return []
      try {
        return typeof images === 'string' ? JSON.parse(images) : images
      } catch (e) {
        return []
      }
    },
    
    previewImage(images, index) {
      const urls = this.parseImages(images)
      uni.previewImage({ urls, current: index })
    }
  }
}
</script>

<style lang="scss" scoped>
.my-reviews-page {
  min-height: 100vh;
  background: #f5f5f5;
  padding: 20rpx;
  padding-bottom: 40rpx;
}

.review-item {
  background: #fff;
  border-radius: 16rpx;
  margin-bottom: 20rpx;
  overflow: hidden;
}

.store-info {
  padding: 24rpx;
  background: linear-gradient(135deg, var(--main-color, #5AAB6E) 0%, var(--main-color-shadow, #3d8b4f) 100%);
  display: flex;
  justify-content: space-between;
  align-items: center;
  
  .store-name {
    font-size: 30rpx;
    color: #fff;
    font-weight: 600;
  }
  .room-name {
    font-size: 26rpx;
    color: rgba(255, 255, 255, 0.8);
  }
}

.review-content {
  padding: 24rpx;
}

.score-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  
  .stars .star {
    font-size: 32rpx;
    color: #ddd;
    &.active { color: #FFB800; }
  }
  .time {
    font-size: 24rpx;
    color: #999;
  }
}

.content {
  font-size: 28rpx;
  color: #333;
  line-height: 1.7;
  margin-top: 20rpx;
}

.tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 20rpx;
  .tag {
    font-size: 24rpx;
    color: var(--main-color, #5AAB6E);
    background: rgba(90, 171, 110, 0.1);
    padding: 8rpx 20rpx;
    border-radius: 24rpx;
  }
}

.images {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 20rpx;
  
  image {
    width: 200rpx;
    height: 200rpx;
    border-radius: 8rpx;
  }
}

.reply {
  margin-top: 20rpx;
  padding: 20rpx;
  background: #f9f9f9;
  border-radius: 12rpx;
  font-size: 26rpx;
  line-height: 1.6;
  .label {
    color: var(--main-color, #5AAB6E);
    font-weight: 500;
  }
  .text {
    color: #666;
  }
}

.loading, .no-more {
  text-align: center;
  padding: 40rpx;
  color: #999;
  font-size: 26rpx;
}

.empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 120rpx 40rpx;
  
  image {
    width: 200rpx;
    height: 200rpx;
    opacity: 0.6;
  }
  text {
    color: #999;
    font-size: 28rpx;
    margin-top: 20rpx;
  }
  .sub {
    font-size: 24rpx;
    color: #bbb;
    margin-top: 8rpx;
  }
}
</style>
