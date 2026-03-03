/**
 * magic 工具函数 - 从 wxs 迁移，替代 magic.wxs
 * 用于 Vue/uni-app 中字符串处理
 */

export function split(tag) {
  if (!tag) return []
  if (Array.isArray(tag)) return tag
  if (typeof tag !== 'string') return []
  return tag.split(',')
}

export function splitkongge(tag) {
  if (!tag) return []
  if (Array.isArray(tag)) return tag
  if (typeof tag !== 'string') return []
  return tag.split(' ')
}

export function splittime(tag) {
  if (!tag || typeof tag !== 'string') return []
  const atime = tag.split(' ')
  if (atime.length) {
    const atime1 = atime[1]
    return atime1 ? atime1.split(':') : []
  }
  return []
}

export function includes(arr, string) {
  if (arr !== undefined && arr.indexOf(string) > -1) {
    return true
  }
  return false
}

export default {
  split,
  splitkongge,
  splittime,
  includes
}
