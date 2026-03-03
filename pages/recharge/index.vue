<template>
  <view class="container" :style="themeStyle">
    <!-- 门店选择 -->
    <view class="store-bar" v-if="isLogin">
      <view class="store-info">
        <image src="/static/icon/stoer.png" class="store-icon" />
        <text class="store-name">{{ storeName || '请选择门店' }}</text>
        <picker :value="storeIndex" :range="stores" range-key="key" @change="bindStoreChange">
          <view class="change-btn">切换门店</view>
        </picker>
      </view>
    </view>

    <!-- 账户余额 -->
    <view class="balance-card">
      <view class="balance-title">
        <image src="/static/icon/wallet.png" class="balance-icon" />
        <text>账户余额</text>
      </view>
      <view class="balance-row">
        <view class="balance-item">
          <image src="/static/icon/balance-account.png" class="balance-img" />
          <text class="balance-num">{{ balance }}</text>
        </view>
        <view class="balance-item">
          <image src="/static/icon/send.png" class="balance-img" />
          <text class="balance-num">{{ giftBalance }}</text>
        </view>
      </view>
    </view>

    <!-- 充值规则选择 -->
    <view class="rule-box" v-if="isLogin">
      <block v-if="discount.length > 0">
        <view class="rule-list">
          <view
            class="rule-item"
            :class="selectedRuleIndex === index ? 'active' : ''"
            v-for="(item, index) in discount"
            :key="index"
            @tap="chooseRule(index)"
          >
            <view class="rule-tag" v-if="item.gift_money">送¥{{ item.gift_money }}</view>
            <view class="rule-price">
              <text class="yen">¥</text>
              <text class="amount">{{ item.pay_money }}</text>
            </view>
          </view>
        </view>
      </block>
      <block v-else>
        <view class="no-rules">
          <text>管理员未添加充值规则</text>
        </view>
      </block>
    </view>

    <!-- 未登录提示 -->
    <view class="no-login" v-if="!isLogin">
      <text class="no-login-text">请登录后进行充值</text>
      <navigator class="login-btn" url="/pages/user/login">登录</navigator>
    </view>

    <!-- 充值说明 -->
    <view class="desc-title">
      <text class="line"></text>
      <text>充值说明</text>
      <text class="line"></text>
    </view>
    <view class="desc-card">
      <view class="desc-line">1. 余额终身有效!可以下单时支付、续费。</view>
      <view class="desc-line">2. 余额按门店独立结算，仅充值时选中的门店可使用,请仔细确认充值的门店是否正确！</view>
      <view class="desc-line">3. 系统会优先扣除账户余额，扣除完毕后再扣除赠送余额。</view>
      <view class="desc-line">4. 充值实时到账，如已扣费但未到账，请您联系客服处理。</view>
      <view class="desc-line">5. 充值赠送活动以页面实时显示为准！</view>
    </view>

    <!-- 立即充值按钮 -->
    <view class="pay-btn-wrap" v-if="isLogin && discount.length > 0">
      <button class="pay-btn" :disabled="!payMoney" :class="payMoney ? '' : 'disabled'" @tap="submitPay">立即充值</button>
    </view>

    <view class="footer-space"></view>
  </view>
</template>

<script>
import http from '@/utils/http'

export default {
  data: function() {
    return {
      isLogin: false,
      stores: [],
      storeIndex: 0,
      storeName: '',
      storeId: '',
      balance: 0,
      giftBalance: 0,
      discount: [],
      payMoney: '',
      selectedRuleIndex: -1,
      userId: ''
    }
  },

  onLoad: function(options) {
    if (options && options.store_id) {
      uni.setStorageSync('global_store_id', options.store_id)
    }
  },

  onShow: function() {
    var app = getApp()
    this.isLogin = app.globalData.isLogin || false
    var storeId = uni.getStorageSync('global_store_id') || ''
    this.storeId = storeId
    this.payMoney = ''
    this.selectedRuleIndex = -1
    if (this.isLogin) {
      this.getUserInfo()
      this.getStoreList()
    }
  },

  methods: {
    getUserInfo: function() {
      var that = this
      http.get('/member/user/get').then(function(res) {
        if (res.code === 0) {
          that.userId = res.data.id
        }
      })
    },

    getStoreList: function() {
      var that = this
      http.get('/member/index/getStoreList', { cityName: '' }).then(function(res) {
        if (res.code === 0 && res.data) {
          var rawList = res.data.list || res.data
          if (!rawList || !rawList.length) return
          var storeArr = []
          for (var i = 0; i < rawList.length; i++) {
            var it = rawList[i]
            storeArr.push({
              key: it.key || it.name || it.store_name || '',
              value: it.value || it.store_id || it.id || ''
            })
          }
          that.stores = storeArr
          if (!that.storeId) {
            that.storeId = storeArr[0].value
            that.storeName = storeArr[0].key
            that.storeIndex = 0
          } else {
            for (var j = 0; j < storeArr.length; j++) {
              if (storeArr[j].value == that.storeId) {
                that.storeIndex = j
                that.storeName = storeArr[j].key
                break
              }
            }
          }
          that.getDiscount()
          that.getStoreBalance()
        }
      })
    },

    bindStoreChange: function(e) {
      var idx = e.detail.value
      this.storeIndex = idx
      this.storeId = this.stores[idx].value
      this.storeName = this.stores[idx].key
      this.payMoney = ''
      this.selectedRuleIndex = -1
      this.getDiscount()
      this.getStoreBalance()
    },

    getStoreBalance: function() {
      var that = this
      http.get('/member/user/getStoreBalance/' + that.storeId, { store_id: that.storeId }).then(function(res) {
        if (res.code === 0) {
          that.balance = res.data.balance || 0
          that.giftBalance = res.data.gift_balance || 0
        }
      })
    },

    getDiscount: function() {
      var that = this
      http.get('/member/order/getDiscountRules/' + that.storeId).then(function(res) {
        if (res.code === 0) {
          that.discount = res.data || []
        }
      })
    },

    chooseRule: function(index) {
      this.selectedRuleIndex = index
      this.payMoney = this.discount[index].pay_money
    },

    submitPay: function() {
      var that = this
      if (!that.payMoney) {
        uni.showToast({ title: '请选择充值金额', icon: 'none' })
        return
      }
      uni.showModal({
        title: '提示',
        content: '您当前选择的门店为：\n【' + that.storeName + '】\n充值的余额仅该门店可用！确认充值吗？',
        confirmText: '确认',
        success: function(res) {
          if (res.confirm) {
            http.post('/member/user/preRechargeBalance', {
              user_id: that.userId,
              store_id: that.storeId,
              amount: that.payMoney
            }).then(function(info) {
              if (info.code === 0) {
                var d = info.data || {}
                if (d.timeStamp) {
                  that.doWxPay(d)
                } else {
                  var msg = d.message || info.msg || '充值成功'
                  uni.showModal({
                    title: '充值成功',
                    content: msg,
                    showCancel: false,
                    success: function() {
                      that.getStoreBalance()
                    }
                  })
                }
              } else {
                uni.showModal({ content: info.msg || '充值失败', showCancel: false })
              }
            }).catch(function(e) {
              uni.showModal({ content: (e && e.msg) || '充值失败', showCancel: false })
            })
          }
        }
      })
    },

    doWxPay: function(payData) {
      var that = this
      uni.requestPayment({
        timeStamp: payData.timeStamp,
        nonceStr: payData.nonceStr,
        package: payData.pkg,
        signType: payData.signType,
        paySign: payData.paySign,
        success: function() {
          uni.showToast({ title: '支付成功!', icon: 'success' })
          that.getStoreBalance()
        },
        fail: function() {
          uni.showToast({ title: '支付取消', icon: 'none' })
        }
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 140rpx;
}

/* 门店选择 */
.store-bar {
  background: #fff;
  padding: 24rpx 30rpx;
  margin-bottom: 20rpx;
}
.store-info {
  display: flex;
  align-items: center;
}
.store-icon {
  width: 36rpx;
  height: 36rpx;
  margin-right: 12rpx;
}
.store-name {
  font-size: 32rpx;
  font-weight: 600;
  color: #333;
  margin-right: 20rpx;
}
.change-btn {
  background: var(--main-color, #5AAB6E);
  color: #fff;
  font-size: 24rpx;
  padding: 8rpx 24rpx;
  border-radius: 30rpx;
}

/* 账户余额 */
.balance-card {
  background: #fff;
  margin: 0 20rpx 20rpx;
  border-radius: 17rpx;
  padding: 30rpx;
}
.balance-title {
  display: flex;
  align-items: center;
  margin-bottom: 30rpx;
  font-size: 32rpx;
  font-weight: 600;
  color: #333;
}
.balance-icon {
  width: 40rpx;
  height: 40rpx;
  margin-right: 12rpx;
}
.balance-row {
  display: flex;
  justify-content: space-around;
}
.balance-item {
  display: flex;
  flex-direction: column;
  align-items: center;
}
.balance-img {
  width: 80rpx;
  height: 80rpx;
  margin-bottom: 16rpx;
}
.balance-num {
  font-size: 48rpx;
  font-weight: 600;
  color: var(--main-color, #5AAB6E);
}

/* 充值规则 */
.rule-box {
  padding: 0 20rpx;
}
.rule-list {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  padding: 0 10rpx;
}
.rule-item {
  width: 48%;
  margin-bottom: 20rpx;
  background: #fff;
  border-radius: 17rpx;
  padding: 30rpx 20rpx;
  text-align: center;
  position: relative;
  border: 2rpx solid #eee;
  box-sizing: border-box;
}
.rule-item.active {
  border-color: var(--main-color, #5AAB6E);
  background: #f0faf3;
}
.rule-tag {
  position: absolute;
  top: 0;
  left: 0;
  background: var(--main-color, #5AAB6E);
  color: #fff;
  padding: 6rpx 20rpx;
  border-radius: 17rpx 0 17rpx 0;
  font-size: 22rpx;
  font-weight: 600;
}
.rule-price {
  margin-top: 10rpx;
}
.rule-price .yen {
  font-size: 28rpx;
  color: #333;
}
.rule-price .amount {
  font-size: 56rpx;
  font-weight: 700;
  color: #333;
}
.no-rules {
  text-align: center;
  padding: 60rpx 0;
  color: #999;
  font-size: 28rpx;
}

/* 未登录 */
.no-login {
  text-align: center;
  padding: 80rpx 0;
}
.no-login-text {
  font-size: 28rpx;
  color: #999;
  display: block;
  margin-bottom: 30rpx;
}
.login-btn {
  display: inline-block;
  padding: 15rpx 80rpx;
  background: var(--main-color, #5AAB6E);
  color: #fff;
  border-radius: 50rpx;
  font-size: 28rpx;
}

/* 充值说明 */
.desc-title {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40rpx 0 20rpx;
  font-size: 30rpx;
  color: #666;
}
.desc-title .line {
  width: 60rpx;
  height: 2rpx;
  background: #ccc;
  margin: 0 20rpx;
}
.desc-card {
  background: #fff;
  margin: 0 20rpx;
  border-radius: 17rpx;
  padding: 30rpx;
}
.desc-line {
  font-size: 26rpx;
  color: #666;
  line-height: 44rpx;
  margin-bottom: 6rpx;
}

/* 充值按钮 */
.pay-btn-wrap {
  padding: 30rpx 40rpx;
}
.pay-btn {
  width: 100%;
  height: 90rpx;
  line-height: 90rpx;
  background: var(--main-color, #5AAB6E);
  color: #fff;
  font-size: 32rpx;
  border-radius: 45rpx;
  text-align: center;
  border: none;
}
.pay-btn.disabled {
  background: #ccc;
  color: #999;
}
.footer-space {
  height: 40rpx;
}
</style>
