<template>
  <view class="page" :style="themeStyle">
    <view class="loading" v-if="loading">
      <text class="loading-text">加载中...</text>
    </view>
    <view class="empty" v-else-if="helpList.length === 0">
      <text class="empty-text">暂无帮助文档</text>
    </view>
    <view class="help-list" v-else>
      <view 
        class="help-item" 
        v-for="(item, index) in helpList" 
        :key="index"
        @tap="showDetail(item)"
      >
        <view class="item-left">
          <view class="question">{{ item.title }}</view>
          <view class="category" v-if="item.category || item.type">{{ categoryLabel(item.category || item.type) }}</view>
        </view>
        <text class="arrow">›</text>
      </view>
    </view>

    <!-- 详情弹窗 -->
    <view class="popup-mask" v-if="showPopup" @tap="showPopup = false">
      <view class="popup-content" @tap.stop>
        <view class="detail-title">{{ currentItem.title }}</view>
        <scroll-view scroll-y class="detail-scroll">
          <view class="detail-answer">{{ currentItem.content }}</view>
        </scroll-view>
        <button class="btn" @tap="showPopup = false">知道了</button>
      </view>
    </view>
  </view>
</template>

<script>
import api from '@/api/index.js'

export default {
  data() {
    return {
      loading: true,
      showPopup: false,
      currentItem: {},
      helpList: [],
      categoryMap: {
        'usage': '使用指南',
        'payment': '支付相关',
        'coupon': '优惠券',
        'order': '订单相关',
        'account': '账户相关',
        'booking': '预约相关',
        'membership': '会员相关',
        'refund': '退款相关',
        'guide': '使用指南'
      }
    }
  },
  onShow() {
    this.loadHelpList()
  },
  methods: {
    async loadHelpList() {
      this.loading = true
      try {
        const res = await api.common.getHelpList()
        if (res.code === 0 && res.data) {
          this.helpList = Array.isArray(res.data) ? res.data : (res.data.list || [])
        } else {
          this.helpList = []
        }
      } catch (e) {
        console.error('加载帮助列表失败', e)
        this.helpList = []
      } finally {
        this.loading = false
      }
    },
    showDetail(item) {
      this.currentItem = item
      this.showPopup = true
    },
    categoryLabel(type) {
      return this.categoryMap[type] || type
    }
  }
}
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background: #f5f5f5;
}
.loading, .empty {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 200rpx 0;
}
.loading-text, .empty-text {
  font-size: 28rpx;
  color: #999;
}
.help-list {
  padding: 20rpx;
}
.help-item {
  background: #fff;
  padding: 30rpx;
  margin-bottom: 20rpx;
  border-radius: 20rpx;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.item-left {
  flex: 1;
}
.question {
  font-size: 28rpx;
  color: #333;
}
.category {
  font-size: 22rpx;
  color: #999;
  margin-top: 8rpx;
}
.arrow {
  font-size: 36rpx;
  color: #ccc;
  margin-left: 20rpx;
}

/* 自定义弹窗 */
.popup-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  z-index: 999;
  display: flex;
  align-items: flex-end;
}
.popup-content {
  width: 100%;
  background: #fff;
  border-radius: 24rpx 24rpx 0 0;
  padding: 40rpx;
  max-height: 70vh;
  display: flex;
  flex-direction: column;
}
.detail-title {
  font-size: 36rpx;
  font-weight: bold;
  margin-bottom: 30rpx;
  color: #333;
}
.detail-scroll {
  max-height: 50vh;
  margin-bottom: 30rpx;
}
.detail-answer {
  font-size: 28rpx;
  color: #666;
  line-height: 2;
  white-space: pre-wrap;
}
.btn {
  width: 100%;
  height: 88rpx;
  line-height: 88rpx;
  background: var(--main-color, #5AAB6E);
  color: #fff;
  border-radius: 44rpx;
  font-size: 32rpx;
  border: none;
}
</style>
