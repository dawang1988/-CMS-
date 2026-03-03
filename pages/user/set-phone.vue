<template>
  <view class="container" :style="themeStyle">
    <view class="form">
      <view class="form-item">
        <view class="label">手机号</view>
        <input 
          class="input" 
          v-model="phone" 
          type="number"
          placeholder="请输入手机号" 
          maxlength="11"
        />
      </view>
      <view class="form-item">
        <view class="label">验证码</view>
        <view class="code-row">
          <input 
            class="input code-input" 
            v-model="code" 
            type="number"
            placeholder="请输入验证码" 
            maxlength="6"
          />
          <view class="code-btn" :class="{ disabled: countdown > 0 }" @tap="sendCode">
            {{ countdown > 0 ? countdown + 's后重发' : '获取验证码' }}
          </view>
        </view>
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
      phone: '',
      code: '',
      countdown: 0,
      timer: null
    }
  },

  onLoad(options) {
    this.phone = options.phone || ''
  },

  onUnload() {
    if (this.timer) clearInterval(this.timer)
  },

  methods: {
    checkPhone(phone) {
      return /^1[3-9]\d{9}$/.test(phone)
    },

    async sendCode() {
      if (this.countdown > 0) return

      if (!this.phone || !this.checkPhone(this.phone)) {
        uni.showToast({ title: '请填写正确的手机号', icon: 'none' })
        return
      }

      try {
        const res = await http.request({
          url: '/member/user/send-code',
          method: 'POST',
          data: { mobile: this.phone }
        })

        if (res.code === 0) {
          uni.showToast({ title: '验证码已发送', icon: 'success' })
          this.countdown = 60
          this.timer = setInterval(() => {
            this.countdown--
            if (this.countdown <= 0) {
              clearInterval(this.timer)
              this.timer = null
            }
          }, 1000)
        }
      } catch (e) {
        uni.showToast({ title: e.msg || '发送失败', icon: 'none' })
      }
    },

    async submit() {
      if (!this.phone) {
        uni.showToast({ title: '请填写手机号', icon: 'none' })
        return
      }
      if (!this.checkPhone(this.phone)) {
        uni.showToast({ title: '请填写正确的手机号', icon: 'none' })
        return
      }
      if (!this.code) {
        uni.showToast({ title: '请填写验证码', icon: 'none' })
        return
      }

      try {
        const res = await http.request({
          url: '/member/user/update-mobile',
          method: 'POST',
          data: {
            mobile: this.phone,
            code: this.code
          }
        })

        if (res.code === 0) {
          uni.showToast({ title: '修改成功', icon: 'success' })
          setTimeout(() => { uni.navigateBack() }, 500)
        }
      } catch (e) {
        uni.showToast({ title: e.msg || '修改失败', icon: 'none' })
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
.code-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.code-input {
  flex: 1;
}
.code-btn {
  flex-shrink: 0;
  font-size: 26rpx;
  color: var(--main-color, #5AAB6E);
  padding: 10rpx 20rpx;
  border: 1rpx solid var(--main-color, #5AAB6E);
  border-radius: 8rpx;
  margin-left: 20rpx;
}
.code-btn.disabled {
  color: #999;
  border-color: #ddd;
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