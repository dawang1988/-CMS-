<template>
  <view class="container" :style="themeStyle">
    <view class="form">
      <view class="form-item">
        <view class="label">昵称</view>
        <input 
          class="input" 
          v-model="name" 
          placeholder="请输入昵称" 
          maxlength="20"
        />
      </view>
    </view>

    <view class="submit-btn">
      <button class="btn" @tap="submit">保存</button>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http'

export default {
  data() {
    return {
      name: ''
    }
  },

  onLoad(options) {
    this.name = options.name || ''
  },

  methods: {
    async submit() {
      if (!this.name) {
        uni.showToast({
          title: '请填写昵称',
          icon: 'none'
        })
        return
      }

      try {
        const res = await http.request({
          url: '/member/user/updateNickname',
          method: 'POST',
          data: {
            nickname: this.name
          }
        })

        if (res.code === 0) {
          uni.showToast({
            title: '修改成功',
            icon: 'success'
          })
          setTimeout(() => {
            uni.navigateBack()
          }, 500)
        }
      } catch (e) {
        uni.showToast({
          title: '修改失败，请重试',
          icon: 'none'
        })
      }
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

.form {
  background: #fff;
  border-radius: 16rpx;
  padding: 0 30rpx;
}

.form-item {
  padding: 30rpx 0;
  border-bottom: 1rpx solid #f5f5f5;

  .label {
    font-size: 28rpx;
    color: #333;
    margin-bottom: 20rpx;
  }

  .input {
    font-size: 28rpx;
    color: #333;
  }
}

.submit-btn {
  margin-top: 40rpx;

  .btn {
    width: 100%;
    height: 88rpx;
    line-height: 88rpx;
    background: var(--main-color, #5AAB6E);
    color: #fff;
    border-radius: 16rpx;
    font-size: 28rpx;
  }
}
</style>
