<template>
  <view class="container" :style="themeStyle">
    <view class="info-list">
      <!-- 头像 -->
      <view class="info-item" @tap="chooseAvatar">
        <view class="label">头像</view>
        <view class="value">
          <image class="avatar" :src="displayAvatar" mode="aspectFill" />
          <text class="arrow">›</text>
        </view>
      </view>

      <!-- 昵称 -->
      <view class="info-item" @tap="setUserName">
        <view class="label">昵称</view>
        <view class="value">
          <text>{{ userinfo.nickname || '未设置' }}</text>
          <text class="arrow">›</text>
        </view>
      </view>

      <!-- 手机号 -->
      <view class="info-item" @tap="setPhone">
        <view class="label">手机号</view>
        <view class="value">
          <text>{{ userinfo.phone || '未绑定' }}</text>
          <text class="arrow">›</text>
        </view>
      </view>
    </view>

    <!-- 退出登录 -->
    <view class="logout-btn">
      <button class="btn" @tap="exitLogin">退出登录</button>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http'
import config from '@/config/index.js'

export default {
  data() {
    return {
      userinfo: {}
    }
  },

  computed: {
    displayAvatar() {
      const avatar = this.userinfo.avatar
      if (avatar && avatar.length > 5) {
        if (avatar.startsWith('http')) return avatar
        return config.imageBase + avatar
      }
      return '/static/logo.png'
    }
  },

  onShow() {
    this.getuserinfo()
  },

  methods: {
    // 获取用户信息
    async getuserinfo() {
      try {
        const res = await http.request({
          url: '/member/user/get',
          method: 'GET'
        })

        if (res.code === 0) {
          this.userinfo = res.data
        }
      } catch (e) {
      }
    },

    // 选择头像
    chooseAvatar() {
      uni.chooseImage({
        count: 1,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: (res) => {
          const tempFilePath = res.tempFilePaths[0]
          this.uploadAvatar(tempFilePath)
        }
      })
    },

    // 上传头像
    uploadAvatar(filePath) {
      uni.showLoading({ title: '上传中...' })
      
      http.upload(filePath).then(data => {
        uni.hideLoading()
        const url = data.url || data
        this.updateAvatar(url)
      }).catch(() => {
        uni.hideLoading()
        uni.showToast({ title: '上传失败', icon: 'none' })
      })
    },

    // 更新头像
    async updateAvatar(avatarUrl) {
      try {
        const res = await http.request({
          url: '/member/user/updateAvatar',
          method: 'POST',
          data: {
            avatar: avatarUrl
          }
        })

        if (res.code === 0) {
          uni.showToast({
            title: '修改成功',
            icon: 'success'
          })
          this.getuserinfo()
        }
      } catch (e) {
      }
    },

    // 修改昵称
    setUserName() {
      const name = this.userinfo.nickname || ''
      uni.navigateTo({
        url: `/pages/user/set-name?name=${name}`
      })
    },

    // 修改手机号
    setPhone() {
      const phone = this.userinfo.phone || ''
      uni.navigateTo({
        url: `/pages/user/set-phone?phone=${phone}`
      })
    },

    // 退出登录
    exitLogin() {
      uni.showModal({
        title: '提示',
        content: '确定要退出登录吗？',
        success: async (res) => {
          if (res.confirm) {
            try {
              await http.request({
                url: '/member/auth/logout',
                method: 'POST'
              })

              uni.showToast({
                title: '已退出',
                icon: 'success'
              })

              // 清除登录信息
              uni.removeStorageSync('token')
              uni.removeStorageSync('userDatatoken')
              const app = getApp()
              app.globalData.isLogin = false
              app.globalData.userDatatoken = {}

              setTimeout(() => {
                uni.navigateBack()
              }, 500)
            } catch (e) {
            }
          }
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
  padding: 20rpx;
}

.info-list {
  background: #fff;
  border-radius: 16rpx;
  overflow: hidden;
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 30rpx;
  border-bottom: 1rpx solid #f5f5f5;

  &:last-child {
    border-bottom: none;
  }

  .label {
    font-size: 28rpx;
    color: #333;
  }

  .value {
    display: flex;
    align-items: center;
    gap: 20rpx;

    text {
      font-size: 28rpx;
      color: #666;
    }
  }

  .avatar {
    width: 80rpx;
    height: 80rpx;
    border-radius: 50%;
  }
}

.arrow {
  font-size: 36rpx;
  color: #999;
}

.logout-btn {
  margin-top: 40rpx;

  .btn {
    width: 100%;
    height: 88rpx;
    line-height: 88rpx;
    background: #fff;
    border-radius: 16rpx;
    font-size: 28rpx;
    color: #ff4d4f;
  }
}
</style>
