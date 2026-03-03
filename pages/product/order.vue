<template>
  <view class="container" :style="themeStyle">
    <!-- 业态分类（仅管理员模式显示） -->
    <view class="room-class-tabs" v-if="manager">
      <view 
        v-for="(item, index) in roomClassTabs" 
        :key="index"
        :class="['class-tab', { 'active': roomClass === item.id }]"
        @tap="roomClassChange(item.id)"
      >
        <text class="tab-icon">{{ item.icon }}</text>
        <text>{{ item.name }}</text>
        <text class="tab-count" v-if="item.count > 0">{{ item.count }}</text>
      </view>
    </view>

    <!-- Tab切换 -->
    <view class="tabs" :style="{ top: manager ? '80rpx' : '0' }">
      <view 
        v-for="(item, index) in tabs" 
        :key="index"
        :class="['tab', { 'active border-primary color-primary': index === status }]"
        @tap="tabChange(index)"
      >
        {{ item.name }}
      </view>
    </view>

    <!-- 订单列表 -->
    <scroll-view 
      class="order-list" 
      :style="{ paddingTop: manager ? '160rpx' : '80rpx' }"
      scroll-y 
      @scrolltolower="loadMore"
    >
      <view v-if="filteredOrderList.length > 0" class="list-wrapper">
        <view 
          class="order-card" 
          v-for="(item, index) in filteredOrderList" 
          :key="index"
          @tap="orderInfo(item)"
        >
          <!-- 订单头部 -->
          <view class="order-header">
            <view class="order-no">{{ item.order_no }}</view>
            <view class="order-header-right">
              <view :class="['room-class-tag', getRoomClassStyle(item.room_class)]" v-if="manager && item.room_class !== undefined">
                {{ getRoomClassName(item.room_class) }}
              </view>
              <view :class="['order-status', getStatusClass(item.status)]">
                {{ getStatusText(item.status) }}
              </view>
            </view>
          </view>

          <!-- 订单信息 -->
          <view class="order-info">
            <view class="info-row">
              <text class="label">下单时间：</text>
              <text>{{ item.create_time }}</text>
            </view>
            <view class="info-row">
              <text class="label">场地名称：</text>
              <text>{{ item.room_name }}</text>
            </view>
            <view class="info-row" v-if="manager">
              <text class="label">联系电话：</text>
              <text @tap.stop="call(item.order_id)">{{ item.userPhone }}</text>
            </view>
          </view>

          <view class="divider"></view>

          <!-- 商品列表 -->
          <view class="product-list">
            <view 
              class="product-item" 
              v-for="(product, pIndex) in (isShowAll ? item.productInfoVoList : item.productInfoVoListThree)" 
              :key="pIndex"
            >
              <image class="product-img" :src="product.image" mode="aspectFill" />
              <view class="product-info">
                <view class="product-name">{{ product.name }}</view>
                <view class="product-spec">{{ product.valueStr }}</view>
              </view>
              <view class="product-price-num">
                <view class="product-price">¥{{ product.price }}</view>
                <view class="product-num">x{{ product.number }}</view>
              </view>
            </view>

            <!-- 展开/收起 -->
            <view 
              class="show-more" 
              v-if="item.productInfoVoList.length > 3"
              @tap.stop="showMore"
            >
              <text>{{ isShowAll ? '收起' : '展示更多' }}</text>
            </view>
          </view>

          <view class="divider"></view>

          <!-- 备注 -->
          <view class="order-remark" v-if="item.mark">
            <text>备注：{{ item.mark }}</text>
          </view>

          <!-- 订单底部 -->
          <view class="order-footer">
            <view class="order-total">
              <text class="total-label">共{{ item.productNum }}件</text>
              <text class="total-price">¥{{ item.pay_amount }}</text>
            </view>
            
            <!-- 操作按钮 -->
            <view class="order-actions" v-if="item.status === 0 || item.status === 1">
              <button 
                v-if="item.status === 0 && !manager" 
                class="btn btn-pay" 
                size="mini"
                @tap.stop="tpPay(item.order_id)"
              >
                支付
              </button>
              <button 
                class="btn btn-cancel" 
                size="mini"
                @tap.stop="showCancel(item.order_id)"
              >
                取消
              </button>
              <button 
                v-if="item.status === 1 && manager" 
                class="btn btn-finish" 
                size="mini"
                @tap.stop="showFinish(item.order_id)"
              >
                完成
              </button>
            </view>
          </view>
        </view>

        <!-- 加载提示 -->
        <view class="load-more">
          <text v-if="!hasMore">没有更多啦~</text>
        </view>
      </view>

      <!-- 空状态 -->
      <view v-else class="empty-state">
        <image src="/static/logo.png" mode="aspectFit" />
        <text>暂无订单</text>
      </view>
    </scroll-view>
  </view>
</template>

<script>
import http from '@/utils/http'
import { PRODUCT_ORDER_STATUS_MAP, ROOM_CLASS_MAP } from '@/utils/constants'
import { getProductOrderStatusName, getRoomClassName } from '@/utils/format'
import listMixin from '@/mixins/listMixin'

export default {
  mixins: [listMixin],
  data() {
    return {
      currentPage: 1,
      productOrderList: [],
      hasMore: false,
      isShowAll: false,
      manager: false,
      order_id: '',
      store_id: '',
      status: -1,
      roomClass: -1, // -1=全部, 0=棋牌, 1=台球, 2=KTV
      tabs: [
        { id: -1, name: '全部' },
        { id: 0, name: '待支付' },
        { id: 1, name: '待配送' },
        { id: 2, name: '已完成' },
        { id: 3, name: '已取消' }
      ],
      roomClassTabs: [
        { id: -1, name: '全部', icon: '📋', count: 0 },
        { id: 0, name: '棋牌', icon: '🀄', count: 0 },
        { id: 1, name: '台球', icon: '🎱', count: 0 },
        { id: 2, name: 'KTV', icon: '🎤', count: 0 }
      ]
    }
  },

  computed: {
    // 根据业态分类过滤订单
    filteredOrderList() {
      if (this.roomClass === -1) {
        return this.productOrderList
      }
      return this.productOrderList.filter(item => item.room_class === this.roomClass)
    }
  },

  onLoad(options) {
    this.manager = options.manager === 'true' || false
    this.store_id = options.store_id || ''
  },

  onShow() {
    if (this.manager) {
      this.getManageOrderPage(true)
    } else {
      this.getOrderPage(true)
    }
  },

  onReachBottom() {
    this.loadMore()
  },

  methods: {
    // 加载更多
    loadMore() {
      if (this.hasMore) {
        if (this.manager) {
          this.getManageOrderPage(false)
        } else {
          this.getOrderPage(false)
        }
      }
    },
    // Tab切换
    tabChange(status) {
      this.status = status
      if (this.manager) {
        this.getManageOrderPage(true)
      } else {
        this.getOrderPage(true)
      }
    },

    // 业态分类切换
    roomClassChange(roomClass) {
      this.roomClass = roomClass
    },

    // 更新业态分类计数
    updateRoomClassCounts() {
      const counts = { '-1': this.productOrderList.length, '0': 0, '1': 0, '2': 0 }
      this.productOrderList.forEach(item => {
        if (item.room_class !== undefined && counts[item.room_class] !== undefined) {
          counts[item.room_class]++
        }
      })
      this.roomClassTabs = this.roomClassTabs.map(tab => ({
        ...tab,
        count: counts[tab.id] || 0
      }))
    },

    // 获取订单列表
    async getOrderPage(refresh = false) {
      const token = uni.getStorageSync('token')
      if (!token) return

      const currentPage = refresh ? 1 : this.currentPage + 1
      this.currentPage = currentPage

      try {
        const res = await http.request({
          url: '/product/order/page',
          method: 'POST',
          data: {
            status: this.status === -1 ? null : this.status,
            pageSize: 10,
            pageNo: currentPage
          }
        })

        if (res.code === 0) {
          const productOrderList = res.data.list.map(item => {
            let totalNumber = 0
            if (item.productInfoVoList) {
              item.productInfoVoList.forEach(product => {
                totalNumber += product.number
              })
              item.productInfoVoListThree = item.productInfoVoList.slice(0, 3)
            } else {
              item.productInfoVoList = []
              item.productInfoVoListThree = []
            }
            item.productNum = totalNumber
            return item
          })

          if (refresh) {
            this.productOrderList = productOrderList
          } else {
            this.productOrderList = [...this.productOrderList, ...productOrderList]
          }

          this.hasMore = this.currentPage * 10 < res.data.total
        }
      } catch (e) {
      }
    },
    async getManageOrderPage(refresh = false) {
      const token = uni.getStorageSync('token')
      if (!token || !this.manager) return

      const currentPage = refresh ? 1 : this.currentPage + 1
      this.currentPage = currentPage

      try {
        const res = await http.request({
          url: '/product/order/manage/page',
          method: 'POST',
          data: {
            store_id: this.store_id,
            status: this.status === -1 ? null : this.status,
            pageSize: 100,
            pageNo: currentPage
          }
        })

        if (res.code === 0) {
          const productOrderList = res.data.list.map(item => {
            let totalNumber = 0
            if (item.productInfoVoList) {
              item.productInfoVoList.forEach(product => {
                totalNumber += product.number
              })
              item.productInfoVoListThree = item.productInfoVoList.slice(0, 3)
            } else {
              item.productInfoVoList = []
              item.productInfoVoListThree = []
            }
            item.productNum = totalNumber
            return item
          })

          if (refresh) {
            this.productOrderList = productOrderList
          } else {
            this.productOrderList = [...this.productOrderList, ...productOrderList]
          }

          this.hasMore = this.currentPage * 100 < res.data.total
          this.updateRoomClassCounts()
        }
      } catch (e) {
      }
    },

    // 展开/收起
    showMore() {
      this.isShowAll = !this.isShowAll
    },

    // 取消订单
    showCancel(orderId) {
      uni.showModal({
        title: '温馨提示',
        content: '您确定要取消此订单吗？',
        success: (res) => {
          if (res.confirm) {
            this.order_id = orderId
            this.cancelPay()
          }
        }
      })
    },

    // 取消支付
    async cancelPay() {
      const url = this.manager ? '/product/order/cancelByAdmin' : '/product/order/cancel'
      
      try {
        const res = await http.request({
          url,
          method: 'POST',
          data: {
            order_id: this.order_id
          }
        })

        if (res.code === 0) {
          uni.showToast({
            title: '取消成功'
          })
          
          if (this.manager) {
            this.getManageOrderPage(true)
          } else {
            this.getOrderPage(true)
          }
        }
      } catch (e) {
      }
    },

    // 完成订单
    showFinish(orderId) {
      uni.showModal({
        title: '温馨提示',
        content: '您确定要完成此订单吗？',
        success: (res) => {
          if (res.confirm) {
            this.order_id = orderId
            this.finishOrder()
          }
        }
      })
    },

    // 完成订单
    async finishOrder() {
      try {
        const res = await http.request({
          url: `/product/order/finish/${this.order_id}`,
          method: 'POST'
        })

        uni.showToast({
          title: '订单已完结'
        })

        if (this.manager) {
          this.getManageOrderPage(true)
        } else {
          this.getOrderPage(true)
        }
      } catch (e) {
      }
    },

    // 支付
    async tpPay(orderId) {
      try {
        uni.showLoading({
          title: '加载中'
        })

        const res = await http.request({
          url: `/product/order/pay/${orderId}`,
          method: 'POST',
          data: { pay_type: 1 } // 默认微信支付
        })

        uni.hideLoading()

        if (res.code === 0) {
          if (res.data && res.data.timeStamp) {
            // 微信支付
            this.payMent(res.data)
          } else {
            // 余额支付成功或免支付
            uni.showToast({ title: '支付成功', icon: 'success' })
            if (this.manager) {
              this.getManageOrderPage(true)
            } else {
              this.getOrderPage(true)
            }
          }
        }
      } catch (e) {
        uni.hideLoading()
        uni.showToast({
          title: e.msg || '支付失败',
          icon: 'none'
        })
      }
    },

    // 微信支付
    payMent(payData) {
      uni.requestPayment({
        timeStamp: payData.timeStamp,
        nonceStr: payData.nonceStr,
        package: payData.pkg,
        signType: payData.signType,
        paySign: payData.paySign,
        success: () => {
          uni.showToast({
            title: '支付成功!'
          })
          this.getOrderPage(true)
        },
        fail: () => {
          uni.showToast({
            title: '支付失败!',
            icon: 'error'
          })
        }
      })
    },

    // 拨打电话
    async call(orderId) {
      try {
        const res = await http.request({
          url: `/product/order/phone/${orderId}`,
          method: 'GET'
        })

        uni.makePhoneCall({
          phoneNumber: res.data
        })
      } catch (e) {
      }
    },

    // 订单详情
    orderInfo(item) {
      uni.navigateTo({
        url: `/pages/product/order-info?storeId=${item.store_id}&orderId=${item.order_id}`
      })
    },

    // 获取状态文本
    getStatusText: getProductOrderStatusName,

    // 获取状态样式类
    getStatusClass(status) {
      const classMap = {
        0: 'status-wait',
        1: 'status-send',
        2: 'status-end',
        3: 'status-cancel'
      }
      return classMap[status] || ''
    },

    // 获取业态名称
    getRoomClassName,

    // 获取业态样式
    getRoomClassStyle(roomClass) {
      const styleMap = { 0: 'class-mahjong', 1: 'class-pool', 2: 'class-ktv' }
      return styleMap[roomClass] || ''
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  min-height: 100vh;
  background: #f5f5f5;
}

/* 业态分类标签 */
.room-class-tabs {
  position: fixed;
  z-index: 10;
  top: 0;
  left: 0;
  width: 100%;
  height: 80rpx;
  background: #fff;
  display: flex;
  justify-content: space-around;
  align-items: center;
  border-bottom: 1rpx solid #eee;
}

.class-tab {
  display: flex;
  align-items: center;
  gap: 8rpx;
  padding: 12rpx 24rpx;
  font-size: 26rpx;
  color: #666;
  border-radius: 30rpx;
}

.class-tab.active {
  background: var(--main-color, #5AAB6E);
  color: #fff;
}

.tab-icon {
  font-size: 28rpx;
}

.tab-count {
  background: rgba(0,0,0,0.1);
  padding: 2rpx 12rpx;
  border-radius: 20rpx;
  font-size: 22rpx;
}

.class-tab.active .tab-count {
  background: rgba(255,255,255,0.3);
}

.tabs {
  position: fixed;
  z-index: 9;
  top: 0;
  left: 0;
  width: 100%;
  height: 80rpx;
  background: #fff;
  display: flex;
  justify-content: space-between;
  padding: 0 30rpx;
  box-sizing: border-box;
}

.tab {
  line-height: 80rpx;
  width: 200rpx;
  text-align: center;
  font-size: 28rpx;
  color: #666;
}

.tab.active {
  border-width: 0;
  border-bottom-width: 2rpx;
  border-style: solid;
  font-weight: bold;
}

.color-primary {
  color: var(--main-color, #5AAB6E);
}

.border-primary {
  border-color: var(--main-color, #5AAB6E);
}

.order-list {
  height: calc(100vh - 80rpx);
  padding-top: 80rpx;
}

.list-wrapper {
  padding: 20rpx;
}

.order-card {
  background: #fff;
  border-radius: 16rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20rpx;
}

.order-header-right {
  display: flex;
  align-items: center;
  gap: 16rpx;
}

.order-no {
  font-size: 30rpx;
  color: #2880F3;
  font-weight: 600;
}

.order-status {
  padding: 8rpx 24rpx;
  border-radius: 100rpx;
  font-size: 24rpx;
  font-weight: 600;
}

/* 业态标签样式 */
.room-class-tag {
  padding: 6rpx 16rpx;
  border-radius: 8rpx;
  font-size: 22rpx;
  font-weight: 500;
}

.class-mahjong {
  background: #e8f5e9;
  color: #4caf50;
}

.class-pool {
  background: #e3f2fd;
  color: #2196f3;
}

.class-ktv {
  background: #fff3e0;
  color: #ff9800;
}

.status-wait {
  background: #F8CB04;
  color: #fff;
}

.status-send {
  background: #71BCFA;
  color: #fff;
}

.status-end {
  background: #81FA71;
  color: #fff;
}

.status-cancel {
  background: rgba(83, 87, 87, 0.2);
  color: #fff;
}

.order-info {
  margin-bottom: 20rpx;
}

.info-row {
  font-size: 24rpx;
  color: #666;
  margin-bottom: 10rpx;
}

.info-row .label {
  color: #999;
}

.divider {
  height: 1rpx;
  background: rgba(86, 87, 87, 0.2);
  margin: 20rpx 0;
}

.product-list {
  margin-bottom: 20rpx;
}

.product-item {
  display: flex;
  align-items: center;
  margin-bottom: 20rpx;
}

.product-img {
  width: 100rpx;
  height: 100rpx;
  border-radius: 8rpx;
  margin-right: 20rpx;
}

.product-info {
  flex: 1;
}

.product-name {
  font-size: 28rpx;
  color: #333;
  margin-bottom: 8rpx;
}

.product-spec {
  font-size: 24rpx;
  color: #999;
}

.product-price-num {
  text-align: right;
}

.product-price {
  font-size: 28rpx;
  color: #333;
  margin-bottom: 8rpx;
}

.product-num {
  font-size: 24rpx;
  color: #999;
}

.show-more {
  text-align: center;
  font-size: 24rpx;
  color: var(--main-color, #5AAB6E);
  padding: 10rpx 0;
}

.order-remark {
  padding: 20rpx;
  background: rgba(228, 228, 224, 0.4);
  border-radius: 8rpx;
  font-size: 24rpx;
  color: #666;
  margin-bottom: 20rpx;
}

.order-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.order-total {
  display: flex;
  align-items: center;
  gap: 20rpx;
}

.total-label {
  font-size: 24rpx;
  color: #666;
}

.total-price {
  font-size: 32rpx;
  color: #2880F3;
  font-weight: 600;
}

.order-actions {
  display: flex;
  gap: 20rpx;
}

.btn {
  padding: 12rpx 32rpx;
  font-size: 24rpx;
  border-radius: 8rpx;
}

.btn-pay {
  background: #A1EBC2;
  color: #13CE66;
}

.btn-cancel {
  background: #A1ACA6;
  color: #fff;
}

.btn-finish {
  background: var(--main-color, #5AAB6E);
  color: #fff;
}

.load-more {
  text-align: center;
  padding: 40rpx 0;
  font-size: 24rpx;
  color: #999;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 200rpx 0;
}

.empty-state image {
  width: 200rpx;
  height: 200rpx;
  margin-bottom: 20rpx;
}

.empty-state text {
  font-size: 28rpx;
  color: #999;
}
</style>
