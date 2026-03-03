<template>
  <view class="whole" id="richText">
    <view :style="{height: textTool ? '200rpx' : '100rpx'}"></view>
    <view class="page-body">
      <view class="wrapper">
        <editor 
          id="editor" 
          class="ql-container" 
          :placeholder="placeholder" 
          show-img-size 
          show-img-toolbar 
          show-img-resize 
          @statuschange="onStatusChange" 
          :read-only="readOnly" 
          @ready="onEditorReady" 
          @focus="bindfocus" 
          @blur="bindblur" 
          @input="bindinput"
        />
      </view>
    </view>
    <view class="editor-toolbar" @tap="format">
      <view class="toolbar-2">
        <view class="tool-item-cell">
          <view class="tool-item-box">
            <view class="cell-rg-shadow"></view>
            <scroll-view scroll-x class="flex-sb" style="height:70rpx;white-space: nowrap;">
              <view class="tool-item">
                <text class="iconfont icon-choosepicture" data-tool_name="insertImage" @tap="toolEvent"></text>
              </view>
              <view class="tool-item">
                <text class="iconfont icon-moredo" data-tool_name="showTextTool" @tap="toolEvent"></text>
              </view>
              <view class="tool-item">
                <text 
                  :class="['iconfont', 'icon-h1', formats.header === 1 ? 'ql-active' : '']"
                  data-tool_name="text_H1" 
                  data-name="header" 
                  :data-value="1" 
                  @tap="toolEvent"
                ></text>
              </view>
              <view class="tool-item">
                <text class="iconfont icon-rili" data-tool_name="insertDate" @tap="toolEvent"></text>
              </view>
              <view class="tool-item">
                <text class="iconfont icon-backleft" data-tool_name="undo" @tap="toolEvent"></text>
              </view>
              <view class="tool-item">
                <text class="iconfont icon-backright" data-tool_name="redo" @tap="toolEvent"></text>
              </view>
              <view class="tool-item">
                <text class="iconfont icon-rubbish" data-tool_name="clear" @tap="toolEvent"></text>
              </view>
            </scroll-view>
          </view>
        </view>
        <view class="save-icon" @tap="getEditorContent">
          {{buttonTxt}}
        </view>
      </view>
      
      <view class="toolbar-1" v-if="textTool">
        <scroll-view scroll-x style="height:70rpx;white-space: nowrap;">
          <view class="tool-item">
            <text :class="['iconfont', 'icon-weight', formats.bold ? 'ql-active' : '']" data-name="bold"></text>
          </view>
          <view class="tool-item">
            <text :class="['iconfont', 'icon-xieti', formats.italic ? 'ql-active' : '']" data-name="italic"></text>
          </view>
          <view class="tool-item">
            <text :class="['iconfont', 'icon-xiahuaxian', formats.underline ? 'ql-active' : '']" data-name="underline"></text>
          </view>
          <view class="tool-item">
            <text class="iconfont icon-fengexian" @tap="insertDivider"></text>
          </view>
          <view class="tool-item">
            <text :class="['iconfont', 'icon-duiqileft', formats.align === 'left' ? 'ql-active' : '']" data-name="align" data-value="left"></text>
          </view>
          <view class="tool-item">
            <text :class="['iconfont', 'icon-duiqicenter', formats.align === 'center' ? 'ql-active' : '']" data-name="align" data-value="center"></text>
          </view>
          <view class="tool-item">
            <text :class="['iconfont', 'icon-duiqiright', formats.align === 'right' ? 'ql-active' : '']" data-name="align" data-value="right"></text>
          </view>
          <view class="tool-item">
            <text :class="['iconfont', 'icon-averagesize', formats.align === 'justify' ? 'ql-active' : '']" data-name="align" data-value="justify"></text>
          </view>
          <view class="tool-item">
            <text class="iconfont icon-todolist" data-name="list" data-value="check"></text>
          </view>
          <view class="tool-item">
            <text :class="['iconfont', 'icon-shuzisort', formats.list === 'ordered' ? 'ql-active' : '']" data-name="list" data-value="ordered"></text>
          </view>
          <view class="tool-item">
            <text :class="['iconfont', 'icon-diansort', formats.list === 'bullet' ? 'ql-active' : '']" data-name="list" data-value="bullet"></text>
          </view>
        </scroll-view>
      </view>
    </view>
  </view>
</template>

<script>
const supportDateFormat = ['YY-MM', 'YY.MM.DD', 'YY-MM-DD', 'YY/MM/DD', 'YY.MM.DD HH:MM', 'YY/MM/DD HH:MM', 'YY-MM-DD HH:MM'];

export default {
  name: 'richText',
  props: {
    readOnly: {
      type: Boolean,
      default: false
    },
    placeholder: {
      type: String,
      default: '开始编辑吧...'
    },
    formatDate: {
      type: String,
      default: 'YY-MM-DD HH:MM'
    },
    buttonTxt: {
      type: String,
      default: '保存'
    },
    html: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      formats: {},
      textTool: false,
      editorCtx: null
    }
  },
  methods: {
    toolEvent(e) {
      const toolName = e.currentTarget.dataset.tool_name;
      switch (toolName) {
        case 'insertImage':
          this.insertImageEvent();
          break;
        case 'showTextTool':
          this.showTextTool();
          break;
        case 'insertDate':
          this.insertDate();
          break;
        case 'undo':
          this.undo();
          break;
        case 'redo':
          this.restore();
          break;
        case 'clear':
          this.clearBeforeEvent();
          break;
      }
    },
    onEditorReady() {
      this.$emit('onEditorReady');
      uni.createSelectorQuery().in(this).select('#editor').context(res => {
        this.editorCtx = res.context;
        if (this.html) {
          this.setContents(this.html);
        }
      }).exec();
    },
    setContents(richtext) {
      if (this.editorCtx) {
        this.editorCtx.setContents({
          html: richtext,
          success: res => {
            // setContents success
          }
        });
      }
    },
    undo() {
      if (this.editorCtx) {
        this.editorCtx.undo();
        this.$emit('undo');
      }
    },
    restore() {
      if (this.editorCtx) {
        this.editorCtx.redo();
        this.$emit('restore');
      }
    },
    format(e) {
      const { name, value } = e.target.dataset;
      if (!name || !this.editorCtx) return;
      this.editorCtx.format(name, value);
    },
    onStatusChange(e) {
      this.formats = e.detail;
    },
    insertDivider() {
      if (this.editorCtx) {
        this.editorCtx.insertDivider({
          success: res => {
            // insert divider success
          }
        });
      }
    },
    clear() {
      if (this.editorCtx) {
        this.editorCtx.clear({
          success: res => {
            this.$emit('clearSuccess');
          }
        });
      }
    },
    clearBeforeEvent() {
      this.$emit('clearBeforeEvent');
    },
    removeFormat() {
      if (this.editorCtx) {
        this.editorCtx.removeFormat();
      }
    },
    insertDate() {
      if (supportDateFormat.indexOf(this.formatDate) < 0) {
        // format date error
        return;
      }
      const formatDate = this.getThisDate(this.formatDate);
      if (this.editorCtx) {
        this.editorCtx.insertText({ text: formatDate });
      }
    },
    insertImageEvent() {
      this.$emit('insertImageEvent');
    },
    insertImageMethod(path) {
      return new Promise((resolve, reject) => {
        if (this.editorCtx) {
          this.editorCtx.insertImage({
            src: path,
            data: { id: 'image' },
            success: res => resolve(res),
            fail: res => reject(res)
          });
        } else {
          reject(new Error('Editor not ready'));
        }
      });
    },
    getEditorContent() {
      if (this.editorCtx) {
        this.editorCtx.getContents({
          success: res => {
            this.$emit('getEditorContent', { value: res });
          }
        });
      }
    },
    showTextTool() {
      this.textTool = !this.textTool;
    },
    bindfocus(e) {
      this.$emit('bindfocus', { value: e });
    },
    bindblur(e) {
      this.$emit('bindblur', { value: e });
    },
    bindinput(e) {
      this.$emit('bindinput', { value: e });
    },
    getThisDate(format) {
      const date = new Date();
      const year = date.getFullYear();
      const month = date.getMonth() + 1;
      const day = date.getDate();
      const h = date.getHours();
      const m = date.getMinutes();
      
      const zero = (value) => value < 10 ? '0' + value : value;
      
      switch (format) {
        case 'YY-MM':
          return year + '-' + zero(month);
        case 'YY.MM.DD':
          return year + '.' + zero(month) + '.' + zero(day);
        case 'YY-MM-DD':
          return year + '-' + zero(month) + '-' + zero(day);
        case 'YY.MM.DD HH:MM':
          return year + '.' + zero(month) + '.' + zero(day) + ' ' + zero(h) + ':' + zero(m);
        case 'YY/MM/DD HH:MM':
          return year + '/' + zero(month) + '/' + zero(day) + ' ' + zero(h) + ':' + zero(m);
        case 'YY-MM-DD HH:MM':
          return year + '-' + zero(month) + '-' + zero(day) + ' ' + zero(h) + ':' + zero(m);
        default:
          return year + '/' + zero(month) + '/' + zero(day);
      }
    }
  }
}
</script>

<style scoped>
.page-body {
  padding-bottom: 100rpx;
}

.editor-toolbar {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  z-index: 9999;
}

.editor-toolbar .iconfont {
  display: flex;
  align-items: center;
  justify-content: center;
}

.toolbar-1 {
  padding: 5rpx 0;
  background: #e4e4e4;
}

.editor-toolbar .tool-item {
  display: inline-block;
}

.toolbar-2 {
  padding: 5rpx 20px 5rpx 10px;
  background: #f4f4f4;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: relative;
}

.toolbar-2 .tool-item-cell {
  max-width: 80%;
}

.toolbar-2 .tool-item-box {
  position: relative;
}

.toolbar-2 .cell-rg-shadow {
  position: absolute;
  right: 0;
  top: 0;
  width: 1px;
  height: 100%;
  z-index: 999;
  background: #dddddd;
}

.iconfont {
  display: inline-block;
  padding: 8px 8px;
  width: 24px;
  height: 24px;
  cursor: pointer;
  font-size: 20px;
}

.ql-container {
  box-sizing: border-box;
  padding: 12px 15px;
  width: 100%;
  min-height: 30vh;
  height: auto;
  background: #fff;
  font-size: 16px;
  line-height: 1.5;
}

.ql-active {
  color: #06c;
}

.save-icon {
  padding: 15rpx 30rpx;
  font-size: 20rpx;
  background: #bf98d2;
  color: #fff;
}

.flex-sb {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
</style>
