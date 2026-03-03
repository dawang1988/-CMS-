<template>
  <view class="container" :style="themeStyle">
    <!-- 余额信息卡片 -->
    <view class="balance-card">
      <view class="balance-row">
        <view class="balance-item">
          <text class="balance-label">账户余额(元)</text>
          <text class="balance-value">{{ balance }}</text>
        </view>
      </view>
    </view>

    <!-- 日期搜索 -->
    <view class="search" @tap="goSearch">
      <text class="iconfont icon-rili"></text>
      <input type="text" placeholder="开始时间" disabled :value="sdt" />
      <text>至</text>
      <input type="text" placeholder="结束时间" disabled :value="edt" />
    </view>

    <!-- 明细列表 -->
    <view class="list">
      <view class="title">
        <view class="border bg-primary"></view>
        余额使用明细
      </view>

      <block v-if="MainList.length > 0">
        <view class="item" v-for="(item, index) in MainList" :key="index">
          <view class="line">
            <label>门店：</label>
            <label v-if="item.remark">{{ item.remark }}</label>
            <label v-else>-</label>
          </view>
          <view class="line">
            <label>时间：</label>
            <text>{{ item.create_time }}</text>
          </view>
          <view class="line">
            <label>类型：</label>
            <text v-if="item.type === 6">管理员清空</text>
            <text v-else-if="item.type === 5">管理员赠送</text>
            <text v-else-if="item.type === 4">订单退款</text>
            <text v-else-if="item.type === 3">订单支付</text>
            <text v-else-if="item.type === 2">充值赠送</text>
            <text v-else-if="item.type === 1">在线充值</text>
            <text v-else>其他</text>
          </view>
          <view class="line">
            <label>备注：</label>
            <text>{{ item.remark }}</text>
          </view>
          <view class="tip">
            <text>余额:{{ item.balance_after || 0 }}元</text>
          </view>
          <view class="total" :class="item.type !== 3 ? 'color-primary' : ''">
            <view class="price">{{ getSign(item) }}{{ Math.abs(item.amount) }}元</view>
            <view>账户余额</view>
          </view>
        </view>

        <view class="noteMore" v-if="canLoadMore">上拉查看更多...</view>
      </block>

      <view class="nodata-list" v-else>暂无数据</view>
    </view>

    <view class="footer"></view>

    <!-- 日期选择弹窗 -->
    <uni-popup ref="calendarPopup" type="bottom">
      <view class="calendar-box">
        <view class="calendar-header">
          <text @tap="onClose">取消</text>
          <text class="cal-title">选择日期范围</text>
          <text @tap="onConfirm">确定</text>
        </view>
        <uni-calendar
          :insert="true"
          :lunar="false"
          :range="true"
          @change="onDateChange"
        />
      </view>
    </uni-popup>
  </view>
</template>

<script>
import http from '@/utils/http'
import { getBalanceTypeName, formatDate } from '@/utils/format'
import listMixin from '@/mixins/listMixin'

export default {
  mixins: [listMixin],
  data: function() {
    return {
      balance: '0.00',
      sdt: '',
      edt: '',
      MainList: [],
      startDate: '',
      endDate: ''
    }
  },

  onLoad: function() {
    this.getListData('refresh')
  },

  onPullDownRefresh: function() {
    var that = this
    that.pageNo = 1
    that.canLoadMore = true
    that.MainList = []
    that.getListData('refresh')
    setTimeout(function() {
      uni.stopPullDownRefresh()
    }, 500)
  },

  onReachBottom: function() {
    if (this.canLoadMore) {
      this.getListData()
    } else {
      uni.showToast({
        title: '我是有底线的...',
        icon: 'none'
      })
    }
  },

  methods: {
    getSign: function(item) {
      return item.type === 3 ? '-' : '+'
    },

    getListData: function(type) {
      var that = this
      var token = uni.getStorageSync('token')
      if (!token) {
        uni.showModal({
          content: '请您先登录，再重试！',
          showCancel: false
        })
        return
      }

      if (type === 'refresh') {
        that.pageNo = 1
        that.MainList = []
      }

      http.post('/member/user/getMoneyBillPage', {
        pageNo: that.pageNo,
        pageSize: 10,
        start_time: that.sdt,
        end_time: that.edt
      }).then(function(res) {
        if (res.code === 0) {
          // 更新余额
          if (res.data.balance !== undefined) {
            that.balance = Number(res.data.balance).toFixed(2)
          }

          var list = res.data.list || []
          if (list.length === 0) {
            that.canLoadMore = false
          } else {
            that.MainList = that.MainList.concat(list)
            that.pageNo++
            that.canLoadMore = that.MainList.length < res.data.total
          }
        } else {
          uni.showModal({
            content: res.msg || '获取失败',
            showCancel: false
          })
        }
      })
    },

    goSearch: function() {
      this.$refs.calendarPopup.open()
    },

    onClose: function() {
      this.$refs.calendarPopup.close()
    },

    onDateChange: function(e) {
      if (e.range) {
        this.startDate = e.range.before
        this.endDate = e.range.after
      }
    },

    onConfirm: function() {
      if (this.startDate && this.endDate) {
        this.sdt = this.formatDate(this.startDate)
        this.edt = this.formatDate(this.endDate)
        this.getListData('refresh')
      }
      this.onClose()
    },

    formatDate: function(date) {
      if (!date) return ''
      var d = new Date(date)
      var year = d.getFullYear()
      var month = (d.getMonth() + 1).toString()
      var day = d.getDate().toString()
      if (month.length < 2) month = '0' + month
      if (day.length < 2) day = '0' + day
      return year + '-' + month + '-' + day
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  min-height: 100vh;
  padding: 18rpx 16rpx 30rpx 16rpx;
  background: #f5f5f5;
}

.balance-card {
  background: linear-gradient(135deg, var(--main-color, #5AAB6E), #3d8b52);
  border-radius: 16rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
  color: #fff;
}

.balance-row {
  display: flex;
  justify-content: center;
  align-items: center;
}

.balance-item {
  text-align: center;
}

.balance-label {
  font-size: 26rpx;
  opacity: 0.85;
  display: block;
  margin-bottom: 10rpx;
}

.balance-value {
  font-size: 56rpx;
  font-weight: bold;
  display: block;
}

.search {
  display: flex;
  border-radius: 16rpx;
  align-items: center;
  padding: 0 30rpx;
  background-color: #fff;
}

.search input {
  height: 70rpx;
  line-height: 70rpx;
  width: 250rpx;
  text-align: center;
  font-size: 26rpx;
}

.search text {
  font-size: 26rpx;
  color: #666;
}

.list {
  margin-top: 20rpx;
  background-color: #fff;
  padding: 34rpx 20rpx 20rpx 20rpx;
  border-radius: 16rpx;
}

.list .title {
  line-height: 1;
  font-size: 30rpx;
  font-weight: 600;
  padding-bottom: 20rpx;
  display: flex;
  align-items: center;
}

.list .title .border {
  width: 6rpx;
  height: 30rpx;
  margin-right: 10rpx;
}

.bg-primary {
  background: var(--main-color, #5AAB6E);
}

.item {
  border-bottom: 1rpx solid #f2f2f2;
  padding: 20rpx 0;
  position: relative;
}

.item:last-of-type {
  border-bottom: none;
}

.item .line {
  font-size: 24rpx;
  margin-bottom: 10rpx;
  line-height: 36rpx;
  display: flex;
  width: 520rpx;
}

.item .line label {
  color: #666;
  flex-shrink: 0;
}

.item .total {
  position: absolute;
  top: 20rpx;
  right: 0rpx;
  font-size: 24rpx;
  text-align: center;
  color: #F73F4C;
}

.item .total.color-primary {
  color: var(--main-color, #5AAB6E);
}

.item .total .price {
  font-size: 30rpx;
  font-weight: 600;
  margin-bottom: 10rpx;
}

.item .tip {
  text-align: right;
  color: #999;
  font-size: 24rpx;
  position: absolute;
  bottom: 30rpx;
  right: 0rpx;
}

.item .tip text {
  margin-left: 20rpx;
}

.noteMore {
  text-align: center;
  padding: 20rpx;
  color: #999;
  font-size: 24rpx;
}

.nodata-list {
  text-align: center;
  padding: 60rpx 0;
  color: #999;
  font-size: 28rpx;
}

.footer {
  height: 180rpx;
}

.calendar-box {
  background: #fff;
  border-radius: 20rpx 20rpx 0 0;
}

.calendar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 30rpx;
  border-bottom: 1rpx solid #f0f0f0;
}

.calendar-header text {
  font-size: 28rpx;
  color: #666;
}

.cal-title {
  font-size: 32rpx;
  font-weight: bold;
  color: #333;
}
</style>
