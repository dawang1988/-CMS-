<template>
  <view class="container" :style="themeStyle">
    <view class="form" v-if="!success">
      <view class="field-item cell-link">
        <view class="field-label">所属城市</view>
        <picker mode="region" @change="bindRegionChange" :value="regionArray">
          <view class="field-value">
            <text :class="{ 'has-value': region }">{{ region || '请选择意向开店城市' }}</text>
            <text class="link-arrow">›</text>
          </view>
        </picker>
      </view>
      <view class="field-item">
        <view class="field-label">您的姓名</view>
        <input v-model="name" placeholder="请填写您的姓名" maxlength="5" class="field-input" />
      </view>
      <view class="field-item">
        <view class="field-label">您的电话</view>
        <input v-model="phone" type="number" placeholder="请填写您的电话" maxlength="11" class="field-input" />
      </view>
      <view class="field-item">
        <view class="field-label">详细地址</view>
        <input v-model="address" placeholder="选填，意向开店地址" class="field-input" />
      </view>
      <view class="field-item">
        <view class="field-label">预算金额</view>
        <input v-model="budget" placeholder="选填，单位：万元" class="field-input" />
      </view>
      <view class="field-item">
        <view class="field-label">从业经验</view>
        <textarea v-model="experience" placeholder="选填，请简述您的相关从业经验" class="field-textarea" maxlength="500" />
      </view>
      <view class="field-item">
        <view class="field-label">备注</view>
        <input v-model="remark" placeholder="选填" class="field-input" />
      </view>
      <view class="note" v-if="tele">一个电话解决您的开店疑惑：{{ tele }}</view>
    </view>
    <view class="success" v-if="success">
      <view class="success-icon">✓</view>
      <view class="success-text">提交成功</view>
      <view class="success-info">您的申请已提交，请等待官方人员与您联系!您也可以主动拨打{{ tele }}咨询!</view>
    </view>
    <view class="btns">
      <button class="btn tel" hover-class="btn-hover" @tap="call">电话咨询</button>
      <button v-if="!success" class="btn submit" hover-class="btn-hover" @tap="submit">提交申请</button>
    </view>
  </view>
</template>
<script>
import api from '@/api/index.js'

export default {
  data() {
    return {
      region: '',
      regionArray: [],
      name: '',
      phone: '',
      address: '',
      budget: '',
      experience: '',
      remark: '',
      tele: '',
      success: false
    }
  },

  onShow() {
    this.getInfo()
  },

  methods: {
    // 地区选择回调
    bindRegionChange(e) {
      // e.detail.value 是数组 [省, 市, 区]
      this.regionArray = e.detail.value
      this.region = e.detail.value.join(' ')
    },

    // 提交申请
    async submit() {
      if (!this.phone || !this.name || !this.region) {
        uni.showToast({ title: '请填写完整', icon: 'none' })
        return
      }
      if (!/^1[3-9]\d{9}$/.test(this.phone)) {
        uni.showToast({ title: '请填写正确的手机号', icon: 'none' })
        return
      }
      try {
        const res = await api.post('/member/user/saveFranchiseInfo', {
          name: this.name,
          phone: this.phone,
          city: this.region,
          address: this.address,
          budget: this.budget,
          experience: this.experience,
          remark: this.remark
        })
        if (res.code === 0) {
          this.success = res.data
        }
      } catch (e) {
        console.error('提交加盟申请失败', e)
      }
    },

    // 获取加盟信息
    async getInfo() {
      try {
        const res = await api.get('/member/user/getFranchiseInfo')
        if (res.code === 0) {
          this.tele = res.data.franchise
          this.success = res.data.isCommit
        }
      } catch (e) {
        console.error('获取加盟信息失败', e)
      }
    },

    // 电话咨询
    call() {
      if (this.tele) {
        uni.makePhoneCall({ phoneNumber: this.tele })
      } else {
        uni.showToast({ title: '暂无咨询电话', icon: 'none' })
      }
    }
  }
}
</script>

<style lang="scss" scoped>
page {
  background: #f5f5f5;
}

.container {
  padding: 30rpx;
  min-height: 100vh;
  background: #f5f5f5;
}

.form {
  background: #fff;
  border-radius: 30rpx;
  overflow: hidden;
  padding: 0 30rpx;
}

.field-item {
  padding: 24rpx 0;
  border-bottom: 1rpx solid #f5f5f5;
}

.cell-link {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.field-label {
  font-size: 28rpx;
  color: #333;
  min-width: 140rpx;
}

.field-value {
  display: flex;
  align-items: center;
  flex: 1;
  justify-content: flex-end;
}

.field-value text {
  font-size: 28rpx;
  color: #999;
}

.field-value .has-value {
  color: #333;
}

.link-arrow {
  font-size: 36rpx;
  margin-left: 8rpx;
  color: #ccc;
}

.field-input {
  font-size: 28rpx;
  text-align: right;
  padding: 10rpx 0;
}

.field-textarea {
  font-size: 28rpx;
  width: 100%;
  min-height: 120rpx;
  padding: 10rpx 0;
  text-align: right;
}

.note {
  background: #fdf6ec;
  padding: 0 20rpx;
  line-height: 60rpx;
  font-size: 24rpx;
  color: #999;
  margin: 30rpx 0;
  border-radius: 8rpx;
}

.btns {
  display: flex;
  margin-top: 50rpx;
  justify-content: space-around;
}

.btn {
  width: 300rpx;
  height: 90rpx;
  line-height: 90rpx;
  box-sizing: border-box;
  font-size: 28rpx;
  border-radius: 60rpx;
  text-align: center;
}

.tel {
  border: 1rpx solid var(--main-color, #5AAB6E);
  color: var(--main-color, #5AAB6E);
  background: #fff;
}

.submit {
  background: var(--main-color, #5AAB6E);
  color: #fff;
}

.btn-hover {
  opacity: 0.8;
}

.success {
  width: 600rpx;
  text-align: center;
  margin: 100rpx auto;
}

.success-icon {
  font-size: 150rpx;
  color: var(--main-color, #5AAB6E);
  line-height: 1;
}

.success-text {
  font-size: 40rpx;
  color: #333;
  margin: 10rpx 0 50rpx;
}

.success-info {
  text-align: left;
  font-size: 28rpx;
  color: #666;
  line-height: 1.6;
}
</style>
