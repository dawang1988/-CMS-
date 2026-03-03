<template>
  <view class="page" :style="themeStyle">
    <!-- 筛选下拉菜单 -->
    <block v-if="isLogin">
      <view class="tabs">
        <view class="dropdown-menu">
          <view class="dropdown-item" @tap="showStatusDropdown">
            <text>{{ statusText }}</text>
            <text style="font-size:20rpx;margin-left:8rpx;">▼</text>
          </view>
          <view class="dropdown-item" @tap="showColumnDropdown">
            <text>{{ columnText }}</text>
            <text style="font-size:20rpx;margin-left:8rpx;">▼</text>
          </view>
        </view>
      </view>
    </block>

    <view class="container">
      <!-- 已登录 -->
      <block v-if="isLogin">
        <block v-if="orderlist.length > 0">
          <view class="lists" v-for="(item, index) in orderlist" :key="index">
            <view class="item" @tap="openDoor" :data-no="item.order_no">
              <view class="top">
                <view class="left">
                  <image src="/static/icon/stoer.png" />
                  <text class="text">{{ item.store_name }}</text>
                </view>
                <text class="right">查看详情</text>
              </view>
              
              <view class="highlight">
                <view class="left">
                  <image src="/static/icon/time-icon.png" />
                  <text>{{ item.full_time }}</text>
                </view>
                <view class="right">¥<text>{{ item.pay_amount }}</text></view>
              </view>
              
              <view class="info-line">
                <text class="bold">场地名称：</text>
                <text>{{ item.room_name }}</text>
              </view>
              
              <view class="info-line">
                <text class="bold">订单编号：</text>
                <text @tap.stop="copyOrderNo" :data-order="item.order_no">{{ item.order_no }}</text>
              </view>
              
              <view class="info-line">
                <text class="bold">订单状态：</text>
                <text>{{ ['未开始','进行中','已完成','已取消'][item.status] || '未知' }}</text>
              </view>
              
              <view class="info-line" v-if="item.deposit">
                <text class="bold">支付押金：￥</text>
                <view class="price">
                  {{ item.deposit }}
                  <block v-if="item.depositRefund">(已退回)</block>
                </view>
              </view>
              
              <view class="info-line display-space">
                <view>
                  <text class="bold">支付方式：</text>
                  <text>{{ item.pay_type===0?'管理员':item.pay_type===1?'微信':item.pay_type===2?'余额':item.pay_type===3?'团购':item.pay_type===4?'套餐':'预订' }}</text>
                </view>
                <block v-if="item.status == 0">
                  <text class="right" @tap.stop="cancelOrder" :data-info="item">申请退款</text>
                </block>
              </view>
              
              <view class="info-line" v-if="item.refund_price">
                <text class="bold">订单退款：</text>
                <text>￥{{ item.refund_price }}</text>
              </view>
              
              <view class="info-line display-space">
                <view class="discount-tag" v-if="item.bargain_price">
                  <image src="/static/icon/discount-bg.png" />
                  <view>已砍价<text>¥{{ item.bargain_price }}</text>元</view>
                </view>
                <view v-else></view>
                <view class="review-btn" v-if="item.status == 2 && !item.is_reviewed" @tap.stop="goReview" :data-id="item.id" :data-store="item.store_name" :data-room="item.room_name">
                  去评价
                </view>
                <view class="reviewed-tag" v-if="item.status == 2 && item.is_reviewed">
                  已评价
                </view>
              </view>
              
              <!-- 订单二维码或状态标签 -->
              <block v-if="item.status == 0 || item.status == 1">
                <view class="status-tag" @tap.stop="showOrderQr" :data-no="item.order_no">
                  <image src="/static/icon/scan.png" style="width:80rpx;height:80rpx;" />
                </view>
              </block>
              <block v-else>
                <image class="status-tag" :src="`/static/icon/status${item.status}.png`" />
              </block>
            </view>
          </view>
          
          <view v-if="!canLoadMore" class="notes">
            <image src="/static/icon/more.png" />
            到底了，没有更多订单啦~
          </view>
        </block>
        
        <block v-else>
          <view class="empty-state">
            <image src="/static/logo.png" class="empty-icon" mode="aspectFit" />
            <view class="empty-text">暂无订单</view>
            <view class="empty-tip">快去预约一个房间吧</view>
            <button class="empty-btn" hover-class="empty-btn-hover" @tap="goBooking">去预约</button>
          </view>
        </block>
      </block>
      
      <!-- 未登录 -->
      <block v-else>
        <view class="containerlogin">
          <view class="photo">
            <view class="img">
              <image src="/static/logo.png" mode="widthFix" />
            </view>
            <view class="name">{{ appName }}</view>
          </view>
          <button hover-class="button-click" class="loginBtn bg-primary" @tap="phone">登录后查看</button>
        </view>
      </block>
    </view>

    <!-- 状态筛选弹窗 -->
    <u-popup :show="statusDropdownShow" mode="top" @close="statusDropdownShow = false">
      <view class="dropdown-list">
        <view 
          class="dropdown-list-item" 
          :class="{ active: value1 === item.value }" 
          v-for="(item, index) in option1" 
          :key="index"
          @tap="selectStatus(item)"
        >
          {{ item.text }}
        </view>
      </view>
    </u-popup>

    <!-- 排序筛选弹窗 -->
    <u-popup :show="columnDropdownShow" mode="top" @close="columnDropdownShow = false">
      <view class="dropdown-list">
        <view 
          class="dropdown-list-item" 
          :class="{ active: value2 === item.value }" 
          v-for="(item, index) in option2" 
          :key="index"
          @tap="selectColumn(item)"
        >
          {{ item.text }}
        </view>
      </view>
    </u-popup>

    <!-- 取消订单弹窗 -->
    <u-popup :show="cancelOrderShow" mode="center" @close="cancelOrderShow = false">
      <view class="dialog">
        <view class="dialog-title">取消订单</view>
        <view class="item">
          <label>当前位置：</label>
          <text>{{ orderInfo.room_name }}（{{ ['未知','小包','中包','大包','豪包','商务包','斯洛克','中式黑八','美式球桌'][orderInfo.room_type] }}）</text>
        </view>
        <view class="item">
          <label>开始时间：</label>
          <text>{{ orderInfo.start_time }}</text>
        </view>
        <view class="item">
          <label>结束时间：</label>
          <text>{{ orderInfo.end_time }}</text>
        </view>
        <view class="item">
          <view class="color-attention note" v-if="orderInfo.pay_type == 1">温馨提示：取消后，微信支付退款将在1-3个工作日内原路退回！</view>
          <view class="color-attention note" v-else>温馨提示：取消后，费用将按原支付方式退回！</view>
        </view>
        <view class="dialog-btns">
          <view class="btn" @tap="cancelOrderShow = false">暂不取消</view>
          <view class="btn active" @tap="cancelConfirm">确认取消</view>
        </view>
      </view>
    </u-popup>

    <!-- 取消成功弹窗 -->
    <u-popup :show="cancelOrderSuccess" mode="center" @close="cancelOrderSuccess = false">
      <view class="dialog">
        <view class="dialog-title">订单取消成功</view>
        <view class="item">
          <label>已为您成功取消下列订单</label>
        </view>
        <view class="item">
          <label>当前位置：</label>
          <text>{{ orderInfo.room_name }}（{{ ['未知','小包','中包','大包','豪包','商务包','斯洛克','中式黑八','美式球桌'][orderInfo.room_type] }}）</text>
        </view>
        <view class="item">
          <label>预约时间：</label>
          <text>{{ orderInfo.start_time }}~{{ orderInfo.end_time }}</text>
        </view>
        <view class="item">
          <view class="color-attention note" v-if="orderInfo.pay_type == 1">取消成功，微信支付退款将在1-3个工作日内原路退回！</view>
          <view class="color-attention note" v-else>取消成功，费用已返还到原支付方式！</view>
        </view>
        <view class="dialog-btns">
          <view class="btn active" @tap="cancelOrderSuccess = false">好的</view>
        </view>
      </view>
    </u-popup>

    <!-- 订单二维码弹窗 -->
    <view v-if="showModal" class="modal-mask" @touchmove.stop.prevent>
      <view class="modal-content" @tap.stop>
        <view class="modal-title">请将二维码对准识别区</view>
        <view class="qrcode-canvas-wrapper">
          <canvas class="qrcode-canvas" canvas-id="myQrcode"></canvas>
        </view>
        <view class="guide-section">
          <image src="/static/img/zhiyin.jpg" class="guide-image" mode="aspectFit" />
        </view>
        <button class="close-btn" @tap="closeModal">关闭</button>
      </view>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http-legacy'
import dayjs from 'dayjs'
import drawQrcode from '@/utils/weapp.qrcode.min.js'
import { ORDER_STATUS_LIST, ORDER_SORT_LIST } from '@/utils/constants'
import { getOrderStatusName, getPayTypeName, getRoomTypeName, combineDateTime } from '@/utils/format'
import listMixin from '@/mixins/listMixin'

export default {
  mixins: [listMixin],
  data() {
    return {
      appName: '',
      statusBarHeight: 0,
      titleBarHeight: 0,
      option1: ORDER_STATUS_LIST,
      option2: [
        { text: "默认排序", value: "0" },
        { text: "下单时间", value: "1" },
        { text: "预约时间", value: "2" },
      ],
      value1: -1,
      value2: "0",
      statusText: "全部状态",
      columnText: "默认排序",
      statusDropdownShow: false,
      columnDropdownShow: false,
      cancelOrderShow: false,
      cancelOrderSuccess: false,
      status: "",
      orderColumn: "",
      orderlist: [],
      isLogin: false,
      orderInfo: {},
      showModal: false,
    }
  },

  onLoad(options) {
    const app = getApp()
    this.appName = app.globalData.appName || '自助棋牌'
    const systemInfo = uni.getSystemInfoSync()
    this.statusBarHeight = systemInfo.statusBarHeight || 0
    this.titleBarHeight = 44
  },

  onShow() {
    const app = getApp()
    this.isLogin = app.globalData.isLogin || false
    
    if (app.globalData.isLogin) {
      this.getOrderListdata("refresh")
    }
  },

  onPullDownRefresh() {
    this.orderlist = []
    this.canLoadMore = true
    this.pageNo = 1
    this.getOrderListdata("refresh")
    uni.stopPullDownRefresh()
  },

  onReachBottom() {
    if (this.canLoadMore) {
      this.pageNo++
      this.getOrderListdata("")
    } else {
      uni.showToast({
        title: "我是有底线的...",
        icon: 'none'
      })
    }
  },

  methods: {
    showStatusDropdown() {
      this.statusDropdownShow = true
    },

    showColumnDropdown() {
      this.columnDropdownShow = true
    },

    selectStatus(item) {
      this.value1 = item.value
      this.statusText = item.text
      this.statusDropdownShow = false
      
      if (item.value == -1) {
        this.status = ""
      } else {
        this.status = item.value
      }
      this.getOrderListdata("refresh")
    },

    selectColumn(item) {
      this.value2 = item.value
      this.columnText = item.text
      this.columnDropdownShow = false
      
      if (item.value == 0) {
        this.orderColumn = ""
      } else if (item.value == 1) {
        this.orderColumn = "createTime"
      } else if (item.value == 2) {
        this.orderColumn = "startTime"
      }
      this.getOrderListdata("refresh")
    },

    openDoor(e) {
      const no = e.currentTarget.dataset.no
      uni.navigateTo({
        url: "/pages/order/detail?orderNo=" + no + "&toPage=true",
      })
    },

    cancelOrder(e) {
      const orderInfo = e.currentTarget.dataset.info
      this.orderInfo = orderInfo
      this.cancelOrderShow = true
    },

    cancelConfirm() {
      const app = getApp()
      if (app.globalData.isLogin) {
        http.request(
          "/member/order/cancelOrder/" + this.orderInfo.order_id,
          "1",
          "post",
          {
            order_id: this.orderInfo.order_id,
          },
          app.globalData.userDatatoken.access_token,
          "",
          (info) => {
            if (info.code == 0) {
              this.cancelOrderShow = false
              this.cancelOrderSuccess = true
              this.getOrderListdata("refresh")
            } else {
              uni.showModal({
                content: info.msg,
                showCancel: false,
              })
            }
          },
          () => {}
        )
      }
    },

    getOrderListdata(e) {
      const app = getApp()
      if (app.globalData.isLogin) {
        let message = ""
        if (e == "refresh") {
          this.orderlist = []
          this.canLoadMore = true
          this.pageNo = 1
          message = "获取中..."
        }
        
        http.request(
          "/member/order/getOrderPage",
          "1",
          "post",
          {
            "pageNo": this.pageNo,
            "pageSize": 10,
            "status": this.status,
            "orderColumn": this.orderColumn
          },
          app.globalData.userDatatoken.access_token,
          message,
          (info) => {
            if (info.code == 0) {
              if (info.data.list.length === 0) {
                this.canLoadMore = false
              } else {
                const newList = info.data.list.map((el) => {
                  el.full_time = this.combineDateTime(el.start_time, el.end_time)
                  return el
                })
                
                if (this.orderlist.length > 0) {
                  this.orderlist = this.orderlist.concat(newList)
                } else {
                  this.orderlist = newList
                }
                
                this.canLoadMore = this.orderlist.length < info.data.total
              }
            } else {
              uni.showModal({
                content: info.msg,
                showCancel: false,
              })
            }
          },
          () => {}
        )
      }
    },

    phone() {
      uni.navigateTo({
        url: '/pages/user/login',
      })
    },

    copyOrderNo(e) {
      uni.setClipboardData({
        data: e.currentTarget.dataset.order,
        success: () => {
          uni.showToast({ title: "订单号已复制" })
        },
      })
    },

    goReview(e) {
      const { id, store, room } = e.currentTarget.dataset
      uni.navigateTo({
        url: `/pages/order/review?order_id=${id}&store_name=${encodeURIComponent(store || '')}&room_name=${encodeURIComponent(room || '')}`
      })
    },

    goBooking() {
      uni.switchTab({
        url: '/pages/door/index'
      })
    },

    combineDateTime,

    showOrderQr(e) {
      const orderNo = e.currentTarget.dataset.no
      this.showModal = true
      this.$nextTick(() => {
        // 使用 weapp.qrcode 生成二维码
        drawQrcode({
          width: 200,
          height: 200,
          canvasId: 'myQrcode',
          text: orderNo,
          _this: this
        })
      })
    },

    closeModal() {
      this.showModal = false
    },
  }
}
</script>

<style lang="scss">
.page {
  min-height: 100vh;
  background: #F7F7F7;
  padding-bottom: 148rpx;
}
</style>
<style lang="scss" scoped>

.tabs {
  width: 100%;
  height: 90rpx;
  background: #fff;
  border-bottom: 1rpx solid #ddd;
}

.dropdown-menu {
  display: flex;
  height: 100%;
}

.dropdown-item {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 30rpx;
  color: #333;
}

.dropdown-list {
  background: #fff;
  padding: 20rpx 0;
}

.dropdown-list-item {
  padding: 20rpx 40rpx;
  font-size: 30rpx;
  color: #333;
}

.dropdown-list-item.active {
  color: var(--main-color, #6da773fd);
  font-weight: 600;
}

.lists {
  padding: 10rpx 26rpx;
}

.lists .item {
  background: #fff;
  border-radius: 17rpx;
  margin: 10rpx;
  position: relative;
  overflow: hidden;
}

.lists .item .top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 17rpx 20rpx;
  border-bottom: 2rpx solid #E3E3E3;
}

.lists .item .left {
  display: flex;
  align-items: center;
  font-size: 31rpx;
  font-weight: 500;
}

.lists .item .left image {
  width: 35rpx;
  height: 32rpx;
  margin-right: 20rpx;
}

.lists .item .left .text {
  width: 400rpx;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.lists .item .right {
  text-decoration-line: underline;
  font-weight: 500;
  font-size: 26rpx;
  color: #817F7F;
}

.lists .item .highlight {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 6rpx 30rpx;
}

.lists .item .highlight .left image {
  width: 32rpx;
  height: 32rpx;
}

.lists .item .highlight .left {
  display: flex;
  align-items: center;
  font-size: 31rpx;
  font-weight: 500;
}

.lists .item .highlight .right {
  font-weight: 300;
  font-size: 32rpx;
  color: #FFA313;
  text-decoration-line: none !important;
}

.lists .item .highlight text {
  font-weight: 600;
  font-size: 32rpx;
}

.lists .item .info-line {
  font-weight: 300;
  font-size: 28rpx;
  color: #000000;
  display: flex;
  align-items: flex-end;
  padding: 5rpx 25rpx;
}

.lists .item .info-line.display-space {
  display: flex;
  justify-content: space-between;
}

.lists .item .bold {
  font-weight: 500;
  font-size: 28rpx;
}

.lists .discount-tag {
  position: relative;
}

.lists .discount-tag image {
  width: 350rpx;
  height: 84rpx;
}

.lists .discount-tag view {
  position: absolute;
  left: 29%;
  top: 38%;
  font-weight: 600;
  font-size: 26rpx;
  color: #fff;
}

.lists .discount-tag view text {
  color: #F73F4C;
}

.lists .item .status-tag {
  width: 108rpx;
  height: 108rpx;
  position: absolute;
  top: 46%;
  right: 35rpx;
  align-items: center;
  text-align: center;
}

.notes {
  width: 100%;
  font-weight: 500;
  font-size: 21rpx;
  color: var(--main-color, #5AAB6E);
  text-align: center;
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: 20rpx;
}

.notes image {
  width: 30rpx;
  height: 30rpx;
  margin-right: 12rpx;
}

.containerlogin {
  padding: 0 60rpx;
}

.photo {
  padding: 60rpx 0;
  text-align: center;
}

.photo .img {
  width: 200rpx;
  height: 200rpx;
  border-radius: 100%;
  margin: 0 auto;
  overflow: hidden;
}

.photo .img image {
  width: 100%;
  height: 100%;
}

.photo .name {
  margin-top: 20rpx;
  font-size: 32rpx;
}

.loginBtn {
  margin-top: 60rpx;
  border-radius: 50rpx;
  font-size: 28rpx;
  height: 90rpx;
  line-height: 90rpx;
  background: var(--main-color, #6da773fd);
  color: #fff;
}

.dialog {
  padding: 30rpx;
  background: #fff;
  border-radius: 17rpx;
}

.dialog-title {
  font-size: 36rpx;
  font-weight: 600;
  text-align: center;
  margin-bottom: 30rpx;
}

.dialog .item {
  display: flex;
  font-size: 28rpx;
  line-height: 40rpx;
  padding: 10rpx 0;
}

.dialog .item label {
  color: #666;
  flex-shrink: 0;
}

.dialog .item text {
  font-weight: 600;
  font-size: 26rpx;
}

.dialog .item .note {
  font-size: 24rpx;
  color: #ff4757;
}

.dialog-btns {
  display: flex;
  justify-content: space-around;
  margin-top: 30rpx;
}

.dialog-btns .btn {
  width: 200rpx;
  height: 70rpx;
  line-height: 70rpx;
  text-align: center;
  border-radius: 35rpx;
  background: #BCBCBC;
  color: #fff;
}

.dialog-btns .btn.active {
  background: var(--main-color, #6da773fd);
}

.modal-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 999;
  background: linear-gradient(to bottom right, rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.65));
  display: flex;
  justify-content: center;
  align-items: center;
  backdrop-filter: blur(10px);
}

.modal-content {
  width: 92%;
  background: linear-gradient(180deg, #ffffff 0%, #f3f3f5 100%);
  border-radius: 32rpx;
  box-shadow: 0 12rpx 30rpx rgba(0, 0, 0, 0.2);
  padding: 10rpx 10rpx 20rpx;
  text-align: center;
  animation: fadeIn 0.3s ease-in-out;
}

.modal-title {
  font-size: 38rpx;
  font-weight: 600;
  color: #fff;
  background-color: var(--main-color, #6da773fd);
  border-radius: 15rpx;
  line-height: 38rpx;
  margin-bottom: 40rpx;
  padding: 20rpx 0rpx;
  text-align: center;
}

.qrcode-canvas-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  padding: 5rpx;
}

.qrcode-canvas {
  height: 200px;
  width: 200px;
}

.guide-image {
  margin: 0 auto;
  display: block;
}

.close-btn {
  width: 70%;
  height: 88rpx;
  line-height: 88rpx;
  font-size: 30rpx;
  font-weight: 600;
  color: white;
  background-color: var(--main-color, #6da773fd);
  border: none;
  border-radius: 44rpx;
  box-shadow: 0 8rpx 18rpx var(--main-color, #6da773fd);
  margin: 0 auto;
  display: block;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: scale(.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.review-btn {
  background: var(--main-color, #5AAB6E);
  color: #fff;
  font-size: 24rpx;
  padding: 8rpx 24rpx;
  border-radius: 24rpx;
}

.reviewed-tag {
  color: #999;
  font-size: 24rpx;
}

/* 空状态样式 */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 100rpx 40rpx;
}
.empty-icon {
  width: 200rpx;
  height: 200rpx;
  margin-bottom: 30rpx;
}
.empty-text {
  font-size: 32rpx;
  color: #666;
  margin-bottom: 16rpx;
}
.empty-tip {
  font-size: 26rpx;
  color: #999;
  margin-bottom: 30rpx;
}
.empty-btn {
  width: 240rpx;
  height: 80rpx;
  line-height: 80rpx;
  background: var(--main-color, #5AAB6E);
  color: #fff;
  font-size: 28rpx;
  border-radius: 40rpx;
}
.empty-btn-hover {
  opacity: 0.8;
}
</style>
