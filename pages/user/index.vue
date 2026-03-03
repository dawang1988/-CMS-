<template>
  <view class="page" :style="themeStyle">
    <view class="logo-top" :style="{ paddingTop: statusBarHeight + 10 + 'px' }">
      <image src="/static/img/user-bg.png" />
      <view class="name">个人中心</view>
    </view>
    
    <view class="container">
      <view class="top">
        <!-- 已登录 -->
        <block v-if="isLogin">
          <view hover-class="button-click" class="userInfo" @tap="gotosetuserinfo">
            <view class="left">
              <view class="photo">
                <image :src="avatarUrl" mode="aspectFill"></image>
              </view>
              <view class="info">
                <view class="name-row">
                  <text class="name">{{ userinfo.nickname && userinfo.nickname.length ? userinfo.nickname : "暂无昵称" }}</text>
                  <text class="vip-tag" v-if="userinfo.vip_name">{{ userinfo.vip_name }}</text>
                </view>
                <view class="phone">{{ userinfo.phone || '' }}</view>
              </view>
            </view>
            <image class="setting" src="/static/icon/setting.png" />
          </view>
        </block>
        
        <!-- 未登录 -->
        <block v-else>
          <view hover-class="button-click" class="userInfo" @tap="gotologin">
            <view class="left">
              <image class="photo" src="/static/logo.png"></image>
              <view class="info">
                <view class="name">点击登录</view>
              </view>
            </view>
            <text style="font-size:36rpx;color:#fff;">›</text>
          </view>
        </block>
        
        <!-- 余额显示 -->
        <view class="balance-box" v-if="isLogin" @tap="goBalance">
          <view class="item">
            <view class="balance-icon ye">¥</view>
            <view class="money">{{ userinfo.balance || 0 }}</view>
          </view>
          <view class="item">
            <view class="balance-icon zeng">赠</view>
            <view class="money">{{ userinfo.gift_balance || 0 }}</view>
          </view>
        </view>
      </view>
      
      <view class="info-container">
        <!-- 我的账户 -->
        <view class="account-box" v-if="isLogin">
          <view class="top">我的账户</view>
          <view class="account-bottom">
            <view @tap="goScore">
              <text class="num">{{ userinfo.score || 0 }}</text>
              <text>积分</text>
            </view>
            <view @tap="goCoupon">
              <text class="num">{{ userinfo.couponCount || 0 }}</text>
              <text>优惠券</text>
            </view>
          </view>
          <!-- VIP进度 -->
          <view class="vip-progress" v-if="userinfo.next_vip_name">
            <view class="vip-progress-text">
              <text>距{{ userinfo.next_vip_name }}还需 {{ (userinfo.next_vip_score || 0) - (userinfo.score || 0) }} 积分</text>
              <text class="vip-discount" v-if="userinfo.vip_discount && userinfo.vip_discount < 100">当前享{{ userinfo.vip_discount / 10 }}折</text>
            </view>
            <view class="vip-bar-bg">
              <view class="vip-bar" :style="{ width: vipProgress + '%' }"></view>
            </view>
          </view>
          <view class="vip-progress" v-else-if="userinfo.vip_name">
            <view class="vip-progress-text">
              <text>已达最高等级：{{ userinfo.vip_name }}</text>
              <text class="vip-discount" v-if="userinfo.vip_discount && userinfo.vip_discount < 100">享{{ userinfo.vip_discount / 10 }}折</text>
            </view>
          </view>
        </view>
        
        <!-- 优惠中心和加入我们 -->
        <view class="line-box">
          <view class="item" @tap="goCoupon">
            <image class="line-icon" src="/static/icon/coupon.png" mode="aspectFit"></image>
            <text style="margin-left:15rpx">领券中心</text>
          </view>
          <view class="item" @tap="goJoin">
            <image class="line-icon" src="/static/icon/stores.png" mode="aspectFit"></image>
            <text style="margin-left:15rpx">加入我们</text>
          </view>
        </view>
        
        <!-- 常用功能 -->
        <view class="control-box">
          <view class="top">常用功能</view>
          <view class="control">
            <view class="item" @tap="goOrderList">
              <image class="ctrl-icon" src="/static/icon/order.png" mode="aspectFit"></image>
              <text>预定订单</text>
            </view>
            <view class="item" @tap="goProductOrder">
              <image class="ctrl-icon" src="/static/icon/orders.png" mode="aspectFit"></image>
              <text>商品订单</text>
            </view>
            <view class="item" @tap="goMyBalance">
              <image class="ctrl-icon" src="/static/icon/balance.png" mode="aspectFit"></image>
              <text>余额账单</text>
            </view>
            <view class="item" @tap="goCoupon">
              <image class="ctrl-icon" src="/static/icon/coupon.png" mode="aspectFit"></image>
              <text>我的优惠券</text>
            </view>
            <view class="item" @tap="goHelp">
              <image class="ctrl-icon" src="/static/icon/help.png" mode="aspectFit"></image>
              <text>常见问题</text>
            </view>

            <view class="item" @tap="goShop">
              <image class="ctrl-icon" src="/static/icon/pack.png" mode="aspectFit"></image>
              <text>商品存取</text>
            </view>
            <view class="item" @tap="goCard" v-if="cardEnabled">
              <image class="ctrl-icon" src="/static/icons/card.png" mode="aspectFit"></image>
              <text>会员卡</text>
            </view>
            <view class="item" @tap="goGame" v-if="gameEnabled">
              <image class="ctrl-icon" src="/static/icon/game.png" mode="aspectFit"></image>
              <text>拼场组局</text>
            </view>
            <view class="item" @tap="goFeedback">
              <image class="ctrl-icon" src="/static/icon/help.png" mode="aspectFit"></image>
              <text>意见反馈</text>
            </view>
            <view class="item" @tap="goMyReviews">
              <image class="ctrl-icon" src="/static/icon/star.png" mode="aspectFit"></image>
              <text>我的评价</text>
            </view>
          </view>
        </view>
        
        <!-- 功能专区（管理员/保洁员） -->
        <block v-if="isLogin && (userinfo.user_type == 12 || userinfo.user_type == 13 || userinfo.user_type == 14)">
          <!-- 管理员快捷入口 -->
          <view class="control-box" v-if="userinfo.user_type == 12 || userinfo.user_type == 13">
            <view class="top">管理功能</view>
            <view class="control">
              <view class="item" @tap="goSetStore">
                <image class="ctrl-icon" src="/static/icon/stoer.png" mode="aspectFit"></image>
                <view>门店管理</view>
              </view>
              <view class="item" @tap="goRoomList">
                <image class="ctrl-icon" src="/static/icon/room-c.png" mode="aspectFit"></image>
                <view>房间控制</view>
              </view>
              <view class="item" @tap="goSetOrder">
                <image class="ctrl-icon" src="/static/icon/order-c.png" mode="aspectFit"></image>
                <view>订单管理</view>
              </view>
              <view class="item" @tap="goScanQr">
                <image class="ctrl-icon" src="/static/icon/scan.png" mode="aspectFit"></image>
                <view>团购验券</view>
              </view>
              <view class="item" @tap="goTaskManager">
                <image class="ctrl-icon" src="/static/icon/cleaner-c.png" mode="aspectFit"></image>
                <view>保洁订单</view>
              </view>
            </view>
          </view>
          
          <!-- 保洁端功能 -->
          <view class="control-box" v-if="userinfo.user_type == 14">
            <view class="top">保洁功能</view>
            <view class="control">
              <view class="item" @tap="goTask">
                <image class="ctrl-icon" src="/static/icon/cleaner-c.png" mode="aspectFit"></image>
                <view>任务大厅</view>
              </view>
              <view class="item" @tap="goRoomList">
                <image class="ctrl-icon" src="/static/icon/room-c.png" mode="aspectFit"></image>
                <view>房间状态</view>
              </view>
              <view class="item" @tap="goTaskStatics">
                <image class="ctrl-icon" src="/static/icon/data-c.png" mode="aspectFit"></image>
                <view>任务统计</view>
              </view>
            </view>
          </view>
        </block>
      </view>
      
      <view class="version">版本:{{ version }}</view>
    </view>
    
    <!-- 服务到期提醒弹窗 -->
    <view v-if="showExpireModal" class="expire-mask">
      <view class="expire-modal">
        <view class="expire-title">到期提醒</view>
        <view class="expire-info">请及时联系管理员进行续期</view>
        <scroll-view scroll-y class="expire-list">
          <block v-for="(item, index) in serviceInfo" :key="index">
            <view class="expire-item" v-if="item.show">
              <view class="name-box">
                <text class="name-type">{{ item.type==1?'租户':item.type==2?'门店':item.type==3?'团购核销':'未知' }}</text>
                <text class="name">{{ item.name }}</text>
              </view>
              <text class="date">到期时间{{ item.expireDate }}</text>
            </view>
          </block>
        </scroll-view>
        <view class="expire-footer">
          <button class="btn primary" @tap="closeExpireModal">我知道了</button>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http'
import { getUserTypeName } from '@/utils/format'

export default {
  data() {
    return {
      statusBarHeight: 0,
      titleBarHeight: 0,
      isLogin: false,
      sysinfo: {},
      userinfo: {
        couponCount: 0,
        giftBalance: 0,
        balance: 0,
        avatar: '',
        nickname: '',
        mobile: '',
        userType: 11
      },
      cardList: [],
      serviceInfo: [],
      showExpireModal: false,
      version: '',
      gameEnabled: false,
      cardEnabled: false
    }
  },

  computed: {
    avatarUrl() {
      const config = require('@/config/index.js').default || require('@/config/index.js')
      const avatar = this.userinfo.avatar
      if (avatar && avatar.length > 5) {
        if (avatar.startsWith('http')) return avatar
        return config.imageBase + avatar
      }
      return '/static/logo.png'
    },
    vipProgress() {
      var score = this.userinfo.score || 0
      var next = this.userinfo.next_vip_score || 0
      if (next <= 0) return 100
      return Math.min(100, Math.round(score / next * 100))
    }
  },

  onLoad(options) {
    const config = require('@/config/index.js').default || require('@/config/index.js')
    this.version = config.version || '1.0.0'
    this.getSysInfo()
    const systemInfo = uni.getSystemInfoSync()
    this.statusBarHeight = systemInfo.statusBarHeight || 0
    this.titleBarHeight = uni.getStorageSync("titleBarHeight") || 44
  },

  onShow() {
    const app = getApp()
    this.isLogin = app.globalData.isLogin || false
    
    if (!app.globalData.isLogin) {
      this.userinfo = {
        couponCount: 0,
        giftBalance: 0,
        balance: 0,
        avatar: '',
        nickname: '',
        mobile: '',
        userType: 11
      }
    }
    this.getuserinfo()
    this.checkGameEnabled()
    this.checkCardEnabled()
  },

  methods: {
    checkGameEnabled() {
      http.get('/system/config/get', { key: 'game_enabled' })
        .then(res => {
          if (res.code === 0) {
            this.gameEnabled = res.data.value !== '0'
          }
        })
        .catch(() => {
          this.gameEnabled = false
        })
    },

    checkCardEnabled() {
      const storeId = uni.getStorageSync('global_store_id') || ''
      if (!storeId) {
        this.cardEnabled = false
        return
      }
      http.get('/member/card/checkEnabled', { store_id: storeId })
        .then(res => {
          if (res.code === 0) {
            this.cardEnabled = res.data.enabled
          }
        })
        .catch(() => {
          this.cardEnabled = false
        })
    },

    getuserinfo() {
      const app = getApp()
      if (app.globalData.isLogin) {
        http.get('/member/user/get')
          .then(info => {
            if (info.code == 0) {
              this.userinfo = info.data
              
              // 检查服务到期信息
              if (info.data.user_type == 12 || info.data.user_type == 13) {
                http.post('/member/store/getServiceInfo')
                  .then(res => {
                    if (res.code == 0) {
                      this.serviceInfo = res.data
                      if (res.data && res.data.length > 0) {
                        this.showExpireModal = true
                      }
                    }
                  })
                  .catch(err => {
                  })
              }
            }
          })
          .catch(err => {
          })
      }
    },

    getSysInfo() {
      http.get('/member/index/getSysInfo')
        .then(info => {
          if (info.code == 0) {
            this.sysinfo = info.data
            // 从后台获取版本号
            if (info.data.app_version) {
              this.version = info.data.app_version
            }
          }
        })
        .catch(err => {
        })
    },

    gotosetuserinfo() {
      uni.navigateTo({
        url: '/pages/user/set-info',
      })
    },

    gotologin() {
      uni.navigateTo({
        url: '/pages/user/login',
      })
    },

    goBalance() {
      if (this.isLogin) {
        uni.navigateTo({
          url: '/pages/balance/get',
        })
      } else {
        this.gotologin()
      }
    },

    goScore() {
      uni.navigateTo({
        url: '/pages/score/index',
      })
    },

    goCoupon() {
      uni.navigateTo({
        url: '/pages/coupon/index',
      })
    },

    goRecharge() {
      uni.switchTab({
        url: '/pages/recharge/index',
      })
    },

    goJoin() {
      if (this.isLogin) {
        uni.navigateTo({
          url: '/pages/join/index',
        })
      } else {
        this.gotologin()
      }
    },

    goOrderList() {
      uni.switchTab({
        url: '/pages/order/list',
      })
    },

    goProductOrder() {
      if (this.isLogin) {
        uni.navigateTo({
          url: '/pages/product/order',
        })
      } else {
        this.gotologin()
      }
    },

    goMyBalance() {
      if (this.isLogin) {
        uni.navigateTo({
          url: '/pages/balance/index',
        })
      } else {
        this.gotologin()
      }
    },

    goHelp() {
      uni.navigateTo({
        url: '/pages/help/index',
      })
    },



    goShop() {
      if (this.isLogin) {
        uni.navigateTo({
          url: '/pages/shop/pickup',
        })
      } else {
        this.gotologin()
      }
    },

    goFeedback() {
      uni.navigateTo({
        url: '/pages/user/feedback',
      })
    },

    goMyReviews() {
      if (this.isLogin) {
        uni.navigateTo({
          url: '/pages/user/my-reviews',
        })
      } else {
        this.gotologin()
      }
    },

    goCard() {
      if (this.isLogin) {
        const storeId = uni.getStorageSync('global_store_id') || ''
        uni.navigateTo({
          url: '/pages/card/index?store_id=' + storeId,
        })
      } else {
        this.gotologin()
      }
    },

    goGame() {
      if (this.isLogin) {
        uni.navigateTo({
          url: '/pages/door/door',
        })
      } else {
        this.gotologin()
      }
    },

    // 管理员功能
    goSetStore() {
      const storeId = this.userinfo.store_id || uni.getStorageSync('global_store_id') || ''
      uni.navigateTo({
        url: `/pagesA/store/set?store_id=${storeId}`,
      })
    },

    goRoomList() {
      const storeId = this.userinfo.store_id || uni.getStorageSync('global_store_id') || ''
      uni.navigateTo({
        url: `/pagesA/room/list?store_id=${storeId}`,
      })
    },

    goSetOrder() {
      const storeId = this.userinfo.store_id || uni.getStorageSync('global_store_id') || ''
      uni.navigateTo({
        url: `/pagesA/order/set?store_id=${storeId}`,
      })
    },

    goScanQr() {
      const storeId = this.userinfo.store_id || uni.getStorageSync('global_store_id') || ''
      uni.navigateTo({
        url: `/pagesA/scan/qr?store_id=${storeId}`,
      })
    },

    goTaskManager() {
      const storeId = this.userinfo.store_id || uni.getStorageSync('global_store_id') || ''
      uni.navigateTo({
        url: `/pagesA/task/manager?store_id=${storeId}`,
      })
    },

    // 保洁端功能
    goTask() {
      const storeId = this.userinfo.store_id || uni.getStorageSync('global_store_id') || ''
      uni.navigateTo({
        url: `/pagesA/task/index?store_id=${storeId}`,
      })
    },

    goTaskStatics() {
      const storeId = this.userinfo.store_id || uni.getStorageSync('global_store_id') || ''
      uni.navigateTo({
        url: `/pagesA/statics/task?store_id=${storeId}`,
      })
    },

    closeExpireModal() {
      this.showExpireModal = false
    },
  }
}
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background: #F6F6F6;
}

.logo-top {
  position: relative;
  top: 0;
  left: 0;
  width: 100%;
  padding: 20rpx 0 260rpx 0;
  background-color: var(--main-color, #5AAB6E);
  z-index: 1;
}

.logo-top .name {
  color: #FFFFFF;
  font-weight: 400;
  font-size: 32rpx;
  text-align: center;
  position: relative;
  z-index: 100;
}

.logo-top image {
  width: 100%;
  height: 485rpx;
  position: absolute;
  top: 0;
  left: 0;
  z-index: 1;
}

.container {
  padding-bottom: 38rpx;
  margin-top: -240rpx;
  position: relative;
  z-index: 2;
}

.container .top {
  padding: 0rpx 26rpx 70rpx 26rpx;
}

.top .userInfo {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: transparent;
  padding: 0;
  text-align: left;
  color: #fff;
  line-height: 1;
  border-radius: 0;
  border: none;
}

.top .userInfo::after {
  border: none;
}

.top .userInfo .left {
  display: flex;
  margin-left: 20rpx;
  align-items: center;
}

.top .userInfo .photo {
  width: 138rpx;
  height: 138rpx;
  min-width: 138rpx;
  min-height: 138rpx;
  border-radius: 100%;
  overflow: hidden;
  background-color: #FFE500;
  flex-shrink: 0;
}

.top .userInfo .photo image {
  width: 138rpx;
  height: 138rpx;
  display: block;
}

.top .userInfo .info {
  margin-left: 40rpx;
  font-size: 30rpx;
  font-weight: 400;
  font-size: 21rpx;
  color: #FFFFFF;
}

.top .userInfo .name {
  margin-bottom: 10rpx;
  font-weight: bold;
  font-size: 35rpx;
  color: #FFFFFF;
}

.setting {
  width: 42rpx;
  height: 42rpx;
  margin-right: 16rpx;
}

.balance-box {
  background: linear-gradient(180deg, #F5CA94 0%, #EFCB97 100%);
  border-radius: 18rpx 18rpx 0rpx 0rpx;
  font-weight: 400;
  font-size: 28rpx;
  color: #937746;
  padding: 20rpx 26rpx;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 10rpx;
}

.balance-box .item {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  width: 45%;
  margin-left: 10rpx;
}

.balance-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 48rpx;
  height: 48rpx;
  border-radius: 50%;
  font-size: 24rpx;
  font-weight: bold;
  color: #fff;
  flex-shrink: 0;
}

.balance-icon.ye {
  background-color: #FF5252;
}

.balance-icon.zeng {
  background-color: #FF5252;
}

.balance-box .item .money {
  font-size: 36rpx;
  color: var(--main-color, #5AAB6E);
  font-weight: 600;
  margin-left: 16rpx;
}

.info-container {
  background: #F6F6F6;
  border-radius: 28rpx 28rpx 0rpx 0rpx;
  padding: 10rpx 26rpx;
  margin-top: -62rpx;
}

.info-container .top {
  font-weight: 600;
  font-size: 32rpx;
  color: #252525;
  padding: 0;
}

.info-container .account-box {
  background-color: #fff;
  padding: 14rpx 32rpx;
  border-radius: 18rpx 18rpx 18rpx 18rpx;
  margin-bottom: 24rpx;
}

.account-box .account-bottom {
  display: flex;
  justify-content: space-between;
}

.account-box .account-bottom view {
  flex-grow: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  font-weight: 500;
  font-size: 28rpx;
  color: #3E3E3E;
}

.account-box .account-bottom .num {
  color: var(--main-color, #6da773fd);
  font-size: 35rpx;
}

.line-box {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 26rpx;
}

.line-box .item {
  display: flex;
  justify-content: center;
  align-items: center;
  font-weight: 600;
  font-size: 33rpx;
  height: 143rpx;
  width: 335rpx;
  border-radius: 17rpx;
}

.line-box .item:first-of-type {
  background: linear-gradient(#D6F5DD 30%, #fff 80%);
  color: var(--main-color, #6da773fd);
}

.line-box .item:last-of-type {
  background: linear-gradient(#FFECD1 30%, #fff 80%);
  color: #E8B573;
}

.control-box {
  background: #FFFFFF;
  border-radius: 18rpx 18rpx 18rpx 18rpx;
  padding: 22rpx 34rpx;
  font-weight: 400;
  font-size: 26rpx;
  color: #252525;
  margin-top: 26rpx;
}

.control-box .control {
  display: flex;
  flex-wrap: wrap;
}

.control-box .control .item {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 25%;
  margin: 20rpx 0;
}

.ctrl-icon {
  width: 56rpx;
  height: 56rpx;
  margin-bottom: 10rpx;
}

.line-icon {
  width: 48rpx;
  height: 48rpx;
}

.version {
  font-size: 24rpx;
  color: var(--main-color, #6da773fd);
  text-align: center;
  font-weight: 600;
  margin-top: 20rpx;
}

/* VIP标签 */
.name-row {
  display: flex;
  align-items: center;
  margin-bottom: 10rpx;
}
.name-row .name {
  font-weight: bold;
  font-size: 35rpx;
  color: #FFFFFF;
  margin-bottom: 0;
}
.vip-tag {
  background: linear-gradient(135deg, #f0c27f, #d4a44c);
  color: #fff;
  font-size: 20rpx;
  padding: 2rpx 12rpx;
  border-radius: 16rpx;
  margin-left: 12rpx;
  font-weight: 500;
  white-space: nowrap;
}

/* VIP进度 */
.vip-progress {
  padding: 16rpx 0 6rpx;
  border-top: 1rpx solid #f0f0f0;
  margin-top: 12rpx;
}
.vip-progress-text {
  display: flex;
  justify-content: space-between;
  font-size: 22rpx;
  color: #999;
  margin-bottom: 10rpx;
}
.vip-discount {
  color: var(--main-color, #5AAB6E);
  font-weight: 500;
}
.vip-bar-bg {
  height: 12rpx;
  background: #eee;
  border-radius: 6rpx;
  overflow: hidden;
}
.vip-bar {
  height: 100%;
  background: linear-gradient(90deg, var(--main-color, #5AAB6E), #f0c27f);
  border-radius: 6rpx;
  transition: width 0.3s;
}

/* 服务到期提醒弹窗 */
.expire-mask {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, .55);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  animation: fadeIn .25s ease-out;
}

.expire-mask .expire-modal {
  width: 680rpx;
  max-height: 78vh;
  background: #ffffff;
  border-radius: 24rpx;
  box-shadow: 0 12rpx 40rpx rgba(0, 0, 0, .18);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.expire-mask .expire-title {
  padding: 36rpx 24rpx;
  font-size: 34rpx;
  font-weight: 600;
  text-align: center;
  color: #333;
  border-bottom: 1rpx solid #f0f0f0;
  background: #fafafa;
}

.expire-mask .expire-info {
  font-size: 34rpx;
  font-weight: 600;
  text-align: center;
  color: rgb(238, 36, 36);
  padding: 20rpx 0;
}

.expire-mask .expire-list {
  flex: 1;
  padding: 0 32rpx;
  box-sizing: border-box;
}

.expire-mask .expire-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 28rpx 0;
  border-bottom: 1rpx solid #f7f7f7;
  font-size: 30rpx;
  line-height: 1.45;
}

.expire-mask .expire-item .name-box {
  flex: 1;
  min-width: 0;
  margin-right: 16rpx;
}

.expire-mask .expire-item .name-type,
.expire-mask .expire-item .name {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.expire-mask .expire-item .name-type {
  font-size: 26rpx;
  color: #666;
  margin-bottom: 4rpx;
}

.expire-mask .expire-item .name {
  font-weight: 500;
  color: #333;
}

.expire-mask .expire-item .date {
  flex-shrink: 0;
  font-size: 26rpx;
  color: #ff4757;
}

.expire-mask .expire-footer {
  padding: 32rpx;
  display: flex;
  justify-content: center;
}

.expire-mask .btn {
  width: 240rpx;
  height: 76rpx;
  line-height: 76rpx;
  text-align: center;
  font-size: 30rpx;
  border-radius: 38rpx;
  border: none;
}

.expire-mask .primary {
  background: linear-gradient(135deg, #07c160, #06a050);
  color: #fff;
}

.expire-mask .primary:active {
  opacity: .9;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: scale(.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
</style>
