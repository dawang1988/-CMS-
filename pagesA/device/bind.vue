<template>
  <view class="page" :style="themeStyle">
    <view class="header">
      <view class="title">设备绑定</view>
      <view class="desc">扫描设备上的二维码进行绑定</view>
    </view>

    <!-- 扫码按钮 -->
    <view class="scan-box" @tap="scanQR">
      <image class="scan-icon" src="/static/icon/scan.png" mode="aspectFit" />
      <text>扫描设备二维码</text>
    </view>

    <!-- 手动输入 -->
    <view class="manual-box">
      <view class="label">或手动输入设备编号</view>
      <input class="input" v-model="deviceNo" placeholder="请输入设备编号" />
      <button class="btn" @tap="queryDevice">查询设备</button>
    </view>

    <!-- 设备信息 -->
    <view class="device-info" v-if="deviceInfo">
      <view class="info-title">设备信息</view>
      <view class="info-row">
        <text class="label">设备编号：</text>
        <text>{{ deviceInfo.device_no }}</text>
      </view>
      <view class="info-row">
        <text class="label">设备类型：</text>
        <text>{{ deviceTypeMap[deviceInfo.device_type] || deviceInfo.device_type }}</text>
      </view>
      <view class="info-row" v-if="deviceInfo.is_bound">
        <text class="label">绑定状态：</text>
        <text class="bound">已绑定</text>
      </view>
      <view class="info-row" v-if="deviceInfo.store_name">
        <text class="label">所属门店：</text>
        <text>{{ deviceInfo.store_name }}</text>
      </view>
      <view class="info-row" v-if="deviceInfo.room_name">
        <text class="label">所属房间：</text>
        <text>{{ deviceInfo.room_name }}</text>
      </view>

      <!-- 绑定表单 -->
      <view class="bind-form" v-if="deviceInfo.can_bind">
        <view class="form-title">绑定到</view>
        <picker mode="selector" :range="storeList" range-key="name" @change="onStoreChange">
          <view class="picker">
            <text>门店：</text>
            <text class="value">{{ selectedStore ? selectedStore.name : '请选择门店' }}</text>
          </view>
        </picker>
        <picker mode="selector" :range="roomList" range-key="name" @change="onRoomChange" v-if="selectedStore">
          <view class="picker">
            <text>房间：</text>
            <text class="value">{{ selectedRoom ? selectedRoom.name : '请选择房间（可选）' }}</text>
          </view>
        </picker>
        <button class="bind-btn" @tap="bindDevice" :disabled="!selectedStore">确认绑定</button>
      </view>

      <!-- 已绑定操作 -->
      <view class="bound-actions" v-if="deviceInfo.is_bound && deviceInfo.can_bind">
        <button class="unbind-btn" @tap="unbindDevice">解除绑定</button>
      </view>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http'

export default {
  data() {
    return {
      deviceNo: '',
      deviceInfo: null,
      storeList: [],
      roomList: [],
      selectedStore: null,
      selectedRoom: null,
      deviceTypeMap: {
        'gateway': '房间网关',
        'lock': '智能门锁',
        'door': '门禁设备',
        'kt': '空调控制器',
        'light': '灯光控制器',
        'mahjong': '麻将机控制器'
      }
    }
  },
  onLoad() {
    this.loadStores()
  },
  methods: {
    // 扫码
    scanQR() {
      uni.scanCode({
        onlyFromCamera: false,
        success: (res) => {
          this.deviceNo = res.result
          this.queryDevice()
        }
      })
    },
    // 查询设备
    async queryDevice() {
      if (!this.deviceNo) {
        uni.showToast({ title: '请输入设备编号', icon: 'none' })
        return
      }
      uni.showLoading({ title: '查询中...' })
      try {
        const res = await http.get('/member/device/scan', { device_no: this.deviceNo })
        uni.hideLoading()
        if (res.code === 0) {
          this.deviceInfo = res.data
        } else {
          uni.showToast({ title: res.msg || '设备不存在', icon: 'none' })
        }
      } catch (e) {
        uni.hideLoading()
        uni.showToast({ title: '查询失败', icon: 'none' })
      }
    },
    // 加载门店列表
    async loadStores() {
      try {
        const res = await http.post('/member/store/getPageList', { pageNo: 1, pageSize: 100 })
        if (res.code === 0) {
          this.storeList = res.data.list || res.data || []
        }
      } catch (e) {}
    },
    // 选择门店
    onStoreChange(e) {
      const idx = e.detail.value
      this.selectedStore = this.storeList[idx]
      this.selectedRoom = null
      this.loadRooms()
    },
    // 加载房间列表
    async loadRooms() {
      if (!this.selectedStore) return
      try {
        const res = await http.get(`/member/store/getRoomList/${this.selectedStore.id}`)
        if (res.code === 0) {
          this.roomList = res.data || []
        }
      } catch (e) {}
    },
    // 选择房间
    onRoomChange(e) {
      const idx = e.detail.value
      this.selectedRoom = this.roomList[idx]
    },
    // 绑定设备
    async bindDevice() {
      if (!this.selectedStore) {
        uni.showToast({ title: '请选择门店', icon: 'none' })
        return
      }
      uni.showLoading({ title: '绑定中...' })
      try {
        const res = await http.post('/member/device/bind', {
          device_no: this.deviceNo,
          store_id: this.selectedStore.id,
          room_id: this.selectedRoom ? this.selectedRoom.id : 0
        })
        uni.hideLoading()
        if (res.code === 0) {
          uni.showToast({ title: '绑定成功', icon: 'success' })
          this.queryDevice() // 刷新状态
        } else {
          uni.showToast({ title: res.msg || '绑定失败', icon: 'none' })
        }
      } catch (e) {
        uni.hideLoading()
        uni.showToast({ title: '绑定失败', icon: 'none' })
      }
    },
    // 解绑设备
    unbindDevice() {
      uni.showModal({
        title: '确认解绑',
        content: '解绑后设备将无法控制，确定要解绑吗？',
        success: async (res) => {
          if (res.confirm) {
            uni.showLoading({ title: '解绑中...' })
            try {
              const res = await http.post('/member/device/unbind', { device_no: this.deviceNo })
              uni.hideLoading()
              if (res.code === 0) {
                uni.showToast({ title: '解绑成功', icon: 'success' })
                this.deviceInfo = null
              } else {
                uni.showToast({ title: res.msg || '解绑失败', icon: 'none' })
              }
            } catch (e) {
              uni.hideLoading()
              uni.showToast({ title: '解绑失败', icon: 'none' })
            }
          }
        }
      })
    }
  }
}
</script>

<style scoped>
.page {
  min-height: 100vh;
  background: #f5f5f5;
  padding: 30rpx;
}

.header {
  text-align: center;
  padding: 40rpx 0;
}

.header .title {
  font-size: 40rpx;
  font-weight: bold;
  color: #333;
}

.header .desc {
  font-size: 28rpx;
  color: #999;
  margin-top: 10rpx;
}

.scan-box {
  background: var(--main-color, #5AAB6E);
  border-radius: 20rpx;
  padding: 60rpx 40rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  color: #fff;
  font-size: 32rpx;
}

.scan-box .scan-icon {
  width: 120rpx;
  height: 120rpx;
  margin-bottom: 20rpx;
}

.manual-box {
  background: #fff;
  border-radius: 20rpx;
  padding: 30rpx;
  margin-top: 30rpx;
}

.manual-box .label {
  font-size: 28rpx;
  color: #666;
  margin-bottom: 20rpx;
}

.manual-box .input {
  border: 1px solid #ddd;
  border-radius: 10rpx;
  padding: 20rpx;
  font-size: 28rpx;
}

.manual-box .btn {
  background: var(--main-color, #5AAB6E);
  color: #fff;
  border-radius: 10rpx;
  margin-top: 20rpx;
  font-size: 28rpx;
}

.device-info {
  background: #fff;
  border-radius: 20rpx;
  padding: 30rpx;
  margin-top: 30rpx;
}

.device-info .info-title {
  font-size: 32rpx;
  font-weight: bold;
  color: #333;
  margin-bottom: 20rpx;
  padding-bottom: 20rpx;
  border-bottom: 1px solid #eee;
}

.device-info .info-row {
  display: flex;
  padding: 15rpx 0;
  font-size: 28rpx;
}

.device-info .info-row .label {
  color: #666;
  width: 180rpx;
}

.device-info .info-row .bound {
  color: var(--main-color, #5AAB6E);
}

.bind-form {
  margin-top: 30rpx;
  padding-top: 30rpx;
  border-top: 1px solid #eee;
}

.bind-form .form-title {
  font-size: 30rpx;
  font-weight: bold;
  color: #333;
  margin-bottom: 20rpx;
}

.bind-form .picker {
  display: flex;
  justify-content: space-between;
  padding: 25rpx 20rpx;
  background: #f9f9f9;
  border-radius: 10rpx;
  margin-bottom: 20rpx;
  font-size: 28rpx;
}

.bind-form .picker .value {
  color: var(--main-color, #5AAB6E);
}

.bind-form .bind-btn {
  background: var(--main-color, #5AAB6E);
  color: #fff;
  border-radius: 10rpx;
  margin-top: 20rpx;
  font-size: 30rpx;
}

.bind-form .bind-btn[disabled] {
  background: #ccc;
}

.bound-actions {
  margin-top: 30rpx;
  padding-top: 30rpx;
  border-top: 1px solid #eee;
}

.bound-actions .unbind-btn {
  background: #ff6b6b;
  color: #fff;
  border-radius: 10rpx;
  font-size: 28rpx;
}
</style>
