<template>
  <view class="container" :style="themeStyle">
    <!-- 员工列表 -->
    <view class="list" v-if="list.length > 0">
      <view class="item" v-for="item in list" :key="item.id">
        <view class="top">
          <view class="left">
            <view class="img">
              <image :src="item.avatar || '/static/logo.png'" mode="aspectFill"></image>
            </view>
          </view>
          <view class="right">
            <view class="info">姓名：{{ item.name || item.nickname || '' }}</view>
            <view class="info">手机号：{{ item.phone }}</view>
            <view class="info">当前角色：<text :class="'role-' + item.user_type">{{ getRoleName(item.user_type) }}</text></view>
          </view>
        </view>
        <view class="btns">
          <view class="btn primary" @tap="showPermissionDialog(item)">设置权限</view>
        </view>
      </view>
    </view>

    <!-- 空状态 -->
    <view class="nodata-list" v-else-if="loaded">暂无管理员数据</view>

    <!-- 加载更多 -->
    <view class="noteMore" v-if="canLoadMore && list.length > 0">下拉刷新查看更多...</view>

    <!-- 权限设置弹窗 -->
    <u-popup :show="showDialog" mode="bottom" @close="showDialog = false">
      <view class="dialog">
        <view class="dialog-title">
          <text>权限设置 - {{ currentUser.name || currentUser.nickname }}</text>
          <view class="close-btn" @tap="showDialog = false">✕</view>
        </view>

        <!-- 角色选择 -->
        <view class="section">
          <view class="section-title">角色类型</view>
          <view class="role-list">
            <view class="role-item" :class="{ active: selectedRole == 12 }" @tap="selectedRole = 12">
              <text class="role-name">超管</text>
              <text class="role-desc">拥有所有管理权限</text>
            </view>
            <view class="role-item" :class="{ active: selectedRole == 13 }" @tap="selectedRole = 13">
              <text class="role-name">店长</text>
              <text class="role-desc">拥有门店日常管理权限</text>
            </view>
            <view class="role-item" :class="{ active: selectedRole == 14 }" @tap="selectedRole = 14">
              <text class="role-name">保洁员</text>
              <text class="role-desc">仅保洁任务相关权限</text>
            </view>
          </view>
        </view>

        <!-- 功能权限（超管和店长可配置） -->
        <view class="section" v-if="selectedRole == 12 || selectedRole == 13">
          <view class="section-title">功能权限</view>
          <view class="perm-group" v-for="group in permGroups" :key="group.key">
            <view class="group-title">{{ group.name }}</view>
            <view class="perm-list">
              <view class="perm-item" v-for="perm in group.items" :key="perm.key" @tap="togglePerm(perm.key)">
                <text>{{ perm.name }}</text>
                <switch :checked="selectedPerms.indexOf(perm.key) > -1" color="#5AAB6E" @change="togglePerm(perm.key)" />
              </view>
            </view>
          </view>
        </view>

        <!-- 底部按钮 -->
        <view class="dialog-footer">
          <view class="btn cancel" @tap="showDialog = false">取消</view>
          <view class="btn confirm" @tap="savePermission">保存</view>
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
      store_name: '',
      list: [],
      loaded: false,
      pageNo: 1,
      pageSize: 20,
      canLoadMore: true,
      showDialog: false,
      currentUser: {},
      selectedRole: 12,
      selectedPerms: [],
      permGroups: [
        {
          key: 'store',
          name: '店铺管理',
          items: [
            { key: 'store_info', name: '信息修改' },
            { key: 'room_manage', name: '房间管理' },
            { key: 'notice', name: '公告提醒' },
            { key: 'sound', name: '播报管理' },
            { key: 'template', name: '更换模板' },
            { key: 'statistics', name: '数据统计' },
            { key: 'device', name: '设备管理' }
          ]
        },
        {
          key: 'auth',
          name: '权限管理',
          items: [
            { key: 'admin_manage', name: '员工管理' },
            { key: 'cleaner_manage', name: '保洁员管理' },
            { key: 'vip_blacklist', name: '会员黑名单' }
          ]
        },
        {
          key: 'business',
          name: '经营管理',
          items: [
            { key: 'discount', name: '充值规则' },
            { key: 'package', name: '套餐管理' },
            { key: 'coupon', name: '优惠券' },
            { key: 'product_manage', name: '商品管理' },
            { key: 'product_order', name: '商品订单' },
            { key: 'pay_refund', name: '微信支付退款' }
          ]
        },
        {
          key: 'marketing',
          name: '团购营销',
          items: [
            { key: 'meituan', name: '美团授权' },
            { key: 'douyin', name: '抖音授权' }
          ]
        }
      ]
    }
  },

  onLoad(options) {
    this.store_id = options.store_id || ''
    this.store_name = decodeURIComponent(options.store_name || '')
  },

  onShow() {
    this.getListData('refresh')
  },

  onPullDownRefresh() {
    this.getListData('refresh')
    setTimeout(() => { uni.stopPullDownRefresh() }, 500)
  },

  onReachBottom() {
    if (this.canLoadMore) {
      this.getListData()
    }
  },

  methods: {
    getRoleName(type) {
      const map = { 12: '超管', 13: '店长', 14: '保洁员' }
      return map[type] || '普通用户'
    },

    async getListData(type) {
      if (type === 'refresh') {
        this.list = []
        this.canLoadMore = true
        this.pageNo = 1
      }
      try {
        const res = await http.post('/member/manager/getPermissionList', {
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
        }
      } catch (e) {
      }
      this.loaded = true
    },

    showPermissionDialog(item) {
      this.currentUser = item
      this.selectedRole = item.user_type || 13
      // 解析已有权限
      if (item.permissions) {
        try {
          this.selectedPerms = typeof item.permissions === 'string' ? JSON.parse(item.permissions) : item.permissions
        } catch (e) {
          this.selectedPerms = []
        }
      } else {
        // 默认权限：超管全选，店长部分
        this.selectedPerms = this.getDefaultPerms(this.selectedRole)
      }
      this.showDialog = true
    },

    getDefaultPerms(role) {
      const all = []
      this.permGroups.forEach(g => {
        g.items.forEach(p => { all.push(p.key) })
      })
      if (role == 12) return all
      if (role == 13) {
        // 店长默认去掉：员工管理、数据统计、支付退款
        return all.filter(k => !['admin_manage', 'statistics', 'pay_refund'].includes(k))
      }
      return []
    },

    togglePerm(key) {
      const idx = this.selectedPerms.indexOf(key)
      if (idx > -1) {
        this.selectedPerms.splice(idx, 1)
      } else {
        this.selectedPerms.push(key)
      }
    },

    async savePermission() {
      try {
        const res = await http.post('/member/manager/savePermission', {
          store_id: this.store_id,
          user_id: this.currentUser.id,
          user_type: this.selectedRole,
          permissions: JSON.stringify(this.selectedPerms)
        })
        if (res.code === 0) {
          uni.showToast({ title: '保存成功', icon: 'success' })
          this.showDialog = false
          this.getListData('refresh')
        } else {
          uni.showModal({ content: res.msg, showCancel: false })
        }
      } catch (e) {
        uni.showToast({ title: e.msg || '保存失败', icon: 'none' })
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 40rpx;
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
  .img {
    width: 100rpx;
    height: 100rpx;
    border-radius: 50%;
    overflow: hidden;
    image { width: 100%; height: 100%; }
  }
}
.right {
  flex: 1;
  .info {
    font-size: 28rpx;
    color: #333;
    margin-bottom: 8rpx;
  }
}
.role-12 { color: #f5222d; font-weight: bold; }
.role-13 { color: #fa8c16; font-weight: bold; }
.role-14 { color: #1890ff; font-weight: bold; }
.btns {
  display: flex;
  justify-content: flex-end;
  margin-top: 16rpx;
  padding-top: 16rpx;
  border-top: 1rpx solid #f0f0f0;
  .btn {
    padding: 12rpx 40rpx;
    border-radius: 8rpx;
    font-size: 26rpx;
    &.primary { background: var(--main-color, #5AAB6E); color: #fff; }
  }
}
.dialog {
  padding: 30rpx;
  max-height: 70vh;
  overflow-y: auto;
}
.dialog-title {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 34rpx;
  font-weight: bold;
  margin-bottom: 30rpx;
  padding-bottom: 20rpx;
  border-bottom: 1rpx solid #f0f0f0;
  .close-btn {
    font-size: 36rpx;
    color: #999;
    padding: 10rpx;
  }
}
.section {
  margin-bottom: 30rpx;
}
.section-title {
  font-size: 30rpx;
  font-weight: bold;
  color: #333;
  margin-bottom: 16rpx;
}
.role-list {
  display: flex;
  gap: 16rpx;
}
.role-item {
  flex: 1;
  padding: 20rpx;
  border: 2rpx solid #e0e0e0;
  border-radius: 12rpx;
  text-align: center;
  &.active {
    border-color: var(--main-color, #5AAB6E);
    background: #f0faf2;
  }
  .role-name {
    display: block;
    font-size: 28rpx;
    font-weight: bold;
    color: #333;
    margin-bottom: 6rpx;
  }
  .role-desc {
    display: block;
    font-size: 22rpx;
    color: #999;
  }
}
.perm-group {
  margin-bottom: 20rpx;
}
.group-title {
  font-size: 26rpx;
  color: #666;
  margin-bottom: 10rpx;
  padding-left: 10rpx;
  border-left: 6rpx solid #5AAB6E;
}
.perm-list {
  background: #fafafa;
  border-radius: 12rpx;
  padding: 0 20rpx;
}
.perm-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 18rpx 0;
  border-bottom: 1rpx solid #f0f0f0;
  font-size: 28rpx;
  color: #333;
  &:last-child { border-bottom: none; }
}
.dialog-footer {
  display: flex;
  gap: 20rpx;
  margin-top: 30rpx;
  .btn {
    flex: 1;
    height: 80rpx;
    line-height: 80rpx;
    text-align: center;
    border-radius: 40rpx;
    font-size: 30rpx;
    &.cancel { background: #f5f5f5; color: #666; }
    &.confirm { background: var(--main-color, #5AAB6E); color: #fff; }
  }
}
</style>
