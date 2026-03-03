<template>
  <view class="page" :style="themeStyle">
    <view class="form-box">
      <!-- 反馈类型 -->
      <view class="form-item">
        <view class="label">反馈类型</view>
        <view class="type-list">
          <view
            v-for="(item, index) in typeList"
            :key="index"
            class="type-tag"
            :class="{ active: form.type === item.value }"
            @tap="form.type = item.value"
          >{{ item.label }}</view>
        </view>
      </view>

      <!-- 反馈内容 -->
      <view class="form-item">
        <view class="label">反馈内容 <text class="required">*</text></view>
        <textarea
          class="textarea"
          v-model="form.content"
          placeholder="请详细描述您的问题或建议..."
          maxlength="500"
          :auto-height="false"
        />
        <view class="word-count">{{ form.content.length }}/500</view>
      </view>

      <!-- 图片上传 -->
      <view class="form-item">
        <view class="label">上传图片（选填，最多3张）</view>
        <view class="image-list">
          <view v-for="(img, idx) in imageList" :key="idx" class="img-wrap">
            <image :src="img" mode="aspectFill" @tap="previewImage(idx)" />
            <view class="del-btn" @tap="removeImage(idx)">×</view>
          </view>
          <view class="img-add" v-if="imageList.length < 3" @tap="chooseImage">
            <text class="plus">+</text>
            <text class="add-text">添加图片</text>
          </view>
        </view>
      </view>

      <!-- 联系方式 -->
      <view class="form-item">
        <view class="label">联系方式（选填）</view>
        <input class="input" v-model="form.contact" placeholder="手机号或微信号，方便我们联系您" />
      </view>
    </view>

    <button class="submit-btn" :disabled="submitting" @tap="submit">
      {{ submitting ? '提交中...' : '提交反馈' }}
    </button>
  </view>
</template>

<script>
import http from '@/utils/http'

export default {
  data() {
    return {
      form: {
        type: 1,
        content: '',
        contact: ''
      },
      typeList: [
        { label: '功能建议', value: 1 },
        { label: 'Bug反馈', value: 2 },
        { label: '投诉', value: 3 },
        { label: '其他', value: 4 }
      ],
      imageList: [],
      submitting: false
    }
  },
  methods: {
    chooseImage() {
      const count = 3 - this.imageList.length
      uni.chooseImage({
        count,
        sizeType: ['compressed'],
        success: (res) => {
          res.tempFilePaths.forEach(path => {
            if (this.imageList.length < 3) {
              this.uploadImage(path)
            }
          })
        }
      })
    },
    uploadImage(filePath) {
      uni.showLoading({ title: '上传中...' })
      http.upload(filePath).then(data => {
        uni.hideLoading()
        const url = data.url || data
        this.imageList.push(url)
      }).catch(() => {
        uni.hideLoading()
        uni.showToast({ title: '图片上传失败', icon: 'none' })
      })
    },
    removeImage(idx) {
      this.imageList.splice(idx, 1)
    },
    previewImage(idx) {
      uni.previewImage({
        current: idx,
        urls: this.imageList
      })
    },
    submit() {
      if (!this.form.content.trim()) {
        uni.showToast({ title: '请输入反馈内容', icon: 'none' })
        return
      }
      this.submitting = true
      http.post('/system/feedback/create', {
        type: this.form.type,
        content: this.form.content,
        images: this.imageList,
        contact: this.form.contact
      }).then(res => {
        this.submitting = false
        if (res.code === 0) {
          uni.showToast({ title: '提交成功，感谢反馈', icon: 'success' })
          setTimeout(() => uni.navigateBack(), 1500)
        } else {
          uni.showToast({ title: res.msg || '提交失败', icon: 'none' })
        }
      }).catch(() => {
        this.submitting = false
        uni.showToast({ title: '提交失败，请重试', icon: 'none' })
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background: #f6f6f6;
  padding: 24rpx;
}
.form-box {
  background: #fff;
  border-radius: 18rpx;
  padding: 32rpx;
}
.form-item {
  margin-bottom: 32rpx;
}
.label {
  font-size: 28rpx;
  color: #333;
  font-weight: 500;
  margin-bottom: 16rpx;
}
.required {
  color: #ff4757;
}
.type-list {
  display: flex;
  flex-wrap: wrap;
  gap: 16rpx;
}
.type-tag {
  padding: 12rpx 32rpx;
  border-radius: 32rpx;
  font-size: 26rpx;
  color: #666;
  background: #f5f5f5;
  border: 2rpx solid #eee;
}
.type-tag.active {
  color: #fff;
  background: var(--main-color, #5AAB6E);
  border-color: var(--main-color, #5AAB6E);
}
.textarea {
  width: 100%;
  height: 240rpx;
  background: #f9f9f9;
  border-radius: 12rpx;
  padding: 20rpx;
  font-size: 28rpx;
  box-sizing: border-box;
}
.word-count {
  text-align: right;
  font-size: 22rpx;
  color: #999;
  margin-top: 8rpx;
}
.image-list {
  display: flex;
  flex-wrap: wrap;
  gap: 16rpx;
}
.img-wrap {
  position: relative;
  width: 180rpx;
  height: 180rpx;
  border-radius: 12rpx;
  overflow: hidden;
}
.img-wrap image {
  width: 100%;
  height: 100%;
}
.del-btn {
  position: absolute;
  top: 0;
  right: 0;
  width: 40rpx;
  height: 40rpx;
  background: rgba(0,0,0,0.5);
  color: #fff;
  font-size: 28rpx;
  text-align: center;
  line-height: 40rpx;
  border-radius: 0 0 0 12rpx;
}
.img-add {
  width: 180rpx;
  height: 180rpx;
  background: #f5f5f5;
  border: 2rpx dashed #ccc;
  border-radius: 12rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
.plus {
  font-size: 48rpx;
  color: #ccc;
}
.add-text {
  font-size: 22rpx;
  color: #999;
  margin-top: 4rpx;
}
.input {
  width: 100%;
  height: 80rpx;
  background: #f9f9f9;
  border-radius: 12rpx;
  padding: 0 20rpx;
  font-size: 28rpx;
  box-sizing: border-box;
}
.submit-btn {
  margin-top: 40rpx;
  background: var(--main-color, #5AAB6E);
  color: #fff;
  border-radius: 44rpx;
  height: 88rpx;
  line-height: 88rpx;
  font-size: 32rpx;
  font-weight: 500;
  border: none;
}
.submit-btn[disabled] {
  opacity: 0.6;
}
</style>
