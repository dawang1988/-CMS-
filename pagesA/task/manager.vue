<template>
  <view class="page" :style="themeStyle">
    <!-- 筛选栏：只保留状态筛选，门店固定 -->
    <view class="tabs">
      <picker :value="statusIndex" :range="statusOptions" range-key="text" @change="statusChange">
        <view class="picker-item">
          {{ statusOptions[statusIndex].text }}
          <text class="arrow">▼</text>
        </view>
      </picker>
      <view class="picker-item store-label">
        <text>{{ storeName || '当前门店' }}</text>
      </view>
    </view>
    
    <view class="container">
      <!-- 手动创建按钮 -->
      <view class="create-btn" @tap="showCreateDialog">
        <text>+ 手动创建保洁任务</text>
      </view>
      
      <!-- 列表 -->
      <view class="lists" v-if="list.length">
        <view class="item" v-for="(item, index) in list" :key="index">
          <view 
            class="tag"
            :class="item.statusClass"
          >
            {{ item.statusText }}
          </view>
          <view class="info">
            <label>订单编号：</label>
            <text>{{ item.order_no }}</text>
          </view>
          <view class="info">
            <label>门店名称：</label>
            <text>{{ item.store_name }}</text>
          </view>
          <view class="info">
            <label>房间名称：</label>
            <text>{{ item.room_name }}</text>
          </view>
          <view class="info">
            <label>接单用户：</label>
            <text>{{ item.userName || '未指派' }}</text>
          </view>
          <view class="info">
            <label>预计时间：</label>
            <text v-if="item.order_end_time">{{ item.order_end_time }}</text>
            <text v-else>-</text>
          </view>
          <view class="info" v-if="item.remark">
            <label>备注：</label>
            <text>{{ item.remark }}</text>
          </view>
          
          <!-- 操作按钮区域 -->
          <view class="btns">
            <block v-if="item.status === 0">
              <button class="btn assign" @click="showAssignDialog(item)">指派</button>
              <button class="btn accept" @click="managerJiedan(item.clearId || item.id)">管理员接单</button>
              <button class="btn warning" @click="cancelOrder(item.clearId || item.id)">取消</button>
            </block>
            <block v-if="item.status === 1">
              <button class="btn start" @click="managerStart(item.clearId || item.id)">开始清洁</button>
              <button class="btn warning" @click="cancelOrder(item.clearId || item.id)">取消</button>
              <button class="btn primary" @click="goTaskDetail(item.clearId || item.id)">详情</button>
            </block>
            <block v-if="item.status === 2">
              <button class="btn finish" @click="managerFinish(item.clearId || item.id)">完成清洁</button>
              <button class="btn warning" @click="cancelOrder(item.clearId || item.id)">取消</button>
              <button class="btn primary" @click="goTaskDetail(item.clearId || item.id)">详情</button>
            </block>
            <block v-if="item.status === 3">
              <button class="btn settle" @click="showSettleDialog(item)">结算</button>
              <button class="btn primary" @click="goTaskDetail(item.clearId || item.id)">详情</button>
            </block>
            <block v-if="item.status === 4 || item.status === 5 || item.status === 6">
              <button class="btn primary" @click="goTaskDetail(item.clearId || item.id)">详情</button>
            </block>
          </view>
        </view>
      </view>
      
      <view class="note-more" v-if="list.length && canLoadMore">下拉加载更多...</view>
      <view class="nodata-list" v-if="!list.length">暂无任务...</view>
    </view>
    
    <!-- 指派弹窗 -->
    <view class="dialog-mask" v-if="assignVisible" @tap="closeAssign">
      <view class="dialog-box" @tap.stop="noop">
        <view class="dialog-title">指派保洁员</view>
        <view class="dialog-body">
          <view class="dialog-info">任务：{{ assignTask.room_name }} ({{ assignTask.store_name }})</view>
          <view v-if="cleanerList.length === 0" class="dialog-empty">
            暂无保洁员，请先在保洁员管理中添加
          </view>
          <view v-else class="cleaner-list">
            <view 
              class="cleaner-item" 
              v-for="(c, ci) in cleanerList" 
              :key="ci"
              :class="{ selected: selectedCleaner === c.id }"
              @tap="selectCleaner(c.id)"
            >
              <text class="cleaner-name">{{ c.name || c.nickname || '未设置昵称' }}</text>
              <text class="cleaner-mobile">{{ c.phone || '' }}</text>
              <text class="check" v-if="selectedCleaner === c.id">✓</text>
            </view>
          </view>
        </view>
        <view class="dialog-footer">
          <view class="dialog-btn cancel" @tap="closeAssign">取消</view>
          <view class="dialog-btn confirm" @tap="submitAssign">确定指派</view>
        </view>
      </view>
    </view>
    
    <!-- 结算弹窗 -->
    <view class="dialog-mask" v-if="settleVisible" @tap="closeSettle">
      <view class="dialog-box" @tap.stop="noop">
        <view class="dialog-title">任务结算</view>
        <view class="dialog-body">
          <view class="dialog-info">任务：{{ settleTask.room_name }} ({{ settleTask.store_name }})</view>
          <view class="dialog-info">接单人：{{ settleTask.userName || '-' }}</view>
          <view class="dialog-line">
            <text class="dialog-label">结算金额：</text>
            <input class="dialog-input" v-model="settleAmount" type="digit" placeholder="请输入金额" />
            <text class="dialog-unit">元</text>
          </view>
        </view>
        <view class="dialog-footer">
          <view class="dialog-btn cancel" @tap="closeSettle">取消</view>
          <view class="dialog-btn confirm" @tap="submitSettle">确定结算</view>
        </view>
      </view>
    </view>
    
    <!-- 手动创建弹窗：门店固定，只选房间 -->
    <view class="dialog-mask" v-if="createVisible" @tap="closeCreate">
      <view class="dialog-box" @tap.stop="noop">
        <view class="dialog-title">手动创建保洁任务</view>
        <view class="dialog-body">
          <view class="dialog-line">
            <text class="dialog-label">门店：</text>
            <text class="dialog-value">{{ storeName }}</text>
          </view>
          <view class="dialog-line">
            <text class="dialog-label">房间：</text>
            <picker :value="createRoomIndex" :range="createRooms" range-key="text" @change="createRoomChange">
              <view class="dialog-picker">
                {{ createRooms[createRoomIndex] ? createRooms[createRoomIndex].text : '请选择房间' }}
                <text class="arrow">▼</text>
              </view>
            </picker>
          </view>
          <view class="dialog-line">
            <text class="dialog-label">备注：</text>
            <input class="dialog-input" v-model="createRemark" placeholder="可选" />
          </view>
        </view>
        <view class="dialog-footer">
          <view class="dialog-btn cancel" @tap="closeCreate">取消</view>
          <view class="dialog-btn confirm" @tap="submitCreate">确定创建</view>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http.js'

export default {
  data() {
    return {
      statusOptions: [
        { text: '全部状态', value: '' },
        { text: '待接单', value: 0 },
        { text: '已接单', value: 1 },
        { text: '已开始', value: 2 },
        { text: '已完成', value: 3 },
        { text: '已取消', value: 4 },
        { text: '被驳回', value: 5 },
        { text: '已结算', value: 6 }
      ],
      statusIndex: 0,
      status: '',
      store_id: '',
      storeName: '',
      pageNo: 1,
      pageSize: 10,
      canLoadMore: true,
      list: [],
      start_time: '',
      end_time: '',
      assignVisible: false,
      assignTask: {},
      cleanerList: [],
      selectedCleaner: '',
      settleVisible: false,
      settleTask: {},
      settleAmount: '',
      createVisible: false,
      createRooms: [{ text: '请选择房间', value: '' }],
      createRoomIndex: 0,
      createRemark: ''
    }
  },
  onLoad: function(options) {
    if (options.status) {
      this.status = Number(options.status)
      for (var i = 0; i < this.statusOptions.length; i++) {
        if (this.statusOptions[i].value === this.status) {
          this.statusIndex = i
          break
        }
      }
    }
    if (options.start_time) this.start_time = options.start_time
    if (options.end_time) this.end_time = options.end_time
    // 门店ID必须传入
    if (options.store_id) {
      this.store_id = options.store_id
    }
    this.getStoreName()
  },
  onShow: function() {
    this.getListData('refresh')
  },
  onPullDownRefresh: function() {
    this.pageNo = 1
    this.canLoadMore = true
    this.list = []
    this.getListData('refresh')
    uni.stopPullDownRefresh()
  },
  onReachBottom: function() {
    if (this.canLoadMore) {
      this.getListData('')
    } else {
      uni.showToast({ title: '我是有底线的...', icon: 'none' })
    }
  },
  methods: {
    // 获取门店名称
    getStoreName: function() {
      var that = this
      if (!that.store_id) return
      http.get('/member/store/getStoreListByAdmin').then(function(res) {
        if (res.code === 0 && res.data) {
          var list = res.data.list || res.data
          if (Array.isArray(list)) {
            for (var i = 0; i < list.length; i++) {
              var it = list[i]
              var id = String(it.value || it.id || '')
              if (id === String(that.store_id)) {
                that.storeName = it.key || it.name || it.store_name || ''
                break
              }
            }
          }
        }
      })
    },
    statusChange: function(e) {
      this.statusIndex = Number(e.detail.value)
      this.status = this.statusOptions[this.statusIndex].value
      this.getListData('refresh')
    },
    getListData: function(type) {
      var that = this
      if (type === 'refresh') {
        uni.showLoading({ title: '加载中...' })
        that.pageNo = 1
        that.list = []
      }
      http.post('/member/manager/getClearManagerPage', {
        pageNo: that.pageNo,
        pageSize: that.pageSize,
        store_id: that.store_id,
        user_id: '',
        status: that.status,
        start_time: that.start_time,
        end_time: that.end_time
      }).then(function(res) {
        uni.hideLoading()
        if (res.code === 0) {
          if (res.data.list.length === 0) {
            that.canLoadMore = false
          } else {
            var classMap = { 0: 'red', 1: 'blue2', 2: 'blue2', 3: 'green', 4: 'gray', 5: 'yellow', 6: 'blue' }
            var textMap = { 0: '待接单', 1: '已接单', 2: '已开始', 3: '已完成', 4: '已取消', 5: '被驳回', 6: '已结算' }
            var items = res.data.list
            for (var i = 0; i < items.length; i++) {
              items[i].statusClass = classMap[items[i].status] || 'red'
              items[i].statusText = textMap[items[i].status] || '待接单'
            }
            that.list = that.list.concat(items)
            that.pageNo++
            that.canLoadMore = that.list.length < res.data.total
          }
        }
      }).catch(function() {
        uni.hideLoading()
      })
    },
    doAction: function(url, successMsg) {
      var that = this
      http.post(url).then(function(result) {
        if (result.code === 0) {
          uni.showToast({ title: successMsg, icon: 'success' })
          setTimeout(function() { that.getListData('refresh') }, 500)
        } else {
          uni.showToast({ title: result.msg || '操作失败', icon: 'none' })
        }
      }).catch(function(e) {
        uni.showToast({ title: (e && e.msg) || '操作失败', icon: 'none' })
      })
    },
    managerJiedan: function(clearId) {
      var that = this
      uni.showModal({
        title: '提示',
        content: '管理员直接接单？',
        success: function(res) {
          if (res.confirm) {
            that.doAction('/member/clear/managerJiedan/' + clearId, '接单成功')
          }
        }
      })
    },
    managerStart: function(clearId) {
      var that = this
      uni.showModal({
        title: '提示',
        content: '确认开始清洁？',
        success: function(res) {
          if (res.confirm) {
            that.doAction('/member/clear/managerStart/' + clearId, '已开始清洁')
          }
        }
      })
    },
    managerFinish: function(clearId) {
      var that = this
      uni.showModal({
        title: '提示',
        content: '确认完成清洁？房间将恢复为空闲状态。',
        success: function(res) {
          if (res.confirm) {
            that.doAction('/member/clear/managerFinish/' + clearId, '清洁完成')
          }
        }
      })
    },
    cancelOrder: function(clearId) {
      var that = this
      uni.showModal({
        title: '提示',
        content: '是否确认取消该保洁订单？',
        success: function(res) {
          if (res.confirm) {
            that.doAction('/member/manager/cancelClear/' + clearId, '操作成功')
          }
        }
      })
    },
    goTaskDetail: function(clearId) {
      uni.navigateTo({ url: '/pagesA/task/detail?id=' + clearId })
    },
    // 指派弹窗：获取本门店保洁员
    showAssignDialog: function(item) {
      var that = this
      that.assignTask = item
      that.selectedCleaner = ''
      that.assignVisible = true
      http.get('/member/clear/getCleanerList', { store_id: that.store_id }).then(function(res) {
        if (res.code === 0) {
          var data = res.data
          that.cleanerList = Array.isArray(data) ? data : (data.list || [])
        }
      }).catch(function() {
        that.cleanerList = []
      })
    },
    selectCleaner: function(id) {
      this.selectedCleaner = id
    },
    closeAssign: function() { this.assignVisible = false },
    closeSettle: function() { this.settleVisible = false },
    closeCreate: function() { this.createVisible = false },
    noop: function() {},
    submitAssign: function() {
      var that = this
      if (!that.selectedCleaner) {
        return uni.showToast({ title: '请选择保洁员', icon: 'none' })
      }
      http.post('/member/clear/assign/' + (that.assignTask.clearId || that.assignTask.id), {
        cleaner_id: that.selectedCleaner
      }).then(function(result) {
        if (result.code === 0) {
          uni.showToast({ title: '指派成功', icon: 'success' })
          that.assignVisible = false
          setTimeout(function() { that.getListData('refresh') }, 500)
        } else {
          uni.showToast({ title: result.msg || '指派失败', icon: 'none' })
        }
      }).catch(function(e) {
        uni.showToast({ title: (e && e.msg) || '指派失败', icon: 'none' })
      })
    },
    showSettleDialog: function(item) {
      this.settleTask = item
      this.settleAmount = ''
      this.settleVisible = true
    },
    submitSettle: function() {
      var that = this
      if (!that.settleAmount && that.settleAmount !== '0') {
        return uni.showToast({ title: '请输入结算金额', icon: 'none' })
      }
      http.post('/member/clear/settle/' + (that.settleTask.clearId || that.settleTask.id), {
        amount: that.settleAmount
      }).then(function(result) {
        if (result.code === 0) {
          uni.showToast({ title: '结算成功', icon: 'success' })
          that.settleVisible = false
          setTimeout(function() { that.getListData('refresh') }, 500)
        } else {
          uni.showToast({ title: result.msg || '结算失败', icon: 'none' })
        }
      }).catch(function(e) {
        uni.showToast({ title: (e && e.msg) || '结算失败', icon: 'none' })
      })
    },
    // 手动创建：门店固定，只加载当前门店的房间
    showCreateDialog: function() {
      var that = this
      that.createVisible = true
      that.createRoomIndex = 0
      that.createRooms = [{ text: '请选择房间', value: '' }]
      that.createRemark = ''
      if (!that.store_id) return
      http.get('/member/store/getRoomList/' + that.store_id).then(function(res) {
        if (res.code === 0 && res.data) {
          var rooms = Array.isArray(res.data) ? res.data : (res.data.list || [])
          that.createRooms = [{ text: '请选择房间', value: '' }]
          for (var i = 0; i < rooms.length; i++) {
            that.createRooms.push({ text: rooms[i].name, value: rooms[i].id })
          }
        }
      })
    },
    createRoomChange: function(e) {
      this.createRoomIndex = Number(e.detail.value)
    },
    submitCreate: function() {
      var that = this
      var roomId = that.createRooms[that.createRoomIndex].value
      if (!that.store_id) {
        return uni.showToast({ title: '门店信息缺失', icon: 'none' })
      }
      if (!roomId) {
        return uni.showToast({ title: '请选择房间', icon: 'none' })
      }
      http.post('/member/clear/createManual', {
        store_id: that.store_id,
        room_id: roomId,
        remark: that.createRemark
      }).then(function(result) {
        if (result.code === 0) {
          uni.showToast({ title: '创建成功', icon: 'success' })
          that.createVisible = false
          setTimeout(function() { that.getListData('refresh') }, 500)
        } else {
          uni.showToast({ title: result.msg || '创建失败', icon: 'none' })
        }
      }).catch(function(e) {
        uni.showToast({ title: (e && e.msg) || '创建失败', icon: 'none' })
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background: #f5f5f5;
}

.tabs {
  display: flex;
  background: #fff;
  
  picker {
    flex: 1;
  }
  
  .picker-item {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24rpx;
    font-size: 28rpx;
    color: #333;
    border-right: 1rpx solid #eee;
    
    &:last-child {
      border-right: none;
    }
    
    .arrow {
      font-size: 20rpx;
      color: #999;
      margin-left: 8rpx;
    }
  }
  
  .store-label {
    flex: 1;
    color: var(--main-color, #5AAB6E);
    font-weight: 500;
  }
}

.container {
  padding: 20rpx;
}

.create-btn {
  background: var(--main-color, #5AAB6E);
  color: #fff;
  text-align: center;
  padding: 20rpx;
  border-radius: 12rpx;
  margin-bottom: 20rpx;
  font-size: 30rpx;
}

.lists {
  .item {
    background: #fff;
    border-radius: 12rpx;
    padding: 24rpx;
    margin-bottom: 20rpx;
    position: relative;
  }
  
  .tag {
    position: absolute;
    top: 0;
    right: 0;
    padding: 8rpx 20rpx;
    font-size: 24rpx;
    border-radius: 0 12rpx 0 12rpx;
    
    &.red { background: #ff4d4f; color: #fff; }
    &.blue { background: #1890ff; color: #fff; }
    &.blue2 { background: #40a9ff; color: #fff; }
    &.green { background: #52c41a; color: #fff; }
    &.gray { background: #999; color: #fff; }
    &.yellow { background: #faad14; color: #fff; }
  }
  
  .info {
    display: flex;
    margin-bottom: 12rpx;
    font-size: 28rpx;
    
    label {
      color: #999;
      width: 180rpx;
      flex-shrink: 0;
    }
    
    text {
      color: #333;
      flex: 1;
    }
  }
  
  .btns {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
    margin-top: 20rpx;
    padding-top: 20rpx;
    border-top: 1rpx solid #eee;
    
    .btn {
      font-size: 24rpx;
      padding: 10rpx 24rpx;
      border-radius: 8rpx;
      line-height: 1.4;
      
      &.assign { background: #1890ff; color: #fff; }
      &.accept { background: var(--main-color, #5AAB6E); color: #fff; }
      &.start { background: var(--main-color, #5AAB6E); color: #fff; }
      &.finish { background: #07c160; color: #fff; }
      &.settle { background: #722ed1; color: #fff; }
      &.warning { background: #faad14; color: #fff; }
      &.primary { background: #1890ff; color: #fff; }
    }
  }
}

.note-more {
  text-align: center;
  color: #999;
  font-size: 26rpx;
  padding: 20rpx;
}

.nodata-list {
  text-align: center;
  color: #999;
  font-size: 28rpx;
  padding: 100rpx 0;
}

.dialog-mask {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
}

.dialog-box {
  width: 620rpx;
  background: #fff;
  border-radius: 16rpx;
  overflow: hidden;
  max-height: 80vh;
}

.dialog-title {
  text-align: center;
  font-size: 32rpx;
  font-weight: bold;
  padding: 30rpx 0 20rpx;
}

.dialog-body {
  padding: 0 30rpx 20rpx;
  max-height: 50vh;
  overflow-y: auto;
}

.dialog-info {
  font-size: 28rpx;
  color: #666;
  padding: 10rpx 0;
}

.dialog-line {
  display: flex;
  align-items: center;
  padding: 16rpx 0;
  border-bottom: 1rpx solid #f0f0f0;
}

.dialog-label {
  width: 120rpx;
  font-size: 28rpx;
  color: #333;
  flex-shrink: 0;
}

.dialog-value {
  font-size: 28rpx;
  color: #666;
  flex: 1;
}

.dialog-input {
  flex: 1;
  height: 60rpx;
  font-size: 28rpx;
  border: 1rpx solid #eee;
  border-radius: 8rpx;
  padding: 0 16rpx;
}

.dialog-unit {
  font-size: 28rpx;
  color: #666;
  margin-left: 10rpx;
}

.dialog-picker {
  flex: 1;
  font-size: 28rpx;
  color: #333;
  display: flex;
  align-items: center;
  
  .arrow {
    font-size: 20rpx;
    color: #999;
    margin-left: 8rpx;
  }
}

.dialog-empty {
  text-align: center;
  color: #999;
  padding: 40rpx 0;
  font-size: 28rpx;
}

.cleaner-list {
  margin-top: 16rpx;
}

.cleaner-item {
  display: flex;
  align-items: center;
  padding: 20rpx 16rpx;
  border-bottom: 1rpx solid #f0f0f0;
  border-radius: 8rpx;
  
  &.selected {
    background: #e6f7ff;
    border-color: #1890ff;
  }
  
  .cleaner-name {
    flex: 1;
    font-size: 28rpx;
    color: #333;
  }
  
  .cleaner-mobile {
    font-size: 26rpx;
    color: #999;
    margin-right: 16rpx;
  }
  
  .check {
    color: #1890ff;
    font-size: 32rpx;
    font-weight: bold;
  }
}

.dialog-footer {
  display: flex;
  border-top: 1rpx solid #eee;
}

.dialog-btn {
  flex: 1;
  height: 90rpx;
  line-height: 90rpx;
  text-align: center;
  font-size: 30rpx;
  
  &.cancel { color: #666; }
  &.confirm { color: #1890ff; font-weight: bold; }
}
</style>
