<template>
  <view class="container" :style="themeStyle">
    <!-- 列表 -->
    <view class="list" v-if="list.length > 0">
      <view class="item" v-for="item in list" :key="item.user_id">
        <view class="top">
          <view class="left">
            <view class="img">
              <image :src="item.avatar || '/static/logo.png'" mode="aspectFill"></image>
            </view>
            <view class="nick">{{ item.nickname }}</view>
          </view>
          <view class="right">
            <view class="info-box">
              <view class="info">门店：{{ item.store_name }}</view>
              <view class="info">姓名：{{ item.name }}</view>
              <view class="info">手机号：{{ item.phone }}</view>
              <view class="info">总收入：{{ item.total_money || 0 }}元</view>
              <view class="info">已完成：{{ item.finish_count || 0 }}单,已结算：{{ item.settlement_count || 0 }}单</view>
            </view>
            <view class="btns">
              <view class="btn green" @tap="taskSettle(item)">任务结算</view>
              <view class="btn red" @tap="deleteCleaner(item)">删除</view>
            </view>
          </view>
        </view>
      </view>
    </view>
    
    <!-- 空状态 -->
    <view class="nodata-list" v-else>暂无数据</view>
    
    <!-- 加载更多提示 -->
    <view class="noteMore" v-if="canLoadMore && list.length > 0">下拉刷新查看更多...</view>
    
    <!-- 底部按钮 -->
    <view class="bottom" @tap="showAddDialog">添加保洁员</view>
    
    <!-- 添加弹窗 -->
    <u-popup :show="show" mode="center" @close="show = false">
      <view class="dialog">
        <view class="dialog-title">添加保洁员</view>
        <view class="dialog-content">
          <view class="field">
            <label>门店：</label>
            <text>{{ storeName }}</text>
          </view>
          <view class="field">
            <label>手机号：</label>
            <input v-model="mobile" type="number" placeholder="输入手机号搜索" @input="onMobileInput" />
          </view>
          <!-- 搜索结果 -->
          <view class="search-result" v-if="searchUser">
            <view class="user-card">
              <image class="user-avatar" :src="searchUser.avatar || '/static/logo.png'" mode="aspectFill"></image>
              <view class="user-info">
                <view class="user-nick">{{ searchUser.nickname || '未设置昵称' }}</view>
                <view class="user-phone">{{ searchUser.phone }}</view>
              </view>
              <view class="user-check">✓</view>
            </view>
          </view>
          <view class="search-tip" v-else-if="searchDone && !searchUser">
            <text class="tip-text">未找到该手机号用户，请先让对方在小程序注册</text>
          </view>
          <view class="field" v-if="searchUser">
            <label>备注名：</label>
            <input v-model="name" :placeholder="searchUser.nickname || '可选'" />
          </view>
        </view>
        <view class="dialog-btns">
          <view class="btn cancel" @tap="cancel">取消</view>
          <view class="btn confirm" :class="{ disabled: !searchUser }" @tap="submit">确认</view>
        </view>
      </view>
    </u-popup>
  </view>
</template>

<script>
import http from '@/utils/http'

export default {
  data() {
    return {
      store_id: '',
      storeName: '',
      show: false,
      pageNo: 1,
      pageSize: 10,
      canLoadMore: true,
      list: [],
      mobile: '',
      name: '',
      searchUser: null,
      searchDone: false,
      searchTimer: null
    }
  },

  onLoad(options) {
    this.store_id = options.store_id || ''
    this.storeName = decodeURIComponent(options.store_name || '')
  },

  onShow() {
    this.getListData('refresh')
  },

  onPullDownRefresh() {
    this.pageNo = 1
    this.canLoadMore = true
    this.list = []
    this.getListData('refresh')
    setTimeout(() => {
      uni.stopPullDownRefresh()
    }, 500)
  },

  onReachBottom() {
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
    // 获取列表数据
    async getListData(type) {
      if (type === 'refresh') {
        this.list = []
        this.canLoadMore = true
        this.pageNo = 1
      }

      try {
        const res = await http.post('/member/manager/getClearUserPage', {
          pageNo: this.pageNo,
          pageSize: this.pageSize,
          store_id: this.store_id
        })

        if (res.code === 0) {
          const data = res.data || {}
          if (data.list && data.list.length > 0) {
            this.list = [...this.list, ...data.list]
            this.pageNo++
            this.canLoadMore = this.list.length < data.total
          } else {
            this.canLoadMore = false
          }
        } else {
          uni.showModal({
            content: res.msg,
            showCancel: false
          })
        }
      } catch (e) {
      }
    },

    // 删除保洁员
    deleteCleaner(item) {
      uni.showModal({
        title: '提示',
        content: `员工在${item.store_name}完成的任务需全部结算后才允许删除！删除后该员工将无法在此门店接单！请确认！`,
        confirmText: '确认删除',
        success: async (res) => {
          if (res.confirm) {
            try {
              const result = await http.post(`/member/manager/deleteClearUser/${item.store_id}/${item.user_id}`, {
                store_id: item.store_id,
                user_id: item.user_id
              })
              
              if (result.code === 0) {
                uni.showToast({
                  title: '删除成功',
                  icon: 'success'
                })
                this.getListData('refresh')
              } else {
                uni.showModal({
                  content: result.msg,
                  showCancel: false
                })
              }
            } catch (e) {
              uni.showModal({
                content: e.msg || '删除失败',
                showCancel: false
              })
            }
          }
        }
      })
    },

    // 显示添加弹窗
    showAddDialog() {
      this.show = true
      this.mobile = ''
      this.name = ''
      this.searchUser = null
      this.searchDone = false
    },

    // 手机号输入时搜索
    onMobileInput() {
      this.searchUser = null
      this.searchDone = false
      if (this.searchTimer) clearTimeout(this.searchTimer)
      if (this.mobile.length < 11) return
      this.searchTimer = setTimeout(() => {
        this.searchByMobile()
      }, 300)
    },

    // 按手机号搜索用户
    async searchByMobile() {
      if (!this.mobile || this.mobile.length < 11) return
      try {
        const res = await http.post('/member/manager/searchUserByPhone', {
          phone: this.mobile
        })
        this.searchDone = true
        if (res.code === 0 && res.data) {
          this.searchUser = res.data
          this.name = res.data.nickname || ''
        } else {
          this.searchUser = null
        }
      } catch (e) {
        this.searchDone = true
        this.searchUser = null
      }
    },

    // 提交
    async submit() {
      if (!this.searchUser) {
        uni.showToast({ title: '请先搜索并选择用户', icon: 'none' })
        return
      }

      try {
        const res = await http.post('/member/manager/saveClearUser', {
          store_id: this.store_id,
          name: this.name || this.searchUser.nickname || '',
          mobile: this.mobile
        })

        if (res.code === 0) {
          uni.showToast({ title: '保存成功', icon: 'success' })
          this.show = false
          this.getListData('refresh')
        } else {
          uni.showModal({ content: res.msg, showCancel: false })
        }
      } catch (e) {
        uni.showModal({ content: '请求服务异常，请稍后重试', showCancel: false })
      }
    },

    // 取消
    cancel() {
      this.show = false
      this.name = ''
      this.mobile = ''
    },

    // 去结算
    taskSettle(item) {
      uni.navigateTo({
        url: `/pagesA/task/settle?info=${encodeURIComponent(JSON.stringify(item))}`
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 120rpx;
}

.list {
  padding: 20rpx;
}

.item {
  background: #fff;
  border-radius: 16rpx;
  padding: 24rpx;
  margin-bottom: 20rpx;
}

.top {
  display: flex;
}

.left {
  margin-right: 20rpx;
  text-align: center;
  
  .img {
    width: 100rpx;
    height: 100rpx;
    border-radius: 50%;
    overflow: hidden;
    margin-bottom: 10rpx;
    
    image {
      width: 100%;
      height: 100%;
    }
  }
  
  .nick {
    font-size: 24rpx;
    color: #666;
    max-width: 120rpx;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}

.right {
  flex: 1;
  
  .info-box {
    margin-bottom: 20rpx;
  }
  
  .info {
    font-size: 26rpx;
    color: #333;
    margin-bottom: 8rpx;
  }
  
  .btns {
    display: flex;
    gap: 20rpx;
    
    .btn {
      padding: 12rpx 24rpx;
      border-radius: 8rpx;
      font-size: 26rpx;
      
      &.green {
        background: #52c41a;
        color: #fff;
      }
      
      &.red {
        background: #ff4d4f;
        color: #fff;
      }
    }
  }
}

.bottom {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 100rpx;
  line-height: 100rpx;
  text-align: center;
  background: var(--main-color, #5AAB6E);
  color: #fff;
  font-size: 32rpx;
}

// 弹窗样式
.dialog {
  width: 600rpx;
  padding: 40rpx;
}

.dialog-title {
  font-size: 34rpx;
  font-weight: bold;
  text-align: center;
  margin-bottom: 30rpx;
}

.dialog-content {
  margin-bottom: 30rpx;
}

.field {
  display: flex;
  align-items: center;
  padding: 20rpx 0;
  border-bottom: 1rpx solid #f0f0f0;
  
  label {
    width: 160rpx;
    font-size: 28rpx;
    color: #333;
  }
  
  input {
    flex: 1;
    font-size: 28rpx;
  }
  
  text {
    flex: 1;
    font-size: 28rpx;
    color: #666;
  }
}

.dialog-btns {
  display: flex;
  gap: 20rpx;
  
  .btn {
    flex: 1;
    height: 80rpx;
    line-height: 80rpx;
    text-align: center;
    border-radius: 40rpx;
    font-size: 30rpx;
    
    &.cancel {
      background: #f5f5f5;
      color: #666;
    }
    
    &.confirm {
      background: var(--main-color, #5AAB6E);
      color: #fff;
    }
    
    &.disabled {
      opacity: 0.5;
    }
  }
}

.search-result {
  padding: 20rpx 0;
}

.user-card {
  display: flex;
  align-items: center;
  background: #f0faf3;
  border: 2rpx solid var(--main-color, #5AAB6E);
  border-radius: 12rpx;
  padding: 20rpx;
}

.user-avatar {
  width: 80rpx;
  height: 80rpx;
  border-radius: 50%;
  margin-right: 16rpx;
}

.user-info {
  flex: 1;
}

.user-nick {
  font-size: 28rpx;
  color: #333;
  font-weight: 500;
}

.user-phone {
  font-size: 24rpx;
  color: #999;
  margin-top: 4rpx;
}

.user-check {
  color: var(--main-color, #5AAB6E);
  font-size: 36rpx;
  font-weight: bold;
}

.search-tip {
  padding: 20rpx 0;
  
  .tip-text {
    font-size: 26rpx;
    color: #ff4d4f;
  }
}
</style>
