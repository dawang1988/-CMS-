/**
 * http-legacy.js — 兼容层（已废弃，请使用 http.js）
 * 
 * 此文件现在只是 http.js 中 legacyRequest / legacyUploadFile 的代理。
 * 所有配置（baseUrl、tenantId 等）统一从 config/index.js 读取。
 * 
 * 旧调用方式仍然有效：
 *   http.request(url, urltype, method, data, token, message, successCb, failCb)
 *   http.uploadFile(url, data, message, successCb, failCb)
 */
import { legacyRequest, legacyUploadFile } from './http.js'

export default {
	request: legacyRequest,
	uploadFile: legacyUploadFile
}
