<template>
  <view class="page" :style="themeStyle">
    <view class="container" v-if="!isLoading">
      <!-- 优惠券卡片 -->
      <view class="coupon-card">
        <view class="coupon-header">
          <view class="coupon-type">{{ couponTypeText }}</view>
          <view class="coupon-name">{{ name }}</view>
        </view>

        <view class="coupon-body">
          <view class="coupon-price">
            <text class="price-symbol">¥</text>
            <text class="price-value">{{ amount }}</text>
          </view>
          <view class="coupon-info">
            <view class="info-item">
              <text class="label">适用门店：</text>
              <text class="value">{{ store_name || '不限制' }}</text>
            </view>
            <view class="info-item" v-if="min_amount > 0">
              <text class="label">使用门槛：</text>
              <text class="value">满{{ min_amount }}元可用</text>
            </view>
            <view class="info-item" v-if="roomTypeText">
              <text class="label">适用房型：</text>
              <text class="value">{{ roomTypeText }}</text>
            </view>
            <view class="info-item">
              <text class="label">有效期至：</text>
              <text class="value">{{ end_time }}</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 活动信息 -->
      <view class="activity-info">
        <view class="activity-title">{{ active_name }}</view>
        <view class="activity-stats">
          <view class="stat-item">
            <text class="stat-label">总数量：</text>
            <text class="stat-value">{{ num }}</text>
          </view>
          <view class="stat-item">
            <text class="stat-label">剩余：</text>
            <text class="stat-value highlight">{{ balance_num }}</text>
          </view>
        </view>
        <view class="activity-time">
          <text>活动截止：{{ active_end_time }}</text>
        </view>
        <view class="remaining-days" v-if="!isActivityEnded && remainingDays > 0">
          还剩 {{ remainingDays }} 天
        </view>
      </view>

      <!-- 领取按钮 -->
      <view class="receive-btn">
        <button 
          class="btn-primary" 
          :disabled="isGet || balance_num <= 0 || isActivityEnded"
          @click="receiveCoupon"
        >
          {{ buttonText }}
        </button>
      </view>

      <!-- 说明 -->
      <view class="desc-box">
        <view class="desc-title">使用说明：</view>
        <view class="desc-item">1. 每人限领一张</view>
        <view class="desc-item">2. 领取后请在有效期内使用</view>
        <view class="desc-item">3. 优惠券不可转赠他人</view>
        <view class="desc-item">4. 优惠券不可兑换现金</view>
      </view>
    </view>

    <!-- 加载中 -->
    <view v-else class="loading-container">
      <view class="loading-text">加载中...</view>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http.js'

export default {
  data() {
    return {
      id: null,
      coupon_id: null,
      name: '优惠券名称',
      active_name: '活动名称',
      num: 0,
      balance_num: 0,
      active_end_time: '',
      end_time: '',
      min_amount: 0,
      amount: 0,
      store_id: null,
      store_name: '',
      room_class: 1,
      type: 1,
      isGet: false,
      couponTypeText: '',
      buttonText: '立即领取',
      remainingDays: 0,
      isActivityEnded: false,
      roomTypeText: '',
      isLoading: true
    }
  },
  onLoad(options) {
    let couponId = options.coupon_id
    if (!couponId) {
      const query = uni.getEnterOptionsSync?.()?.query
      if (query?.coupon_id) {
        couponId = query.coupon_id
      }
    }
    this.coupon_id = couponId
  },
  onShow() {
    if (this.coupon_id) {
      this.getInfo()
    }
  },
  onShareAppMessage() {
    return {
      title: '邀请您领取优惠券~',
      path: `/pages/coupon/active?coupon_id=${this.coupon_id}`,
      imageUrl: '/static/img/share_coupon.jpg'
    }
  },
  onShareTimeline() {
    return {
      title: '邀请您领取优惠券~',
      path: `/pages/coupon/active?coupon_id=${this.coupon_id}`,
      imageUrl: '/static/img/share_coupon.jpg'
    }
  },
  methods: {
    // 获取活动信息
    async getInfo() {
      try {
        const res = await http.post(`/member/couponActive/getById?couponId=${this.coupon_id}`)
        Object.assign(this.$data, res.data || res)
        this.isLoading = false
        this.initCouponData()
        this.checkActivityStatus()
        this.checkReceiveStatus()
        this.calculateRemainingDays()
      } catch (e) {
        this.isLoading = false
        uni.showToast({
          title: '加载失败，请重试',
          icon: 'none'
        })
      }
    },

    // 初始化优惠券数据
    initCouponData() {
      // 优惠券类型
      const typeMap = {
        1: '抵扣券',
        2: '满减券',
        3: '加时券'
      }
      this.couponTypeText = typeMap[this.type] || '优惠券'

      // 房间类型
      const roomTypeMap = {
        1: '大包',
        2: '中包',
        3: '小包',
        4: '豪包',
        5: '商务包',
        6: '斯洛克',
        7: '中式黑八',
        8: '美式球桌'
      }
      this.roomTypeText = roomTypeMap[this.room_class] || '全场通用'
    },

    // 检查活动状态
    checkActivityStatus() {
      if (!this.active_end_time) {
        this.isActivityEnded = false
        return
      }
      const endTime = new Date(this.active_end_time)
      const now = new Date()
      this.isActivityEnded = now > endTime
    },

    // 计算剩余天数
    calculateRemainingDays() {
      if (!this.active_end_time) {
        this.remainingDays = 0
        return
      }
      const endTime = new Date(this.active_end_time)
      const now = new Date()
      const diffTime = endTime - now
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      this.remainingDays = Math.max(0, diffDays)
    },

    // 检查领取状态
    checkReceiveStatus() {
      const token = uni.getStorageSync('token')
      const isLogin = !!token

      if (this.isActivityEnded) {
        this.buttonText = '返回门店下单'
      } else if (this.balance_num <= 0) {
        this.buttonText = '已抢完'
      } else if (this.isGet) {
        this.buttonText = '已领取'
      } else if (!isLogin) {
        this.buttonText = '请登录后领取'
      } else {
        this.buttonText = '立即领取'
      }
    },

    // 领取优惠券
    async receiveCoupon() {
      const token = uni.getStorageSync('token')
      if (!token) {
        uni.navigateTo({
          url: '/pages/user/login'
        })
        return
      }

      if (this.isActivityEnded) {
        this.goBackToOrder()
        return
      }

      if (this.isGet || this.balance_num <= 0) {
        return
      }

      try {
        uni.showLoading({ title: '操作中...' })
        await http.post(`/member/couponActive/putCoupon?id=${this.id}`)
        uni.hideLoading()
        
        uni.showToast({
          title: '领取成功',
          icon: 'success'
        })
        
        this.getInfo()
      } catch (e) {
        uni.hideLoading()
        uni.showToast({
          title: e.msg || '操作失败，请重试',
          icon: 'none'
        })
      }
    },

    // 返回门店下单
    goBackToOrder() {
      uni.setStorageSync('global_store_id', this.store_id)
      uni.switchTab({
        url: '/pages/door/index'
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 40rpx;
}

.container {
  max-width: 700rpx;
  margin: 0 auto;
}

.coupon-card {
  background: #fff;
  border-radius: 20rpx;
  overflow: hidden;
  margin-bottom: 40rpx;
  box-shadow: 0 8rpx 20rpx rgba(0, 0, 0, 0.1);
}

.coupon-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 32rpx;
  color: #fff;

  .coupon-type {
    font-size: 24rpx;
    opacity: 0.9;
    margin-bottom: 8rpx;
  }

  .coupon-name {
    font-size: 36rpx;
    font-weight: bold;
  }
}

.coupon-body {
  padding: 32rpx;

  .coupon-price {
    text-align: center;
    margin-bottom: 32rpx;

    .price-symbol {
      font-size: 40rpx;
      color: #ff6b00;
    }

    .price-value {
      font-size: 80rpx;
      font-weight: bold;
      color: #ff6b00;
    }
  }

  .coupon-info {
    .info-item {
      display: flex;
      font-size: 26rpx;
      line-height: 2;

      .label {
        color: #999;
      }

      .value {
        flex: 1;
        color: #333;
      }
    }
  }
}

.activity-info {
  background: #fff;
  border-radius: 20rpx;
  padding: 32rpx;
  margin-bottom: 40rpx;

  .activity-title {
    font-size: 32rpx;
    font-weight: bold;
    color: #333;
    margin-bottom: 20rpx;
  }

  .activity-stats {
    display: flex;
    gap: 40rpx;
    margin-bottom: 16rpx;

    .stat-item {
      font-size: 26rpx;

      .stat-label {
        color: #999;
      }

      .stat-value {
        color: #333;
        font-weight: bold;

        &.highlight {
          color: #ff6b00;
        }
      }
    }
  }

  .activity-time {
    font-size: 24rpx;
    color: #999;
    margin-bottom: 12rpx;
  }

  .remaining-days {
    font-size: 28rpx;
    color: #ff6b00;
    font-weight: bold;
  }
}

.receive-btn {
  margin-bottom: 40rpx;
  
  button {
    width: 100%;
    height: 88rpx;
    line-height: 88rpx;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border: none;
    border-radius: 44rpx;
    font-size: 32rpx;
    font-weight: bold;
    
    &[disabled] {
      opacity: 0.6;
    }
  }
}

.desc-box {
  background: rgba(255, 255, 255, 0.9);
  border-radius: 20rpx;
  padding: 32rpx;

  .desc-title {
    font-size: 28rpx;
    font-weight: bold;
    color: #333;
    margin-bottom: 20rpx;
  }

  .desc-item {
    font-size: 24rpx;
    color: #666;
    line-height: 2;
  }
}

.loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  
  .loading-text {
    font-size: 28rpx;
    color: #fff;
  }
}
</style>
