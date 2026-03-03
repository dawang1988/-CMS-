<template>
  <view class="store-reviews" v-if="total > 0 || loading">
    <view class="section-header" @tap="goReviewList">
      <view class="left">
        <text class="title">用户评价</text>
        <view class="score-info" v-if="avgScore > 0">
          <text class="score">{{ avgScore }}</text>
          <text class="star">★</text>
          <text class="count">（{{ total }}条）</text>
        </view>
      </view>
      <view class="right">
        <text>查看全部</text>
        <text class="arrow">›</text>
      </view>
    </view>
    
    <view class="review-list" v-if="list.length > 0">
      <view class="review-item" v-for="(item, index) in list" :key="index">
        <view class="user-info">
          <image class="avatar" :src="item.avatar || '/static/logo.png'" mode="aspectFill" />
          <view class="info">
            <text class="nickname">{{ item.nickname || '匿名用户' }}</text>
            <view class="meta">
              <view class="stars">
                <text v-for="i in 5" :key="i" :class="['star', { active: i <= item.score }]">★</text>
              </view>
              <text class="room">{{ item.room_name }}</text>
            </view>
          </view>
          <text class="time">{{ formatTime(item.create_time) }}</text>
        </view>
        
        <view class="content" v-if="item.content">{{ item.content }}</view>
        
        <view class="tags" v-if="item.tags">
          <text class="tag" v-for="(tag, idx) in parseTags(item.tags)" :key="idx">{{ tag }}</text>
        </view>
        
        <view class="images" v-if="item.images" @tap.stop="previewImages(item.images)">
          <image 
            v-for="(img, idx) in parseImages(item.images).slice(0, 3)" 
            :key="idx" 
            :src="img" 
            mode="aspectFill"
          />
          <view class="more" v-if="parseImages(item.images).length > 3">
            +{{ parseImages(item.images).length - 3 }}
          </view>
        </view>
        
        <view class="reply" v-if="item.reply">
          <text class="label">商家回复：</text>
          <text class="text">{{ item.reply }}</text>
        </view>
      </view>
    </view>
    
    <view class="loading" v-else-if="loading">
      <text>加载中...</text>
    </view>
    
    <view class="empty" v-else>
      <text>暂无评价</text>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http.js'

export default {
  name: 'StoreReviews',
  props: {
    storeId: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      list: [],
      total: 0,
      avgScore: 0,
      loading: false
    }
  },
  watch: {
    storeId: {
      immediate: true,
      handler(val) {
        if (val) this.loadReviews()
      }
    }
  },
  methods: {
    async loadReviews() {
      if (!this.storeId) return
      this.loading = true
      try {
        const res = await http.get('/member/review/list', {
          store_id: this.storeId,
          pageNo: 1,
          pageSize: 3
        })
        if (res.code === 0) {
          this.list = res.data.list || []
          this.total = res.data.total || 0
          this.avgScore = res.data.avg_score || 0
        }
      } catch (e) {
        // ignore
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
    
    previewImages(images) {
      const urls = this.parseImages(images)
      if (urls.length > 0) {
        uni.previewImage({ urls, current: 0 })
      }
    },
    
    formatTime(time) {
      if (!time) return ''
      const d = new Date(time)
      const now = new Date()
      const diff = now - d
      if (diff < 60000) return '刚刚'
      if (diff < 3600000) return Math.floor(diff / 60000) + '分钟前'
      if (diff < 86400000) return Math.floor(diff / 3600000) + '小时前'
      if (diff < 2592000000) return Math.floor(diff / 86400000) + '天前'
      return `${d.getMonth() + 1}月${d.getDate()}日`
    },
    
    goReviewList() {
      uni.navigateTo({
        url: `/pages/order/review-list?store_id=${this.storeId}`
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.store-reviews {
  background: #fff;
  margin: 20rpx;
  border-radius: 16rpx;
  padding: 24rpx;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20rpx;
  
  .left {
    display: flex;
    align-items: center;
    .title {
      font-size: 32rpx;
      font-weight: 600;
      color: #333;
    }
    .score-info {
      margin-left: 16rpx;
      display: flex;
      align-items: center;
      .score {
        font-size: 32rpx;
        font-weight: 600;
        color: #FFB800;
      }
      .star {
        color: #FFB800;
        font-size: 28rpx;
        margin-left: 4rpx;
      }
      .count {
        font-size: 24rpx;
        color: #999;
        margin-left: 8rpx;
      }
    }
  }
  
  .right {
    display: flex;
    align-items: center;
    font-size: 26rpx;
    color: #999;
    .arrow {
      font-size: 32rpx;
      margin-left: 4rpx;
    }
  }
}

.review-item {
  padding: 20rpx 0;
  border-bottom: 1rpx solid #f5f5f5;
  &:last-child { border-bottom: none; }
}

.user-info {
  display: flex;
  align-items: flex-start;
  
  .avatar {
    width: 64rpx;
    height: 64rpx;
    border-radius: 50%;
    flex-shrink: 0;
  }
  
  .info {
    flex: 1;
    margin-left: 16rpx;
    .nickname {
      font-size: 28rpx;
      color: #333;
      font-weight: 500;
    }
    .meta {
      display: flex;
      align-items: center;
      margin-top: 6rpx;
      .stars .star {
        font-size: 22rpx;
        color: #ddd;
        &.active { color: #FFB800; }
      }
      .room {
        font-size: 22rpx;
        color: #999;
        margin-left: 12rpx;
      }
    }
  }
  
  .time {
    font-size: 22rpx;
    color: #bbb;
    flex-shrink: 0;
  }
}

.content {
  font-size: 28rpx;
  color: #333;
  line-height: 1.6;
  margin-top: 16rpx;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 16rpx;
  .tag {
    font-size: 22rpx;
    color: var(--main-color, #5AAB6E);
    background: rgba(90, 171, 110, 0.1);
    padding: 6rpx 16rpx;
    border-radius: 20rpx;
  }
}

.images {
  display: flex;
  gap: 12rpx;
  margin-top: 16rpx;
  position: relative;
  
  image {
    width: 160rpx;
    height: 160rpx;
    border-radius: 8rpx;
  }
  
  .more {
    position: absolute;
    right: 0;
    bottom: 0;
    width: 160rpx;
    height: 160rpx;
    background: rgba(0, 0, 0, 0.5);
    color: #fff;
    font-size: 28rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8rpx;
  }
}

.reply {
  margin-top: 16rpx;
  padding: 16rpx;
  background: #f9f9f9;
  border-radius: 8rpx;
  font-size: 26rpx;
  .label {
    color: var(--main-color, #5AAB6E);
    font-weight: 500;
  }
  .text {
    color: #666;
  }
}

.loading, .empty {
  text-align: center;
  padding: 40rpx;
  color: #999;
  font-size: 26rpx;
}
</style>
