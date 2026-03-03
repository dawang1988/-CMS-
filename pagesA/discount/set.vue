<template>
  <view class="page" :style="themeStyle">
    <!-- 门店选择 -->
    <view class="tabs">
      <picker :value="storeIndex" :range="stores" range-key="text" @change="storeChange">
        <view class="picker-value">
          {{ stores[storeIndex] ? stores[storeIndex].text : '请选择门店' }}
          <text class="arrow">▼</text>
        </view>
      </picker>
    </view>
    
    <view class="container" :style="isIpx ? 'padding-bottom:168rpx' : 'padding-bottom:120rpx'">
      <!-- 列表 -->
      <view class="list" v-if="list.length">
        <view 
          class="item" 
          :class="item.status === 0 ? 'disabled' : ''"
          v-for="(item, index) in list" 
          :key="index"
        >
          <view class="info">
            <view class="line">
              <label>适用门店：</label>
              <text>{{ item.store_name }}</text>
            </view>
            <view class="line">
              <label>充值金额：</label>
              <text>{{ item.pay_money }}元</text>
            </view>
            <view class="line">
              <label>赠送金额：</label>
              <text>{{ item.gift_money }}元</text>
            </view>
            <view class="line">
              <label>状态：</label>
              <text :class="item.status == 1 ? 'status-on' : 'status-off'">{{ item.status == 1 ? '启用' : '禁用' }}</text>
            </view>
            <view class="line">
              <label>创建时间：</label>
              <text>{{ item.create_time }}</text>
            </view>
            <view class="line">
              <label>过期时间：</label>
              <text>{{ item.end_time }}</text>
            </view>
          </view>
          <view class="btns">
            <button 
              :class="item.status == 1 ? 'btn red' : 'btn green'" 
              @click="setStatus(item)"
            >
              {{ item.status == 1 ? '禁用' : '启用' }}
            </button>
            <button class="btn primary" @click="edit(item)">修改</button>
            <button class="btn danger" @click="deleteRule(item)">删除</button>
          </view>
        </view>
      </view>
      
      <!-- 无数据 -->
      <view class="nodata-list" v-else>暂无数据</view>
      
      <view class="note-more" v-if="list.length">下拉刷新加载更多</view>
    </view>
    
    <!-- 底部按钮 -->
    <view class="bottom" :class="isIpx ? 'fix-iphonex-button' : ''" @click="edit(null)">
      添加规则
    </view>
    
    <!-- 弹窗 -->
    <view class="dialog-mask" v-if="showDialog" @click="closeDialog"></view>
    <view class="dialog" v-if="showDialog">
      <view class="dialog-title">填写规则</view>
      <view class="dialog-content">
        <view class="field">
          <label>适用门店：</label>
          <view class="picker-input disabled">
            {{ stores[storeIndex] ? stores[storeIndex].text : '请先选择门店' }}
          </view>
        </view>
        <view class="field">
          <label>充值金额：</label>
          <input v-model="pay_money" type="digit" placeholder="请输入" />
        </view>
        <view class="field">
          <label>赠送金额：</label>
          <input v-model="gift_money" type="digit" placeholder="请输入" />
        </view>
        <view class="field">
          <label>过期时间：</label>
          <picker mode="date" :value="end_time" @change="dateChange">
            <view class="picker-input">
              {{ end_time || '请选择时间' }}
            </view>
          </picker>
        </view>
        <view class="field">
          <label>状态：</label>
          <picker :value="statusIndex" :range="statusOptions" range-key="text" @change="statusChange">
            <view class="picker-input">
              {{ statusOptions[statusIndex].text }}
            </view>
          </picker>
        </view>
      </view>
      <view class="dialog-btns">
        <button class="btn cancel" @click="cancel">取消</button>
        <button class="btn confirm" @click="submit">确定</button>
      </view>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http.js'

export default {
  data() {
    return {
      store_id: '',
      stores: [],
      storeIndex: 0,
      pageNo: 1,
      pageSize: 10,
      canLoadMore: true,
      list: [],
      showDialog: false,
      discountId: '',
      pay_money: '',
      gift_money: '',
      end_time: '',
      statusIndex: 0,
      statusOptions: [{ text: '启用', value: 1 }, { text: '禁用', value: 0 }],
      isIpx: false
    }
  },
  onLoad(options) {
    const systemInfo = uni.getSystemInfoSync()
    this.isIpx = systemInfo.model && systemInfo.model.indexOf('iPhone X') !== -1
    
    if (options.store_id) {
      this.store_id = Number(options.store_id)
    }
    this.getStoreList()
    this.getListData('refresh')
  },
  onPullDownRefresh() {
    this.pageNo = 1
    this.canLoadMore = true
    this.list = []
    this.getListData('refresh')
    uni.stopPullDownRefresh()
  },
  onReachBottom() {
    if (this.canLoadMore) {
      this.getListData('')
    } else {
      uni.showToast({ title: '我是有底线的...', icon: 'none' })
    }
  },
  methods: {
    async getStoreList() {
      try {
        const res = await http.get('/member/store/getStoreListByAdmin')
        if (res.code === 0 && res.data) {
          this.stores = res.data.map(it => ({ text: it.key, value: it.value }))
          // 找到当前门店的索引
          const index = this.stores.findIndex(it => it.value === this.store_id)
          if (index !== -1) {
            this.storeIndex = index
          }
        }
      } catch (e) {
      }
    },
    
    storeChange(e) {
      this.storeIndex = e.detail.value
      this.store_id = this.stores[this.storeIndex].value
      this.getListData('refresh')
    },
    
    dateChange(e) {
      this.end_time = e.detail.value
    },
    
    statusChange(e) {
      this.statusIndex = e.detail.value
    },
    
    async getListData(type) {
      try {
        if (type === 'refresh') {
          uni.showLoading({ title: '加载中...' })
          this.pageNo = 1
          this.list = []
        }
        
        const res = await http.post('/member/store/getDiscountRulesPage', {
          pageNo: this.pageNo,
          pageSize: this.pageSize,
          store_id: this.store_id || (this.stores[this.storeIndex] ? this.stores[this.storeIndex].value : '')
        })
        
        uni.hideLoading()
        
        if (res.code === 0) {
          if (res.data.list.length === 0) {
            this.canLoadMore = false
          } else {
            this.list = this.list.concat(res.data.list)
            this.pageNo++
            this.canLoadMore = this.list.length < res.data.total
          }
        }
      } catch (e) {
        uni.hideLoading()
      }
    },
    
    async setStatus(item) {
      try {
        const res = await http.post(`/member/store/changeDiscountRulesStatus/${item.id}`)
        if (res.code === 0) {
          uni.showToast({ title: '设置成功', icon: 'success' })
          this.getListData('refresh')
        } else {
          uni.showToast({ title: res.msg || '操作失败', icon: 'none' })
        }
      } catch (e) {
        uni.showToast({ title: e.msg || '操作失败', icon: 'none' })
      }
    },
    
    async edit(item) {
      if (item) {
        this.discountId = item.id
        this.pay_money = String(item.pay_money)
        this.gift_money = String(item.gift_money)
        this.end_time = item.end_time
        this.statusIndex = item.status == 1 ? 0 : 1
      } else {
        this.discountId = ''
        this.pay_money = ''
        this.gift_money = ''
        this.end_time = ''
        this.statusIndex = 0
      }
      this.showDialog = true
    },
    
    closeDialog() {
      this.showDialog = false
    },
    
    cancel() {
      this.pay_money = ''
      this.gift_money = ''
      this.end_time = ''
      this.statusIndex = 0
      this.showDialog = false
    },
    
    async submit() {
      if (!this.stores[this.storeIndex] || !this.pay_money || !this.end_time) {
        uni.showToast({ title: '请填写完整', icon: 'none' })
        return
      }
      
      try {
        uni.showLoading({ title: '保存中...' })
        const res = await http.post('/member/store/saveDiscountRuleDetail', {
          id: this.discountId || '',
          store_id: this.stores[this.storeIndex].value,
          pay_money: this.pay_money,
          gift_money: this.gift_money || '0',
          end_time: this.end_time,
          status: this.statusOptions[this.statusIndex].value
        })
        uni.hideLoading()
        
        if (res.code === 0) {
          uni.showToast({ title: '设置成功', icon: 'success' })
          this.showDialog = false
          this.getListData('refresh')
        } else {
          uni.showToast({ title: res.msg || '保存失败', icon: 'none' })
        }
      } catch (e) {
        uni.hideLoading()
        uni.showToast({ title: e.msg || '保存失败', icon: 'none' })
      }
    },
    
    deleteRule(item) {
      uni.showModal({
        title: '提示',
        content: `确定删除该充值规则？`,
        success: async (res) => {
          if (res.confirm) {
            try {
              const result = await http.post(`/member/store/deleteDiscountRule/${item.id}`)
              if (result.code === 0) {
                uni.showToast({ title: '删除成功' })
                this.getListData('refresh')
              } else {
                uni.showToast({ title: result.msg || '删除失败', icon: 'none' })
              }
            } catch (e) {
              uni.showToast({ title: '删除失败', icon: 'none' })
            }
          }
        }
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background: #f5f5f5;
}

.tabs {
  background: #fff;
  padding: 20rpx 30rpx;
  
  .picker-value {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 28rpx;
    color: #333;
    
    .arrow {
      font-size: 24rpx;
      color: #999;
    }
  }
}

.container {
  padding: 20rpx;
}

.list {
  .item {
    background: #fff;
    border-radius: 12rpx;
    padding: 24rpx;
    margin-bottom: 20rpx;
    
    &.disabled {
      opacity: 0.6;
    }
  }
  
  .info {
    .line {
      display: flex;
      margin-bottom: 16rpx;
      font-size: 28rpx;
      
      label {
        color: #999;
        width: 180rpx;
      }
      
      text {
        color: #333;
        flex: 1;
        
        &.status-on {
          color: #07c160;
        }
        
        &.status-off {
          color: #ff4d4f;
        }
      }
    }
  }
  
  .btns {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 20rpx;
    margin-top: 20rpx;
    padding-top: 20rpx;
    border-top: 1rpx solid #eee;
    
    .btn {
      font-size: 26rpx;
      padding: 12rpx 30rpx;
      border-radius: 8rpx;
      
      &.red {
        background: #fff;
        color: #ff4d4f;
        border: 1rpx solid #ff4d4f;
      }
      
      &.green {
        background: #fff;
        color: #07c160;
        border: 1rpx solid #07c160;
      }
      
      &.primary {
        background: #07c160;
        color: #fff;
      }
      
      &.danger {
        background: #fff;
        color: #ff4d4f;
        border: 1rpx solid #ff4d4f;
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
  background: #07c160;
  color: #fff;
  font-size: 32rpx;
}

.fix-iphonex-button {
  padding-bottom: env(safe-area-inset-bottom);
}

.dialog-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 100;
}

.dialog {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 600rpx;
  background: #fff;
  border-radius: 16rpx;
  z-index: 101;
  overflow: hidden;
  
  .dialog-title {
    text-align: center;
    padding: 30rpx;
    font-size: 32rpx;
    font-weight: bold;
    border-bottom: 1rpx solid #eee;
  }
  
  .dialog-content {
    padding: 30rpx;
    
    .field {
      display: flex;
      align-items: center;
      margin-bottom: 20rpx;
      
      label {
        width: 180rpx;
        font-size: 28rpx;
        color: #333;
      }
      
      input {
        flex: 1;
        height: 70rpx;
        padding: 0 20rpx;
        border: 1rpx solid #ddd;
        border-radius: 8rpx;
        font-size: 28rpx;
      }
      
      .picker-input {
        flex: 1;
        height: 70rpx;
        line-height: 70rpx;
        padding: 0 20rpx;
        border: 1rpx solid #ddd;
        border-radius: 8rpx;
        font-size: 28rpx;
        color: #333;
        
        &.disabled {
          background: #f5f5f5;
          color: #999;
        }
      }
    }
  }
  
  .dialog-btns {
    display: flex;
    border-top: 1rpx solid #eee;
    
    .btn {
      flex: 1;
      height: 90rpx;
      line-height: 90rpx;
      text-align: center;
      font-size: 30rpx;
      border-radius: 0;
      
      &.cancel {
        background: #fff;
        color: #666;
        border-right: 1rpx solid #eee;
      }
      
      &.confirm {
        background: #fff;
        color: #07c160;
      }
    }
  }
}
</style>
