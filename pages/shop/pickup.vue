<template>
  <view class="container" :style="themeStyle">
    <!-- Tab栏 -->
    <view class="tab-bar">
      <view
        class="tab-item"
        :class="currentTab === 0 ? 'active' : ''"
        @tap="switchTab(0)"
      >
        <text>待取</text>
      </view>
      <view
        class="tab-item"
        :class="currentTab === 1 ? 'active' : ''"
        @tap="switchTab(1)"
      >
        <text>已取</text>
      </view>
      <view
        class="tab-item"
        :class="currentTab === 2 ? 'active' : ''"
        @tap="switchTab(2)"
      >
        <text>已取消</text>
      </view>
    </view>

    <!-- 门店选择 -->
    <view class="search-bar">
      <text class="search-label">门店名称</text>
      <picker :value="storeIndex" :range="storeNames" @change="onStoreChange">
        <view class="picker-value">{{ storeNames[storeIndex] || '请选择门店' }}</view>
      </picker>
    </view>

    <!-- 列表 -->
    <scroll-view class="list-wrap" scroll-y @scrolltolower="loadMore">
      <block v-if="list.length > 0">
        <view class="order-card" v-for="(item, index) in list" :key="index">
          <view class="card-header">
            <text class="store-name">{{ item.store_name || '未知门店' }}</text>
            <text class="status-tag" :class="'status-' + item.status">{{ getStatusText(item.status) }}</text>
          </view>
          <view class="card-body">
            <view class="product-list">
              <view class="product-item" v-for="(p, pi) in item.products" :key="pi">
                <image class="product-img" :src="p.image || '/static/img/default-product.png'" mode="aspectFill" />
                <view class="product-info">
                  <text class="product-name">{{ p.name }}</text>
                  <text class="product-spec" v-if="p.spec">{{ p.spec }}</text>
                  <text class="product-price">¥{{ p.price }} x{{ p.number }}</text>
                </view>
              </view>
            </view>
          </view>
          <view class="card-footer">
            <text class="order-time">{{ item.create_time }}</text>
            <text class="order-total">合计: ¥{{ item.total_amount }}</text>
          </view>
        </view>
      </block>
      <block v-else>
        <view class="empty-box">
          <text class="empty-icon">📦</text>
          <text class="empty-text">暂无数据</text>
        </view>
      </block>
    </scroll-view>
  </view>
</template>

<script>
import http from '@/utils/http.js'

export default {
  data: function() {
    return {
      currentTab: 0,
      stores: [],
      storeNames: ['全部门店'],
      storeIds: [''],
      storeIndex: 0,
      list: [],
      pageNo: 1,
      pageSize: 10,
      total: 0,
      loading: false
    }
  },
  onShow: function() {
    this.pageNo = 1
    this.list = []
    this.getStoreList()
  },
  methods: {
    getStoreList: function() {
      var that = this
      http.get('/member/index/getStoreList', { cityName: '' }).then(function(res) {
        if (res.code === 0 && res.data) {
          var rawList = res.data.list || res.data
          if (!rawList || !rawList.length) {
            that.getList()
            return
          }
          var names = ['全部门店']
          var ids = ['']
          for (var i = 0; i < rawList.length; i++) {
            var it = rawList[i]
            names.push(it.key || it.name || it.store_name || '')
            ids.push(it.value || it.store_id || it.id || '')
          }
          that.storeNames = names
          that.storeIds = ids
        }
        that.getList()
      }).catch(function() {
        that.getList()
      })
    },
    onStoreChange: function(e) {
      this.storeIndex = e.detail.value
      this.pageNo = 1
      this.list = []
      this.getList()
    },
    switchTab: function(tab) {
      this.currentTab = tab
      this.pageNo = 1
      this.list = []
      this.getList()
    },
    getStatusText: function(status) {
      // 状态: 0未支付 1待取 2已取 3已取消
      var map = { 0: '未支付', 1: '待取', 2: '已取', 3: '已取消' }
      return map[status] || '未知'
    },
    getList: function() {
      if (this.loading) return
      this.loading = true
      var that = this
      // tab: 0=待取(status 1), 1=已取(status 2), 2=已取消(status 3)
      // 注：状态0是未支付，不在存取列表显示
      var statusArr = [[1], [2], [3]]
      var params = {
        pageNo: that.pageNo,
        pageSize: that.pageSize,
        statusList: statusArr[that.currentTab].join(',')
      }
      var selectedStoreId = that.storeIds[that.storeIndex]
      if (selectedStoreId) {
        params.store_id = selectedStoreId
      }
      http.post('/product/order/page', params).then(function(res) {
        that.loading = false
        if (res.code === 0) {
          var data = res.data || {}
          var rows = data.list || data.records || []
          // 解析 product_info
          for (var i = 0; i < rows.length; i++) {
            var item = rows[i]
            if (!item.products) {
              try {
                if (typeof item.product_info === 'string') {
                  item.products = JSON.parse(item.product_info)
                } else if (item.product_info && typeof item.product_info === 'object') {
                  item.products = Array.isArray(item.product_info) ? item.product_info : [item.product_info]
                } else {
                  item.products = []
                }
              } catch (e) {
                item.products = []
              }
            }
          }
          that.total = data.total || 0
          if (that.pageNo === 1) {
            that.list = rows
          } else {
            that.list = that.list.concat(rows)
          }
        }
      }).catch(function() {
        that.loading = false
      })
    },
    loadMore: function() {
      if (this.list.length < this.total) {
        this.pageNo++
        this.getList()
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  min-height: 100vh;
  background: #f5f5f5;
}

.tab-bar {
  display: flex;
  background: #FFD700;
  height: 88rpx;
}
.tab-item {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32rpx;
  color: #666;
  position: relative;
}
.tab-item.active {
  color: #333;
  font-weight: bold;
}
.tab-item.active::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 60rpx;
  height: 6rpx;
  background: #333;
  border-radius: 3rpx;
}

.search-bar {
  display: flex;
  align-items: center;
  background: #fff;
  padding: 20rpx 30rpx;
  border-bottom: 1rpx solid #eee;
}
.search-label {
  font-size: 28rpx;
  color: var(--main-color, #5AAB6E);
  font-weight: 600;
  margin-right: 20rpx;
  white-space: nowrap;
}
.picker-value {
  font-size: 28rpx;
  color: #666;
  height: 60rpx;
  line-height: 60rpx;
}

.list-wrap {
  height: calc(100vh - 168rpx);
}

.order-card {
  background: #fff;
  margin: 20rpx;
  border-radius: 16rpx;
  overflow: hidden;
}
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24rpx 30rpx;
  border-bottom: 1rpx solid #f5f5f5;
}
.store-name {
  font-size: 30rpx;
  font-weight: 600;
  color: #333;
}
.status-tag {
  font-size: 24rpx;
  padding: 4rpx 16rpx;
  border-radius: 20rpx;
}
.status-0, .status-1 {
  color: #ff6b00;
  background: #fff3e6;
}
.status-2 {
  color: var(--main-color, #5AAB6E);
  background: #e8f5e9;
}
.status-3 {
  color: #999;
  background: #f5f5f5;
}

.card-body {
  padding: 20rpx 30rpx;
}
.product-item {
  display: flex;
  align-items: center;
  padding: 12rpx 0;
}
.product-img {
  width: 100rpx;
  height: 100rpx;
  border-radius: 12rpx;
  margin-right: 20rpx;
  flex-shrink: 0;
}
.product-info {
  flex: 1;
}
.product-name {
  font-size: 28rpx;
  color: #333;
  display: block;
  margin-bottom: 6rpx;
}
.product-spec {
  font-size: 24rpx;
  color: #999;
  display: block;
  margin-bottom: 6rpx;
}
.product-price {
  font-size: 26rpx;
  color: #ff6b00;
  display: block;
}

.card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20rpx 30rpx;
  border-top: 1rpx solid #f5f5f5;
}
.order-time {
  font-size: 24rpx;
  color: #999;
}
.order-total {
  font-size: 28rpx;
  color: #333;
  font-weight: 600;
}

.empty-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 200rpx 0;
}
.empty-icon {
  font-size: 120rpx;
  margin-bottom: 20rpx;
}
.empty-text {
  font-size: 28rpx;
  color: #999;
}
</style>
