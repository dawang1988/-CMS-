<template>
  <view class="container" :style="themeStyle">
    <!-- 顶部门店信息 -->
    <view class="top">
      <view class="top-doorInfo">
        <view class="top-title">
          <text class="iconfont icon-store"></text>
          {{ storeName }}
        </view>
        <view class="info">
          <view class="store-id">
            <text class="store-text">门店ID：<text>{{ store_id }}</text></text>
          </view>
          <view class="store-time" v-if="expireTime">
            <text class="store-text">{{ expireTime }}到期</text>
          </view>
        </view>
      </view>
    </view>

    <view class="info-container">
      <!-- 店铺管理 -->
      <view class="control-box">
        <view class="box-title">店铺管理</view>
        <view class="control">
          <view class="item" @tap="goPage('/pagesA/store/set-info')">
            <text class="menu-icon">✏️</text>
            <text>信息修改</text>
          </view>
          <view class="item" @tap="goPage('/pagesA/door/set-list')">
            <text class="menu-icon">🚪</text>
            <text>房间管理</text>
          </view>
          <view class="item" @tap="previewImage(qrCode)">
            <text class="menu-icon">📱</text>
            <text>门店二维码</text>
          </view>
          <view class="item" @tap="goPage('/pagesA/notice/manage')">
            <text class="menu-icon">📢</text>
            <text>公告提醒</text>
          </view>
          <view class="item" @tap="goPage('/pagesA/store/set-sound')">
            <text class="menu-icon">🔊</text>
            <text>播报管理</text>
          </view>
          <view class="item" @tap="goPage('/pagesA/task/manager')">
            <text class="menu-icon">🧹</text>
            <text>保洁任务</text>
          </view>
          <view class="item" @tap="goTemplate">
            <text class="menu-icon">🎨</text>
            <text>更换模板</text>
          </view>
          <view class="item" @tap="goPage('/pagesA/statics/index')">
            <text class="menu-icon">📊</text>
            <text>数据统计</text>
          </view>
          <view class="item" @tap="goPage('/pagesA/vip/config')">
            <text class="menu-icon" :style="{color: themeColor}">👑</text>
            <text>VIP规则</text>
          </view>
          <view class="item" @tap="goPage('/pagesA/vip/list')">
            <text class="menu-icon">👑</text>
            <text>VIP列表</text>
          </view>
          <view class="item" @tap="goDeviceList">
            <text class="menu-icon">⚙️</text>
            <text>设备管理</text>
          </view>
          <view class="item" @tap="resetQrCode">
            <text class="menu-icon">🔄</text>
            <text>一键重新生成全部小程序码</text>
          </view>
        </view>
      </view>

      <!-- 权限管理 -->
      <view class="control-box">
        <view class="box-title">权限管理</view>
        <view class="control">
          <view class="item" @tap="goAdminPage">
            <text class="menu-icon">👤</text>
            <text>员工管理</text>
          </view>
          <view class="item" @tap="goCleanerPage">
            <text class="menu-icon">🧹</text>
            <text>保洁员管理</text>
          </view>

          <view class="item" @tap="goPage('/pagesA/vip/blacklist')">
            <text class="menu-icon">⛔</text>
            <text>会员黑名单</text>
          </view>
        </view>
      </view>

      <!-- 经营管理 -->
      <view class="control-box">
        <view class="box-title">经营管理</view>
        <view class="control">
          <view class="item" @tap="goPage('/pagesA/discount/set')">
            <text class="menu-icon">💰</text>
            <text>充值规则</text>
          </view>
          <view class="item" @tap="goPackagePage">
            <text class="menu-icon">📦</text>
            <text>套餐管理</text>
          </view>
          <view class="item" @tap="goCouponPage">
            <text class="menu-icon">🎫</text>
            <text>优惠券</text>
          </view>
          <view class="item" @tap="goPage('/pagesA/product/kind-manage')">
            <text class="menu-icon">📋</text>
            <text>商品分类管理</text>
          </view>
          <view class="item" @tap="goPage('/pagesA/product/manage')">
            <text class="menu-icon">🛍️</text>
            <text>商品管理</text>
          </view>
          <view class="item" @tap="goProductOrder">
            <text class="menu-icon">📝</text>
            <text>商品订单</text>
          </view>
          <view class="item" @tap="goPage('/pagesA/order/pay-refund')">
            <text class="menu-icon">💳</text>
            <text>微信支付退款</text>
          </view>
        </view>
      </view>

      <!-- 团购营销 -->
      <view class="control-box">
        <view class="box-title">团购营销</view>
        <view class="control">
          <view class="item" @tap="meituanScope">
            <text class="menu-icon">🏪</text>
            <text>美团授权</text>
          </view>
          <view class="item" @tap="goPage('/pagesA/meituan/cancel')">
            <text class="menu-icon">🏪</text>
            <text>预订退款</text>
          </view>
          <view class="item" @tap="douyinScope">
            <text class="menu-icon">🎵</text>
            <text>抖音授权</text>
          </view>
          <view class="item" @tap="setDouyinId">
            <text class="menu-icon">🎵</text>
            <text>设置抖音ID</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 设置门锁密码弹窗 -->
    <u-popup :show="setLockPwdShow" mode="center" @close="setLockPwdShow = false">
      <view class="dialog">
        <view class="dialog-title">设置门锁密码</view>
        <view class="dialog-content">
          <view class="dialog-item">
            <label>新管理员密码：</label>
            <input v-model="lock_pwd" type="number" maxlength="10" placeholder="请输入4-10位纯数字密码" />
          </view>
        </view>
        <view class="dialog-btns">
          <view class="btn cancel" @tap="setLockPwdShow = false">取消</view>
          <view class="btn confirm" @tap="confirmSetLockPwd">确认</view>
        </view>
      </view>
    </u-popup>

    <!-- 设置抖音ID弹窗 -->
    <u-popup :show="setDYShow" mode="center" @close="setDYShow = false">
      <view class="dialog">
        <view class="dialog-title">设置抖音ID</view>
        <view class="dialog-content">
          <view class="dialog-item">
            <label>抖音门店ID：</label>
            <input v-model="dyId" type="number" maxlength="32" placeholder="请输入抖音门店ID（不是账户ID）" />
          </view>
          <view class="dialog-tips">
            重要提示：抖音授权完成后，必须设置，否则无法核销。在抖音来客APP=》门店管理，查看门店ID。不是账户ID！不是账户ID！不是账户ID！
          </view>
        </view>
        <view class="dialog-btns">
          <view class="btn cancel" @tap="setDYShow = false">取消</view>
          <view class="btn confirm" @tap="confirmSetDYID">确认</view>
        </view>
      </view>
    </u-popup>
  </view>
</template>

<script>
import http from '@/utils/http'
import config from '@/config/index.js'

export default {
  data() {
    return {
      store_id: '',
      storeName: '',
      store_name: '',
      qrCode: '',
      expireTime: '',
      dyId: '',
      simple_model: '',
      template_key: '',
      lock_data: '',
      setLockPwdShow: false,
      setDYShow: false,
      lock_pwd: ''
    }
  },

  onLoad(options) {
    if (options.storeInfo) {
      const storeInfo = JSON.parse(decodeURIComponent(options.storeInfo))
      this.store_id = storeInfo.id
      this.expire_time = storeInfo.expire_time
      this.lock_data = storeInfo.lock_data
    } else if (options.store_id) {
      this.store_id = options.store_id
    } else {
      this.store_id = uni.getStorageSync('global_store_id')
    }
  },

  onShow() {
    this.getData()
  },

  methods: {
    async getData() {
      try {
        const res = await http.get(`/member/store/getDetail/${this.store_id}`)
        if (res.code === 0) {
          this.storeName = res.data.name || ''
          this.store_name = res.data.name || ''
          this.qrCode = res.data.qr_code
          this.dyId = res.data.dy_id
          this.simple_model = res.data.simple_model
          this.template_key = res.data.template_key
          this.expireTime = res.data.expire_time || ''
        }
      } catch (e) {
        // error handled
      }
    },
    previewImage(url) {
      if (url) {
        // 如果是相对路径，拼接完整URL
        const fullUrl = url.startsWith('http') ? url : config.imageBase + url
        uni.previewImage({ urls: [fullUrl], current: fullUrl })
      } else {
        uni.showModal({ content: '暂无小程序码，请先点击"一键重新生成全部小程序码"', showCancel: false })
      }
    },
    goPage(url) {
      uni.navigateTo({ url: `${url}?store_id=${this.store_id}` })
    },
    goTemplate() {
      uni.navigateTo({
        url: `/pagesA/template/set?store_id=${this.store_id}&simple_model=${this.simple_model}&template_key=${this.template_key}`
      })
    },
    goDeviceList() {
      uni.navigateTo({
        url: `/pagesA/device/list?store_id=${this.store_id}&store_name=${encodeURIComponent(this.store_name)}`
      })
    },
    goAdminPage() {
      uni.navigateTo({
        url: `/pagesA/admin/admin?store_id=${this.store_id}&store_name=${encodeURIComponent(this.store_name)}`
      })
    },
    goCleanerPage() {
      uni.navigateTo({
        url: `/pagesA/cleaner/cleaner?store_id=${this.store_id}&store_name=${encodeURIComponent(this.store_name)}`
      })
    },
    goPackagePage() {
      uni.navigateTo({
        url: `/pagesA/package/manage?store_id=${this.store_id}&store_name=${encodeURIComponent(this.store_name)}`
      })
    },
    goCouponPage() {
      uni.navigateTo({
        url: `/pagesA/coupon/set?store_id=${this.store_id}&store_name=${encodeURIComponent(this.store_name)}`
      })
    },
    goProductOrder() {
      uni.navigateTo({
        url: `/pages/product/order?manager=true&store_id=${this.store_id}`
      })
    },
    async meituanScope() {
      try {
        const res = await http.post('/member/store/getGroupPayAuthUrl', {
          store_id: this.store_id, groupPayType: 1
        })
        if (res.code === 0) {
          uni.showModal({
            title: '提示',
            content: '请点击复制按钮,然后打开系统浏览器,并粘贴链接打开! 完成授权流程',
            confirmText: '复制',
            success: (modalRes) => {
              if (modalRes.confirm) {
                uni.setClipboardData({ data: res.data, success: () => { uni.showToast({ title: '已复制到剪贴板！', icon: 'success' }) } })
              }
            }
          })
        } else {
          uni.showModal({ content: res.msg, showCancel: false })
        }
      } catch (e) {
        uni.showToast({ title: e.msg || '获取授权链接失败', icon: 'none' })
      }
    },
    async douyinScope() {
      try {
        const res = await http.post('/member/store/getGroupPayAuthUrl', {
          store_id: this.store_id, groupPayType: 2
        })
        if (res.code === 0) {
          uni.showModal({
            title: '提示',
            content: '请点击复制按钮,然后打开系统浏览器,并粘贴链接打开! 完成授权流程,授权完成后一定要设置抖音门店ID！',
            confirmText: '复制',
            success: (modalRes) => {
              if (modalRes.confirm) {
                uni.setClipboardData({ data: res.data, success: () => { uni.showToast({ title: '已复制到剪贴板！', icon: 'success' }) } })
              }
            }
          })
        } else {
          uni.showModal({ content: res.msg, showCancel: false })
        }
      } catch (e) {
        uni.showToast({ title: e.msg || '获取授权链接失败', icon: 'none' })
      }
    },
    setDouyinId() {
      this.setDYShow = true
    },
    async confirmSetDYID() {
      if (!this.dyId || this.dyId.length === 0) {
        uni.showToast({ title: '未填写门店ID', icon: 'error' })
        return
      }
      try {
        const res = await http.post(`/member/store/setDouyinId?store_id=${this.store_id}&dyId=${this.dyId}`)
        if (res.code === 0) {
          uni.showToast({ title: '操作成功', icon: 'success' })
          this.setDYShow = false
          this.getData()
        } else {
          uni.showModal({ content: res.msg, showCancel: false })
        }
      } catch (e) {
        uni.showToast({ title: e.msg || '设置失败', icon: 'none' })
      }
    },
    resetQrCode() {
      uni.showModal({
        title: '温馨提示',
        content: '将重新生成此门店的二维码以及所有房间的二维码、续费码，时间较长，请耐心等待！您是否确认重置？',
        success: async (res) => {
          if (res.confirm) {
            try {
              uni.showLoading({ title: '操作中...' })
              const result = await http.post(`/member/store/resetQrcode?store_id=${this.store_id}`)
              uni.hideLoading()
              if (result.code === 0) {
                uni.showToast({ title: '操作成功', icon: 'success' })
                this.getData()
              } else {
                uni.showModal({ content: result.msg, showCancel: false })
              }
            } catch (e) {
              uni.hideLoading()
              uni.showToast({ title: e.msg || '操作失败', icon: 'none' })
            }
          }
        }
      })
    },
    confirmSetLockPwd() {
      if (!this.lock_pwd || this.lock_pwd.length < 4) {
        uni.showToast({ title: '密码不合法', icon: 'error' })
        return
      }
      this.setLockPwdShow = false
      this.lock_pwd = ''
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 40rpx;
}
.top {
  background: linear-gradient(135deg, var(--main-color, #6da773fd) 0%, var(--main-color, #5AAB6E) 100%);
  padding: 30rpx;
}
.top-doorInfo {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 16rpx;
  padding: 30rpx;
}
.top-title {
  font-size: 36rpx;
  font-weight: bold;
  color: #333;
  margin-bottom: 20rpx;
  display: flex;
  align-items: center;
  gap: 10rpx;
}
.info {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.store-text {
  font-size: 26rpx;
  color: #666;
}
.info-container {
  padding: 20rpx;
}
.control-box {
  background: #fff;
  border-radius: 16rpx;
  margin-bottom: 20rpx;
  overflow: hidden;
}
.box-title {
  font-size: 32rpx;
  font-weight: bold;
  color: #333;
  padding: 30rpx 30rpx 20rpx;
  border-bottom: 1rpx solid #f0f0f0;
}
.control {
  display: flex;
  flex-wrap: wrap;
  padding: 20rpx 10rpx;
}
.item {
  width: 25%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 20rpx 10rpx;
  .iconfont {
    font-size: 48rpx;
    margin-bottom: 12rpx;
  }
  .menu-icon {
    font-size: 48rpx;
    margin-bottom: 12rpx;
  }
  text {
    font-size: 24rpx;
    color: #333;
    text-align: center;
    line-height: 1.3;
  }
}
.dialog {
  width: 600rpx;
  padding: 40rpx;
}
.dialog-title {
  font-size: 34rpx;
  font-weight: bold;
  text-align: center;
  margin-bottom: 30rpx;
}
.dialog-content {
  margin-bottom: 30rpx;
}
.dialog-item {
  display: flex;
  align-items: center;
  margin-bottom: 20rpx;
  label {
    font-size: 28rpx;
    color: #333;
    white-space: nowrap;
  }
  input {
    flex: 1;
    border: 1rpx solid #ddd;
    border-radius: 8rpx;
    padding: 16rpx 20rpx;
    font-size: 28rpx;
  }
}
.dialog-tips {
  font-size: 24rpx;
  color: #ff6600;
  line-height: 1.5;
  padding: 20rpx;
  background: #fff7e6;
  border-radius: 8rpx;
}
.dialog-btns {
  display: flex;
  gap: 20rpx;
  .btn {
    flex: 1;
    height: 80rpx;
    line-height: 80rpx;
    text-align: center;
    border-radius: 40rpx;
    font-size: 30rpx;
    &.cancel {
      background: #f5f5f5;
      color: #666;
    }
    &.confirm {
      background: var(--main-color, #5AAB6E);
      color: #fff;
    }
  }
}
</style>
