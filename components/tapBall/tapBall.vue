<template>
  <view 
    class="floating-ball" 
    :style="{left: ballLeft + 'px', top: ballTop + 'px'}"
    @touchstart="handleTouchStart"
    @touchmove.stop.prevent="handleTouchMove"
    @touchend.stop.prevent="handleTouchEnd"
    @click="toggleMenu"
  >
    <view class="menu" :class="isMenuOpen ? 'menu-open' : 'menu-close'">
      <view class="tip">
        <image src="/static/img/arrow-down.png" mode="aspectFit"/>
        <text>收起</text>
      </view>
      <view>
        <view 
          class="menu-item" 
          v-for="(item, index) in menuItems" 
          :key="index" 
          @click.stop="handleMenuItemTap(item.value)"
        >
          <image :src="item.icon"/>
          <view>{{item.text}}</view>
        </view>
      </view>
    </view>
    <image class="ball" src="/static/img/ball.png" />
  </view>
</template>

<script>
export default {
  name: 'tapBall',
  props: {
    menuItems: {
      type: Array,
      default: () => [
        {
          text: '开大门',
          value: 'openDoor',
          icon: '/static/img/open-door.png'
        },
        {
          text: '开包间',
          value: 'openCompartment',
          icon: '/static/img/open-compartment.png'
        },
        {
          text: '续单',
          value: 'reorder',
          icon: '/static/img/reorder.png'
        }
      ]
    }
  },
  data() {
    return {
      ballLeft: 20,
      ballTop: 400,
      ballSize: 68,
      isMenuOpen: false,
      startX: 0,
      startY: 0,
      startBallLeft: 0,
      startBallTop: 0
    }
  },
  mounted() {
    const systemInfo = uni.getSystemInfoSync();
    const screenWidth = systemInfo.windowWidth;
    const screenHeight = systemInfo.windowHeight;
    const ballSize = Math.ceil(screenWidth * 200 / 750);
    this.ballSize = ballSize;
    this.ballLeft = screenWidth - ballSize;
    this.ballTop = screenHeight / 2 + ballSize * 1.75;
  },
  methods: {
    handleTouchStart(e) {
      this.startX = e.touches[0].clientX;
      this.startY = e.touches[0].clientY;
      this.startBallLeft = this.ballLeft;
      this.startBallTop = this.ballTop;
    },
    handleTouchMove(e) {
      const touchX = e.touches[0].clientX;
      const touchY = e.touches[0].clientY;
      const deltaX = touchX - this.startX;
      const deltaY = touchY - this.startY;
      this.ballLeft = this.startBallLeft + deltaX;
      this.ballTop = this.startBallTop + deltaY;
    },
    handleTouchEnd() {
      const systemInfo = uni.getSystemInfoSync();
      const screenWidth = systemInfo.windowWidth;
      const screenHeight = systemInfo.windowHeight;
      
      if (this.ballLeft < 0) {
        this.ballLeft = 0;
      }
      if (this.ballLeft + this.ballSize > screenWidth) {
        this.ballLeft = screenWidth - this.ballSize;
      }
      if (this.ballTop < 0) {
        this.ballTop = 0;
      }
      if (this.ballTop + this.ballSize > screenHeight) {
        this.ballTop = screenHeight - this.ballSize;
      }
    },
    toggleMenu() {
      this.isMenuOpen = !this.isMenuOpen;
    },
    handleMenuItemTap(value) {
      this.$emit('menu-event', { value });
    }
  }
}
</script>

<style scoped>
.floating-ball {
  position: fixed;
  width: 137rpx;
  height: 137rpx;
  border-radius: 50%;
  z-index: 99;
}

.tip {
  color: #fff;
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 21rpx;
  padding-top: 22rpx;
  margin-bottom: 26rpx;
}

.tip image {
  width: 22rpx;
  height: 22rpx;
  margin-right: 10rpx;
}

.ball {
  width: 100%;
  height: 100%;
  z-index: 1000;
  margin-left: 35.6rpx;
}

.menu {
  position: absolute;
  left: 0;
  bottom: 19rpx;
  width: 180rpx;
  background-color: var(--main-color, #5AAB6E);
  border-radius: 9rpx;
  box-shadow: 0 2rpx 5rpx rgba(0, 0, 0, 0.3);
  overflow: hidden;
  transition: all 0.3s ease;
  z-index: -1;
  padding: 0 12rpx;
}

.menu-open {
  height: 440rpx;
}

.menu-close {
  height: 0;
}

.menu-item {
  padding: 10rpx;
  text-align: center;
  background: #fff;
  border-radius: 9rpx;
  margin-bottom: 18rpx;
  color: var(--main-color, #5AAB6E);
  display: flex;
  align-items: center;
  justify-content: flex-start;
  padding-left: 26rpx;
  height: 44rpx;
}

.menu-item:active {
  background: #d4d1d1;
}

.menu-item image {
  width: 30rpx;
  height: 30rpx;
}

.menu-item view {
  flex-grow: 1;
  font-size: 21rpx;
}
</style>
