<template>
  <view class="review-page" :style="themeStyle">
    <!-- 订单信息 -->
    <view class="order-info">
      <text class="store-name">{{ storeName }}</text>
      <text class="room-name">{{ roomName }}</text>
    </view>

    <!-- 评分 -->
    <view class="section">
      <text class="section-title">整体评分</text>
      <view class="stars">
        <view
          v-for="i in 5"
          :key="i"
          class="star"
          :class="{ active: score >= i }"
          @tap="setScore(i)"
        >★</view>
      </view>
      <text class="score-text">{{ ['', '很差', '较差', '一般', '满意', '非常满意'][score] }}</text>
    </view>

    <!-- 标签选择 -->
    <view class="section">
      <text class="section-title">选择标签</text>
      <view class="tags">
        <view
          v-for="(tag, index) in tagOptions"
          :key="index"
          class="tag"
          :class="{ active: selectedTags.includes(tag) }"
          @tap="toggleTag(tag)"
        >{{ tag }}</view>
      </view>
    </view>

    <!-- 评价内容 -->
    <view class="section">
      <text class="section-title">评价内容</text>
      <textarea
        class="content-input"
        v-model="content"
        placeholder="分享您的消费体验，帮助其他用户做出选择~"
        maxlength="500"
        :auto-height="false"
      />
      <text class="word-count">{{ content.length }}/500</text>
    </view>

    <!-- 图片上传 -->
    <view class="section">
      <text class="section-title">上传图片（最多6张）</text>
      <view class="image-list">
        <view v-for="(img, index) in images" :key="index" class="image-item">
          <image :src="img" mode="aspectFill" @tap="previewImage(index)" />
          <view class="delete-btn" @tap="removeImage(index)">×</view>
        </view>
        <view class="image-add" v-if="images.length < 6" @tap="chooseImage">
          <text class="add-icon">+</text>
          <text class="add-text">添加图片</text>
        </view>
      </view>
    </view>

    <!-- 提交按钮 -->
    <view class="submit-bar">
      <button class="submit-btn" :disabled="submitting" @tap="submitReview">
        {{ submitting ? '提交中...' : '提交评价' }}
      </button>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http.js'

export default {
  data() {
    return {
      orderId: '',
      storeName: '',
      roomName: '',
      score: 5,
      content: '',
      images: [],
      selectedTags: [],
      submitting: false,
      tagOptions: ['环境好', '设备新', '服务好', '性价比高', '位置方便', '干净整洁', '隔音好', '空调给力']
    }
  },

  onLoad(options) {
    this.orderId = options.order_id || ''
    this.storeName = decodeURIComponent(options.store_name || '')
    this.roomName = decodeURIComponent(options.room_name || '')
  },

  methods: {
    setScore(val) {
      this.score = val
    },

    toggleTag(tag) {
      const idx = this.selectedTags.indexOf(tag)
      if (idx > -1) {
        this.selectedTags.splice(idx, 1)
      } else {
        this.selectedTags.push(tag)
      }
    },

    chooseImage() {
      const remaining = 6 - this.images.length
      uni.chooseImage({
        count: remaining,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: (res) => {
          res.tempFilePaths.forEach(path => {
            this.uploadImage(path)
          })
        }
      })
    },

    uploadImage(filePath) {
      uni.showLoading({ title: '上传中...' })
      http.upload(filePath).then(data => {
        uni.hideLoading()
        console.log('上传返回数据:', data)
        const url = typeof data === 'string' ? data : (data.url || data.path || data)
        console.log('图片URL:', url)
        this.images.push(url)
      }).catch((err) => {
        uni.hideLoading()
        console.log('上传失败:', err)
        uni.showToast({ title: '图片上传失败', icon: 'none' })
      })
    },

    removeImage(index) {
      this.images.splice(index, 1)
    },

    previewImage(index) {
      uni.previewImage({
        current: index,
        urls: this.images
      })
    },

    submitReview() {
      if (!this.orderId) {
        return uni.showToast({ title: '订单信息异常', icon: 'none' })
      }
      if (this.score < 1) {
        return uni.showToast({ title: '请选择评分', icon: 'none' })
      }

      this.submitting = true
      http.post('/member/review/save', {
        order_id: this.orderId,
        score: this.score,
        content: this.content,
        images: JSON.stringify(this.images),
        tags: JSON.stringify(this.selectedTags)
      }).then(res => {
        this.submitting = false
        uni.showToast({ title: '评价成功', icon: 'success' })
        setTimeout(() => {
          uni.navigateBack()
        }, 1500)
      }).catch(err => {
        this.submitting = false
        uni.showToast({ title: err.msg || '提交失败', icon: 'none' })
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.review-page {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 140rpx;
}

.order-info {
  background: #fff;
  padding: 30rpx;
  display: flex;
  justify-content: space-between;
  align-items: center;
  .store-name {
    font-size: 32rpx;
    font-weight: 600;
    color: #333;
  }
  .room-name {
    font-size: 28rpx;
    color: #666;
  }
}

.section {
  background: #fff;
  margin-top: 20rpx;
  padding: 30rpx;
  .section-title {
    font-size: 30rpx;
    font-weight: 600;
    color: #333;
    display: block;
    margin-bottom: 20rpx;
  }
}

.stars {
  display: flex;
  align-items: center;
  .star {
    font-size: 56rpx;
    color: #ddd;
    margin-right: 16rpx;
    transition: color 0.2s;
    &.active {
      color: #FFB800;
    }
  }
}

.score-text {
  font-size: 26rpx;
  color: #FFB800;
  margin-top: 10rpx;
  display: block;
}

.tags {
  display: flex;
  flex-wrap: wrap;
  gap: 16rpx;
  .tag {
    padding: 12rpx 28rpx;
    border-radius: 30rpx;
    font-size: 26rpx;
    color: #666;
    background: #f5f5f5;
    border: 2rpx solid #eee;
    &.active {
      color: var(--main-color, #5AAB6E);
      background: rgba(90, 171, 110, 0.1);
      border-color: var(--main-color, #5AAB6E);
    }
  }
}

.content-input {
  width: 100%;
  height: 200rpx;
  background: #f9f9f9;
  border-radius: 12rpx;
  padding: 20rpx;
  font-size: 28rpx;
  box-sizing: border-box;
}

.word-count {
  display: block;
  text-align: right;
  font-size: 24rpx;
  color: #999;
  margin-top: 10rpx;
}

.image-list {
  display: flex;
  flex-wrap: wrap;
  gap: 16rpx;
  .image-item {
    width: 200rpx;
    height: 200rpx;
    border-radius: 12rpx;
    overflow: hidden;
    position: relative;
    image {
      width: 100%;
      height: 100%;
    }
    .delete-btn {
      position: absolute;
      top: 0;
      right: 0;
      width: 44rpx;
      height: 44rpx;
      background: rgba(0, 0, 0, 0.5);
      color: #fff;
      font-size: 32rpx;
      text-align: center;
      line-height: 44rpx;
      border-radius: 0 0 0 12rpx;
    }
  }
  .image-add {
    width: 200rpx;
    height: 200rpx;
    border-radius: 12rpx;
    border: 2rpx dashed #ccc;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    .add-icon {
      font-size: 56rpx;
      color: #ccc;
      line-height: 60rpx;
    }
    .add-text {
      font-size: 24rpx;
      color: #999;
      margin-top: 8rpx;
    }
  }
}

.submit-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 20rpx 30rpx;
  padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
  background: #fff;
  box-shadow: 0 -2rpx 10rpx rgba(0, 0, 0, 0.05);
  .submit-btn {
    width: 100%;
    height: 88rpx;
    line-height: 88rpx;
    background: var(--main-color, #5AAB6E);
    color: #fff;
    font-size: 32rpx;
    font-weight: 600;
    border-radius: 44rpx;
    text-align: center;
    &[disabled] {
      opacity: 0.6;
    }
  }
}
</style>
