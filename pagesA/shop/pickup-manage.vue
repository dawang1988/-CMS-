<template>
  <view class="container" :style="themeStyle">
    <!-- Tab栏 -->
    <view class="tab-bar">
      <view class="tab-item" :class="currentTab === 0 ? 'active' : ''" @tap="switchTab(0)">
        <text>待取</text>
      </view>
      <view class="tab-item" :class="currentTab === 1 ? 'active' : ''" @tap="switchTab(1)">
        <text>已取</text>
      </view>
      <view class="tab-item" :class="currentTab === 2 ? 'active' : ''" @tap="switchTab(2)">
        <text>已取消</text>
      </view>
    </view>

    <!-- 门店选择 -->
    <view class="filter-bar">
      <text class="filter-label">门店</text>
      <picker :value="storeIndex" :range="storeNames" @change="onStoreChange">
        <view class="picker-value">{{ storeNames[storeIndex] || '全部门店' }}</view>
      </picker>
    </view>

    <!-- 列表 -->
    <scroll-view class="list-wrap" scroll-y @scrolltolower="loadMore">
      <block v-if="list.length > 0">
        <view class="order-card" v-for="(item, index) in list" :key="index">
          <view class="card-header">
            <text class="store-name">{{ item.store_name || '未知门店' }}</text>
            <text class="status-text" :class="'s' + item.status">{{ item.statusText }}</text>
          </view>
          <view class="card-body">
            <view class="user-info">
              <text class="user-name">{{ item.userName || '用户' }}</text>
              <text class="user-phone" v-if="item.userPhone">{{ item.userPhone }}</text>
            </view>
            <view class="product-item" v-for="(p, pi) in item.products" :key="pi">
              <image class="product-img" :src="p.image || '/static/img/default-product.png'" mode="aspectFill" />
              <view class="product-detail">
                <text class="product-name">{{ p.name }}</text>
                <text class="product-num">x{{ p.number || 1 }}</text>
              </view>
            </view>
          </view>
          <view class="card-footer">
            <text class="order-time">{{ item.create_time }}</text>
            <view class="actions" v-if="item.status === 1">
              <button class="btn-pickup" @tap="doPickup(item)">标记已取</button>
            </view>
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
      pageSize: 20,
      total: 0,
      loading: false
    }
  },
  onShow: function() {
    this.getStoreList()
  },
  methods: {
    getStoreList: function() {
      var that = this
      http.get('/member/index/getStoreList', { cityName: '' }).then(function(res) {
        if (res.code === 0 && res.data) {
          var rawList = res.data.list || res.data
          if (!rawList || !rawList.length) { that.loadList(); return }
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
        that.pageNo = 1
        that.list = []
        that.loadList()
      }).catch(function() { that.loadList() })
    },
    onStoreChange: function(e) {
      this.storeIndex = e.detail.value
      this.pageNo = 1
      this.list = []
      this.loadList()
    },
    switchTab: function(tab) {
      this.currentTab = tab
      this.pageNo = 1
      this.list = []
      this.loadList()
    },
    loadList: function() {
      if (this.loading) return
      this.loading = true
      var that = this
      // 管理员用 manage/page 接口查所有用户的订单
      // 状态: 0未支付 1待取 2已取 3已取消
      var statusMap = [[1], [2], [3]]
      var params = {
        pageNo: that.pageNo,
        pageSize: that.pageSize,
        statusList: statusMap[that.currentTab].join(',')
      }
      var sid = that.storeIds[that.storeIndex]
      if (sid) params.store_id = sid

      http.post('/product/order/manage/page', params).then(function(res) {
        that.loading = false
        if (res.code === 0) {
          var data = res.data || {}
          var rows = data.list || data.records || []
          var statusTextMap = { 0: '未支付', 1: '待取', 2: '已取', 3: '已取消' }
          for (var i = 0; i < rows.length; i++) {
            var item = rows[i]
            item.statusText = statusTextMap[item.status] || '未知'
            if (!item.products) {
              try {
                if (typeof item.product_info === 'string') {
                  item.products = JSON.parse(item.product_info)
                } else if (item.product_info) {
                  item.products = Array.isArray(item.product_info) ? item.product_info : [item.product_info]
                } else {
                  item.products = []
                }
              } catch (e) { item.products = [] }
            }
            // 补充用户信息
            if (!item.userName) item.userName = item.user_name || ''
            if (!item.userPhone) item.userPhone = item.user_phone || ''
          }
          that.total = data.total || 0
          if (that.pageNo === 1) {
            that.list = rows
          } else {
            that.list = that.list.concat(rows)
          }
        }
      }).catch(function() { that.loading = false })
    },
    loadMore: function() {
      if (this.list.length < this.total) {
        this.pageNo++
        this.loadList()
      }
    },
    doPickup: function(item) {
      var that = this
      uni.showModal({
        title: '确认',
        content: '确认该订单商品已被取走？',
        success: function(res) {
          if (res.confirm) {
            http.post('/product/order/finish/' + item.id).then(function(r) {
              if (r.code === 0) {
                uni.showToast({ title: '已标记为已取', icon: 'success' })
                that.pageNo = 1
                that.list = []
                that.loadList()
              } else {
                uni.showModal({ content: r.msg || '操作失败', showCancel: false })
              }
            })
          }
        }
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.container { min-height: 100vh; background: #f5f5f5; }
.tab-bar { display: flex; background: #FFD700; height: 88rpx; }
.tab-item { flex: 1; display: flex; align-items: center; justify-content: center; font-size: 32rpx; color: #666; position: relative; }
.tab-item.active { color: #333; font-weight: bold; }
.tab-item.active::after { content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 60rpx; height: 6rpx; background: #333; border-radius: 3rpx; }
.filter-bar { display: flex; align-items: center; background: #fff; padding: 20rpx 30rpx; border-bottom: 1rpx solid #eee; }
.filter-label { font-size: 28rpx; color: var(--main-color, #5AAB6E); font-weight: 600; margin-right: 20rpx; }
.picker-value { font-size: 28rpx; color: #666; height: 60rpx; line-height: 60rpx; }
.list-wrap { height: calc(100vh - 168rpx); }
.order-card { background: #fff; margin: 20rpx; border-radius: 16rpx; overflow: hidden; }
.card-header { display: flex; justify-content: space-between; align-items: center; padding: 24rpx 30rpx; border-bottom: 1rpx solid #f5f5f5; }
.store-name { font-size: 30rpx; font-weight: 600; color: #333; }
.status-text { font-size: 26rpx; font-weight: 500; }
.s0, .s1 { color: #ff6b00; }
.s2 { color: var(--main-color, #5AAB6E); }
.s3 { color: #999; }
.card-body { padding: 20rpx 30rpx; }
.user-info { display: flex; align-items: center; margin-bottom: 16rpx; }
.user-name { font-size: 28rpx; color: #333; margin-right: 16rpx; }
.user-phone { font-size: 24rpx; color: #999; }
.product-item { display: flex; align-items: center; padding: 10rpx 0; }
.product-img { width: 80rpx; height: 80rpx; border-radius: 10rpx; margin-right: 16rpx; flex-shrink: 0; }
.product-detail { flex: 1; display: flex; justify-content: space-between; align-items: center; }
.product-name { font-size: 28rpx; color: #333; }
.product-num { font-size: 26rpx; color: #999; }
.card-footer { display: flex; justify-content: space-between; align-items: center; padding: 20rpx 30rpx; border-top: 1rpx solid #f5f5f5; }
.order-time { font-size: 24rpx; color: #999; }
.btn-pickup { background: var(--main-color, #5AAB6E); color: #fff; font-size: 26rpx; padding: 10rpx 30rpx; border-radius: 30rpx; border: none; }
.empty-box { display: flex; flex-direction: column; align-items: center; padding: 200rpx 0; }
.empty-icon { font-size: 120rpx; margin-bottom: 20rpx; }
.empty-text { font-size: 28rpx; color: #999; }
</style>
