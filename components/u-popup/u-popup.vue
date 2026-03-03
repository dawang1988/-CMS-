<template>
  <view v-if="show" class="u-popup" @tap.self="handleClickMask">
    <!-- 遮罩层 -->
    <view class="u-popup__mask" :class="{ 'u-popup__mask--show': showContent }" @tap.stop="handleClickMask"></view>
    <!-- 内容区 -->
    <view 
      class="u-popup__content" 
      :class="[
        `u-popup__content--${mode}`,
        { 'u-popup__content--show': showContent }
      ]"
      :style="[contentStyle, customStyle]"
      @tap.stop
    >
      <view v-if="closeable" class="u-popup__close" @tap="close">✕</view>
      <slot></slot>
    </view>
  </view>
</template>

<script>
export default {
  name: 'u-popup',
  props: {
    show: { type: Boolean, default: false },
    mode: { type: String, default: 'bottom' },
    round: { type: [String, Number], default: 0 },
    closeable: { type: Boolean, default: false },
    customStyle: { type: [Object, String], default: () => ({}) },
    maskCloseAble: { type: Boolean, default: true },
    zoom: { type: Boolean, default: true },
    safeAreaInsetBottom: { type: Boolean, default: true }
  },
  data() {
    return { showContent: false }
  },
  computed: {
    contentStyle() {
      const s = {}
      const r = this.round
      if (r) {
        const rv = typeof r === 'number' ? r + 'rpx' : (r.includes('rpx') || r.includes('px') ? r : r + 'rpx')
        if (this.mode === 'bottom') s.borderRadius = `${rv} ${rv} 0 0`
        else if (this.mode === 'top') s.borderRadius = `0 0 ${rv} ${rv}`
        else if (this.mode === 'center') s.borderRadius = rv
      }
      return s
    }
  },
  watch: {
    show(val) {
      if (val) {
        this.$nextTick(() => { setTimeout(() => { this.showContent = true }, 30) })
      } else {
        this.showContent = false
      }
    }
  },
  methods: {
    handleClickMask() {
      if (this.maskCloseAble) this.close()
    },
    close() {
      this.showContent = false
      setTimeout(() => { this.$emit('close') }, 250)
    }
  }
}
</script>

<style scoped>
.u-popup {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 10075;
  display: flex;
  align-items: center;
  justify-content: center;
}
.u-popup__mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0);
  transition: background-color 0.3s;
  z-index: 10076;
}
.u-popup__mask--show {
  background-color: rgba(0, 0, 0, 0.5);
}
.u-popup__content {
  position: fixed;
  z-index: 10077;
  background-color: #fff;
  transition: all 0.3s ease-in-out;
  overflow: hidden;
}
.u-popup__content--bottom {
  left: 0;
  right: 0;
  bottom: 0;
  transform: translateY(100%);
  max-height: 80vh;
}
.u-popup__content--bottom.u-popup__content--show {
  transform: translateY(0);
}
.u-popup__content--top {
  left: 0;
  right: 0;
  top: 0;
  transform: translateY(-100%);
  max-height: 80vh;
}
.u-popup__content--top.u-popup__content--show {
  transform: translateY(0);
}
.u-popup__content--center {
  width: 80%;
  max-width: 600rpx;
  max-height: 80vh;
  opacity: 0;
  transform: scale(0.8);
}
.u-popup__content--center.u-popup__content--show {
  opacity: 1;
  transform: scale(1);
}
.u-popup__content--left {
  left: 0;
  top: 0;
  bottom: 0;
  transform: translateX(-100%);
  max-width: 80vw;
}
.u-popup__content--left.u-popup__content--show {
  transform: translateX(0);
}
.u-popup__content--right {
  right: 0;
  top: 0;
  bottom: 0;
  transform: translateX(100%);
  max-width: 80vw;
}
.u-popup__content--right.u-popup__content--show {
  transform: translateX(0);
}
.u-popup__close {
  position: absolute;
  top: 20rpx;
  right: 20rpx;
  width: 44rpx;
  height: 44rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28rpx;
  color: #909399;
  z-index: 1;
}
</style>
