import '../css/app.css'

/* bootstrap */
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap'

/* ==============================
   LOAD MORE EVENTS
================================ */
let page = 1
const btn = document.getElementById('loadMoreBtn')

if (btn) {
    btn.addEventListener('click', function () {
        page++
        const url = `${btn.dataset.url}?page=${page}`

        fetch(url)
            .then(res => {
                if (!res.ok) throw new Error('Erro 404: rota não encontrada')
                return res.text()
            })
            .then(html => {
                if (html.trim() === '') {
                    btn.style.display = 'none'

                    if (!document.getElementById('noMoreEventsMsg')) {
                        const msg = document.createElement('p')
                        msg.id = 'noMoreEventsMsg'
                        msg.className = 'text-center mt-4 text-gray-600 font-semibold'
                        msg.innerText = 'Não há mais eventos disponíveis.'
                        btn.parentNode.appendChild(msg)
                    }
                    return
                }

                document
                    .getElementById('events-container-grid')
                    ?.insertAdjacentHTML('beforeend', html)
            })
            .catch(err =>
                console.error('Erro ao carregar mais eventos:', err)
            )
    })
}

/* ==============================
   MOBILE MENU
================================ */
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('menu-toggle')
    const menu = document.getElementById('mobile-menu')

    if (!toggle || !menu) return

    toggle.addEventListener('click', () => {
        menu.classList.toggle('hidden')
    })
})

/* ==============================
   PHONE MASK - COORDINATOR
================================ */
document.addEventListener('DOMContentLoaded', () => {
    const phoneInput = document.getElementById('coordinator_phone')

    if (!phoneInput) return

    phoneInput.addEventListener('input', e => {
        let value = e.target.value.replace(/\D/g, '')

        if (value.length > 11) {
            value = value.slice(0, 11)
        }

        if (value.length > 6) {
            value = value.replace(
                /^(\d{2})(\d{5})(\d{0,4})$/,
                '($1) $2-$3'
            )
        } else if (value.length > 2) {
            value = value.replace(
                /^(\d{2})(\d{0,5})$/,
                '($1) $2'
            )
        } else if (value.length > 0) {
            value = value.replace(/^(\d{0,2})$/, '($1')
        }

        e.target.value = value
    })
})

