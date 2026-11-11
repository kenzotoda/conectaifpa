@once
    @push('scripts')
        <script>
            (function () {
                const url = @json(route('accounts.lookup'));

                window.scrollAddedIntoView = function (element) {
                    if (!element) {
                        return;
                    }
                    element.style.scrollMarginTop = '5.5rem';
                    element.style.scrollMarginBottom = '5.5rem';
                    requestAnimationFrame(function () {
                        element.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
                        const field = element.querySelector('input:not([type="hidden"]):not([readonly]), select, textarea');
                        if (field) {
                            field.focus({ preventScroll: true });
                        }
                    });
                };

                async function lookup(input) {
                    const box = input.closest('.account-lookup');
                    if (!box) {
                        return;
                    }
                    const status = box.querySelector('.account-lookup-status');
                    const name = box.querySelector('.account-lookup-name');
                    const extra = box.querySelector('.account-lookup-extra');
                    const email = input.value.trim();
                    if (email === '') {
                        if (name) name.value = '';
                        if (extra) extra.value = '';
                        if (status) status.textContent = '';
                        return;
                    }
                    const params = new URLSearchParams({
                        email: email,
                        papel: input.dataset.lookupRole || 'participant',
                    });
                    const response = await fetch(url + '?' + params.toString(), {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (response.status === 403) {
                        if (status) status.textContent = 'Esta consulta não está disponível para a sua conta.';
                        return;
                    }
                    const data = await response.json();
                    if (!data.found) {
                        if (name) name.value = '';
                        if (extra) extra.value = '';
                        if (status) status.textContent = data.message || 'Conta não encontrada.';
                        return;
                    }
                    if (name) name.value = data.name || '';
                    if (extra) extra.value = data.institution || '';
                    if (status) status.textContent = 'Conta encontrada.';
                }

                document.addEventListener('input', function (event) {
                    const input = event.target.closest?.('.account-lookup-email');
                    if (!input) {
                        return;
                    }
                    clearTimeout(input.lookupTimer);
                    input.lookupTimer = setTimeout(function () {
                        lookup(input);
                    }, 350);
                });
                document.addEventListener('focusout', function (event) {
                    const input = event.target.closest?.('.account-lookup-email');
                    if (!input) {
                        return;
                    }
                    clearTimeout(input.lookupTimer);
                    lookup(input);
                });

                function reindexOrganizerRows(list) {
                    list.querySelectorAll('.organizer-row').forEach(function (row, idx) {
                        const email = row.querySelector('.account-lookup-email');
                        const name = row.querySelector('.account-lookup-name');
                        if (email) email.name = 'organizers[' + idx + '][email]';
                        if (name) name.name = 'organizers[' + idx + '][name]';
                    });
                }

                document.getElementById('add-organizer-row')?.addEventListener('click', function () {
                    const list = document.getElementById('organizers-create-list') || document.getElementById('organizers-edit-list');
                    if (!list) {
                        return;
                    }
                    const idx = list.querySelectorAll('.organizer-row').length;
                    const row = document.createElement('div');
                    row.className = 'organizer-row account-lookup grid grid-cols-1 md:grid-cols-2 gap-4 items-start';
                    row.innerHTML =
                        '<div><label class="form-label block text-sm font-montserrat mb-2">E-mail do organizador *</label>' +
                        '<input type="email" name="organizers[' + idx + '][email]" class="account-lookup-email form-input w-full px-4 py-3 rounded-lg font-open-sans" data-lookup-role="organizer" placeholder="organizador@email.com"></div>' +
                        '<div><label class="form-label block text-sm font-montserrat mb-2">Nome</label>' +
                        '<input type="text" name="organizers[' + idx + '][name]" readonly class="account-lookup-name form-input w-full px-4 py-3 rounded-lg font-open-sans bg-slate-50 cursor-not-allowed" placeholder="Preenchido pela conta"></div>' +
                        '<p class="account-lookup-status text-xs text-slate-500 m-0 md:col-span-2"></p>' +
                        '<div class="md:col-span-2 flex justify-end">' +
                        '<button type="button" class="btn-form-remove btn-remove-organizer">' +
                        '<ion-icon name="trash-outline" aria-hidden="true"></ion-icon> Remover</button></div>';
                    list.appendChild(row);
                    window.scrollAddedIntoView(row);
                });

                document.getElementById('include-self-organizer')?.addEventListener('click', function () {
                    const email = (this.dataset.email || '').trim();
                    const list = document.getElementById('organizers-create-list') || document.getElementById('organizers-edit-list');
                    if (!list || email === '') {
                        return;
                    }
                    const inputs = [...list.querySelectorAll('.account-lookup-email')];
                    const same = inputs.find((input) => input.value.trim().toLowerCase() === email.toLowerCase());
                    if (same) {
                        same.dispatchEvent(new Event('focusout', { bubbles: true }));
                        window.scrollAddedIntoView(same.closest('.organizer-row'));
                        return;
                    }
                    let input = inputs.find((field) => field.value.trim() === '');
                    if (!input) {
                        document.getElementById('add-organizer-row')?.click();
                        const rows = list.querySelectorAll('.organizer-row');
                        input = rows[rows.length - 1]?.querySelector('.account-lookup-email');
                    }
                    if (!input) {
                        return;
                    }
                    input.value = email;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('focusout', { bubbles: true }));
                    window.scrollAddedIntoView(input.closest('.organizer-row'));
                });

                document.addEventListener('click', function (event) {
                    const button = event.target.closest?.('.btn-remove-organizer');
                    if (!button) {
                        return;
                    }
                    const row = button.closest('.organizer-row');
                    const list = row?.parentElement;
                    if (!row || !list) {
                        return;
                    }
                    row.remove();
                    reindexOrganizerRows(list);
                });
            })();
        </script>
    @endpush
@endonce
