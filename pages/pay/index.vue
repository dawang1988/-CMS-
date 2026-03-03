<template>
  <view class="page" :style="themeStyle">
    <!-- 商品列表 -->
    <view class="goods-box">
      <view class="box-title">商品清单</view>
      <view class="goods-list">
        <view 
          class="goods-item" 
          v-for="(item, index) in showItems" 
          :key="index"
        >
          <image class="item-img" :src="item.image" mode="aspectFill" />
          <view class="item-info">
            <view class="item-name">{{ item.name }}</view>
            <view class="item-price">¥{{ item.price }}</view>
          </view>
          <view class="item-control">
            <text style="font-size:32rpx;color:#999;" @click="handleCartItemReduce(index)">-</text>
            <view class="item-num">{{ item.number }}</view>
            <text style="font-size:32rpx;color:#5677fc;" @click="handleCartItemAdd(index)">+</text>
          </view>
        </view>
      </view>
      
      <!-- 展开/收起 -->
      <view class="show-more" v-if="payCart.length > 2" @click="showMore">
        <text>{{ isShowAll ? '收起' : `还有${payCart.length - 2}件商品` }}</text>
        <text style="font-size:24rpx;">{{ isShowAll ? '▲' : '▼' }}</text>
      </view>
    </view>

    <!-- 配送信息 -->
    <view class="delivery-box">
      <view class="box-title">配送信息</view>
      <view class="cell-group">
        <view class="cell-item" @click="showRoomPicker = true">
          <text class="cell-title">配送房间</text>
          <view class="cell-value">
            <text class="cell-text">{{ selectedRoom }}</text>
            <text class="cell-arrow">›</text>
          </view>
        </view>
      </view>
      <view class="mark-box">
        <view class="mark-label">订单备注</view>
        <textarea v-model="mark" placeholder="请输入备注信息（选填）" maxlength="200" class="mark-textarea" />
      </view>
    </view>

    <!-- 支付方式 -->
    <view class="pay-type-box">
      <view class="box-title">支付方式</view>
      <view class="pay-type-list">
        <view class="pay-type-item" @click="payType = 2">
          <view class="pay-type-left">
            <text class="pay-type-icon">💰</text>
            <text class="pay-type-name">余额支付</text>
            <text class="pay-type-balance">（余额：¥{{ userBalance }}）</text>
          </view>
          <view :class="['pay-type-radio', { active: payType === 2 }]"></view>
        </view>
        <view class="pay-type-item" @click="payType = 1">
          <view class="pay-type-left">
            <text class="pay-type-icon">💳</text>
            <text class="pay-type-name">微信支付</text>
          </view>
          <view :class="['pay-type-radio', { active: payType === 1 }]"></view>
        </view>
      </view>
    </view>

    <!-- 价格明细 -->
    <view class="price-box">
      <view class="price-item">
        <text class="label">商品数量</text>
        <text class="value">{{ productNum }}件</text>
      </view>
      <view class="price-item total">
        <text class="label">合计</text>
        <text class="value">¥{{ cartTotalPrice }}</text>
      </view>
    </view>

    <!-- 底部支付栏 -->
    <view class="pay-bar">
      <view class="pay-info">
        <text class="pay-label">实付款：</text>
        <text class="pay-price">¥{{ cartTotalPrice }}</text>
      </view>
      <view class="pay-btn" @click="topay">
        立即支付
      </view>
    </view>

    <!-- 房间选择器 -->
    <u-popup :show="showRoomPicker" mode="bottom" @close="showRoomPicker = false">
      <view class="picker-box">
        <view class="picker-header">
          <text @click="showRoomPicker = false">取消</text>
          <text class="picker-title">选择房间</text>
          <text class="picker-confirm" @click="showRoomPicker = false">确定</text>
        </view>
        <scroll-view scroll-y class="picker-list">
          <view class="picker-item" :class="{ active: selectedRoomIndex === index }" v-for="(item, index) in roomColumns[0]" :key="index" @click="onRoomConfirm([index])">
            {{ item }}
          </view>
        </scroll-view>
      </view>
    </u-popup>
  </view>
</template>

<script>
import http from '@/utils/http.js'

export default {
  data() {
    return {
      store_id: '',
      payCart: [],
      roomList: [],
      roomIndex: '',
      selectedRoomIndex: -1,
      room_id: '',
      cartTotalPrice: 0,
      showItems: [],
      isShowAll: false,
      mark: '',
      productNum: 0,
      order_no: '',
      showRoomPicker: false,
      payType: 2,
      userBalance: '0.00'
    }
  },
  computed: {
    selectedRoom() {
      return this.roomList[this.roomIndex]?.text || '请选择房间'
    },
    roomColumns() {
      return [this.roomList.map(item => item.text)]
    }
  },
  onLoad(options) {
    this.store_id = options.store_id || options.storeId || ''
    let cart = uni.getStorageSync('payCart') || []
    this.payCart = cart
    this.showItems = cart.slice(0, 2)
    
    let num = 0
    cart.forEach(item => {
      num += item.number
    })
    this.productNum = num
  },
  onShow() {
    this.getRoomList()
    this.calculateCartTotalPrice()
    this.getUserBalance()
  },
  methods: {
    // 获取用户余额
    async getUserBalance() {
      try {
        const res = await http.get('/member/user/info')
        if (res.code === 0 && res.data) {
          const b = parseFloat(res.data.balance || 0) + parseFloat(res.data.gift_balance || 0)
          this.userBalance = b.toFixed(2)
        }
      } catch (e) {}
    },

    // 计算总价
    calculateCartTotalPrice() {
      let totalPrice = this.payCart.reduce((acc, cur) => acc + cur.number * cur.price, 0)
      this.cartTotalPrice = parseFloat(totalPrice).toFixed(2)
    },

    // 展开/收起
    showMore() {
      this.isShowAll = !this.isShowAll
      this.showItems = this.isShowAll ? this.payCart : this.payCart.slice(0, 2)
    },

    // 获取房间列表
    async getRoomList() {
      if (!this.store_id) return
      try {
        const res = await http.get(`/member/index/getRoomList/${this.store_id}`, {
          store_id: this.store_id
        })
        if (res.code === 0 && res.data) {
          const list = Array.isArray(res.data) ? res.data : (res.data.list || [])
          this.roomList = [
            { text: '请选择房间', value: '' },
            ...list.map(it => ({ text: it.name || it.room_name || it.key, value: it.id || it.value }))
          ]
        }
      } catch (e) {
        console.error('获取房间列表失败', e)
      }
    },

    // 房间选择确认
    onRoomConfirm(value) {
      const idx = value[0]
      this.selectedRoomIndex = idx
      this.roomIndex = idx
      this.showRoomPicker = false
    },

    // 减少商品
    handleCartItemReduce(index) {
      if (this.payCart[index].number === 1) {
        this.payCart.splice(index, 1)
      } else {
        this.payCart[index].number--
      }
      
      if (this.payCart.length === 0) {
        const pages = getCurrentPages()
        if (pages.length > 1) {
          uni.navigateBack()
        } else {
          uni.switchTab({ url: '/pages/door/index' })
        }
      }
      
      this.showItems = this.isShowAll ? this.payCart : this.payCart.slice(0, 2)
      this.calculateCartTotalPrice()
      this.updateProductNum()
    },

    // 增加商品
    handleCartItemAdd(index) {
      this.payCart[index].number++
      this.showItems = this.isShowAll ? this.payCart : this.payCart.slice(0, 2)
      this.calculateCartTotalPrice()
      this.updateProductNum()
    },

    // 更新商品数量
    updateProductNum() {
      let num = 0
      this.payCart.forEach(item => {
        num += item.number
      })
      this.productNum = num
    },

    // 支付
    async topay() {
      if (this.payCart.length <= 0) {
        uni.showToast({
          title: '非法操作，无购买商品',
          icon: 'none'
        })
        return
      }
      
      if (!this.roomIndex || this.roomIndex === 0) {
        uni.showToast({
          title: '请选择下单房间',
          icon: 'none'
        })
        return
      }

      try {
        uni.showLoading({ title: '努力加载中' })
        const res = await http.post('/product/order/create', {
          productInfo: this.payCart,
          store_id: this.store_id,
          room_id: this.roomList[this.roomIndex].value,
          mark: this.mark,
          pay_type: this.payType
        })
        
        uni.hideLoading()
        const orderData = res.data || res
        this.order_no = orderData.order_no

        if (orderData.paid) {
          // 余额支付成功
          uni.showToast({ title: '支付成功', icon: 'success' })
          uni.removeStorageSync('payCart')
          // 更新购物车
          this.updateLocalCart()
          setTimeout(() => {
            uni.navigateTo({ url: '/pages/product/order' })
          }, 1500)
          return
        }
        
        if (orderData.timeStamp) {
          // 微信支付
          this.payMent(orderData)
        } else {
          // 订单已创建但未支付（微信支付未配置等），跳转订单列表
          uni.showToast({ title: '订单已创建，请在订单列表中支付', icon: 'none' })
          uni.removeStorageSync('payCart')
          this.updateLocalCart()
          setTimeout(() => {
            uni.navigateTo({ url: '/pages/product/order' })
          }, 1500)
        }
      } catch (e) {
        uni.hideLoading()
        uni.showToast({
          title: e.msg || '下单失败',
          icon: 'none'
        })
      }
    },

    // 更新本地购物车（下单后清除已购买的商品）
    updateLocalCart() {
      let pay = this.payCart
      let cart = uni.getStorageSync('cart') || []
      let newcart = []
      
      for (let i = 0; i < cart.length; i++) {
        let found = false
        for (let j = 0; j < pay.length; j++) {
          if (cart[i].id == pay[j].id) {
            found = true
            let num = cart[i].number - pay[j].number
            if (num > 0) {
              cart[i].number = num
              newcart.push(cart[i])
            }
            break
          }
        }
        if (!found) {
          newcart.push(cart[i])
        }
      }
      uni.setStorageSync('cart', newcart)
    },

    // 微信支付
    payMent(pay) {
      uni.requestPayment({
        timeStamp: pay.timeStamp,
        nonceStr: pay.nonceStr,
        package: pay.pkg,
        signType: pay.signType,
        paySign: pay.paySign,
        success: () => {
          uni.navigateTo({
            url: '/pages/product/order'
          })
        },
        fail: () => {
          uni.showToast({
            title: '支付失败!',
            icon: 'none'
          })
          const pages = getCurrentPages()
          if (pages.length > 1) {
            uni.navigateBack()
          } else {
            uni.switchTab({ url: '/pages/door/index' })
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
  padding-bottom: 120rpx;
}

.pay-type-box {
  background: #fff;
  margin-bottom: 20rpx;
}

.pay-type-list {
  padding: 0 32rpx;
}

.pay-type-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 28rpx 0;
  border-bottom: 1rpx solid #f5f5f5;

  &:last-child {
    border-bottom: none;
  }
}

.pay-type-left {
  display: flex;
  align-items: center;

  .pay-type-icon {
    font-size: 36rpx;
    margin-right: 16rpx;
  }

  .pay-type-name {
    font-size: 28rpx;
    color: #333;
  }

  .pay-type-balance {
    font-size: 24rpx;
    color: #999;
    margin-left: 8rpx;
  }
}

.pay-type-radio {
  width: 36rpx;
  height: 36rpx;
  border-radius: 50%;
  border: 2rpx solid #ccc;
  box-sizing: border-box;

  &.active {
    border: 10rpx solid #5AAB6E;
  }
}

.goods-box,
.delivery-box,
.pay-type-box,
.price-box {
  background: #fff;
  margin-bottom: 20rpx;
}

.box-title {
  padding: 24rpx 32rpx;
  font-size: 28rpx;
  font-weight: bold;
  color: #333;
  border-bottom: 1rpx solid #f5f5f5;
}

.goods-list {
  padding: 0 32rpx;
}

.goods-item {
  display: flex;
  align-items: center;
  padding: 24rpx 0;
  border-bottom: 1rpx solid #f5f5f5;

  &:last-child {
    border-bottom: none;
  }

  .item-img {
    width: 120rpx;
    height: 120rpx;
    border-radius: 12rpx;
    margin-right: 20rpx;
  }

  .item-info {
    flex: 1;

    .item-name {
      font-size: 28rpx;
      color: #333;
      margin-bottom: 12rpx;
    }

    .item-price {
      font-size: 28rpx;
      color: #ff6b00;
    }
  }

  .item-control {
    display: flex;
    align-items: center;
    gap: 16rpx;

    .item-num {
      min-width: 40rpx;
      text-align: center;
      font-size: 26rpx;
      color: #333;
    }
  }
}

.show-more {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24rpx;
  font-size: 26rpx;
  color: #999;
  gap: 8rpx;
}

.price-box {
  padding: 24rpx 32rpx;

  .price-item {
    display: flex;
    justify-content: space-between;
    font-size: 26rpx;
    color: #666;
    line-height: 2;

    &.total {
      font-size: 32rpx;
      font-weight: bold;
      color: #333;
      margin-top: 12rpx;
      padding-top: 12rpx;
      border-top: 1rpx solid #f5f5f5;

      .value {
        color: #ff6b00;
      }
    }
  }
}

.mark-box {
  padding: 24rpx 32rpx;
  
  .mark-label {
    font-size: 28rpx;
    color: #333;
    margin-bottom: 16rpx;
  }

  .mark-textarea {
    width: 100%;
    height: 120rpx;
    font-size: 26rpx;
    color: #333;
    box-sizing: border-box;
  }
}

.cell-group {
  padding: 0 32rpx;
}

.cell-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24rpx 0;
  border-bottom: 1rpx solid #f5f5f5;

  .cell-title {
    font-size: 28rpx;
    color: #333;
    flex-shrink: 0;
  }

  .cell-value {
    display: flex;
    align-items: center;
    
    .cell-text {
      font-size: 28rpx;
      color: #999;
      margin-right: 8rpx;
    }

    .cell-arrow {
      font-size: 32rpx;
      color: #ccc;
    }
  }
}

.pay-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  align-items: center;
  height: 100rpx;
  padding: 0 32rpx;
  background: #fff;
  box-shadow: 0 -2rpx 10rpx rgba(0, 0, 0, 0.05);
  z-index: 100;

  .pay-info {
    flex: 1;

    .pay-label {
      font-size: 26rpx;
      color: #666;
    }

    .pay-price {
      font-size: 36rpx;
      font-weight: bold;
      color: #ff6b00;
    }
  }

  .pay-btn {
    padding: 16rpx 48rpx;
    background: $u-primary;
    color: #fff;
    border-radius: 40rpx;
    font-size: 28rpx;
  }
}

.picker-box { background: #fff; border-radius: 20rpx 20rpx 0 0; }
.picker-header { display: flex; justify-content: space-between; align-items: center; padding: 24rpx 32rpx; border-bottom: 1rpx solid #f5f5f5; font-size: 28rpx; color: #999; }
.picker-title { font-size: 30rpx; color: #333; font-weight: bold; }
.picker-confirm { color: var(--main-color, #5AAB6E); }
.picker-list { max-height: 500rpx; }
.picker-item { padding: 24rpx 32rpx; font-size: 28rpx; color: #333; border-bottom: 1rpx solid #f5f5f5; }
.picker-item.active { color: var(--main-color, #5AAB6E); font-weight: bold; }
</style>
