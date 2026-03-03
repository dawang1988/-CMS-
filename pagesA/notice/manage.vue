<template>
  <view class="container" :style="themeStyle">
    <view class="editor-wrapper">
      <!-- 工具栏 -->
      <view class="toolbar">
        <view class="tool-btn" @click="insertImage">
          <text class="iconfont">📷</text>
          <text>插入图片</text>
        </view>
        <view class="tool-btn" @click="clearContent">
          <text class="iconfont">🗑️</text>
          <text>清空</text>
        </view>
      </view>
      
      <!-- 编辑器 -->
      <editor
        id="editor"
        class="editor"
        :placeholder="placeholder"
        @ready="onEditorReady"
        @input="onEditorInput"
      />
    </view>
    
    <!-- 底部按钮 -->
    <view class="footer" :class="isIpx ? 'fix-iphonex-button' : ''">
      <button class="btn-save" @click="submit">保存</button>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http.js'
import config from '@/config/index.js'

export default {
  data() {
    return {
      store_id: '',
      storeInfo: null,
      placeholder: '请输入门店公告内容...',
      editorCtx: null,
      isIpx: false
    }
  },
  onLoad(options) {
    // 检测是否是iPhone X
    const systemInfo = uni.getSystemInfoSync()
    this.isIpx = systemInfo.model && systemInfo.model.indexOf('iPhone X') !== -1
    
    if (options.store_id) {
      this.store_id = Number(options.store_id)
      this.getData()
    }
  },
  methods: {
    // 将 notice HTML 中的相对路径图片转为完整URL
    fixNoticeImages(html) {
      if (!html) return html
      // 匹配 src="/storage/..." 的相对路径，补上 imageBase
      return html.replace(/src="(\/storage\/[^"]+)"/g, 'src="' + config.imageBase + '$1"')
    },
    
    async getData() {
      try {
        const res = await http.get(`/member/store/getDetail/${this.store_id}`)
        if (res.code === 0 && res.data) {
          this.storeInfo = res.data
          // 设置编辑器内容
          if (this.editorCtx && res.data.notice) {
            this.editorCtx.setContents({
              html: this.fixNoticeImages(res.data.notice)
            })
          }
        }
      } catch (e) {
      }
    },
    
    onEditorReady() {
      uni.createSelectorQuery()
        .in(this)
        .select('#editor')
        .context((res) => {
          this.editorCtx = res.context
          // 如果已经有数据，设置内容
          if (this.storeInfo && this.storeInfo.notice) {
            this.editorCtx.setContents({
              html: this.fixNoticeImages(this.storeInfo.notice)
            })
          }
        })
        .exec()
    },
    
    onEditorInput(e) {
      // 可以在这里处理输入事件
    },
    
    insertImage() {
      uni.chooseImage({
        count: 1,
        success: (res) => {
          this.uploadAndInsertImage(res.tempFilePaths[0])
        }
      })
    },
    
    async uploadAndInsertImage(filePath) {
      try {
        uni.showLoading({ title: '上传中...' })
        const result = await http.upload(filePath)
        uni.hideLoading()
        
        // upload 返回的可能是 { url: '...' } 对象或字符串
        const imgUrl = typeof result === 'string' ? result : (result.url || result)
        if (this.editorCtx && imgUrl) {
          const fullUrl = typeof imgUrl === 'string' && imgUrl.startsWith('http') ? imgUrl : config.imageBase + imgUrl
          this.editorCtx.insertImage({
            src: fullUrl,
            width: '100%'
          })
        }
      } catch (e) {
        uni.hideLoading()
        uni.showToast({ title: '上传失败', icon: 'none' })
      }
    },
    
    clearContent() {
      uni.showModal({
        title: '提示',
        content: '确定要清空内容吗？',
        success: (res) => {
          if (res.confirm && this.editorCtx) {
            this.editorCtx.clear()
          }
        }
      })
    },
    
    async submit() {
      if (!this.editorCtx) {
        uni.showToast({ title: '编辑器未就绪', icon: 'none' })
        return
      }
      
      this.editorCtx.getContents({
        success: async (res) => {
          const notice = res.html
          
          try {
            uni.showLoading({ title: '保存中...' })
            
            const result = await http.post('/member/store/save', {
              id: this.store_id,
              notice: notice
            })
            uni.hideLoading()
            
            if (result.code === 0) {
              uni.showToast({ title: '设置成功', icon: 'success' })
              setTimeout(() => uni.navigateBack(), 1000)
            } else {
              uni.showToast({ title: result.msg || '保存失败', icon: 'none' })
            }
          } catch (e) {
            uni.hideLoading()
            uni.showToast({ title: e.msg || '保存失败', icon: 'none' })
          }
        }
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

.editor-wrapper {
  background: #fff;
  margin: 20rpx;
  border-radius: 12rpx;
  overflow: hidden;
}

.toolbar {
  display: flex;
  padding: 20rpx;
  border-bottom: 1rpx solid #eee;
  gap: 30rpx;
}

.tool-btn {
  display: flex;
  align-items: center;
  gap: 8rpx;
  padding: 12rpx 24rpx;
  background: #f5f5f5;
  border-radius: 8rpx;
  font-size: 26rpx;
  color: #666;
  
  .iconfont {
    font-size: 32rpx;
  }
}

.editor {
  min-height: 600rpx;
  padding: 20rpx;
  font-size: 28rpx;
  line-height: 1.6;
}

.footer {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 20rpx 30rpx;
  background: #fff;
  box-shadow: 0 -2rpx 10rpx rgba(0, 0, 0, 0.05);
}

.btn-save {
  width: 100%;
  height: 90rpx;
  line-height: 90rpx;
  background: #07c160;
  color: #fff;
  font-size: 32rpx;
  border-radius: 8rpx;
}

.fix-iphonex-button {
  padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
}
</style>
