<template>
  <u-popup :show="show" mode="center" @close="handleClose" :closeable="true" :round="28">
    <view class="notice-popup">
      <view class="notice-header">📢 门店公告</view>
      <view class="notice-content">
        <rich-text :nodes="noticeHtml"></rich-text>
      </view>
      <view class="notice-footer">
        <view class="notice-footer-btn" @tap="handleClose">我知道了</view>
      </view>
    </view>
  </u-popup>
</template>

<script>
export default {
  name: 'NoticePopup',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    notice: {
      type: String,
      default: ''
    },
    storeId: {
      type: [String, Number],
      default: ''
    },
    // 控制弹窗频率：once-per-day, always, manual
    frequency: {
      type: String,
      default: 'once-per-day'
    }
  },
  computed: {
    noticeHtml() {
      return this.processNoticeHtml(this.notice)
    }
  },
  watch: {
    show(newVal) {
      if (newVal && this.frequency === 'once-per-day') {
        this.markAsShown()
      }
    }
  },
  methods: {
    // 统一处理公告HTML（图片路径和样式）
    processNoticeHtml(html) {
      if (!html) return ''
      
      const imageBase = require('@/config/index.js').default.imageBase
      let processedHtml = html
      
      // 处理相对路径图片
      processedHtml = processedHtml.replace(/src="\/storage/g, `src="${imageBase}/storage`)
      processedHtml = processedHtml.replace(/src='\/storage/g, `src='${imageBase}/storage`)
      
      // 统一图片样式
      processedHtml = processedHtml.replace(/<img([^>]*)>/gi, (match, attrs) => {
        // 移除原有style
        attrs = attrs.replace(/style\s*=\s*["'][^"']*["']/gi, '')
        // 添加统一样式
        return `<img${attrs} style="max-width:100%;width:100%;height:auto;display:block;margin:10px 0;border-radius:8px;">`
      })
      
      return processedHtml
    },
    
    // 检查今天是否已显示
    shouldShow() {
      if (this.frequency === 'always') return true
      if (this.frequency === 'manual') return false
      
      // once-per-day
      const noticeKey = `notice_shown_${this.storeId}`
      const today = new Date().toISOString().slice(0, 10)
      return uni.getStorageSync(noticeKey) !== today
    },
    
    // 标记为已显示
    markAsShown() {
      if (this.frequency === 'once-per-day' && this.storeId) {
        const noticeKey = `notice_shown_${this.storeId}`
        const today = new Date().toISOString().slice(0, 10)
        uni.setStorageSync(noticeKey, today)
      }
    },
    
    handleClose() {
      this.$emit('close')
    }
  }
}
</script>

<style lang="scss" scoped>
.notice-popup {
  width: 600rpx;
  max-height: 80vh;
  background: #fff;
  border-radius: 28rpx;
  overflow: hidden;
}

.notice-header {
  padding: 30rpx;
  text-align: center;
  font-size: 32rpx;
  font-weight: bold;
  color: #333;
  border-bottom: 1rpx solid #f0f0f0;
}

.notice-content {
  padding: 30rpx;
  max-height: 60vh;
  overflow-y: auto;
  font-size: 28rpx;
  line-height: 1.6;
  color: #666;
}

.notice-footer {
  padding: 20rpx 30rpx 30rpx;
  display: flex;
  justify-content: center;
}

.notice-footer-btn {
  width: 100%;
  height: 80rpx;
  line-height: 80rpx;
  text-align: center;
  background: linear-gradient(135deg, #5AAB6E 0%, #6da773 100%);
  color: #fff;
  font-size: 30rpx;
  border-radius: 40rpx;
}
</style>
