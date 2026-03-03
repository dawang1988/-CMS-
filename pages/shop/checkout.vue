<template>
  <view class="container" :style="themeStyle">
    <!-- 门店信息 -->
    <view class="section">
      <view class="section-title">门店信息</view>
      <view class="store-row">
        <text class="store-name">{{ storeName || '未选择门店' }}</text>
      </view>
    </view>

    <!-- 商品列表 -->
    <view class="section">
      <view class="section-title">商品清单</view>
      <view class="product-item" v-for="(item, index) in cart" :key="index">
        <image class="product-img" :src="item.image || '/static/img/default-product.png'" mode="aspectFill" />
        <view class="product-info">
          <text class="product-name">{{ item.name }}</text>
          <view class="product-bottom">
            <text class="product-price">¥{{ item.price }}</text>
            <text class="product-num">x{{ item.number }}</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 取货方式 -->
    <view class="section">
      <view class="section-title">取货方式</view>
      <view class="pickup-options">
        <view class="pickup-item" :class="pickupType === 1 ? 'active' : ''" @tap="pickupType = 1">
          <text class="pickup-icon">🛍️</text>
          <text class="pickup-text">立即取走</text>
          <text class="pickup-desc">现场拿走商品</text>
        </view>
        <view class="pickup-item" :class="pickupType === 2 ? 'active' : ''" @tap="pickupType = 2">
          <text class="pickup-icon">📦</text>
          <text class="pickup-text">寄存</text>
          <text class="pickup-desc">下次来店取走</text>
        </view>
      </view>
    </view>

    <!-- 备注 -->
    <view class="section">
      <view class="section-title">备注</view>
      <input class="mark-input" placeholder="选填，如有特殊要求请备注" :value="mark" @input="onMarkInput" />
    </view>

    <!-- 支付方式 -->
    <view class="section">
      <view class="section-title">支付方式</view>
      <view class="pay-options">
        <view class="pay-item active">
          <image class="pay-icon" src="/static/icon/wepay.png" mode="aspectFit" />
          <text class="pay-text">微信支付</text>
        </view>
      </view>
    </view>

    <!-- 底部结算 -->
    <view class="bottom-bar">
      <view class="total-info">
        <text class="total-label">合计：</text>
        <text class="total-price">¥{{ totalPrice }}</text>
      </view>
      <button class="pay-btn" @tap="submitOrder">微信支付</button>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http.js'

export default {
  data: function() {
    return {
      storeId: '',
      storeName: '',
      cart: [],
      pickupType: 1,
      payType: 1,
      mark: '',
      totalPrice: '0.00',
      balance: '0.00',
      submitting: false
    }
  },
  onLoad: function(options) {
    this.storeId = options.storeId || options.store_id || ''
    this.storeName = options.storeName || ''
    var payCart = uni.getStorageSync('payCart') || []
    this.cart = payCart
    var total = 0
    for (var i = 0; i < payCart.length; i++) {
      total += (payCart[i].price || 0) * (payCart[i].number || 1)
    }
    this.totalPrice = total.toFixed(2)
    // 获取门店名
    if (this.storeId && !this.storeName) {
      var that = this
      http.get('/member/index/getStoreInfo/' + this.storeId).then(function(res) {
        if (res.code === 0 && res.data) {
          that.storeName = res.data.name || res.data.store_name || ''
        }
      })
    }
    // 获取用户余额
    this.getBalance()
  },
  methods: {
    onMarkInput: function(e) {
      this.mark = e.detail.value
    },
    getBalance: function() {
      var that = this
      http.get('/member/user/getStoreBalance/' + this.storeId).then(function(res) {
        if (res.code === 0 && res.data) {
          var bal = parseFloat(res.data.balance || 0) + parseFloat(res.data.gift_balance || 0)
          that.balance = bal.toFixed(2)
        }
      }).catch(function() {})
    },
    submitOrder: function() {
      if (this.submitting) return
      if (!this.cart.length) {
        uni.showToast({ title: '购物车为空', icon: 'none' })
        return
      }
      this.submitting = true
      var that = this
      var markText = that.mark || ''
      if (that.pickupType === 2) {
        markText = '[寄存] ' + markText
      }
      http.post('/product/order/create', {
        store_id: that.storeId,
        productInfo: that.cart,
        mark: markText,
        pay_type: 1,
        pickup_type: that.pickupType
      }).then(function(res) {
        that.submitting = false
        if (res.code === 0) {
          // 微信支付需要调起支付
          if (res.data && res.data.payInfo) {
            that.wxPay(res.data.payInfo, res.data.order_no)
          } else {
            uni.showModal({ content: res.msg || '订单创建成功，请完成支付', showCancel: false })
          }
        } else {
          uni.showModal({ content: res.msg || '下单失败', showCancel: false })
        }
      }).catch(function(e) {
        that.submitting = false
        uni.showModal({ content: (e && e.msg) || '下单失败', showCancel: false })
      })
    },
    wxPay: function(payInfo, orderNo) {
      var that = this
      uni.requestPayment({
        provider: 'wxpay',
        timeStamp: payInfo.timeStamp,
        nonceStr: payInfo.nonceStr,
        package: payInfo.package,
        signType: payInfo.signType || 'MD5',
        paySign: payInfo.paySign,
        success: function() {
          uni.removeStorageSync('payCart')
          uni.removeStorageSync('cart')
          uni.removeStorageSync('goodNum')
          var msg = '支付成功'
          if (that.pickupType === 2) {
            msg = '支付成功，商品已寄存，请在"商品存取"中查看'
          }
          uni.showModal({
            title: '支付成功',
            content: msg,
            showCancel: false,
            success: function() {
              uni.navigateBack({ delta: 2 })
            }
          })
        },
        fail: function(err) {
          if (err.errMsg && err.errMsg.indexOf('cancel') > -1) {
            uni.showToast({ title: '已取消支付', icon: 'none' })
          } else {
            uni.showModal({ content: '支付失败，请重试', showCancel: false })
          }
        }
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.container { min-height: 100vh; background: #f5f5f5; padding-bottom: 140rpx; }
.section { background: #fff; margin: 20rpx; border-radius: 16rpx; padding: 30rpx; }
.section-title { font-size: 30rpx; font-weight: 600; color: #333; margin-bottom: 20rpx; }
.store-row { display: flex; align-items: center; }
.store-name { font-size: 28rpx; color: #666; }

.product-item { display: flex; align-items: center; padding: 16rpx 0; border-bottom: 1rpx solid #f5f5f5; }
.product-item:last-child { border-bottom: none; }
.product-img { width: 100rpx; height: 100rpx; border-radius: 12rpx; margin-right: 20rpx; flex-shrink: 0; }
.product-info { flex: 1; }
.product-name { font-size: 28rpx; color: #333; display: block; margin-bottom: 10rpx; }
.product-bottom { display: flex; justify-content: space-between; align-items: center; }
.product-price { font-size: 28rpx; color: #ff6b00; font-weight: 600; }
.product-num { font-size: 26rpx; color: #999; }

.pickup-options { display: flex; gap: 20rpx; }
.pickup-item {
  flex: 1; display: flex; flex-direction: column; align-items: center;
  padding: 30rpx 20rpx; border: 2rpx solid #eee; border-radius: 16rpx;
  background: #fafafa; transition: all 0.2s;
}
.pickup-item.active { border-color: var(--main-color, #5AAB6E); background: #f0faf3; }
.pickup-icon { font-size: 48rpx; margin-bottom: 10rpx; }
.pickup-text { font-size: 30rpx; font-weight: 600; color: #333; margin-bottom: 6rpx; }
.pickup-desc { font-size: 24rpx; color: #999; }

.mark-input { width: 100%; height: 70rpx; font-size: 28rpx; color: #333; border: 1rpx solid #eee; border-radius: 10rpx; padding: 0 20rpx; box-sizing: border-box; }

.pay-options { display: flex; flex-direction: column; gap: 20rpx; }
.pay-item {
  display: flex; align-items: center; padding: 24rpx 20rpx;
  border: 2rpx solid #eee; border-radius: 16rpx; background: #fafafa;
}
.pay-item.active { border-color: var(--main-color, #5AAB6E); background: #f0faf3; }
.pay-icon { width: 48rpx; height: 48rpx; margin-right: 20rpx; }
.pay-text { font-size: 30rpx; color: #333; }
.pay-balance { display: flex; flex-direction: column; }
.balance-info { font-size: 24rpx; color: #999; margin-top: 4rpx; }

.bottom-bar {
  position: fixed; left: 0; right: 0; bottom: 0; z-index: 100;
  display: flex; align-items: center; justify-content: space-between;
  height: 110rpx; padding: 0 30rpx; background: #fff;
  box-shadow: 0 -2rpx 10rpx rgba(0,0,0,0.05);
}
.total-info { display: flex; align-items: baseline; }
.total-label { font-size: 28rpx; color: #333; }
.total-price { font-size: 40rpx; font-weight: 700; color: #ff6b00; }
.pay-btn {
  background: var(--main-color, #5AAB6E); color: #fff; font-size: 30rpx; padding: 16rpx 50rpx;
  border-radius: 40rpx; border: none; font-weight: 500;
}
</style>
