<template>
  <view class="page" :style="themeStyle">
    <!-- 顶部Tab -->
    <view class="tabs">
      <view class="tab" :class="{ active: tabIndex === 0 }" @tap="tabIndex = 0">购买会员卡</view>
      <view class="tab" :class="{ active: tabIndex === 1 }" @tap="tabIndex = 1">我的会员卡</view>
    </view>

    <!-- 购买会员卡 -->
    <view class="content" v-if="tabIndex === 0">
      <view v-if="!cardEnabled" class="empty-tip">
        <image src="/static/icon/empty.png" mode="aspectFit" class="empty-icon" />
        <text>该门店暂未开启会员卡功能</text>
      </view>
      <view v-else-if="saleCards.length === 0" class="empty-tip">
        <image src="/static/icon/empty.png" mode="aspectFit" class="empty-icon" />
        <text>暂无可购买的会员卡</text>
      </view>
      <view v-else class="card-list">
        <view class="card-item" v-for="card in saleCards" :key="card.id" @tap="showBuyModal(card)">
          <view class="card-header" :class="'type-' + card.type">
            <view class="card-name">{{ card.name }}</view>
            <view class="card-type">{{ card.type_text }}</view>
          </view>
          <view class="card-body">
            <view class="card-value">
              <text class="value">{{ card.value }}</text>
              <text class="unit">{{ getUnit(card.type) }}</text>
            </view>
            <view class="card-info">
              <view class="info-item" v-if="card.discount < 1">
                <text class="label">折扣</text>
                <text class="val discount">{{ card.discount_text }}</text>
              </view>
              <view class="info-item">
                <text class="label">有效期</text>
                <text class="val">{{ card.valid_text }}</text>
              </view>
            </view>
          </view>
          <view class="card-footer">
            <view class="price">
              <text class="symbol">¥</text>
              <text class="amount">{{ card.price }}</text>
            </view>
            <view class="buy-btn">立即购买</view>
          </view>
        </view>
      </view>
    </view>

    <!-- 我的会员卡 -->
    <view class="content" v-if="tabIndex === 1">
      <view v-if="myCards.length === 0" class="empty-tip">
        <image src="/static/icon/empty.png" mode="aspectFit" class="empty-icon" />
        <text>暂无会员卡</text>
        <view class="go-buy" @tap="tabIndex = 0">去购买</view>
      </view>
      <view v-else class="my-card-list">
        <view class="my-card-item" v-for="card in myCards" :key="card.id" :class="{ disabled: card.status !== 1 }">
          <view class="my-card-header" :class="'type-' + card.type">
            <view class="my-card-name">{{ card.name }}</view>
            <view class="my-card-status">{{ card.status_text }}</view>
          </view>
          <view class="my-card-body">
            <view class="remain-info">
              <text class="remain-label">剩余</text>
              <text class="remain-value">{{ card.remain_value }}</text>
              <text class="remain-unit">{{ getUnit(card.type) }}</text>
            </view>
            <view class="progress-bar">
              <view class="progress-inner" :style="{ width: card.progress + '%' }"></view>
            </view>
            <view class="card-meta">
              <text v-if="card.discount < 1" class="discount-tag">{{ card.discount_text }}</text>
              <text class="expire-info">{{ card.expire_text }}</text>
            </view>
          </view>
        </view>
      </view>
    </view>

    <!-- 购买确认弹窗 -->
    <u-popup :show="showBuy" mode="bottom" @close="showBuy = false" :round="20">
      <view class="buy-popup" v-if="selectedCard">
        <view class="popup-header">
          <text class="popup-title">确认购买</text>
          <text class="popup-close" @tap="showBuy = false">×</text>
        </view>
        <view class="popup-body">
          <view class="buy-card-info">
            <view class="buy-card-name">{{ selectedCard.name }}</view>
            <view class="buy-card-desc">{{ selectedCard.type_text }} · {{ selectedCard.value }}{{ getUnit(selectedCard.type) }}</view>
            <view class="buy-card-valid">有效期：{{ selectedCard.valid_text }}</view>
          </view>
          <view class="buy-price">
            <text class="price-label">支付金额</text>
            <text class="price-value">¥{{ selectedCard.price }}</text>
          </view>
          <view class="pay-method">
            <view class="method-item active">
              <image src="/static/icon/wechat.png" class="method-icon" />
              <text class="method-name">微信支付</text>
              <view class="method-check">✓</view>
            </view>
          </view>
        </view>
        <view class="popup-footer">
          <button class="confirm-btn" @tap="confirmBuy" :disabled="buying">
            {{ buying ? '购买中...' : '确认支付 ¥' + selectedCard.price }}
          </button>
        </view>
      </view>
    </u-popup>
  </view>
</template>

<script>
import http from '@/utils/http.js'

export default {
  data() {
    return {
      tabIndex: 0,
      storeId: '',
      cardEnabled: false,
      saleCards: [],
      myCards: [],
      showBuy: false,
      selectedCard: null,
      buying: false
    }
  },
  onLoad(options) {
    this.storeId = options.store_id || uni.getStorageSync('global_store_id') || ''
  },
  onShow() {
    // 检查登录状态
    const app = getApp()
    if (!app.globalData.isLogin) {
      uni.showModal({
        title: '提示',
        content: '请先登录后查看会员卡',
        confirmText: '去登录',
        success: (res) => {
          if (res.confirm) {
            uni.navigateTo({ url: '/pages/user/login' })
          } else {
            uni.navigateBack()
          }
        }
      })
      return
    }
    this.loadData()
  },
  methods: {
    async loadData() {
      await this.loadSaleCards()
      await this.loadMyCards()
    },
    async loadSaleCards() {
      try {
        const res = await http.get('/member/card/getSaleCardPage', { store_id: this.storeId })
        if (res.code === 0) {
          this.cardEnabled = res.data.card_enabled
          this.saleCards = res.data.list || []
        }
      } catch (e) {
        console.error('加载会员卡失败', e)
      }
    },
    async loadMyCards() {
      try {
        const res = await http.get('/member/card/getMyCardPage', { store_id: this.storeId })
        if (res.code === 0) {
          this.myCards = res.data.list || []
        }
      } catch (e) {
        console.error('加载我的会员卡失败', e)
      }
    },
    getUnit(type) {
      const units = { 1: '次', 2: '分钟', 3: '元' }
      return units[type] || ''
    },
    showBuyModal(card) {
      this.selectedCard = card
      this.showBuy = true
    },
    async confirmBuy() {
      if (!this.selectedCard) return
      
      this.buying = true
      try {
        const res = await http.post('/member/card/buy', {
          card_id: this.selectedCard.id
        })
        if (res.code === 0 && res.data) {
          // 调用微信支付
          uni.requestPayment({
            provider: 'wxpay',
            timeStamp: res.data.timeStamp,
            nonceStr: res.data.nonceStr,
            package: res.data.pkg,
            signType: res.data.signType,
            paySign: res.data.paySign,
            success: () => {
              uni.showToast({ title: '购买成功', icon: 'success' })
              this.showBuy = false
              this.loadData()
              this.tabIndex = 1 // 切换到我的会员卡
            },
            fail: (err) => {
              console.error('支付失败', err)
              if (err.errMsg && err.errMsg.indexOf('cancel') > -1) {
                uni.showToast({ title: '已取消支付', icon: 'none' })
              } else {
                uni.showToast({ title: '支付失败', icon: 'none' })
              }
            }
          })
        } else {
          uni.showToast({ title: res.msg || '购买失败', icon: 'none' })
        }
      } catch (e) {
        uni.showToast({ title: '购买失败', icon: 'none' })
      }
      this.buying = false
    }
  }
}
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background: #f5f5f5;
}
.tabs {
  display: flex;
  background: #fff;
  padding: 0 30rpx;
  border-bottom: 1rpx solid #eee;
}
.tab {
  flex: 1;
  text-align: center;
  padding: 28rpx 0;
  font-size: 30rpx;
  color: #666;
  position: relative;
}
.tab.active {
  color: var(--main-color, #5AAB6E);
  font-weight: 600;
}
.tab.active::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 60rpx;
  height: 6rpx;
  background: var(--main-color, #5AAB6E);
  border-radius: 3rpx;
}
.content {
  padding: 20rpx;
}
.empty-tip {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 100rpx 0;
  color: #999;
  font-size: 28rpx;
}
.empty-icon {
  width: 200rpx;
  height: 200rpx;
  margin-bottom: 20rpx;
  opacity: 0.5;
}
.go-buy {
  margin-top: 30rpx;
  padding: 16rpx 60rpx;
  background: var(--main-color, #5AAB6E);
  color: #fff;
  border-radius: 40rpx;
  font-size: 28rpx;
}

/* 可购买会员卡列表 */
.card-list {
  display: flex;
  flex-direction: column;
  gap: 24rpx;
}
.card-item {
  background: #fff;
  border-radius: 20rpx;
  overflow: hidden;
  box-shadow: 0 4rpx 20rpx rgba(0,0,0,0.05);
}
.card-header {
  padding: 24rpx 30rpx;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: #fff;
}
.card-header.type-1 { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.card-header.type-2 { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
.card-header.type-3 { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.card-name {
  font-size: 32rpx;
  font-weight: 600;
}
.card-type {
  font-size: 24rpx;
  padding: 6rpx 16rpx;
  background: rgba(255,255,255,0.2);
  border-radius: 20rpx;
}
.card-body {
  padding: 30rpx;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.card-value {
  display: flex;
  align-items: baseline;
}
.card-value .value {
  font-size: 56rpx;
  font-weight: 700;
  color: #333;
}
.card-value .unit {
  font-size: 26rpx;
  color: #666;
  margin-left: 8rpx;
}
.card-info {
  text-align: right;
}
.info-item {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  margin-bottom: 8rpx;
}
.info-item .label {
  font-size: 24rpx;
  color: #999;
  margin-right: 10rpx;
}
.info-item .val {
  font-size: 26rpx;
  color: #666;
}
.info-item .val.discount {
  color: #ff6b6b;
  font-weight: 600;
}
.card-footer {
  padding: 20rpx 30rpx;
  border-top: 1rpx solid #f5f5f5;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.price {
  color: #ff4757;
}
.price .symbol {
  font-size: 28rpx;
}
.price .amount {
  font-size: 40rpx;
  font-weight: 700;
}
.buy-btn {
  padding: 16rpx 40rpx;
  background: var(--main-color, #5AAB6E);
  color: #fff;
  border-radius: 40rpx;
  font-size: 28rpx;
}

/* 我的会员卡列表 */
.my-card-list {
  display: flex;
  flex-direction: column;
  gap: 24rpx;
}
.my-card-item {
  background: #fff;
  border-radius: 20rpx;
  overflow: hidden;
  box-shadow: 0 4rpx 20rpx rgba(0,0,0,0.05);
}
.my-card-item.disabled {
  opacity: 0.6;
}
.my-card-header {
  padding: 24rpx 30rpx;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: #fff;
}
.my-card-header.type-1 { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.my-card-header.type-2 { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
.my-card-header.type-3 { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.my-card-name {
  font-size: 30rpx;
  font-weight: 600;
}
.my-card-status {
  font-size: 24rpx;
  padding: 6rpx 16rpx;
  background: rgba(255,255,255,0.2);
  border-radius: 20rpx;
}
.my-card-body {
  padding: 30rpx;
}
.remain-info {
  display: flex;
  align-items: baseline;
  margin-bottom: 20rpx;
}
.remain-label {
  font-size: 26rpx;
  color: #999;
  margin-right: 10rpx;
}
.remain-value {
  font-size: 48rpx;
  font-weight: 700;
  color: var(--main-color, #5AAB6E);
}
.remain-unit {
  font-size: 26rpx;
  color: #666;
  margin-left: 8rpx;
}
.progress-bar {
  height: 12rpx;
  background: #eee;
  border-radius: 6rpx;
  overflow: hidden;
  margin-bottom: 20rpx;
}
.progress-inner {
  height: 100%;
  background: linear-gradient(90deg, var(--main-color, #5AAB6E), #7ed56f);
  border-radius: 6rpx;
  transition: width 0.3s;
}
.card-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.discount-tag {
  font-size: 24rpx;
  color: #ff6b6b;
  padding: 4rpx 12rpx;
  background: #fff0f0;
  border-radius: 6rpx;
}
.expire-info {
  font-size: 24rpx;
  color: #999;
}

/* 购买弹窗 */
.buy-popup {
  padding: 30rpx;
  padding-bottom: calc(30rpx + env(safe-area-inset-bottom));
}
.popup-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30rpx;
}
.popup-title {
  font-size: 34rpx;
  font-weight: 600;
  color: #333;
}
.popup-close {
  font-size: 48rpx;
  color: #999;
  line-height: 1;
}
.buy-card-info {
  background: #f8f8f8;
  padding: 24rpx;
  border-radius: 16rpx;
  margin-bottom: 30rpx;
}
.buy-card-name {
  font-size: 32rpx;
  font-weight: 600;
  color: #333;
  margin-bottom: 10rpx;
}
.buy-card-desc {
  font-size: 26rpx;
  color: #666;
  margin-bottom: 6rpx;
}
.buy-card-valid {
  font-size: 24rpx;
  color: #999;
}
.buy-price {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20rpx 0;
  border-bottom: 1rpx solid #eee;
  margin-bottom: 20rpx;
}
.price-label {
  font-size: 28rpx;
  color: #666;
}
.price-value {
  font-size: 40rpx;
  font-weight: 700;
  color: #ff4757;
}
.pay-method {
  margin-bottom: 30rpx;
}
.method-item {
  display: flex;
  align-items: center;
  padding: 24rpx;
  background: #f8f8f8;
  border-radius: 12rpx;
  border: 2rpx solid transparent;
}
.method-item.active {
  border-color: var(--main-color, #5AAB6E);
  background: #f0fff4;
}
.method-icon {
  width: 48rpx;
  height: 48rpx;
  margin-right: 16rpx;
}
.method-name {
  font-size: 28rpx;
  color: #333;
}
.method-balance {
  font-size: 24rpx;
  color: #999;
  margin-left: 10rpx;
}
.method-check {
  margin-left: auto;
  color: var(--main-color, #5AAB6E);
  font-weight: 600;
}
.confirm-btn {
  width: 100%;
  height: 96rpx;
  line-height: 96rpx;
  background: var(--main-color, #5AAB6E);
  color: #fff;
  font-size: 32rpx;
  font-weight: 500;
  border-radius: 48rpx;
  border: none;
}
.confirm-btn[disabled] {
  opacity: 0.6;
}
</style>
