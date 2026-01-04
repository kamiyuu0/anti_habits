import { Controller } from "@hotwired/stimulus"
import Tagify from "@yaireo/tagify"

export default class extends Controller {
  static values = {
    tagsUrl: String,
    initialValue: String
  }

  async connect() {
    this.initializeTagify()
    await this.loadWhitelist()
  }

  disconnect() {
    if (this.tagify) {
      this.tagify.destroy()
    }
  }

  initializeTagify() {
    const input = this.element.querySelector('input[type="text"]') || this.element

    this.tagify = new Tagify(input, {
      placeholder: "例：夜更かし, スマホ依存, SNS",
      maxTags: 10,
      duplicates: false,
      dropdown: {
        enabled: 1, // 1文字以上入力でドロップダウンを表示
        maxItems: 10,
        classname: "tagify__dropdown",
        fuzzySearch: true,
        highlightFirst: true,
        closeOnSelect: false,
        clearOnSelect: true,
        searchKeys: ["value"]
      },
      whitelist: [],
      validate: (tag) => {
        const value = tag.value.trim()
        
        if (!value) {
          return false
        }
        
        if (value.length > 15) {
          return "タグは15文字以内で入力してください"
        }
        
        return true
      },
      transformTag: (tagData) => {
        tagData.value = tagData.value.trim()
      },
      editTags: false,
      backspace: true,
      skipInvalid: true
    })

    // 初期値の設定（編集時）
    if (this.hasInitialValueValue && this.initialValueValue) {
      const tags = this.initialValueValue.split(",").map(t => t.trim()).filter(t => t)
      if (tags.length > 0) {
        this.tagify.addTags(tags)
      }
    }

    // フォーカス時にドロップダウンを表示
    this.tagify.on('focus', () => {
      if (this.tagify.settings.whitelist.length > 0) {
        this.tagify.dropdown.show()
      }
    })

    // フォーム送信時に値を同期
    this.element.closest('form')?.addEventListener('submit', () => {
      this.syncFormValue()
    })

    // タグ変更時に値を同期
    this.tagify.on('add', () => this.syncFormValue())
    this.tagify.on('remove', () => this.syncFormValue())
  }

  async loadWhitelist() {
    if (!this.hasTagsUrlValue) return

    try {
      const response = await fetch(this.tagsUrlValue)
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      const tags = await response.json()
      this.tagify.settings.whitelist = tags.map(tag => ({ value: tag }))
    } catch (error) {
      console.error("Failed to load tags:", error)
    }
  }

  syncFormValue() {
    const tags = this.tagify.value.map(tag => tag.value).filter(tag => tag)
    const textInput = this.element.querySelector('input[type="text"][name*="tag_names"]')

    const value = tags.join(", ")

    if (textInput) {
      textInput.value = value
    }
  }
}

