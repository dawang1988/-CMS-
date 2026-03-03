<template>
	<view class="container" :style="themeStyle">
		<view class="photo">
			<view class="img">
				<image :src="userinfo.avatar || '/static/logo.png'" mode="widthFix" />
			</view>
			<view class="name">{{ appName }}</view>
		</view>
		
		<block v-if="fastLogin">
			<view class="loginBtns">
				<button hover-class="button-click" class="loginBtn2 bg-primary" @getphonenumber="phone" open-type="getPhoneNumber">
					一键登录
				</button>
				<view v-if="mobile" class="loginBtn1 bg-primary" @tap="autoLogin">
					<view class="tInfo">自动登录</view>
					<view class="tInfo">{{ mobile }}</view>
				</view>
			</view>
			<view class="pwdlogin" @tap="pwdlogin">手机号密码登录</view>
			<button hover-class="button-click" class="backBtn" @tap="backHome">返回首页</button>
		</block>
		
		<block v-else>
			<view class="userLogin">
				<view class="item">
					<label class="label">账号:</label>
					<input class="input" v-model="username" type="number" placeholder="请输入11位手机号" maxlength="11" />
				</view>
				<view class="item">
					<label class="label">密码:</label>
					<input class="input" v-model="password" type="password" placeholder="请输入密码（任意6位以上）" maxlength="16" />
				</view>
				<view class="item">
					<view class="btn" @tap="userLogin">登录</view>
					<view class="wxLogin" @tap="wxLogin">返回</view>
				</view>
				<view class="tips">首次使用请用手机号注册</view>
			</view>
		</block>
	</view>
</template>

<script>
import http from '@/utils/http.js'

export default {
	data() {
		return {
			appName: getApp().globalData.appName,
			fastLogin: true,
			username: '',
			password: '',
			mobile: '',
			loginData: '',
			userinfo: {}
		}
	},
	
	onShow() {
		// 如果已经登录，直接返回
		const app = getApp()
		if (app.globalData.isLogin) {
			uni.navigateBack({ delta: 1 })
		}
	},
	
	methods: {
		
		// 一键登录
		phone(e) {
			if (e.detail.errMsg === "getPhoneNumber:fail user deny") {
				uni.showToast({ title: '已取消授权', icon: 'none' })
				return
			}
			
			if (e.detail.errMsg === "getPhoneNumber:ok") {
				uni.showLoading({ title: '登录中...' })
				uni.login({
					provider: 'weixin',
					success: (res) => {
						if (res.code) {
							http.post('/member/auth/weixin-mini-app-login', {
								phoneCode: e.detail.code,
								loginCode: res.code
							}).then(response => {
								uni.hideLoading()
									if (response.code === 0 && response.data) {
									this.saveLoginData(response.data)
								} else {
									uni.showToast({ title: response.msg || '登录失败', icon: 'none' })
								}
							}).catch(err => {
								uni.hideLoading()
							})
						}
					},
					fail: () => {
						uni.hideLoading()
						uni.showToast({ title: '获取登录凭证失败', icon: 'none' })
					}
				})
			}
		},
		
		// 切换到微信登录
		wxLogin() {
			this.fastLogin = true
		},
		
		// 切换到密码登录
		pwdlogin() {
			this.fastLogin = false
			this.password = ''
			this.username = ''  // 清空输入
		},
		
		// 账号密码登录
		userLogin() {
			if (!this.username || this.username.length !== 11) {
				uni.showToast({ title: '账号格式不正确', icon: 'none' })
				return
			}
			
			if (!this.password || this.password.length < 6) {
				uni.showToast({ title: '密码格式不正确', icon: 'none' })
				return
			}
			
			uni.showLoading({ title: '登录中...' })
			
			http.post('/member/auth/login', {
				mobile: this.username,
				password: this.password
			}).then(res => {
				uni.hideLoading()
				if (res.code === 0 && res.data) {
					this.saveLoginData(res.data)
				} else {
					uni.showModal({
						title: '登录失败',
						content: res.msg || '登录失败',
						showCancel: false
					})
				}
			}).catch(err => {
				uni.hideLoading()
			})
		},
		
		// 返回首页
		backHome() {
			uni.switchTab({ url: '/pages/door/index' })
		},
		
		// 自动登录
		autoLogin() {
			this.saveLoginData(this.loginData)
		},
		
		// 保存登录数据
		saveLoginData(data) {
			const app = getApp()
			app.globalData.userDatatoken = data
			app.globalData.isLogin = true
			uni.setStorageSync('userDatatoken', data)
			uni.setStorageSync('token', data.access_token)
			
			uni.showToast({ title: '登录成功', icon: 'success' })
			
			setTimeout(() => {
				// 获取页面栈
				const pages = getCurrentPages()
				if (pages.length > 1) {
					// 有上一页，返回上一页
					uni.navigateBack({ delta: 1 })
				} else {
					// 没有上一页，跳转到首页
					uni.switchTab({ url: '/pages/door/index' })
				}
			}, 500)
		}
	}
}
</script>

<style lang="scss">
page {
	background: #fff;
}
</style>
<style lang="scss" scoped>

.container {
	padding: 0 60rpx;
}

.photo {
	padding: 60rpx 0;
	text-align: center;
}

.photo .img {
	width: 200rpx;
	height: 200rpx;
	margin: 0 auto;
}

.photo .img image {
	width: 100%;
	height: 100%;
	border-radius: 100%;
}

.photo .name {
	margin-top: 20rpx;
}

.pwdlogin {
	margin-top: 30rpx;
	text-align: right;
	font-size: 24rpx;
	margin-left: 30rpx;
	color: #108ee9;
	line-height: 36rpx;
}

.loginBtns {
	display: flex;
	justify-content: center;
}

.loginBtn1 {
	width: 50%;
	margin: 60rpx 20rpx 20rpx 20rpx;
	border-radius: 50rpx;
	font-size: 28rpx;
	height: 90rpx;
	text-align: center;
	background-color: var(--main-color, #5AAB6E);
	color: #fff;
}

.loginBtn1 .tInfo {
	line-height: 45rpx;
}

.loginBtn2 {
	width: 50%;
	margin: 60rpx 20rpx 20rpx 20rpx;
	border-radius: 50rpx;
	font-size: 28rpx;
	height: 90rpx;
	line-height: 90rpx;
	background-color: var(--main-color, #5AAB6E);
	color: #fff;
}

.backBtn {
	width: 50%;
	border-radius: 50rpx;
	margin: 0 auto;
	margin-top: 50rpx;
	font-size: 28rpx;
	height: 90rpx;
	line-height: 90rpx;
}

.userLogin .item {
	display: flex;
	justify-content: center;
	align-items: center;
	margin: 30rpx;
	font-size: 36rpx;
	line-height: 36rpx;
}

.userLogin .item .label {
	font-weight: 600;
	margin-right: 20rpx;
}

.userLogin .item .input {
	flex: 1;
	border: 1rpx solid #E5E5E5;
	padding: 10rpx 20rpx;
	border-radius: 8rpx;
}

.userLogin .item .btn {
	text-align: center;
	padding: 20rpx;
	width: 260rpx;
	background-color: var(--main-color, #5AAB6E);
	color: #fff;
	border-radius: 10rpx;
}

.userLogin .item .wxLogin {
	font-size: 24rpx;
	margin-left: 30rpx;
	color: #0095ff;
	line-height: 36rpx;
}

.bg-primary {
	background-color: var(--main-color, #5AAB6E);
	color: #fff;
}

.tips {
	margin-top: 30rpx;
	text-align: center;
	font-size: 24rpx;
	color: #999;
}
</style>
