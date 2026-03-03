<template>
  <view class="page" :style="themeStyle">
    <!-- 门店信息 -->
    <view class="store-info" v-if="storeInfo.store_name">
      <view class="store-name">{{ storeInfo.store_name }}</view>
      <view class="store-address">{{ storeInfo.address }}</view>
    </view>

    <!-- 商品列表 -->
    <view class="content">
      <!-- 左侧分类 -->
      <scroll-view class="category-list" scroll-y :style="{ height: scrollHeight }">
        <view 
          class="category-item" 
          :class="{ active: currentCateId === item.id }"
          v-for="item in goods" 
          :key="item.id"
          @click="handleMenuTap(item.id)"
        >
          <view class="cate-name">{{ item.name }}</view>
          <view class="cate-badge" v-if="item.kindNum > 0">{{ item.kindNum }}</view>
        </view>
      </scroll-view>

      <!-- 右侧商品 -->
      <scroll-view class="goods-list" scroll-y :scroll-into-view="menuScrollIntoView" :style="{ height: scrollHeight }">
        <view 
          class="goods-category" 
          v-for="category in goods" 
          :key="category.id"
          :id="`cate-${category.id}`"
        >
          <view class="category-title">{{ category.name }}</view>
          <view class="goods-items">
            <view 
              class="goods-item" 
              v-for="good in category.goodsList" 
              :key="good.id"
              @click="showGoodDetailModal(category, good)"
            >
              <image class="good-img" :src="good.image || '/static/img/default-product.png'" mode="aspectFill" />
              <view class="good-info">
                <view class="good-name">{{ good.store_name }}</view>
                <view class="good-price">
                  <text class="price-symbol">¥</text>
                  <text class="price-value">{{ good.price }}</text>
                </view>
              </view>
              <view class="good-action">
                <view class="cart-num" v-if="good.carNum > 0">{{ good.carNum }}</view>
                <text style="font-size:40rpx;color:#5677fc;">+</text>
              </view>
            </view>
          </view>
        </view>
      </scroll-view>
    </view>

    <!-- 购物车 -->
    <view class="cart-bar" @click="openCartPopup">
      <view class="cart-icon">
        <text style="font-size:40rpx;color:#fff;">🛒</text>
        <view class="cart-badge" v-if="goodNum > 0">{{ goodNum }}</view>
      </view>
      <view class="cart-info">
        <view class="cart-price">¥{{ cartTotalPrice }}</view>
        <view class="cart-desc">配送费另计</view>
      </view>
      <view class="cart-btn" @click.stop="toPay">
        去结算
      </view>
    </view>

    <!-- 商品详情弹窗 -->
    <u-popup :show="goodDetailModalVisible" mode="bottom" @close="closeGoodDetailModal">
      <view class="good-detail">
        <view class="detail-header">
          <image class="detail-img" :src="good.image || '/static/img/default-product.png'" mode="aspectFill" />
          <view class="detail-info">
            <view class="detail-name">{{ good.store_name }}</view>
            <view class="detail-price">
              <text class="price-symbol">¥</text>
              <text class="price-value">{{ good.price }}</text>
            </view>
            <view class="detail-stock">库存：{{ good.stock }}</view>
          </view>
        </view>

        <!-- 规格选择 -->
        <view class="spec-box" v-if="good.productAttr && good.productAttr.length > 0">
          <view class="spec-item" v-for="(attr, index) in good.productAttr" :key="index">
            <view class="spec-name">{{ attr.attrName }}</view>
            <view class="spec-values">
              <view 
                class="spec-value" 
                :class="{ active: newValue[index] === value }"
                v-for="(value, key) in attr.attrValueArr" 
                :key="key"
                @click="changeDefault(index, key)"
              >
                {{ value }}
              </view>
            </view>
          </view>
        </view>

        <!-- 数量选择 -->
        <view class="number-box">
          <view class="number-label">数量</view>
          <view class="number-control">
            <text style="font-size:40rpx;color:#999;" @click="handlePropertyReduce">-</text>
            <view class="number-value">{{ good.number }}</view>
            <text style="font-size:40rpx;color:#5677fc;" @click="handlePropertyAdd">+</text>
          </view>
        </view>

        <!-- 确认按钮 -->
        <view class="detail-footer">
          <button class="detail-add-btn" type="primary" @click="handleAddToCartInModal">
            加入购物车
          </button>
        </view>
      </view>
    </u-popup>

    <!-- 购物车弹窗 -->
    <u-popup :show="popupVisible" mode="bottom" @close="popupVisible = false">
      <view class="cart-popup">
        <view class="popup-header">
          <view class="popup-title">购物车</view>
          <view class="popup-clear" @click="handleCartClear">清空</view>
        </view>
        <scroll-view class="cart-items" scroll-y>
          <view class="cart-item" v-for="(item, index) in cart" :key="index">
            <view class="item-name">{{ item.name }}</view>
            <view class="item-price">¥{{ item.price }}</view>
            <view class="item-control">
              <text style="font-size:32rpx;color:#999;" @click="handleCartItemReduce(index)">-</text>
              <view class="item-num">{{ item.number }}</view>
              <text style="font-size:32rpx;color:#5677fc;" @click="handleCartItemAdd(index)">+</text>
            </view>
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
      storeInfo: {},
      goods: [],
      cart: [],
      currentCateId: 0,
      menuScrollIntoView: '',
      goodDetailModalVisible: false,
      popupVisible: false,
      good: {},
      category: {},
      newValue: [],
      goodNum: 0,
      cartTotalPrice: 0,
      scrollHeight: '600px'
    }
  },
  onLoad(options) {
    this.store_id = options.store_id || options.storeId || ''
    // 计算商品列表可用高度
    const sysInfo = uni.getSystemInfoSync()
    const windowHeight = sysInfo.windowHeight
    // 购物车栏高度约50px
    const cartBarHeight = 50
    this.scrollHeight = (windowHeight - cartBarHeight) + 'px'
  },
  onShow() {
    this.getStoreInfo()
    this.getProductList()
  },
  methods: {
    // 获取门店信息
    async getStoreInfo() {
      if (!this.store_id) return
      try {
        const res = await http.get(`/member/index/getStoreInfo/${this.store_id}`)
        if (res.code === 0) {
          this.storeInfo = res.data || {}
        }
      } catch (e) {
      }
    },

    // 获取商品列表
    async getProductList() {
      try {
        const res = await http.get('/product/store-product/products', {
          store_id: this.store_id,
          pageSize: 200
        })
        
        if (res.code === 0) {
          // 后端已按分类分组返回
          const goods = Array.isArray(res.data) ? res.data : []
          if (goods.length === 0) {
            uni.showToast({ title: '暂无商品', icon: 'none' })
            return
          }
          
          this.goods = goods
          if (this.goods.length > 0) {
            this.currentCateId = this.goods[0].id
          }
          this.refreshCart()
          this.calculateCartTotalPrice()
          this.leftKindNum()
          this.rightGoodNum()
        }
      } catch (e) {
        console.error('获取商品列表失败', e)
        uni.showToast({
          title: '获取失败，请重试',
          icon: 'none'
        })
      }
    },

    // 显示商品详情
    showGoodDetailModal(category, good) {
      this.category = JSON.parse(JSON.stringify(category))
      this.good = JSON.parse(JSON.stringify({ ...good, number: 1 }))
      this.goodDetailModalVisible = true
      if (this.good.productAttr && this.good.productAttr.length > 0) {
        this.changePropertyDefault(0, 0, true)
      }
    },

    // 关闭商品详情
    closeGoodDetailModal() {
      this.goodDetailModalVisible = false
      this.category = {}
      this.good = {}
    },

    // 切换规格
    changeDefault(index, key) {
      this.newValue[index] = this.good.productAttr[index].attrValueArr[key]
      let valueStr = this.newValue.join(',')
      let productValue = this.good.productValue[valueStr]
      
      if (!productValue) {
        let skukey = JSON.parse(JSON.stringify(this.newValue))
        skukey.sort((a, b) => a.localeCompare(b))
        valueStr = skukey.join(',')
        productValue = this.good.productValue[valueStr]
      }
      
      this.good.number = 1
      this.good.price = parseFloat(productValue.price).toFixed(2)
      this.good.stock = productValue.stock
      this.good.image = productValue.image || this.good.value.image
      this.good.valueStr = valueStr
    },

    // 初始化默认规格
    changePropertyDefault(index, key, isDefault) {
      if (isDefault) {
        let newValue = []
        for (let i = 0; i < this.good.productAttr.length; i++) {
          newValue[i] = this.good.productAttr[i].attrValueArr[0]
        }
        this.newValue = newValue
      } else {
        this.newValue[index] = this.good.productAttr[index].attrValueArr[key]
      }
      
      let valueStr = this.newValue.join(',')
      let productValue = this.good.productValue[valueStr]
      
      if (!productValue) {
        let skukey = JSON.parse(JSON.stringify(this.newValue))
        skukey.sort((a, b) => a.localeCompare(b))
        valueStr = skukey.join(',')
        productValue = this.good.productValue[valueStr]
      }
      
      this.good.number = 1
      this.good.price = parseFloat(productValue.price).toFixed(2)
      this.good.stock = productValue.stock
      this.good.image = productValue.image || this.good.value.image
      this.good.valueStr = valueStr
    },

    // 减少数量
    handlePropertyReduce() {
      if (this.good.number === 1) return
      this.good.number--
    },

    // 增加数量
    handlePropertyAdd() {
      this.good.number++
    },

    // 加入购物车
    handleAddToCartInModal() {
      if (this.good.stock <= 0) {
        uni.showToast({
          title: '商品库存不足',
          icon: 'none'
        })
        return
      }
      
      this.handleAddToCart(this.category, this.good, this.good.number)
      this.leftKindNum()
      this.rightGoodNum()
      this.closeGoodDetailModal()
    },

    // 添加到购物车
    handleAddToCart(cate, newGood, num) {
      const index = this.cart.findIndex(item => 
        item.id === newGood.id && item.valueStr === newGood.valueStr
      )
      
      if (index > -1) {
        this.cart[index].number += num
      } else {
        this.cart.push({
          id: newGood.id,
          shopId: newGood.shop_id,
          cate_id: cate.id,
          name: newGood.store_name,
          price: newGood.price,
          number: num,
          stock: newGood.stock,
          image: newGood.image,
          valueStr: this.good.valueStr
        })
      }
      
      this.goodNum += num
      uni.setStorageSync('cart', this.cart)
      uni.setStorageSync('goodNum', this.goodNum)
      this.calculateCartTotalPrice()
    },

    // 刷新购物车
    refreshCart() {
      if (this.goods && this.goods.length > 0) {
        let newCart = uni.getStorageSync('cart') || []
        let tmpCart = []
        
        if (newCart) {
          for (let i in newCart) {
            for (let ii in this.goods) {
              for (let iii in this.goods[ii].goodsList) {
                if (newCart[i].id == this.goods[ii].goodsList[iii].id) {
                  tmpCart.push(newCart[i])
                }
              }
            }
          }
          this.cart = tmpCart
        }
        
        let num = 0
        tmpCart.forEach(item => {
          num += item.number
        })
        this.goodNum = num
      }
    },

    // 计算总价
    calculateCartTotalPrice() {
      let totalPrice = this.cart.reduce((acc, cur) => acc + cur.number * cur.price, 0)
      this.cartTotalPrice = parseFloat(totalPrice).toFixed(2)
    },

    // 计算左侧分类数量
    leftKindNum() {
      this.goods.forEach(item => {
        item.kindNum = this.cart.reduce((acc, cur) => {
          return cur.cate_id === item.id ? acc + cur.number : acc
        }, 0)
      })
    },

    // 计算右侧商品数量
    rightGoodNum() {
      this.goods.forEach(category => {
        category.goodsList.forEach(item => {
          item.carNum = this.cart.reduce((acc, cur) => {
            return cur.id === item.id ? acc + cur.number : acc
          }, 0)
        })
      })
    },

    // 点击分类
    handleMenuTap(id) {
      this.currentCateId = id
      this.menuScrollIntoView = `cate-${id}`
    },

    // 打开购物车
    openCartPopup() {
      if (this.cart.length === 0) {
        uni.showToast({
          title: '购物车是空的',
          icon: 'none'
        })
        return
      }
      this.popupVisible = true
    },

    // 清空购物车
    handleCartClear() {
      uni.showModal({
        title: '提示',
        content: '确定清空购物车么',
        success: (res) => {
          if (res.confirm) {
            this.cart = []
            this.goodNum = 0
            this.cartTotalPrice = 0
            this.popupVisible = false
            uni.setStorageSync('cart', [])
            uni.removeStorageSync('goodNum')
            this.leftKindNum()
            this.rightGoodNum()
          }
        }
      })
    },

    // 购物车商品减少
    handleCartItemReduce(index) {
      if (this.cart[index].number === 1) {
        this.cart.splice(index, 1)
      } else {
        this.cart[index].number--
      }
      
      this.goodNum--
      if (this.cart.length === 0) {
        this.popupVisible = false
      }
      
      uni.setStorageSync('cart', this.cart)
      this.calculateCartTotalPrice()
      this.leftKindNum()
      this.rightGoodNum()
    },

    // 购物车商品增加
    handleCartItemAdd(index) {
      this.cart[index].number++
      this.goodNum++
      uni.setStorageSync('cart', this.cart)
      this.calculateCartTotalPrice()
      this.leftKindNum()
      this.rightGoodNum()
    },

    // 去结算
    toPay() {
      const token = uni.getStorageSync('token')
      if (!token) {
        uni.navigateTo({
          url: '/pages/user/login'
        })
        return
      }
      
      if (this.cart.length === 0) {
        uni.showToast({
          title: '购物车是空的',
          icon: 'none'
        })
        return
      }
      
      const cart = this.cart
      let topay = []
      cart.forEach(item => {
        topay.push(item)
      })
      uni.setStorageSync('payCart', topay)
      
      uni.navigateTo({
        url: '/pages/shop/checkout?storeId=' + this.store_id
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.page {
  background: #f5f5f5;
}

.store-info {
  background: #fff;
  padding: 24rpx 32rpx;
  border-bottom: 1rpx solid #f5f5f5;

  .store-name {
    font-size: 32rpx;
    font-weight: bold;
    color: #333;
    margin-bottom: 8rpx;
  }

  .store-address {
    font-size: 24rpx;
    color: #999;
  }
}

.content {
  display: flex;
}

.category-list {
  width: 180rpx;
  background: #f5f5f5;

  .category-item {
    position: relative;
    padding: 32rpx 20rpx;
    text-align: center;
    background: #f5f5f5;

    &.active {
      background: #fff;

      .cate-name {
        color: $u-primary;
        font-weight: bold;
      }
    }

    .cate-name {
      font-size: 26rpx;
      color: #666;
    }

    .cate-badge {
      position: absolute;
      top: 20rpx;
      right: 20rpx;
      min-width: 32rpx;
      height: 32rpx;
      line-height: 32rpx;
      padding: 0 8rpx;
      background: #ff6b00;
      color: #fff;
      font-size: 20rpx;
      border-radius: 16rpx;
      text-align: center;
    }
  }
}

.goods-list {
  flex: 1;
  background: #fff;
}

.goods-category {
  .category-title {
    padding: 24rpx 32rpx;
    font-size: 28rpx;
    font-weight: bold;
    color: #333;
    background: #f5f5f5;
  }
}

.goods-items {
  padding: 0 32rpx;
}

.goods-item {
  display: flex;
  align-items: center;
  padding: 24rpx 0;
  border-bottom: 1rpx solid #f5f5f5;

  .good-img {
    width: 120rpx;
    height: 120rpx;
    border-radius: 12rpx;
    margin-right: 20rpx;
  }

  .good-info {
    flex: 1;

    .good-name {
      font-size: 28rpx;
      color: #333;
      margin-bottom: 12rpx;
    }

    .good-price {
      .price-symbol {
        font-size: 24rpx;
        color: #ff6b00;
      }

      .price-value {
        font-size: 32rpx;
        font-weight: bold;
        color: #ff6b00;
      }
    }
  }

  .good-action {
    position: relative;

    .cart-num {
      position: absolute;
      top: -10rpx;
      right: -10rpx;
      min-width: 32rpx;
      height: 32rpx;
      line-height: 32rpx;
      padding: 0 8rpx;
      background: #ff6b00;
      color: #fff;
      font-size: 20rpx;
      border-radius: 16rpx;
      text-align: center;
    }
  }
}

.cart-bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 100;
  display: flex;
  align-items: center;
  height: 100rpx;
  padding: 0 32rpx;
  background: #fff;
  box-shadow: 0 -2rpx 10rpx rgba(0, 0, 0, 0.05);

  .cart-icon {
    position: relative;
    width: 80rpx;
    height: 80rpx;
    background: $u-primary;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 20rpx;

    .cart-badge {
      position: absolute;
      top: 0;
      right: 0;
      min-width: 32rpx;
      height: 32rpx;
      line-height: 32rpx;
      padding: 0 8rpx;
      background: #ff6b00;
      color: #fff;
      font-size: 20rpx;
      border-radius: 16rpx;
      text-align: center;
    }
  }

  .cart-info {
    flex: 1;

    .cart-price {
      font-size: 32rpx;
      font-weight: bold;
      color: #ff6b00;
    }

    .cart-desc {
      font-size: 22rpx;
      color: #999;
    }
  }

  .cart-btn {
    padding: 16rpx 40rpx;
    background: $u-primary;
    color: #fff;
    border-radius: 40rpx;
    font-size: 28rpx;
  }
}

.good-detail {
  max-height: 80vh;
  background: #fff;
  border-radius: 20rpx 20rpx 0 0;
}

.detail-header {
  display: flex;
  padding: 32rpx;
  border-bottom: 1rpx solid #f5f5f5;

  .detail-img {
    width: 160rpx;
    height: 160rpx;
    border-radius: 12rpx;
    margin-right: 20rpx;
  }

  .detail-info {
    flex: 1;

    .detail-name {
      font-size: 28rpx;
      color: #333;
      margin-bottom: 12rpx;
    }

    .detail-price {
      margin-bottom: 12rpx;

      .price-symbol {
        font-size: 24rpx;
        color: #ff6b00;
      }

      .price-value {
        font-size: 36rpx;
        font-weight: bold;
        color: #ff6b00;
      }
    }

    .detail-stock {
      font-size: 24rpx;
      color: #999;
    }
  }
}

.spec-box {
  padding: 32rpx;
  border-bottom: 1rpx solid #f5f5f5;

  .spec-item {
    margin-bottom: 24rpx;

    &:last-child {
      margin-bottom: 0;
    }

    .spec-name {
      font-size: 26rpx;
      color: #666;
      margin-bottom: 16rpx;
    }

    .spec-values {
      display: flex;
      flex-wrap: wrap;
      gap: 16rpx;

      .spec-value {
        padding: 12rpx 24rpx;
        background: #f5f5f5;
        border-radius: 8rpx;
        font-size: 26rpx;
        color: #666;

        &.active {
          background: $u-primary;
          color: #fff;
        }
      }
    }
  }
}

.number-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 32rpx;
  border-bottom: 1rpx solid #f5f5f5;

  .number-label {
    font-size: 28rpx;
    color: #333;
  }

  .number-control {
    display: flex;
    align-items: center;
    gap: 24rpx;

    .number-value {
      min-width: 60rpx;
      text-align: center;
      font-size: 28rpx;
      color: #333;
    }
  }
}

.detail-footer {
  padding: 32rpx;
}

.cart-popup {
  max-height: 60vh;
  background: #fff;
  border-radius: 20rpx 20rpx 0 0;
}

.popup-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 32rpx;
  border-bottom: 1rpx solid #f5f5f5;

  .popup-title {
    font-size: 32rpx;
    font-weight: bold;
    color: #333;
  }

  .popup-clear {
    font-size: 26rpx;
    color: #999;
  }
}

.cart-items {
  max-height: 400rpx;
  padding: 0 32rpx;
}

.cart-item {
  display: flex;
  align-items: center;
  padding: 24rpx 0;
  border-bottom: 1rpx solid #f5f5f5;

  .item-name {
    flex: 1;
    font-size: 28rpx;
    color: #333;
  }

  .item-price {
    font-size: 28rpx;
    color: #ff6b00;
    margin-right: 20rpx;
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
</style>
