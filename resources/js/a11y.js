/**
 * Preferências de acessibilidade (alto contraste e tamanho da fonte).
 * Persistência em localStorage; classes/atributos em <html>.
 * Vários blocos .nav-a11y-toolbar podem existir (ex.: header desktop + menu mobile).
 */
const STORAGE_CONTRAST = 'conectaifpa-a11y-high-contrast'
const STORAGE_FONT = 'conectaifpa-a11y-font-scale'

const FONT_MIN = -2
const FONT_MAX = 2

function getFontScale() {
    const v = parseInt(localStorage.getItem(STORAGE_FONT) || '0', 10)
    if (Number.isNaN(v)) return 0
    return Math.min(FONT_MAX, Math.max(FONT_MIN, v))
}

function isHighContrast() {
    return localStorage.getItem(STORAGE_CONTRAST) === '1'
}

function applyToDocument() {
    const root = document.documentElement
    const font = getFontScale()
    const hc = isHighContrast()

    root.dataset.a11yFont = String(font)
    root.classList.toggle('a11y-high-contrast', hc)

    root.setAttribute('data-a11y-high-contrast', hc ? 'true' : 'false')

    document.querySelectorAll('[data-a11y-action="contrast"]').forEach(btn => {
        btn.setAttribute('aria-pressed', hc ? 'true' : 'false')
    })

    const labels = { '-2': 'Pequena', '-1': 'Menor', 0: 'Padrão', 1: 'Maior', 2: 'Grande' }
    const labelText = labels[String(font)] || 'Padrão'
    document.querySelectorAll('[data-a11y-target="font-label"]').forEach(el => {
        el.textContent = labelText
    })

    document.querySelectorAll('[data-a11y-action="font-dec"]').forEach(btn => {
        btn.disabled = font <= FONT_MIN
        btn.setAttribute('aria-disabled', font <= FONT_MIN ? 'true' : 'false')
    })
    document.querySelectorAll('[data-a11y-action="font-inc"]').forEach(btn => {
        btn.disabled = font >= FONT_MAX
        btn.setAttribute('aria-disabled', font >= FONT_MAX ? 'true' : 'false')
    })
}

export function setHighContrast(enabled) {
    localStorage.setItem(STORAGE_CONTRAST, enabled ? '1' : '0')
    applyToDocument()
}

export function toggleHighContrast() {
    setHighContrast(!isHighContrast())
}

export function adjustFont(delta) {
    const next = getFontScale() + delta
    if (next < FONT_MIN || next > FONT_MAX) return
    localStorage.setItem(STORAGE_FONT, String(next))
    applyToDocument()
}

export function initA11y() {
    applyToDocument()

    document.querySelectorAll('[data-a11y-action="contrast"]').forEach(btn => {
        btn.addEventListener('click', () => toggleHighContrast())
    })
    document.querySelectorAll('[data-a11y-action="font-dec"]').forEach(btn => {
        btn.addEventListener('click', () => adjustFont(-1))
    })
    document.querySelectorAll('[data-a11y-action="font-inc"]').forEach(btn => {
        btn.addEventListener('click', () => adjustFont(1))
    })
}

if (typeof document !== 'undefined' && document.documentElement) {
    applyToDocument()
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initA11y)
    } else {
        initA11y()
    }
}
