<template>
  <view class="container" :style="themeStyle">
    <!-- 表头 -->
    <view class="head">
      <view class="cell1">门店名称</view>
      <view class="cell2">账户余额</view>
      <view class="cell2">赠送余额</view>
    </view>

    <!-- 列表 -->
    <view class="list">
      <block v-if="MainList.length > 0">
        <view class="item" v-for="(item, index) in MainList" :key="index">
          <view class="name">{{ item.store_name }}</view>
          <view class="price">
            <text class="color-attention">{{ item.balance }}</text>元
          </view>
          <view class="price">
            <text class="color-attention">{{ item.gift_balance }}</text>元
          </view>
        </view>
      </block>
      <block v-else>
        <view class="nodata-list">暂无数据</view>
      </block>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http'

export default {
  data: function() {
    return {
      userinfo: {},
      MainList: []
    }
  },

  onShow: function() {
    this.getData()
  },

  onPullDownRefresh: function() {
    this.getData()
  },

  methods: {
    getData: function() {
      var that = this
      var token = uni.getStorageSync('token')
      if (!token) {
        uni.navigateTo({ url: '/pages/user/login' })
        return
      }

      http.get('/member/user/getGiftBalanceList').then(function(res) {
        uni.stopPullDownRefresh()
        if (res.code === 0) {
          that.MainList = res.data || []
        } else {
          uni.showToast({ title: res.msg || '获取失败', icon: 'none' })
        }
      }).catch(function() {
        uni.stopPullDownRefresh()
        uni.showToast({ title: '获取失败', icon: 'none' })
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  padding: 0 20rpx;
  min-height: 100vh;
  background: #f5f5f5;
}

.head {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  font-size: 32rpx;
  text-align: center;
  padding: 5rpx 40rpx 10rpx 10rpx;
  background: linear-gradient(to bottom right, var(--main-color, #5AAB6E), #8ebcf1);
  color: #fff;
  font-weight: bold;
  border-radius: 20rpx;
  margin-top: 20rpx;
}

.cell1 {
  width: 40%;
  padding: 10rpx 0 0 0;
  box-sizing: border-box;
}

.cell2 {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10rpx;
}

.list {
  margin-top: 10rpx;
  border-top: 1rpx solid #ddd;
  background: #fff;
  border-radius: 20rpx;
  overflow: hidden;
}

.list .item {
  border-bottom: 1rpx solid #ddd;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 5rpx 40rpx 10rpx 10rpx;
  font-size: 32rpx;
  text-align: center;
}

.list .item:last-child {
  border-bottom: none;
}

.list .item .name {
  width: 50%;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  text-align: left;
}

.list .item .price {
  width: 200rpx;
  text-align: right;
}

.color-attention {
  color: #ee0a24;
  font-weight: bold;
}

.nodata-list {
  text-align: center;
  padding: 60rpx 0;
  color: #999;
  font-size: 28rpx;
}
</style>
