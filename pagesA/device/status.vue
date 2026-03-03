<template>
  <view class="page" :style="themeStyle">
    <!-- 门店选择 -->
    <view class="store-selector" @click="showStorePicker = true">
      <text class="store-name">{{ selectedStoreName || '选择门店' }}</text>
      <text class="arrow">▼</text>
    </view>

    <!-- 设备统计 -->
    <view class="stats-bar" v-if="devices.length > 0">
      <view class="stat-item">
        <text class="stat-num">{{ devices.length }}</text>
        <text class="stat-label">总设备</text>
      </view>
      <view class="stat-item">
        <text class="stat-num online">{{ onlineCount }}</text>
        <text class="stat-label">在线</text>
      </view>
      <view class="stat-item">
        <text class="stat-num offline">{{ offlineCount }}</text>
        <text class="stat-label">离线</text>
      </view>
    </view>

    <!-- 设备列表（按房间分组） -->
    <view class="room-group" v-for="group in groupedDevices" :key="group.room_name">
      <view class="room-header">
        <text class="room-name">{{ group.room_name }}</text>
        <text class="room-count">{{ group.devices.length }}个设备</text>
      </view>
      <view class="device-card" v-for="device in group.devices" :key="device.id">
        <view class="device-row">
          <view class="device-left">
            <text class="device-type">{{ getDeviceTypeName(device.device_type) }}</text>
            <text class="device-no">{{ device.device_no }}</text>
          </view>
          <view class="device-right">
            <text class="status-dot" :class="device.online_status ? 'on' : 'off'">
              {{ device.online_status ? '● 在线' : '● 离线' }}
            </text>
          </view>
        </view>
        <view class="device-meta">
          <text class="meta-item" v-if="device.battery_level !== null">
            🔋 {{ device.battery_level }}%
          </text>
          <text class="meta-item" v-if="device.signal_strength !== null">
            📶 {{ device.signal_strength }}dBm
          </text>
          <text class="meta-item" v-if="device.firmware_version">
            v{{ device.firmware_version }}
          </text>
          <text class="meta-item" v-if="device.last_heartbeat">
            {{ formatTime(device.last_heartbeat) }}
          </text>
        </view>
      </view>
    </view>

    <!-- 空状态 -->
    <view class="empty" v-if="selectedStoreId && devices.length === 0">
      <text>该门店暂无绑定设备</text>
      <view class="add-btn" @click="goBindDevice">去绑定设备</view>
    </view>

    <!-- 门店选择弹窗 -->
    <u-popup :show="showStorePicker" mode="bottom" @close="showStorePicker = false">
      <view class="picker-box">
        <view class="picker-header">
          <text @click="showStorePicker = false">取消</text>
          <text class="picker-title">选择门店</text>
          <text class="picker-confirm" @click="showStorePicker = false">确定</text>
        </view>
        <scroll-view scroll-y class="picker-list">
          <view class="picker-item" :class="{ active: selectedStoreId === item.id }"
            v-for="item in storeList" :key="item.id" @click="onStoreSelect(item)">
            {{ item.name }}
          </view>
        </scroll-view>
      </view>
    </u-popup>
  </view>
</template>

<script>
import http from '@/utils/http.js'

export default {
  data() {
    return {
      storeList: [],
      devices: [],
      selectedStoreId: 0,
      selectedStoreName: '',
      showStorePicker: false,
      refreshTimer: null,
    }
  },
  computed: {
    onlineCount() {
      return this.devices.filter(d => d.online_status).length
    },
    offlineCount() {
      return this.devices.filter(d => !d.online_status).length
    },
    groupedDevices() {
      const groups = {}
      this.devices.forEach(d => {
        const roomName = d.room_name || '公共设备'
        if (!groups[roomName]) {
          groups[roomName] = { room_name: roomName, devices: [] }
        }
        groups[roomName].devices.push(d)
      })
      return Object.values(groups)
    }
  },
  onLoad(options) {
    if (options.store_id) {
      this.selectedStoreId = parseInt(options.store_id)
      this.selectedStoreName = options.store_name || ''
      this.loadDevices()
    }
    this.getStoreList()
  },
  onShow() {
    // 每30秒自动刷新
    if (this.selectedStoreId) {
      this.refreshTimer = setInterval(() => this.loadDevices(), 30000)
    }
  },
  onHide() {
    if (this.refreshTimer) {
      clearInterval(this.refreshTimer)
      this.refreshTimer = null
    }
  },
  onUnload() {
    if (this.refreshTimer) {
      clearInterval(this.refreshTimer)
    }
  },
  onPullDownRefresh() {
    this.loadDevices()
    setTimeout(() => uni.stopPullDownRefresh(), 500)
  },
  methods: {
    getDeviceTypeName(type) {
      const types = {
        'lock': '智能锁', 'door': '磁力锁门禁', 'kt': '空调控制器',
        'light': '灯具', 'ball_locker': '锁球器', 'ai_ball_locker': 'AI锁球器',
        'socket': '插座', 'gateway': '网关', 'speaker': '云喇叭',
        'camera': '摄像头', 'ir': '红外控制器',
        'qr': '二维码识别器', 'timer': '计时器', 'controller_3way': '三路控制器',
      }
      return types[type] || type || '未知设备'
    },
    formatTime(time) {
      if (!time) return ''
      const d = new Date(time)
      const now = new Date()
      const diff = Math.floor((now - d) / 1000)
      if (diff < 60) return '刚刚'
      if (diff < 3600) return Math.floor(diff / 60) + '分钟前'
      if (diff < 86400) return Math.floor(diff / 3600) + '小时前'
      return Math.floor(diff / 86400) + '天前'
    },
    async getStoreList() {
      try {
        const res = await http.get('/member/store/getStoreListByAdmin')
        this.storeList = res || []
        // 如果只有一个门店，自动选中
        if (this.storeList.length === 1 && !this.selectedStoreId) {
          this.onStoreSelect(this.storeList[0])
        }
      } catch (e) {}
    },
    onStoreSelect(store) {
      this.selectedStoreId = store.id
      this.selectedStoreName = store.name
      this.showStorePicker = false
      this.loadDevices()
      // 重启定时刷新
      if (this.refreshTimer) clearInterval(this.refreshTimer)
      this.refreshTimer = setInterval(() => this.loadDevices(), 30000)
    },
    async loadDevices() {
      if (!this.selectedStoreId) return
      try {
        const res = await http.get('/member/device/storeDevices', {
          store_id: this.selectedStoreId
        })
        this.devices = res || []
      } catch (e) {
        // 静默
      }
    },
    goBindDevice() {
      uni.navigateTo({ url: '/pagesA/device/bind' })
    }
  }
}
</script>

<style lang="scss" scoped>
.page { min-height: 100vh; background: #f5f5f5; }

.store-selector {
  display: flex; justify-content: center; align-items: center; gap: 10rpx;
  background: #fff; padding: 24rpx; margin-bottom: 2rpx;
}
.store-name { font-size: 32rpx; font-weight: bold; color: #333; }
.arrow { font-size: 22rpx; color: #999; }

.stats-bar {
  display: flex; background: #fff; padding: 24rpx 0; margin-bottom: 20rpx;
}
.stat-item { flex: 1; text-align: center; }
.stat-num { display: block; font-size: 40rpx; font-weight: bold; color: #333; }
.stat-num.online { color: #52c41a; }
.stat-num.offline { color: #ff4d4f; }
.stat-label { font-size: 24rpx; color: #999; }

.room-group { margin-bottom: 20rpx; }
.room-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 16rpx 28rpx; background: #e8f5e9;
}
.room-name { font-size: 28rpx; font-weight: bold; color: #333; }
.room-count { font-size: 24rpx; color: #999; }

.device-card { background: #fff; padding: 20rpx 28rpx; border-bottom: 1rpx solid #f5f5f5; }
.device-row { display: flex; justify-content: space-between; align-items: center; }
.device-type { font-size: 28rpx; color: #333; font-weight: 500; }
.device-no { font-size: 22rpx; color: #999; margin-left: 12rpx; }
.status-dot { font-size: 24rpx; }
.status-dot.on { color: #52c41a; }
.status-dot.off { color: #ff4d4f; }

.device-meta { display: flex; gap: 20rpx; margin-top: 10rpx; flex-wrap: wrap; }
.meta-item { font-size: 22rpx; color: #999; }

.empty { text-align: center; padding: 120rpx 0; color: #999; font-size: 28rpx; }
.add-btn {
  display: inline-block; margin-top: 20rpx; padding: 16rpx 40rpx;
  background: var(--main-color, #5AAB6E); color: #fff; border-radius: 10rpx; font-size: 28rpx;
}

.picker-box { background: #fff; border-radius: 20rpx 20rpx 0 0; }
.picker-header { display: flex; justify-content: space-between; align-items: center; padding: 24rpx 32rpx; border-bottom: 1rpx solid #f5f5f5; font-size: 28rpx; color: #999; }
.picker-title { font-size: 30rpx; color: #333; font-weight: bold; }
.picker-confirm { color: var(--main-color, #5AAB6E); }
.picker-list { max-height: 500rpx; }
.picker-item { padding: 24rpx 32rpx; font-size: 28rpx; color: #333; border-bottom: 1rpx solid #f5f5f5; }
.picker-item.active { color: var(--main-color, #5AAB6E); font-weight: bold; }
</style>
