<template>
  <view class="container" :style="themeStyle">
    <view class="tabs">
      <view
        v-for="(item, index) in tabs"
        :key="index"
        :class="['tab', { 'active border-primary color-primary': currentTab === item.id }]"
        @tap="tabChange(item.id)"
      >{{ item.name }}</view>
    </view>

    <view class="coupon-wrapper" v-if="currentTab === 'center'">
      <view v-if="couponList.length > 0">
        <view class="coupon-active-card" v-for="(item, idx) in couponList" :key="idx">
          <view :class="['card-left', item.type == 2 ? 'bg-primary2' : 'bg-primary1']">
            <view class="card-price">
              <text v-if="item.type == 2">￥</text>{{ item.amount }}{{ item.type == 2 ? '元' : '小时' }}
            </view>
            <view class="card-condition">满{{ item.min_amount }}{{ item.type == 2 ? '元' : '小时' }}可用</view>
          </view>
          <view class="card-right">
            <view class="card-name">{{ item.name }}</view>
            <view class="card-info">{{ getCouponType(item.type) }}</view>
            <view class="card-info">适用门店：{{ item.store_name || '不限制' }}</view>
            <view class="card-info">有效期至：{{ formatDate(item.end_time) }}</view>
            <view class="card-stock">剩余 {{ (item.total || 0) - (item.received || 0) }} 张</view>
          </view>
          <view class="card-btn-wrap">
            <button class="card-btn" :class="{ 'card-btn-disabled': item.claimed }" :disabled="item.claimed" @tap="receiveCoupon(item)">{{ item.claimed ? '已领取' : '领取' }}</button>
          </view>
        </view>
      </view>
      <view class="no-coupon" v-else>
        <image src="/static/logo.png" class="coupon-icon" mode="aspectFit"></image>
        <view class="text">暂无可领取的优惠券</view>
      </view>
    </view>

    <view class="coupon-wrapper" v-if="currentTab !== 'center' && MainList.length > 0">
      <view v-if="currentTab === 0 && from" class="nouse" @tap="chooseNone">
        <text class="name">不使用优惠券</text>
      </view>
      <view v-for="(item, index) in MainList" :key="index" :class="['coupon', { 'disable': !item.enable }]" @tap="chooseCoupon(item, index)">
        <view class="tipInfo" v-if="showTipInfo(item)">
          <view class="box">
            <view class="label">{{ getTipLabel(item) }}</view>
          </view>
        </view>
        <view :class="['left', item.type == 2 ? 'bg-primary2' : 'bg-primary1']">
          <view class="info">
            <view class="price">
              <text v-if="item.type == 2">￥</text>{{ item.amount }}{{ item.type == 2 ? '元' : '小时' }}
            </view>
            <view class="desc">满{{ item.min_amount }}{{ item.type == 2 ? '元' : '小时' }}可用</view>
          </view>
        </view>
        <view class="right">
          <view class="name">{{ item.name }}</view>
          <view class="line"><text class="lbl">类型：</text>{{ getCouponType(item.type) }}</view>
          <view class="line"><text class="lbl">适用门店：</text>{{ item.store_name || '不限制门店' }}</view>
          <view class="line"><text class="lbl">适用包间：</text>{{ getRoomClass(item.room_class) }}</view>
          <view class="line"><text class="lbl">过期时间：</text>{{ formatDate(item.end_time) }}</view>
        </view>
      </view>
    </view>

    <view class="no-coupon" v-if="currentTab !== 'center' && MainList.length === 0">
      <image src="/static/logo.png" class="coupon-icon" mode="aspectFit"></image>
      <view class="text">暂无卡券</view>
      <view class="empty-tip" v-if="currentTab === 0">快去领券中心领取优惠券吧</view>
      <button class="empty-btn" v-if="currentTab === 0" hover-class="empty-btn-hover" @tap="tabChange('center')">去领券</button>
    </view>
  </view>
</template>
<script>
import http from '@/utils/http'
import { formatDate, getCouponTypeName, getRoomClassName } from '@/utils/format'
import listMixin from '@/mixins/listMixin'

export default {
  mixins: [listMixin],
  data() {
    return {
      tabs: [
        { id: 'center', name: '领券中心' },
        { id: 0, name: '未使用' },
        { id: 1, name: '已使用' },
        { id: 2, name: '已过期' }
      ],
      currentTab: 'center',
      MainList: [],
      couponList: [],
      from: false,
      room_id: '',
      coupon_id: '',
      submit_order_hour: 0,
      nightLong: false,
      start_time: '',
      end_time: ''
    }
  },
  onLoad(options) {
    this.from = options.from || false
    if (options.room_id) {
      this.room_id = options.room_id
      this.submit_order_hour = options.orderHours
      this.nightLong = options.nightLong
      this.start_time = options.start_time
      this.end_time = options.end_time
    }
    if (this.from) {
      this.currentTab = 0
      this.getListData('refresh')
    } else {
      this.getCouponCenter()
    }
  },
  onPullDownRefresh() {
    if (this.currentTab === 'center') {
      this.getCouponCenter()
    } else {
      this.refreshList()
      this.getListData('refresh')
    }
    setTimeout(function() { uni.stopPullDownRefresh() }, 500)
  },
  onReachBottom() {
    if (this.currentTab !== 'center' && this.canLoadMore) {
      this.getListData()
    }
  },
  methods: {
    formatDate,
    tabChange(id) {
      this.currentTab = id
      if (id === 'center') {
        this.getCouponCenter()
      } else {
        this.pageNo = 1
        this.MainList = []
        this.canLoadMore = true
        this.getListData('refresh')
      }
    },
    getCouponCenter() {
      var that = this
      http.get('/member/coupon/group-list', { pageNo: 1, pageSize: 50 }).then(function(res) {
        if (res.code === 0) {
          var list = res.data.list || res.data || []
          http.get('/member/coupon/list', { status: '' }).then(function(myRes) {
            var claimedIds = []
            if (myRes.code === 0) {
              var myList = myRes.data || []
              for (var i = 0; i < myList.length; i++) {
                claimedIds.push(myList[i].coupon_id)
              }
            }
            that.couponList = list.map(function(item) {
              item.claimed = claimedIds.indexOf(item.id) > -1
              return item
            })
          }).catch(function() {
            that.couponList = list.map(function(item) {
              item.claimed = false
              return item
            })
          })
        }
      }).catch(function(e) {
        console.error('获取领券中心失败', e)
      })
    },
    receiveCoupon(item) {
      if (item.claimed) return
      var token = uni.getStorageSync('token')
      if (!token) {
        uni.showToast({ title: '请先登录', icon: 'none' })
        return
      }
      var that = this
      http.post('/member/couponActive/putCoupon', { coupon_id: item.id }).then(function(res) {
        if (res.code === 0) {
          uni.showToast({ title: '领取成功', icon: 'success' })
          that.getCouponCenter()
        } else {
          uni.showToast({ title: res.msg || '领取失败', icon: 'none' })
        }
      }).catch(function(e) {
        uni.showToast({ title: (e && e.msg) || '领取失败', icon: 'none' })
        that.getCouponCenter()
      })
    },
    getListData(type) {
      var token = uni.getStorageSync('token')
      if (!token) {
        uni.showModal({ content: '请您先登录，再重试！', showCancel: false })
        return
      }
      if (type === 'refresh') {
        this.pageNo = 1
        this.MainList = []
      }
      var that = this
      http.request({
        url: '/member/user/getCouponPage',
        method: 'POST',
        data: {
          pageNo: that.pageNo,
          pageSize: 10,
          status: that.currentTab,
          room_id: that.room_id,
          nightLong: that.nightLong,
          start_time: that.start_time,
          end_time: that.end_time
        }
      }).then(function(res) {
        if (res.code === 0) {
          var list = res.data.list || []
          if (list.length === 0) {
            that.canLoadMore = false
          } else {
            that.MainList = that.MainList.concat(list)
            that.pageNo++
            that.canLoadMore = that.MainList.length < res.data.total
          }
        }
      }).catch(function() {})
    },
    chooseNone() {
      if (this.from) {
        uni.$emit('selectCoupon', null)
        uni.navigateBack()
      }
    },
    chooseCoupon(item, index) {
      if (!item.enable) return
      if (!this.from) return
      this.coupon_id = item.coupon_id
      var that = this
      setTimeout(function() {
        uni.$emit('selectCoupon', item)
        uni.navigateBack()
      }, 100)
    },
    getCouponType: getCouponTypeName,
    getRoomClass: getRoomClassName,
    showTipInfo(item) {
      if (!this.from) return false
      if (item.status !== 0) return true
      if (!item.enable) return true
      return false
    },
    getTipLabel(item) {
      if (item.status === 1) return '已使用'
      if (item.status === 2) return '已过期'
      if (!item.enable) return '不符合使用条件'
      return ''
    }
  }
}
</script>
<style lang="scss">
page { background: #f5f5f5; }
</style>
<style lang="scss" scoped>
.container { padding: 110rpx 30rpx 30rpx; }
.tabs {
  position: fixed; z-index: 9; top: 0; left: 0;
  width: 100%; height: 80rpx; background: #fff;
  display: flex; justify-content: space-around;
  padding: 0 20rpx; box-sizing: border-box;
}
.tab { line-height: 80rpx; text-align: center; font-size: 28rpx; color: #666; padding: 0 10rpx; }
.tab.active { border-width: 0; border-bottom-width: 4rpx; border-style: solid; font-weight: bold; }
.coupon-active-card {
  display: flex; align-items: center; background: #fff; border-radius: 20rpx;
  overflow: hidden; margin-bottom: 24rpx; box-shadow: 0 2rpx 12rpx rgba(0,0,0,0.06);
}
.card-left {
  width: 200rpx; min-height: 200rpx; display: flex; flex-direction: column;
  align-items: center; justify-content: center; color: #fff; position: relative;
}
.card-left::before, .card-left::after {
  content: ""; display: block; width: 30rpx; height: 30rpx;
  border-radius: 100%; background: #f5f5f5; position: absolute; right: -15rpx;
}
.card-left::before { top: -15rpx; }
.card-left::after { bottom: -15rpx; }
.card-price { font-size: 40rpx; font-weight: 700; }
.card-price text { font-size: 28rpx; }
.card-condition { font-size: 22rpx; margin-top: 8rpx; opacity: 0.9; }
.card-right { flex: 1; padding: 20rpx 16rpx; }
.card-name { font-size: 28rpx; color: #333; font-weight: bold; margin-bottom: 10rpx; }
.card-info { font-size: 22rpx; color: #999; line-height: 1.8; }
.card-stock { font-size: 22rpx; color: #ff6b00; margin-top: 6rpx; }
.card-btn-wrap { padding: 0 20rpx; }
.card-btn {
  width: 120rpx; height: 60rpx; line-height: 60rpx; font-size: 24rpx;
  border-radius: 30rpx; background: var(--main-color, #5AAB6E); color: #fff; text-align: center; padding: 0;
}
.card-btn-disabled { background: #ccc !important; color: #fff; }
.coupon-wrapper { padding-bottom: 30rpx; }
.nouse {
  display: flex; justify-content: space-between; align-items: center;
  font-size: 30rpx; background: #fff; border-radius: 20rpx; padding: 30rpx; margin-bottom: 30rpx;
}
.nouse .name { color: #000; }
.coupon {
  background: #fff; border-radius: 20rpx; display: flex; justify-content: space-between;
  overflow: hidden; margin-bottom: 30rpx; position: relative;
}
.coupon.disable { opacity: 0.6; }
.coupon .left { width: 220rpx; position: relative; color: #fff; }
.coupon .left::before, .coupon .left::after {
  content: ""; display: block; width: 30rpx; height: 30rpx;
  border-radius: 100%; background: #f5f5f5; position: absolute; right: -15rpx;
}
.coupon .left::before { top: -15rpx; }
.coupon .left::after { bottom: -15rpx; }
.bg-primary1 { background: linear-gradient(135deg, var(--main-color, #6da773) 0%, var(--main-color, #5AAB6E) 100%); }
.bg-primary2 { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%); }
.coupon .left .info {
  width: 100%; padding: 0 20rpx; box-sizing: border-box;
  position: absolute; left: 0; top: 50%; transform: translateY(-50%); text-align: center;
}
.coupon .left .info .price { font-size: 40rpx; font-weight: 700; }
.coupon .left .info .price text { font-size: 28rpx; }
.coupon .left .info .desc { font-size: 24rpx; margin-top: 10rpx; opacity: 0.9; }
.coupon .right { flex: 1; box-sizing: border-box; padding: 20rpx 30rpx; position: relative; }
.coupon .right .name { font-size: 30rpx; color: #000; font-weight: bold; margin-bottom: 20rpx; }
.coupon .right .line {
  font-size: 24rpx; color: #999; margin-top: 10rpx;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.coupon .right .line .lbl { color: #666; }
.tipInfo {
  position: absolute; width: 100%; height: 100%; z-index: 1;
  text-align: center; font-size: 24rpx; background: rgba(0,0,0,0.6); color: #fff; border-radius: 20rpx;
}
.tipInfo .label { font-size: 40rpx; margin-bottom: 20rpx; font-weight: bold; }
.tipInfo .box { width: 100%; position: absolute; top: 50%; transform: translateY(-50%); }
.no-coupon {
  display: flex; justify-content: center; align-items: center;
  flex-direction: column; margin-top: 200rpx;
}
.no-coupon .text { color: #848484; font-size: 28rpx; margin-top: 20rpx; }
.coupon-icon { width: 160rpx; height: 160rpx; }
.empty-tip { color: #999; font-size: 24rpx; margin-top: 16rpx; }
.empty-btn { 
  margin-top: 30rpx; 
  width: 200rpx; 
  height: 70rpx; 
  line-height: 70rpx; 
  background: var(--main-color, #5AAB6E); 
  color: #fff; 
  font-size: 28rpx; 
  border-radius: 35rpx; 
}
.empty-btn-hover { opacity: 0.8; }
.color-primary { color: var(--main-color, #5AAB6E); }
.border-primary { border-color: var(--main-color, #5AAB6E); }
</style>
