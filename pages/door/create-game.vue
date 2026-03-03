<template>
  <view class="page" :style="themeStyle">
    <view class="form">
      <view class="section">
        <view class="section-title">房间信息</view>
        <view class="room-card">
          <view class="room-name">{{ roomInfo.room_name || roomInfo.name }}</view>
          <view class="room-price">￥{{ roomInfo.price }}/小时</view>
        </view>
      </view>

      <view class="section">
        <view class="section-title">拼场设置</view>
        <view class="form-item">
          <text class="label">游戏类型</text>
          <view class="input-area">
            <view class="type-tags">
              <view class="type-tag" :class="{ active: gameType === item.value }" 
                v-for="item in gameTypes" :key="item.value" @tap="gameType = item.value">
                {{ item.label }}
              </view>
            </view>
          </view>
        </view>
        <view class="form-item">
          <text class="label">需要人数</text>
          <view class="input-area">
            <view class="num-ctrl">
              <view class="num-btn" @tap="maxPlayers > 2 ? maxPlayers-- : ''">-</view>
              <text class="num-val">{{ maxPlayers }}人</text>
              <view class="num-btn" @tap="maxPlayers < 20 ? maxPlayers++ : ''">+</view>
            </view>
          </view>
        </view>
        <view class="form-item">
          <text class="label">开始时间</text>
          <view class="input-area" @tap="showTimePicker = true">
            <text class="time-val">{{ displayTime }}</text>
            <text class="arrow">›</text>
          </view>
        </view>
        <view class="form-item">
          <text class="label">游戏时长</text>
          <view class="input-area">
            <view class="num-ctrl">
              <view class="num-btn" @tap="hours > 1 ? hours-- : ''">-</view>
              <text class="num-val">{{ hours }}小时</text>
              <view class="num-btn" @tap="hours < 12 ? hours++ : ''">+</view>
            </view>
          </view>
        </view>
</view>
      <view class="section">
        <view class="section-title">备注说明</view>
        <textarea class="remark-input" v-model="remark" placeholder="例如：缺2个人，会打的来" maxlength="200" />
      </view>

      <view class="fee-info">
        <view class="fee-line">
          <text>房间单价</text>
          <text>￥{{ roomInfo.price }}/小时</text>
        </view>
        <view class="fee-line">
          <text>游戏时长</text>
          <text>{{ hours }}小时</text>
        </view>
        <view class="fee-line">
          <text>总费用</text>
          <text class="total-price">￥{{ totalPrice }}</text>
        </view>
        <view class="fee-line">
          <text>人均费用</text>
          <text class="avg-price">≈ ￥{{ avgPrice }}/人</text>
        </view>
      </view>
    </view>

    <view class="bottom-bar">
      <button class="submit-btn" @tap="submitGame">发起组局</button>
    </view>

    <!-- 时间选择 -->
    <u-popup :show="showTimePicker" mode="bottom" round="14" @close="showTimePicker = false">
      <view class="time-picker-popup">
        <view class="picker-header">
          <text class="picker-cancel" @tap="showTimePicker = false">取消</text>
          <text class="picker-title">选择开始时间</text>
          <text class="picker-confirm" @tap="confirmTime">确定</text>
        </view>
        <view class="day-tabs">
          <view class="day-tab" :class="{ active: dayIndex === 0 }" @tap="dayIndex = 0">今天</view>
          <view class="day-tab" :class="{ active: dayIndex === 1 }" @tap="dayIndex = 1">明天</view>
          <view class="day-tab" :class="{ active: dayIndex === 2 }" @tap="dayIndex = 2">后天</view>
        </view>
        <picker-view :value="pickerVal" @change="onPickerChange" style="height: 400rpx;">
          <picker-view-column>
            <view class="pitem" v-for="h in 24" :key="h">{{ (h-1) < 10 ? '0'+(h-1) : (h-1) }}时</view>
          </picker-view-column>
          <picker-view-column>
            <view class="pitem" v-for="m in 60" :key="m">{{ (m-1) < 10 ? '0'+(m-1) : (m-1) }}分</view>
          </picker-view-column>
        </picker-view>
      </view>
    </u-popup>
  </view>
</template>

<script>
import http from '@/utils/http.js'

export default {
  data() {
    return {
      storeId: '',
      roomId: '',
      roomInfo: {},
      gameType: 'mahjong',
      gameTypes: [
        { label: '麻将', value: 'mahjong' },
        { label: '台球', value: 'billiards' },
        { label: '棋牌', value: 'chess' },
        { label: '桌游', value: 'boardgame' },
        { label: '其他', value: 'other' }
      ],
      maxPlayers: 4,
      hours: 2,
      remark: '',
      startTime: null,
      dayIndex: 0,
      pickerVal: [0, 0],
      showTimePicker: false
    }
  },
  computed: {
    displayTime() {
      if (!this.startTime) {
        const now = new Date()
        const h = now.getHours()
        const m = now.getMinutes()
        return `今天 ${h < 10 ? '0'+h : h}:${m < 10 ? '0'+m : m}`
      }
      const d = new Date(this.startTime)
      const today = new Date()
      today.setHours(0,0,0,0)
      const diff = Math.floor((d.getTime() - today.getTime()) / 86400000)
      const dayLabel = diff === 0 ? '今天' : diff === 1 ? '明天' : '后天'
      const h = d.getHours()
      const m = d.getMinutes()
      return `${dayLabel} ${h < 10 ? '0'+h : h}:${m < 10 ? '0'+m : m}`
    },
    totalPrice() {
      return ((this.roomInfo.price || 0) * this.hours).toFixed(0)
    },
    avgPrice() {
      if (this.maxPlayers <= 0) return '0'
      return ((this.roomInfo.price || 0) * this.hours / this.maxPlayers).toFixed(0)
    }
  },
  onLoad(options) {
    this.storeId = options.storeId || ''
    this.roomId = options.roomId || ''
    if (options.roomInfo) {
      try {
        this.roomInfo = JSON.parse(decodeURIComponent(options.roomInfo))
      } catch(e) {}
    }
    // 默认开始时间为当前时间
    this.startTime = new Date()
    const now = new Date()
    this.pickerVal = [now.getHours(), now.getMinutes()]
  },
  methods: {
    onPickerChange(e) {
      this.pickerVal = e.detail.value
    },
    confirmTime() {
      const h = this.pickerVal[0]
      const m = this.pickerVal[1]
      const d = new Date()
      d.setDate(d.getDate() + this.dayIndex)
      d.setHours(h, m, 0, 0)
      if (d.getTime() < Date.now() - 60000) {
        uni.showToast({ title: '不能选择过去的时间', icon: 'none' })
        return
      }
      this.startTime = d
      this.showTimePicker = false
    },
    submitGame() {
      const app = getApp()
      if (!app.globalData.isLogin) {
        uni.navigateTo({ url: '/pages/user/login' })
        return
      }
      if (!this.roomId) {
        uni.showToast({ title: '请选择房间', icon: 'none' })
        return
      }
      const st = this.startTime || new Date()
      const startStr = `${st.getFullYear()}-${(st.getMonth()+1).toString().padStart(2,'0')}-${st.getDate().toString().padStart(2,'0')} ${st.getHours().toString().padStart(2,'0')}:${st.getMinutes().toString().padStart(2,'0')}:00`
      const endDate = new Date(st.getTime() + this.hours * 3600000)
      const endStr = `${endDate.getFullYear()}-${(endDate.getMonth()+1).toString().padStart(2,'0')}-${endDate.getDate().toString().padStart(2,'0')} ${endDate.getHours().toString().padStart(2,'0')}:${endDate.getMinutes().toString().padStart(2,'0')}:00`

      const typeLabel = (this.gameTypes.find(t => t.value === this.gameType) || {}).label || ''

      uni.showLoading({ title: '发布中...' })
      http.post('/member/game/save', {
        store_id: this.storeId,
        room_id: this.roomId,
        title: typeLabel + ' ' + this.maxPlayers + '人局',
        gameType: typeLabel,
        start_time: startStr,
        end_time: endStr,
        maxPlayers: this.maxPlayers,
        remark: this.remark
      }).then(res => {
        uni.hideLoading()
        if (res.code === 0) {
          uni.showToast({ title: '发布成功', icon: 'success' })
          setTimeout(() => {
            uni.navigateBack()
          }, 1000)
        } else {
          uni.showModal({ content: res.msg || '发布失败', showCancel: false })
        }
      }).catch(err => {
        uni.hideLoading()
        uni.showModal({ content: err.msg || '发布失败', showCancel: false })
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.page { min-height: 100vh; background: #f5f5f5; padding-bottom: 140rpx; }
.form { padding: 20rpx; }
.section { background: #fff; border-radius: 16rpx; padding: 30rpx; margin-bottom: 20rpx; }
.section-title { font-size: 30rpx; font-weight: bold; color: #333; margin-bottom: 20rpx; }
.room-card { display: flex; justify-content: space-between; align-items: center; padding: 20rpx; background: #f0f9f2; border-radius: 12rpx; }
.room-name { font-size: 32rpx; font-weight: bold; color: #333; }
.room-price { font-size: 28rpx; color: var(--main-color, #5AAB6E); font-weight: bold; }
.form-item { display: flex; align-items: center; justify-content: space-between; padding: 24rpx 0; border-bottom: 1rpx solid #f5f5f5; }
.form-item:last-child { border-bottom: none; }
.label { font-size: 28rpx; color: #666; min-width: 160rpx; }
.input-area { flex: 1; display: flex; justify-content: flex-end; align-items: center; }
.type-tags { display: flex; flex-wrap: wrap; gap: 16rpx; justify-content: flex-end; }
.type-tag { padding: 12rpx 24rpx; border-radius: 30rpx; font-size: 26rpx; background: #f5f5f5; color: #666; }
.type-tag.active { background: var(--main-color, #5AAB6E); color: #fff; }
.num-ctrl { display: flex; align-items: center; gap: 20rpx; }
.num-btn { width: 56rpx; height: 56rpx; border-radius: 50%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; font-size: 36rpx; color: #333; }
.num-val { font-size: 30rpx; font-weight: bold; color: #333; min-width: 80rpx; text-align: center; }
.time-val { font-size: 28rpx; color: #333; }
.arrow { font-size: 32rpx; color: #999; margin-left: 10rpx; }
.remark-input { width: 100%; height: 160rpx; font-size: 28rpx; padding: 20rpx; background: #f9f9f9; border-radius: 12rpx; box-sizing: border-box; }
.fee-info { background: #fff; border-radius: 16rpx; padding: 30rpx; margin: 0 20rpx; }
.fee-line { display: flex; justify-content: space-between; padding: 16rpx 0; font-size: 28rpx; color: #666; }
.total-price { color: #ee0a24; font-weight: bold; font-size: 32rpx; }
.avg-price { color: var(--main-color, #5AAB6E); font-weight: bold; }
.bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; padding: 20rpx 40rpx; padding-bottom: calc(20rpx + env(safe-area-inset-bottom)); background: #fff; box-shadow: 0 -2rpx 12rpx rgba(0,0,0,0.08); }
.submit-btn { background: var(--main-color, #5AAB6E); color: #fff; border-radius: 40rpx; height: 88rpx; line-height: 88rpx; font-size: 32rpx; font-weight: bold; border: none; }
.time-picker-popup { background: #fff; }
.picker-header { display: flex; justify-content: space-between; align-items: center; padding: 24rpx 30rpx; border-bottom: 1rpx solid #f0f0f0; }
.picker-cancel { color: #999; font-size: 28rpx; }
.picker-title { font-size: 30rpx; font-weight: bold; color: #333; }
.picker-confirm { color: var(--main-color, #5AAB6E); font-size: 28rpx; font-weight: bold; }
.day-tabs { display: flex; justify-content: center; gap: 40rpx; padding: 20rpx; }
.day-tab { padding: 12rpx 30rpx; border-radius: 30rpx; font-size: 26rpx; color: #666; }
.day-tab.active { background: var(--main-color, #5AAB6E); color: #fff; }
.pitem { display: flex; align-items: center; justify-content: center; font-size: 34rpx; }
</style>
