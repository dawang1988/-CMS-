<template>
  <view class="page" :style="themeStyle">
    <!-- 添加房间按钮 -->
    <view class="add-btn-wrapper">
      <button class="add-btn" @tap="addRoom">
        <text style="font-size:28rpx;">+</text>
        添加房间
      </button>
    </view>

    <!-- 房间列表 -->
    <view class="room-list">
      <view 
        class="room-item" 
        v-for="(item, index) in doorList" 
        :key="index"
      >
        <view class="room-header" @tap="toggleFold(index)">
          <view class="left">
            <view class="room-name">{{ item.name }}</view>
            <view class="room-type">{{ getRoomType(item.type) }}</view>
          </view>
          <view class="right">
            <view class="status" :class="[item.status === 0 ? 'disabled' : item.status === 1 ? 'free' : item.status === 2 ? 'cleaning' : item.status === 3 ? 'using' : 'booked']">
              {{ getStatusText(item.status) }}
            </view>
            <text style="font-size:20rpx;">{{ foldIndex === index ? '▲' : '▼' }}</text>
          </view>
        </view>

        <view class="room-content" v-if="foldIndex === index">
          <!-- 房间图片 -->
          <view class="room-images" v-if="item.images">
            <image 
              v-for="(img, idx) in getImages(item.images)" 
              :key="idx"
              class="room-img" 
              :src="img" 
              mode="aspectFill"
              @tap="previewImage(img)"
            ></image>
          </view>

          <!-- 房间信息 -->
          <view class="room-info">
            <view class="info-row">
              <text class="label">价格</text>
              <text class="value">￥{{ item.price }}/小时</text>
            </view>
            <view class="info-row" v-if="item.work_price">
              <text class="label">工作日价时</text>
              <text class="value">￥{{ item.work_price }}/小时</text>
            </view>
            <view class="info-row" v-if="item.min_hour">
              <text class="label">最低消时</text>
              <text class="value">{{ item.min_hour }}小时</text>
            </view>
            <view class="info-row" v-if="item.deposit">
              <text class="label">押金</text>
              <text class="value">￥{{ item.deposit }}</text>
            </view>
            <view class="info-row" v-if="item.label">
              <text class="label">标签</text>
              <text class="value">{{ item.label }}</text>
            </view>
            <view class="info-row" v-if="item.lock_no">
              <text class="label">门锁编号</text>
              <text class="value">{{ item.lock_no }}</text>
            </view>
          </view>

          <!-- 操作按钮 -->
          <view class="action-btns">
            <button class="btn edit" @tap="editRoom(item)">
              <text style="font-size:24rpx;">✎</text>
              编辑
            </button>
            <button class="btn status" @tap="toggleStatus(item)">
              <text style="font-size:24rpx;">⚙</text>
              {{ item.status === 0 ? '启用' : '禁用' }}
            </button>
            <button class="btn delete" @tap="deleteRoom(item)">
              <text style="font-size:24rpx;">🗑</text>
              删除
            </button>
          </view>
        </view>
      </view>

      <!-- 空状时-->
      <view class="empty" v-if="doorList.length === 0">
        <image src="/static/logo.png" mode="aspectFit"></image>
        <text>暂无房间</text>
        <button class="btn-add" @tap="addRoom">添加房间</button>
      </view>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http'

export default {
  data() {
    return {
      store_id: 0,
      doorList: [],
      foldIndex: -1
    }
  },

  onLoad(options) {
    this.store_id = Number(options.store_id)
  },

  onShow() {
    this.getDoorList()
  },

  methods: {
    // 获取房间列表
    async getDoorList() {
      try {
        const res = await http.get(`/member/store/getRoomInfoList/${this.store_id}`, {
          store_id: this.store_id
        })
        
        if (res.code === 0) {
          const data = res.data || {}
          this.doorList = Array.isArray(data) ? data : (data.list || [])
        }
      } catch (e) {
      }
    },

    // 切换折叠
    toggleFold(index) {
      this.foldIndex = this.foldIndex === index ? -1 : index
    },

    // 添加房间
    addRoom() {
      uni.navigateTo({
        url: `/pagesA/door/set-info?store_id=${this.store_id}`
      })
    },

    // 编辑房间
    editRoom(item) {
      uni.navigateTo({
        url: `/pagesA/door/set-info?store_id=${this.store_id}&room_id=${item.id}`
      })
    },

    // 切换状态
    toggleStatus(item) {
      uni.showModal({
        title: '提示',
        content: '确定修改房间状态吗?',
        success: async (res) => {
          if (res.confirm) {
            try {
              const result = await http.post(`/member/store/disableRoom/${item.id}`, {
                room_id: item.id
              })
              
              if (result.code === 0) {
                uni.showToast({
                  title: '操作成功',
                  icon: 'success'
                })
                this.getDoorList()
              }
            } catch (e) {
              uni.showToast({
                title: e.msg || '操作失败',
                icon: 'none'
              })
            }
          }
        }
      })
    },

    // 删除房间
    deleteRoom(item) {
      uni.showModal({
        title: '注意提示',
        content: '请确认是否删除该房间！该房间不能存在未完成的订单。',
        success: async (res) => {
          if (res.confirm) {
            try {
              const result = await http.post(`/member/store/deleteRoomInfo/${item.id}`)
              
              if (result.code === 0) {
                uni.showToast({
                  title: '删除成功',
                  icon: 'success'
                })
                this.getDoorList()
              }
            } catch (e) {
              uni.showToast({
                title: e.msg || '删除失败',
                icon: 'none'
              })
            }
          }
        }
      })
    },

    // 预览图片
    previewImage(url) {
      if (url) {
        uni.previewImage({
          urls: [url],
          current: url
        })
      }
    },

    // 获取图片数组
    getImages(images) {
      if (!images) return []
      if (Array.isArray(images)) return images.filter(img => img)
      try {
        const parsed = JSON.parse(images)
        if (Array.isArray(parsed)) return parsed.filter(img => img)
      } catch(e) {}
      return images.split(',').filter(img => img)
    },

    // 获取房间类型
    getRoomType(type) {
      const typeMap = {
        0: '特价房',
        1: '小包',
        2: '中包',
        3: '大包',
        4: '豪包',
        5: '商务房',
        6: '斯诺克',
        7: '中式黑八',
        8: '美式球桌'
      }
      return typeMap[type] || ''
    },

    // 获取状态文本
    getStatusText(status) {
      const statusMap = {
        0: '禁用',
        1: '空闲',
        2: '使用中',
        3: '维护中',
        4: '待清洁'  // 或已预约，需要结合is_cleaning判断
      }
      return statusMap[status] || ''
    },

    // 获取状态样式
    getStatusClass(status) {
      const classMap = {
        0: 'disabled',
        1: 'free',
        2: 'using',
        3: 'disabled',
        4: 'cleaning'  // 或booked
      }
      return classMap[status] || ''
    }
  }
}
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 100rpx;
}

.add-btn-wrapper {
  padding: 20rpx;
}

.add-btn {
  width: 100%;
  height: 88rpx;
  background: var(--main-color, #6da773fd);
  color: #fff;
  border-radius: 10rpx;
  font-size: 28rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10rpx;
}

.room-list {
  padding: 0 20rpx;
}

.room-item {
  background: #fff;
  border-radius: 20rpx;
  margin-bottom: 20rpx;
  overflow: hidden;
}

.room-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 30rpx;
  border-bottom: 1rpx solid #f0f0f0;
}

.room-name {
  font-size: 32rpx;
  font-weight: bold;
  margin-bottom: 8rpx;
}

.room-type {
  font-size: 24rpx;
  color: #999;
}

.right {
  display: flex;
  align-items: center;
  gap: 20rpx;
}

.status {
  padding: 4rpx 12rpx;
  border-radius: 6rpx;
  font-size: 22rpx;
}

.status.free {
  background: #f6ffed;
  color: #52c41a;
}

.status.disabled {
  background: #f5f5f5;
  color: #999;
}

.status.cleaning {
  background: #fff7e6;
  color: #fa8c16;
}

.status.using {
  background: #e6f7ff;
  color: #1890ff;
}

.status.booked {
  background: #fff1f0;
  color: #f5222d;
}

.room-content {
  padding: 30rpx;
}

.room-images {
  display: flex;
  flex-wrap: wrap;
  gap: 20rpx;
  margin-bottom: 30rpx;
}

.room-img {
  width: 200rpx;
  height: 200rpx;
  border-radius: 10rpx;
}

.room-info {
  margin-bottom: 30rpx;
}

.info-row {
  display: flex;
  justify-content: space-between;
  padding: 20rpx 0;
  border-bottom: 1rpx solid #f0f0f0;
}

.info-row:last-child {
  border-bottom: none;
}

.label {
  font-size: 28rpx;
  color: #666;
}

.value {
  font-size: 28rpx;
  color: #333;
  font-weight: bold;
}

.action-btns {
  display: flex;
  gap: 20rpx;
}

.btn {
  flex: 1;
  height: 70rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
  border-radius: 10rpx;
  font-size: 26rpx;
  border: 1rpx solid #ddd;
}

.btn.edit {
  color: #1890ff;
  border-color: #1890ff;
}

.btn.status {
  color: #fa8c16;
  border-color: #fa8c16;
}

.btn.delete {
  color: #f5222d;
  border-color: #f5222d;
}

.empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 200rpx 0;
}

.empty image {
  width: 200rpx;
  height: 200rpx;
  margin-bottom: 20rpx;
}

.empty text {
  font-size: 28rpx;
  color: #999;
  margin-bottom: 40rpx;
}

.btn-add {
  padding: 20rpx 60rpx;
  background: var(--main-color, #6da773fd);
  color: #fff;
  border-radius: 10rpx;
  font-size: 28rpx;
}
</style>
