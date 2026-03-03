<template>
  <view class="page" :style="themeStyle">
    <!-- 房间类型切换 -->
    <view class="mode-tabs">
      <view 
        class="tab-item" 
        :class="{ active: room_class === 0 }" 
        @click="changeMode(0)"
      >
        棋牌
      </view>
      <view 
        class="tab-item" 
        :class="{ active: room_class === 1 }" 
        @click="changeMode(1)"
      >
        台球
      </view>
      <view 
        class="tab-item" 
        :class="{ active: room_class === 2 }" 
        @click="changeMode(2)"
      >
        KTV
      </view>
    </view>

    <!-- 提示信息 -->
    <view class="tips-box">
      <view class="tip-item">设备出厂已设置预设音频，可以留空</view>
      <view class="tip-item">禁止播放违反法律/伦理的非法文本</view>
      <view class="tip-item">不需要播放的，请设置为"不播放"三个字</view>
    </view>

    <!-- 表单 -->
    <view class="form">
        <view class="form-item">
          <view class="label">欢迎语：</view>
          <textarea v-model="formData.welcomeText" placeholder="最大150个字" maxlength="150" auto-height class="form-textarea" />
          <view class="note">注:订单开始1分钟时播报</view>
        </view>

        <view class="form-item">
          <view class="label">结束前30分钟</view>
          <textarea v-model="formData.endText30" placeholder="最大150个字" maxlength="150" auto-height class="form-textarea" />
        </view>

        <view class="form-item">
          <view class="label">结束前5分钟</view>
          <textarea v-model="formData.endText5" placeholder="最大150个字" maxlength="150" auto-height class="form-textarea" />
        </view>

        <view class="form-item">
          <view class="label">订单结束时</view>
          <textarea v-model="formData.endText" placeholder="最大150个字" maxlength="150" auto-height class="form-textarea" />
        </view>

        <view class="form-item">
          <view class="label">深夜提醒</view>
          <textarea v-model="formData.nightText" placeholder="最大150个字" maxlength="150" auto-height class="form-textarea" />
          <view class="note">注:凌晨第一个整点播报</view>
        </view>

        <view class="form-item">
          <view class="label">自定义提醒</view>
          <textarea v-model="formData.customizeText" placeholder="最大150个字" maxlength="150" auto-height class="form-textarea" />
          <view class="note">注:可以用来临时提示客户等，在房间控制菜单下触发</view>
        </view>
    </view>

    <!-- 底部按钮 -->
    <view class="footer-btns">
      <view class="footer-btn" @click="cancel">取消</view>
      <view class="footer-btn primary" @click="submit">保存</view>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http.js'

export default {
  data() {
    return {
      store_id: '',
      room_class: 0,
      formData: {
        welcomeText: '',
        endText30: '',
        endText5: '',
        endText: '',
        nightText: '',
        customizeText: ''
      }
    }
  },
  onLoad(options) {
    if (options.store_id) {
      this.store_id = options.store_id
      this.getData()
    }
  },
  methods: {
    // 获取语音设置
    async getData() {
      try {
        const res = await http.get(`/member/store/getStoreSoundInfo/${this.store_id}`, {
          room_class: this.room_class
        })
        this.formData = {
          welcomeText: res.welcomeText || '',
          endText30: res.endText30 || '',
          endText5: res.endText5 || '',
          endText: res.endText || '',
          nightText: res.nightText || '',
          customizeText: res.customizeText || ''
        }
      } catch (e) {
      }
    },

    // 切换房间类型
    changeMode(index) {
      this.room_class = index
      this.getData()
    },

    // 取消
    cancel() {
      uni.navigateBack()
    },

    // 保存
    async submit() {
      try {
        await http.post('/member/store/saveStoreSoundInfo', {
          store_id: this.store_id,
          room_class: this.room_class,
          ...this.formData
        })
        uni.showToast({
          title: '设置成功',
          icon: 'success'
        })
      } catch (e) {
        uni.showToast({
          title: e.msg || '保存失败',
          icon: 'none'
        })
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 120rpx;
}

.mode-tabs {
  display: flex;
  background: #fff;
  padding: 20rpx;
  margin-bottom: 20rpx;

  .tab-item {
    flex: 1;
    text-align: center;
    padding: 20rpx 0;
    font-size: 28rpx;
    color: #666;
    border-radius: 8rpx;
    transition: all 0.3s;

    &.active {
      background: $u-primary;
      color: #fff;
    }
  }
}

.tips-box {
  background: #fff;
  padding: 24rpx 32rpx;
  margin-bottom: 20rpx;

  .tip-item {
    font-size: 26rpx;
    color: #ff6b00;
    line-height: 1.8;
  }
}

.form {
  background: #fff;
}

.form-item {
  padding: 24rpx 32rpx;
  border-bottom: 1rpx solid #f5f5f5;

  &:last-child {
    border-bottom: none;
  }

  .label {
    font-size: 28rpx;
    color: #333;
    margin-bottom: 16rpx;
  }

  .note {
    font-size: 24rpx;
    color: #999;
    margin-top: 12rpx;
  }
}

.form-textarea {
  width: 100%;
  min-height: 120rpx;
  padding: 16rpx;
  font-size: 28rpx;
  border: 1rpx solid #e5e5e5;
  border-radius: 8rpx;
  box-sizing: border-box;
}

.footer-btns {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  gap: 20rpx;
  padding: 20rpx;
  background: #fff;
  box-shadow: 0 -2rpx 10rpx rgba(0, 0, 0, 0.05);
  z-index: 100;
}

.footer-btn {
  flex: 1;
  text-align: center;
  padding: 20rpx;
  border-radius: 10rpx;
  font-size: 28rpx;
  background: #f5f5f5;
  color: #666;
}

.footer-btn.primary {
  background: var(--main-color, #5AAB6E);
  color: #fff;
}
</style>
