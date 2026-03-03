<template>
  <view class="review-list-page" :style="themeStyle">
    <!-- 统计信息 -->
    <view class="stats-card">
      <view class="score-box">
        <text class="score">{{ avgScore }}</text>
        <view class="stars">
          <text v-for="i in 5" :key="i" :class="['star', { active: i <= Math.round(avgScore) }]">★</text>
        </view>
        <text class="total">{{ total }}条评价</text>
      </view>
      <view class="score-bars">
        <view class="bar-item" v-for="i in 5" :key="i">
          <text class="label">{{ 6 - i }}星</text>
          <view class="bar-bg">
            <view class="bar" :style="{ width: getScorePercent(6 - i) + '%' }"></view>
          </view>
          <text class="percent">{{ getScorePercent(6 - i) }}%</text>
        </view>
      </view>
    </view>
    
    <!-- 筛选标签 -->
    <view class="filter-tabs">
      <view 
        :class="['tab', { active: filterScore === '' }]" 
        @tap="setFilter('')"
      >全部</view>
      <view 
        v-for="i in 5" 
        :key="i" 
        :class="['tab', { active: filterScore === (6 - i) }]"
        @tap="setFilter(6 - i)"
      >{{ 6 - i }}星</view>
    </view>
    
    <!-- 评价列表 -->
    <view class="list">
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
      
      <view class="loading" v-if="loading">加载中...</view>
      <view class="no-more" v-else-if="!canLoadMore && list.length > 0">没有更多了</view>
      <view class="empty" v-else-if="list.length === 0">暂无评价</view>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http.js'

export default {
  data() {
    return {
      storeId: '',
      list: [],
      total: 0,
      avgScore: 0,
      scoreStats: {},
      pageNo: 1,
      pageSize: 10,
      loading: false,
      canLoadMore: true,
      filterScore: ''
    }
  },
  
  onLoad(options) {
    this.storeId = options.store_id || ''
    uni.setNavigationBarTitle({ title: '用户评价' })
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
      if (!this.storeId || this.loading) return
      this.loading = true
      
      try {
        const params = {
          store_id: this.storeId,
          pageNo: this.pageNo,
          pageSize: this.pageSize
        }
        if (this.filterScore !== '') {
          params.score = this.filterScore
        }
        
        const res = await http.get('/member/review/list', params)
        if (res.code === 0) {
          const newList = res.data.list || []
          if (this.pageNo === 1) {
            this.list = newList
            this.avgScore = res.data.avg_score || 0
            this.total = res.data.total || 0
            this.scoreStats = res.data.score_stats || {}
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
    
    setFilter(score) {
      if (this.filterScore === score) return
      this.filterScore = score
      this.pageNo = 1
      this.list = []
      this.canLoadMore = true
      this.loadReviews()
    },
    
    getScorePercent(score) {
      if (!this.total || !this.scoreStats[score]) return 0
      return Math.round((this.scoreStats[score] / this.total) * 100)
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
.review-list-page {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 40rpx;
}

.stats-card {
  background: #fff;
  padding: 30rpx;
  display: flex;
  
  .score-box {
    width: 200rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-right: 1rpx solid #eee;
    
    .score {
      font-size: 72rpx;
      font-weight: 600;
      color: #FFB800;
    }
    .stars .star {
      font-size: 24rpx;
      color: #ddd;
      &.active { color: #FFB800; }
    }
    .total {
      font-size: 24rpx;
      color: #999;
      margin-top: 8rpx;
    }
  }
  
  .score-bars {
    flex: 1;
    padding-left: 30rpx;
    
    .bar-item {
      display: flex;
      align-items: center;
      margin-bottom: 8rpx;
      
      .label {
        width: 60rpx;
        font-size: 22rpx;
        color: #666;
      }
      .bar-bg {
        flex: 1;
        height: 12rpx;
        background: #eee;
        border-radius: 6rpx;
        overflow: hidden;
        margin: 0 12rpx;
        
        .bar {
          height: 100%;
          background: #FFB800;
          border-radius: 6rpx;
        }
      }
      .percent {
        width: 60rpx;
        font-size: 22rpx;
        color: #999;
        text-align: right;
      }
    }
  }
}

.filter-tabs {
  display: flex;
  background: #fff;
  padding: 20rpx;
  margin-top: 20rpx;
  overflow-x: auto;
  
  .tab {
    flex-shrink: 0;
    padding: 12rpx 28rpx;
    font-size: 26rpx;
    color: #666;
    background: #f5f5f5;
    border-radius: 30rpx;
    margin-right: 16rpx;
    
    &.active {
      color: #fff;
      background: var(--main-color, #5AAB6E);
    }
  }
}

.list {
  padding: 0 20rpx;
}

.review-item {
  background: #fff;
  border-radius: 16rpx;
  padding: 24rpx;
  margin-top: 20rpx;
}

.user-info {
  display: flex;
  align-items: flex-start;
  
  .avatar {
    width: 72rpx;
    height: 72rpx;
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
      margin-top: 8rpx;
      .stars .star {
        font-size: 24rpx;
        color: #ddd;
        &.active { color: #FFB800; }
      }
      .room {
        font-size: 24rpx;
        color: #999;
        margin-left: 16rpx;
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

.loading, .no-more, .empty {
  text-align: center;
  padding: 40rpx;
  color: #999;
  font-size: 26rpx;
}
</style>
