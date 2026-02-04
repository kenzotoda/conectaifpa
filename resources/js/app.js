import '../css/app.css'

/* Bootstrap */
import 'bootstrap/dist/css/bootstrap.min.css'
import { Tooltip } from 'bootstrap'

/* DataTables Bootstrap 5 */
import DataTable from 'datatables.net-bs5'
import 'datatables.net-bs5/css/dataTables.bootstrap5.min.css'

import Swal from 'sweetalert2'
window.Swal = Swal



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


// Bootstrap tooltips
document.addEventListener('DOMContentLoaded', () => {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');

    tooltipTriggerList.forEach(el => {
        new Tooltip(el);
    });
});


document.addEventListener('DOMContentLoaded', function () {

    let table = new DataTable('#participantsTable', {
        responsive: true,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        columnDefs: [
            { orderable: false, targets: 5 } // coluna Ações
        ],
        language: {
            decimal: "",
            emptyTable: "Nenhum registro encontrado",
            info: "Mostrando _START_ até _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 até 0 de 0 registros",
            infoFiltered: "(filtrado de _MAX_ registros no total)",
            thousands: ".",
            lengthMenu: "Mostrar _MENU_ registros",
            loadingRecords: "Carregando...",
            processing: "Processando...",
            search: "Pesquisar:",
            zeroRecords: "Nenhum registro correspondente encontrado",
            paginate: {
                first: "Primeiro",
                last: "Último",
                next: "Próximo",
                previous: "Anterior"
            },
            aria: {
                sortAscending: ": ativar para ordenar a coluna de forma crescente",
                sortDescending: ": ativar para ordenar a coluna de forma decrescente"
            }
        }
    });
});

