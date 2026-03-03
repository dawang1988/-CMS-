<template>
  <view class="page" :style="themeStyle">
    <view class="container">
      <!-- 订单信息 -->
      <view class="order-info">
        <view class="info-item">
          <text class="label">所属门店</text>
          <text class="value">{{ orderInfo.store_name }}</text>
        </view>
        <view class="info-item">
          <text class="label">当前场地</text>
          <text class="value">{{ orderInfo.room_name }}</text>
        </view>
        <view class="info-item">
          <text class="label">开始时间</text>
          <text class="value">{{ orderInfo.start_time }}</text>
        </view>
        <view class="info-item">
          <text class="label">结束时间</text>
          <text class="value">{{ orderInfo.end_time }}</text>
        </view>
      </view>

      <!-- 场地列表 -->
      <view class="lists">
        <view class="title border-primary">场地列表</view>
        <view class="doors" v-if="doorList.length > 0">
          <view 
            class="door" 
            v-for="(item, index) in doorList" 
            :key="index"
            :class="{
              'disabled': isDisabled(item),
              'bg-primary': item.room_id === roomId
            }"
            @tap="choose(item)"
          >
            <view class="name">
              {{ item.room_name }}（{{ getRoomTypeName(item.type) }}）
            </view>
            <view class="current" v-if="item.room_id === orderInfo.room_id">
              <image :src="userinfo.avatar || '/static/logo.png'" mode="widthFix" />
            </view>
            <view class="status" v-else-if="item.room_id === roomId">当前选中</view>
            <view class="status" v-else>{{ getStatusText(item.status) }}</view>
          </view>
        </view>
        <view class="empty" v-else>
          <text>暂无可用场地</text>
        </view>
      </view>

      <!-- 客服提示 -->
      <view class="note" @tap="call">
        <text class="iconfont icon-kefu"></text>
        <text>如有疑问请点击联系客服</text>
      </view>

      <!-- 提交按钮 -->
      <view class="submit bg-primary" @tap="submit">确定更换</view>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http-legacy'

export default {
  data() {
    return {
      userinfo: {},
      orderInfo: {},
      doorList: [],
      room_id: '',
      room_type: 0,
      isManager: false
    }
  },

  onLoad(options) {
    if (options.orderInfo) {
      try {
        this.orderInfo = JSON.parse(decodeURIComponent(options.orderInfo))
        this.room_type = this.orderInfo.room_type || 0
        this.checkUserType()
        this.getuserinfo()
        this.getDoorList()
      } catch (e) {
        uni.showToast({
          title: '参数错误',
          icon: 'none'
        })
      }
    }
  },

  methods: {
    checkUserType() {
      var that = this
      var app = getApp()
      if (!app.globalData.isLogin) return
      http.request(
        '/member/user/get',
        '1',
        'get',
        {},
        app.globalData.userDatatoken ? app.globalData.userDatatoken.access_token : '',
        '',
        function success(info) {
          if (info.code == 0 && info.data) {
            var ut = info.data.user_type || 11
            that.isManager = (ut == 12 || ut == 13)
            // 管理员需要重新获取房间列表（不限制房间类型）
            if (that.isManager) {
              that.getDoorList()
            }
          }
        },
        function fail() {}
      )
    },

    // 获取用户信息
    getuserinfo() {
      const that = this
      const app = getApp()
      if (!app.globalData.isLogin) return

      http.request(
        '/member/user/get',
        '1',
        'get',
        {},
        app.globalData.userDatatoken ? app.globalData.userDatatoken.access_token : '',
        '',
        function success(res) {
          if (res.code === 0) {
            that.userinfo = res.data || {}
          }
        },
        function fail(err) {
        }
      )
    },

    // 获取房间列表
    getDoorList() {
      const that = this
      const app = getApp()

      var params = {
        store_id: that.orderInfo.store_id
      }
      if (!that.isManager) {
        params.roomClass = that.orderInfo.room_class
      }

      http.request(
        '/member/index/getRoomInfoList',
        '1',
        'post',
        params,
        app.globalData.userDatatoken ? app.globalData.userDatatoken.access_token : '',
        '获取中...',
        function success(res) {
          if (res.code === 0) {
            that.doorList = res.data || []
          } else {
            uni.showModal({
              content: res.msg || '获取房间列表失败',
              showCancel: false
            })
          }
        },
        function fail(err) {
          uni.showToast({ title: '获取房间列表失败', icon: 'none' })
        }
      )
    },

    // 判断是否禁用
    isDisabled(item) {
      // 当前房间不能选
      if (item.room_id === this.orderInfo.room_id) return true
      // 禁用的房间不能选（status=0或3）
      if (item.status === 0 || item.status === 3) return true
      // 使用中或待清洁的房间不能选
      if (item.status === 2 || item.status === 4) return true
      // 管理员可以选任意空闲房间
      if (this.isManager) return false
      // 普通用户只能选同等级或更低的
      return item.type > this.orderInfo.room_type
    },

    // 选择房间
    choose(item) {
      // 禁用状态无法更换
      if (this.isDisabled(item)) {
        return
      }
      this.room_id = item.room_id
    },

    // 提交更换
    submit() {
      if (!this.room_id) {
        uni.showToast({ title: '请选择更换场地', icon: 'none' })
        return
      }

      const that = this
      const app = getApp()
      if (!app.globalData.isLogin) {
        uni.navigateTo({ url: '/pages/user/login' })
        return
      }

      http.request(
        `/member/order/changeRoom/${that.orderInfo.order_id}/${that.room_id}`,
        '1',
        'post',
        {
          order_id: that.orderInfo.order_id,
          room_id: that.room_id
        },
        app.globalData.userDatatoken ? app.globalData.userDatatoken.access_token : '',
        '更换中...',
        function success(res) {
          if (res.code === 0) {
            uni.showToast({ title: '更换成功', icon: 'success' })
            setTimeout(() => { uni.navigateBack() }, 1000)
          } else {
            uni.showModal({ content: res.msg || '更换失败', showCancel: false })
          }
        },
        function fail(err) {
          uni.showToast({ title: '更换失败', icon: 'none' })
        }
      )
    },

    // 拨打客服电话
    call() {
      if (this.orderInfo.phone && this.orderInfo.phone.length > 0) {
        uni.makePhoneCall({
          phoneNumber: this.orderInfo.phone
        })
      } else {
        uni.showToast({
          title: '暂无客服电话',
          icon: 'none'
        })
      }
    },

    // 获取房间类型名称
    getRoomTypeName(type) {
      const typeMap = {
        1: '小包',
        2: '中包',
        3: '大包',
        4: '豪包',
        5: '商务包',
        6: '斯洛克',
        7: '中式黑八',
        8: '美式球桌'
      }
      return typeMap[type] || '未知'
    },

    // 获取状态文本
    // 数据库定义：0=停用, 1=空闲, 2=使用中, 3=维护中, 4=待清洁/已预约
    getStatusText(status) {
      const statusMap = {
        0: '禁用',
        1: '空闲',
        2: '使用中',
        3: '维护中',
        4: '待清洁'
      }
      return statusMap[status] || '未知'
    }
  }
}
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 200rpx;
}

.container {
  padding-bottom: 100rpx;
}

.order-info {
  background: #fff;
  margin-bottom: 20rpx;
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 30rpx;
  border-bottom: 1rpx solid #f0f0f0;
}

.info-item:last-child {
  border-bottom: none;
}

.label {
  font-size: 28rpx;
  color: #666;
}

.value {
  font-size: 28rpx;
  color: #333;
}

.lists {
  padding: 30rpx 30rpx 0;
}

.lists .title {
  border-width: 0;
  border-left-width: 6rpx;
  border-style: solid;
  padding-left: 10rpx;
  font-size: 30rpx;
  font-weight: 700;
  line-height: 1;
  margin-bottom: 30rpx;
}

.border-primary {
  border-color: var(--main-color, #5AAB6E);
}

.lists .doors {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
}

.lists .doors .door {
  width: 330rpx;
  border: 1rpx solid #3b3a3a;
  border-radius: 20rpx;
  padding: 20rpx;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30rpx;
  background: #fff;
}

.lists .doors .door .name {
  margin-bottom: 10rpx;
  font-size: 32rpx;
  text-align: center;
}

.lists .doors .door .current {
  width: 50rpx;
  height: 50rpx;
  border-radius: 100%;
  border: 1rpx solid #ddd;
  overflow: hidden;
}

.lists .doors .door .current image {
  width: 100%;
  display: block;
}

.lists .doors .door .status {
  line-height: 50rpx;
  font-size: 26rpx;
  color: #666;
}

.lists .doors .disabled {
  background: #f5f5f5;
  color: #999;
  border-color: #ddd;
}

.bg-primary {
  background: var(--main-color, #5AAB6E) !important;
  color: #fff !important;
  border-color: var(--main-color, #5AAB6E) !important;
}

.bg-primary .name,
.bg-primary .status {
  color: #fff !important;
}

.empty {
  text-align: center;
  padding: 60rpx 0;
  color: #999;
  font-size: 28rpx;
}

.note {
  padding: 0 30rpx;
  display: flex;
  align-items: center;
  font-size: 24rpx;
  margin-top: 20rpx;
}

.note text:last-child {
  margin-left: 10rpx;
  color: #999;
  text-decoration: underline;
}

.submit {
  position: fixed;
  bottom: 120rpx;
  left: 3%;
  height: 100rpx;
  width: 94%;
  text-align: center;
  line-height: 100rpx;
  font-size: 30rpx;
  border-radius: 20rpx;
  color: #fff;
  box-shadow: 0 4rpx 12rpx rgba(90, 171, 110, 0.3);
}
</style>
