import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
  static targets = ["input", "results"]
  static values = {
    url: String,
    minLength: { type: Number, default: 1 }
  }

  connect() {
    this.inputTarget.setAttribute("autocomplete", "off")
    this.resultsTarget.hidden = true
    this.selectedIndex = -1
    
    // デバウンス用
    this.timeout = null
  }

  disconnect() {
    if (this.timeout) {
      clearTimeout(this.timeout)
    }
  }

  onInput(event) {
    const query = this.inputTarget.value.trim()
    
    if (this.timeout) {
      clearTimeout(this.timeout)
    }

    if (query.length < this.minLengthValue) {
      this.hideResults()
      return
    }

    this.timeout = setTimeout(() => {
      this.fetchResults(query)
    }, 300)
  }

  onKeydown(event) {
    if (!this.resultsTarget.hidden) {
      switch (event.key) {
        case "ArrowDown":
          event.preventDefault()
          this.selectNext()
          break
        case "ArrowUp":
          event.preventDefault()
          this.selectPrevious()
          break
        case "Enter":
          event.preventDefault()
          this.selectCurrent()
          break
        case "Escape":
          this.hideResults()
          break
      }
    }
  }

  async fetchResults(query) {
    try {
      const url = `${this.urlValue}?q=${encodeURIComponent(query)}`
      const response = await fetch(url, {
        headers: {
          "Accept": "text/html"
        }
      })

      if (response.ok) {
        const html = await response.text()
        this.resultsTarget.innerHTML = html
        this.showResults()
        this.selectedIndex = -1
      }
    } catch (error) {
      console.error("Autocomplete fetch error:", error)
    }
  }

  showResults() {
    if (this.resultsTarget.children.length > 0) {
      this.resultsTarget.hidden = false
    }
  }

  hideResults() {
    this.resultsTarget.hidden = true
    this.selectedIndex = -1
    this.clearSelection()
  }

  selectNext() {
    const options = this.getOptions()
    if (options.length === 0) return

    this.selectedIndex = Math.min(this.selectedIndex + 1, options.length - 1)
    this.updateSelection(options)
  }

  selectPrevious() {
    const options = this.getOptions()
    if (options.length === 0) return

    this.selectedIndex = Math.max(this.selectedIndex - 1, 0)
    this.updateSelection(options)
  }

  selectCurrent() {
    const options = this.getOptions()
    if (this.selectedIndex >= 0 && this.selectedIndex < options.length) {
      this.choose(options[this.selectedIndex])
    }
  }

  choose(option) {
    const value = option.dataset.autocompleteValue || option.textContent.trim()
    this.inputTarget.value = value
    this.hideResults()
    
    // inputイベントを発火させて、フォーム送信を可能にする
    this.inputTarget.dispatchEvent(new Event("input", { bubbles: true }))
  }

  getOptions() {
    return Array.from(this.resultsTarget.querySelectorAll('[role="option"]:not([aria-disabled])'))
  }

  updateSelection(options) {
    this.clearSelection()
    if (this.selectedIndex >= 0 && this.selectedIndex < options.length) {
      const selected = options[this.selectedIndex]
      selected.classList.add("bg-primary", "text-primary-content", "font-semibold")
      selected.classList.remove("hover:bg-base-200")
      selected.scrollIntoView({ block: "nearest" })
    }
  }

  clearSelection() {
    this.getOptions().forEach(option => {
      option.classList.remove("bg-primary", "text-primary-content", "font-semibold")
      option.classList.add("hover:bg-base-200")
    })
  }

  // クリックで選択
  selectOption(event) {
    const option = event.target.closest('[role="option"]')
    if (option && !option.hasAttribute("aria-disabled")) {
      this.choose(option)
    }
  }

  // フォーカスが外れたら結果を非表示
  onBlur(event) {
    // 少し遅延させて、クリックイベントが先に処理されるようにする
    setTimeout(() => {
      this.hideResults()
    }, 200)
  }
}

