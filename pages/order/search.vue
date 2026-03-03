<template>
  <view class="container" :style="themeStyle">
    <view class="top-section">
      <view class="content">
        <view class="tip-text">
          找团购订单编号(不是团购券码)：
          <text class="info-text">在美团/大众/抖音APP中点击团购券的详情</text>
        </view>
        <view class="tip-text">
          找支付交易单号(不是商家订单号)：
          <text class="info-text">在微信支付/支付宝账单中点击支付记录详情</text>
        </view>
      </view>
      
      <view class="img">
        <image 
          class="example-img" 
          src="/static/img/dingdanhao.jpg" 
          mode="widthFix" 
          @tap="onPreviewImage"
        />
      </view>
      <view class="preview-tip" @tap="onPreviewImage">操作指引(可点击放大)</view>
      
      <view class="form-section">
        <input 
          class="input-box" 
          placeholder="请输入交易单号/团购订单编号" 
          :value="orderNo" 
          @input="bindInputCode"
        />
        <button class="query-btn" @tap="onQuery">查询</button>
      </view>
    </view>
    
    <view class="bottom-tip">
      <view>提示：通常为20位以上数字，请核对后输入。</view>
      <view>查询团购订单，用团购订单编号，不是券码！</view>
      <view>本页面只支持查询通过扫码机下单的订单！</view>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http.js'

export default {
  data() {
    return {
      previewImgUrl: '/static/img/dingdanhao.jpg',
      order_no: ''
    }
  },

  methods: {
    // 预览图片
    onPreviewImage() {
      uni.previewImage({
        urls: ['/static/img/dingdanhao.jpg']
      })
    },

    // 输入订单号
    bindInputCode(e) {
      if (e.detail.value.length >= 20) {
        this.order_no = e.detail.value
      }
    },

    // 查询订单
    onQuery() {
      if (!this.order_no || this.order_no.length < 20) {
        uni.showToast({ title: '错误的编号长度', icon: 'error' })
        return
      }

      uni.showLoading({ title: '获取中...' })

      http.get(`/member/order/getOrderInfoByNo`, { orderKey: this.order_no }).then(res => {
        uni.hideLoading()
        if (res.code === 0) {
          uni.navigateTo({
            url: `/pages/order/detail?toPage=true&orderKey=${this.order_no}`
          })
        } else {
          uni.showModal({
            title: '温馨提示',
            content: res.msg || '订单不存在',
            showCancel: false
          })
        }
      }).catch(err => {
        uni.hideLoading()
        uni.showModal({
          title: '温馨提示',
          content: err.msg || '查询失败，请稍后重试',
          showCancel: false
        })
      })
    }
  }
}
</script>

<style lang="scss" scoped>
/* 主容器 */
.container {
  min-height: 100vh;
  background-color: #f5f5f5;
  padding: 48rpx 32rpx;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', sans-serif;
  box-sizing: border-box;
}

/* 顶部主要区域 */
.top-section {
  background: #ffffff;
  border-radius: 16rpx;
  margin-bottom: 40rpx;
  border: 2rpx solid #e5e5e5;
  overflow: hidden;
}

/* 内容区域 */
.content {
  padding: 48rpx 40rpx;
}

/* 提示文字容器 */
.tip-text {
  margin-bottom: 34rpx;
  font-size: 34rpx;
  font-weight: bold;
  color: #33a2d6;
  line-height: 1.5;
}

.tip-text:last-child {
  margin-bottom: 0;
}

/* 详细信息文字 */
.info-text {
  display: block;
  font-size: 30rpx;
  font-weight: 400;
  color: #666666;
  margin-top: 8rpx;
  line-height: 1.4;
}

/* 图片区域 - 铺满整行 */
.img {
  padding: 0;
  background: #ffffff;
  display: block;
  width: 100%;
  border-bottom: 2rpx solid #f0f0f0;
}

.example-img {
  width: 100%;
  height: auto;
  display: block;
  border: none;
  border-radius: 0;
}

/* 预览提示 */
.preview-tip {
  text-align: center;
  padding: 20rpx;
  color: #1890ff;
  font-size: 28rpx;
}

/* 表单区域 */
.form-section {
  padding: 48rpx 40rpx;
  background: #ffffff;
}

/* 输入框样式 */
.input-box {
  width: 100%;
  height: 96rpx;
  border: 2rpx solid #d9d9d9;
  border-radius: 12rpx;
  padding: 0 32rpx;
  font-size: 32rpx;
  color: #333333;
  background: #ffffff;
  margin-bottom: 26rpx;
  font-family: inherit;
  box-sizing: border-box;
  transition: border-color 0.2s ease;
}

.input-box:focus {
  outline: none;
  border-color: var(--main-color, #5AAB6E);
}

/* 查询按钮 */
.query-btn {
  width: 100%;
  height: 96rpx;
  background: var(--main-color, #5AAB6E);
  border: none;
  border-radius: 12rpx;
  color: #ffffff;
  font-size: 32rpx;
  font-weight: 500;
  cursor: pointer;
  font-family: inherit;
  box-sizing: border-box;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  transition: background-color 0.2s ease;
  line-height: 96rpx;
}

.query-btn::after {
  border: none;
}

/* 底部提示区域 */
.bottom-tip {
  background: #ffffff;
  border-radius: 12rpx;
  font-size: 28rpx;
  padding: 40rpx;
  color: #d40a0a;
  border: 2rpx solid #e5e5e5;
  line-height: 1.8;
}

.bottom-tip view {
  margin-bottom: 10rpx;
}

.bottom-tip view:last-child {
  margin-bottom: 0;
}

/* 小屏幕适配 */
@media (max-width: 480px) {
  .container {
    padding: 40rpx 24rpx;
  }
  
  .content {
    padding: 40rpx 32rpx;
  }
  
  .tip-text {
    font-size: 30rpx;
  }
  
  .info-text {
    font-size: 26rpx;
  }
  
  .form-section {
    padding: 40rpx 32rpx;
  }
  
  .input-box {
    height: 88rpx;
    font-size: 30rpx;
    padding: 0 28rpx;
  }
  
  .query-btn {
    height: 88rpx;
    font-size: 30rpx;
    line-height: 88rpx;
  }
  
  .bottom-tip {
    padding: 32rpx;
    font-size: 26rpx;
  }
}

/* 超小屏幕适配 */
@media (max-width: 360px) {
  .container {
    padding: 32rpx 16rpx;
  }
  
  .content {
    padding: 36rpx 28rpx;
  }
  
  .tip-text {
    font-size: 28rpx;
  }
  
  .info-text {
    font-size: 24rpx;
  }
  
  .form-section {
    padding: 36rpx 28rpx;
  }
  
  .input-box {
    height: 84rpx;
    font-size: 28rpx;
    padding: 0 24rpx;
  }
  
  .query-btn {
    height: 84rpx;
    font-size: 28rpx;
    line-height: 84rpx;
  }
  
  .bottom-tip {
    padding: 28rpx;
    font-size: 24rpx;
  }
}
</style>
