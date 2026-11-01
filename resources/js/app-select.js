const LAYOUT_CLASSES = ['w-full', 'flex-1', 'min-w-0', 'grow', 'shrink']

function shouldSkip(select) {
    if (!(select instanceof HTMLSelectElement)) return true
    if (select.multiple || Number(select.size) > 1) return true
    if (select.dataset.appSelect === 'native') return true
    if (select.classList.contains('profile-picker-native')) return true
    if (select.classList.contains('swal2-select')) return true
    if (select.closest('[data-profile-picker], .dataTables_length, .dt-length, .dataTables_wrapper, .dt-container, .ql-toolbar, .ql-formats, .swal2-container, .swal2-popup')) return true
    if (typeof select.className === 'string' && /\bql-/.test(select.className)) return true
    return false
}

function optionSignature(select) {
    const options = Array.from(select.options).map((option) => {
        const group = option.parentElement && option.parentElement.tagName === 'OPTGROUP'
            ? option.parentElement.label
            : ''
        return [option.value, option.text, option.disabled ? '1' : '0', group].join('\u0001')
    })

    return options.join('\u0002') + '|' + (select.disabled ? '1' : '0')
}

function selectedText(select) {
    const selected = select.options[select.selectedIndex]
    if (!selected || selected.value === '') return ''
    return selected.text
}

function paint(select) {
    const ui = select.__appSelect
    if (!ui) return

    const label = selectedText(select)
    const nextText = label || placeholderText(select)
    if (ui.value.textContent !== nextText) ui.value.textContent = nextText
    ui.value.classList.toggle('is-placeholder', !label)
    if (ui.trigger.disabled !== select.disabled) ui.trigger.disabled = select.disabled
    ui.root.classList.toggle('is-disabled', select.disabled)

    const current = select.value
    ui.menu.querySelectorAll('.app-select-option').forEach((button) => {
        const active = button.dataset.value === current
        button.classList.toggle('is-selected', active)
        button.setAttribute('aria-selected', active ? 'true' : 'false')
    })
}

function placeholderText(select) {
    const empty = Array.from(select.options).find((option) => option.value === '')
    return (empty && empty.text) || 'Selecione'
}

function closeSelect(select) {
    const ui = select.__appSelect
    if (!ui) return
    ui.root.classList.remove('is-open')
    ui.menu.hidden = true
    ui.trigger.setAttribute('aria-expanded', 'false')
}

function placeMenu(select) {
    const ui = select.__appSelect
    const rect = ui.trigger.getBoundingClientRect()
    const spaceBelow = window.innerHeight - rect.bottom - 12
    const spaceAbove = Math.max(140, rect.top - 72)
    const openUp = spaceBelow < 260 && spaceAbove > spaceBelow
    const available = openUp ? spaceAbove : Math.max(160, spaceBelow)

    ui.menu.style.left = Math.max(8, rect.left) + 'px'
    ui.menu.style.width = rect.width + 'px'
    ui.menu.style.maxHeight = available + 'px'

    if (openUp) {
        ui.menu.style.top = 'auto'
        ui.menu.style.bottom = (window.innerHeight - rect.top + 6) + 'px'
    } else {
        ui.menu.style.bottom = 'auto'
        ui.menu.style.top = (rect.bottom + 6) + 'px'
    }
}

function openSelect(select) {
    document.querySelectorAll('[data-app-select-root].is-open select').forEach((other) => {
        if (other !== select) closeSelect(other)
    })

    const ui = select.__appSelect
    placeMenu(select)
    ui.root.classList.add('is-open')
    ui.menu.hidden = false
    ui.trigger.setAttribute('aria-expanded', 'true')
}

function choose(select, value) {
    select.value = value
    select.dispatchEvent(new Event('change', { bubbles: true }))
    const ui = select.__appSelect
    ui.trigger.classList.remove('is-invalid')
    paint(select)
    closeSelect(select)
    ui.trigger.focus()
}

function buildMenu(select) {
    const ui = select.__appSelect
    ui.menu.replaceChildren()

    const appendOption = (option) => {
        const button = document.createElement('button')
        button.type = 'button'
        button.className = 'app-select-option'
        button.setAttribute('role', 'option')
        button.dataset.value = option.value
        button.textContent = option.text
        button.disabled = option.disabled
        button.addEventListener('mousedown', (event) => {
            event.preventDefault()
        })
        button.addEventListener('click', (event) => {
            event.preventDefault()
            event.stopPropagation()
            choose(select, option.value)
        })
        ui.menu.appendChild(button)
    }

    Array.from(select.children).forEach((node) => {
        if (node.tagName === 'OPTGROUP') {
            const title = document.createElement('p')
            title.className = 'app-select-group'
            title.textContent = node.label
            ui.menu.appendChild(title)
            Array.from(node.children).forEach((child) => {
                if (child.tagName === 'OPTION') appendOption(child)
            })
            return
        }

        if (node.tagName === 'OPTION') appendOption(node)
    })
}

function bindLabel(select, trigger) {
    let label = null
    if (select.id) {
        label = document.querySelector(`label[for="${CSS.escape(select.id)}"]`)
    }
    if (!label && select.parentElement) {
        label = Array.from(select.parentElement.children).find((element) => element.tagName === 'LABEL') || null
    }
    if (!label) return

    if (!label.id) label.id = `${select.id || 'campo'}-rotulo`
    trigger.setAttribute('aria-labelledby', label.id)
    label.addEventListener('click', (event) => {
        event.preventDefault()
        event.stopPropagation()
        trigger.focus()
        if (trigger.getAttribute('aria-expanded') !== 'true') trigger.click()
    })
}

function enhance(select) {
    if (select.__appSelect) return

    const root = document.createElement('div')
    root.className = 'app-select'
    root.dataset.appSelectRoot = ''
    LAYOUT_CLASSES.forEach((name) => {
        if (select.classList.contains(name)) root.classList.add(name)
    })

    const trigger = document.createElement('button')
    trigger.type = 'button'
    trigger.className = 'app-select-trigger'
    trigger.setAttribute('aria-haspopup', 'listbox')
    trigger.setAttribute('aria-expanded', 'false')

    const value = document.createElement('span')
    value.className = 'app-select-value'
    trigger.appendChild(value)

    const chevron = document.createElementNS('http://www.w3.org/2000/svg', 'svg')
    chevron.setAttribute('class', 'app-select-chevron')
    chevron.setAttribute('viewBox', '0 0 20 20')
    chevron.setAttribute('aria-hidden', 'true')
    const chevronPath = document.createElementNS('http://www.w3.org/2000/svg', 'path')
    chevronPath.setAttribute('d', 'M5.5 7.5 10 12l4.5-4.5')
    chevronPath.setAttribute('fill', 'none')
    chevronPath.setAttribute('stroke', 'currentColor')
    chevronPath.setAttribute('stroke-width', '1.8')
    chevronPath.setAttribute('stroke-linecap', 'round')
    chevronPath.setAttribute('stroke-linejoin', 'round')
    chevron.appendChild(chevronPath)
    trigger.appendChild(chevron)

    const menu = document.createElement('div')
    menu.className = 'app-select-menu'
    menu.setAttribute('role', 'listbox')
    menu.hidden = true

    bindLabel(select, trigger)

    select.classList.add('app-select-native')
    select.tabIndex = -1
    select.parentNode.insertBefore(root, select)
    root.appendChild(select)
    root.appendChild(trigger)
    root.appendChild(menu)

    if (select.id) {
        const label = document.querySelector(`label[for="${CSS.escape(select.id)}"]`)
        if (label) {
            if (!label.id) label.id = `${select.id}-label`
            trigger.setAttribute('aria-labelledby', label.id)
        }
        menu.id = `${select.id}-menu`
        trigger.setAttribute('aria-controls', menu.id)
    }

    select.__appSelect = { root, trigger, value, menu }
    select.dataset.appSelectSignature = optionSignature(select)
    buildMenu(select)
    paint(select)

    trigger.addEventListener('click', () => {
        if (select.disabled) return
        if (menu.hidden) openSelect(select)
        else closeSelect(select)
    })

    trigger.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeSelect(select)
        if (event.key === 'ArrowDown' && menu.hidden) {
            event.preventDefault()
            openSelect(select)
            menu.querySelector('.app-select-option:not(:disabled)')?.focus()
        }
    })

    menu.addEventListener('keydown', (event) => {
        const options = Array.from(menu.querySelectorAll('.app-select-option:not(:disabled)'))
        const index = options.indexOf(document.activeElement)
        if (event.key === 'ArrowDown') {
            event.preventDefault()
            options[Math.min(index + 1, options.length - 1)]?.focus()
        }
        if (event.key === 'ArrowUp') {
            event.preventDefault()
            options[Math.max(index - 1, 0)]?.focus()
        }
        if (event.key === 'Escape') {
            event.preventDefault()
            closeSelect(select)
            trigger.focus()
        }
    })

    select.addEventListener('change', () => paint(select))
    select.addEventListener('focus', () => trigger.focus())
    select.addEventListener('invalid', (event) => {
        event.preventDefault()
        trigger.classList.add('is-invalid')
        openSelect(select)
        trigger.focus()
    })
}

function refresh(select) {
    const signature = optionSignature(select)
    if (select.dataset.appSelectSignature === signature) {
        paint(select)
        return
    }

    select.dataset.appSelectSignature = signature
    buildMenu(select)
    paint(select)
}

function scan() {
    document.querySelectorAll('select').forEach((select) => {
        if (shouldSkip(select)) return
        if (select.__appSelect) refresh(select)
        else enhance(select)
    })
}

let scheduled = false
function scheduleScan() {
    if (scheduled) return
    scheduled = true
    requestAnimationFrame(() => {
        scheduled = false
        scan()
    })
}

function boot() {
    scan()

    document.addEventListener('click', (event) => {
        document.querySelectorAll('[data-app-select-root].is-open').forEach((root) => {
            if (!root.contains(event.target)) {
                const select = root.querySelector('select')
                if (select) closeSelect(select)
            }
        })
    })

    const reposition = () => {
        document.querySelectorAll('[data-app-select-root].is-open select').forEach((select) => {
            placeMenu(select)
        })
    }
    window.addEventListener('resize', reposition)
    window.addEventListener('scroll', reposition, true)

    const observer = new MutationObserver(() => scheduleScan())
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['disabled'],
    })
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot)
} else {
    boot()
}

window.initAppSelects = scan
